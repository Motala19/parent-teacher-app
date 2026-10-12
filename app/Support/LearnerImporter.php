<?php

namespace App\Support;

use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class LearnerImporter
{
    private array $students = [];        // lower(admission) => ['id', 'new']
    private array $parentsByEmail = [];  // email => entry
    private array $parentsByPhone = [];  // +27... => entry
    private array $linked = [];
    private int $planned = 0;
    private string $unusableHash = '';

    public function __construct(private int $schoolId)
    {
    }

    /* =========================================================
       Value cleaning
       ========================================================= */

    public static function grade(?string $value): ?string
    {
        $v = Str::lower(trim((string) $value));
        $v = preg_replace('/^(grade|gr)\.?\s*/', '', $v);
        $v = preg_replace('/[^a-z0-9]/', '', $v);

        if ($v === '') {
            return null;
        }

        if ($v === 'r') {
            return 'R';
        }

        if (preg_match('/^0*(\d{1,2})[a-z]?$/', $v, $m)) {
            $grade = (string) (int) $m[1];

            return in_array($grade, Subject::GRADES, true) ? $grade : null;
        }

        return null;
    }

    public static function gender(?string $value): ?string
    {
        return match (Str::lower(trim((string) $value))) {
            'm', 'male', 'boy', 'man'        => 'male',
            'f', 'female', 'girl', 'woman'   => 'female',
            'o', 'other', 'x'                => 'other',
            default                          => null,
        };
    }

    public static function date(?string $value): ?string
    {
        $v = trim((string) $value);

        if ($v === '') {
            return null;
        }

        try {
            if (ctype_digit($v)) {
                if (strlen($v) === 8) {
                    $d = Carbon::createFromFormat('Ymd', $v);
                } elseif (strlen($v) === 5 && (int) $v > 10000) {
                    // Excel stores dates as a day count
                    $d = Carbon::create(1899, 12, 30)->addDays((int) $v);
                } else {
                    return null;
                }
            } elseif (preg_match('/^(\d{1,2})[\/\-.](\d{1,2})[\/\-.](\d{4})$/', $v, $m)) {
                $d = Carbon::createSafe((int) $m[3], (int) $m[2], (int) $m[1]);
            } elseif (preg_match('/^(\d{4})[\/\-.](\d{1,2})[\/\-.](\d{1,2})/', $v, $m)) {
                $d = Carbon::createSafe((int) $m[1], (int) $m[2], (int) $m[3]);
            } else {
                $d = Carbon::parse($v);
            }
        } catch (\Throwable $e) {
            return null;
        }

        if (! $d || $d->isFuture() || $d->year < 1990) {
            return null;
        }

        return $d->format('Y-m-d');
    }

    public static function relationship(?string $value): string
    {
        $v = Str::lower(trim((string) $value));

        return match (true) {
            in_array($v, ['mother', 'mom', 'mum', 'mommy', 'mama', 'm'], true) => 'mother',
            in_array($v, ['father', 'dad', 'daddy', 'papa', 'f'], true)        => 'father',
            $v === 'other'                                                      => 'other',
            default                                                             => 'guardian',
        };
    }

    /* =========================================================
       Main entry: $commit = false is a preview (nothing is saved)
       ========================================================= */

    public function process(array $rows, bool $commit): array
    {
        $this->load();
        $this->planned      = 0;
        $this->linked       = [];
        $this->unusableHash = $commit ? Hash::make(Str::random(40)) : '';

        $counts = [
            'rows_total'       => count($rows),
            'rows_ok'          => 0,
            'rows_error'       => 0,
            'learners_created' => 0,
            'learners_updated' => 0,
            'parents_created'  => 0,
            'parents_matched'  => 0,
            'parents_no_email' => 0,
            'parents_skipped'  => 0,
            'links_created'    => 0,
            'links_existing'   => 0,
            'warnings'         => 0,
        ];

        $report     = [];
        $seen       = [];
        $matched    = [];
        $createdIds = [];

        foreach ($rows as $row) {
            $result = $this->row($row, $commit, $seen, $counts, $matched, $createdIds);

            $counts['warnings'] += count($result['warnings']);
            $report[] = $result;
        }

        $counts['parents_matched'] = count($matched);

        return [
            'counts'         => $counts,
            'rows'           => $report,
            'new_parent_ids' => array_values(array_unique($createdIds)),
        ];
    }

    public static function issuesFrom(array $rows, int $cap = 1000): array
    {
        $out = [];

        foreach ($rows as $r) {
            foreach ($r['errors'] as $message) {
                $out[] = ['line' => $r['line'], 'admission' => $r['admission'], 'learner' => $r['learner'], 'level' => 'Error', 'message' => $message];
            }

            foreach ($r['warnings'] as $message) {
                $out[] = ['line' => $r['line'], 'admission' => $r['admission'], 'learner' => $r['learner'], 'level' => 'Warning', 'message' => $message];
            }

            if (count($out) >= $cap) {
                break;
            }
        }

        return $out;
    }

    /* =========================================================
       Internals
       ========================================================= */

    private function load(): void
    {
        $this->students       = [];
        $this->parentsByEmail = [];
        $this->parentsByPhone = [];

        $students = DB::table('students')
            ->where('school_id', $this->schoolId)
            ->whereNotNull('admission_number')
            ->get(['id', 'admission_number']);

        foreach ($students as $s) {
            $this->students[Str::lower((string) $s->admission_number)] = ['id' => (int) $s->id, 'new' => false];
        }

        $parents = User::role('parent')->where('users.school_id', $this->schoolId)->get();

        foreach ($parents as $p) {
            $entry = ['key' => (string) $p->id, 'id' => (int) $p->id, 'name' => $p->name, 'new' => false];

            $this->parentsByEmail[Str::lower((string) $p->email)] = $entry;

            if ($p->phone) {
                $this->parentsByPhone[$p->phone] = $entry;
            }
        }
    }

    private function row(array $row, bool $commit, array &$seen, array &$counts, array &$matched, array &$createdIds): array
    {
        $adm   = trim((string) ($row['admission_number'] ?? ''));
        $first = trim((string) ($row['learner_first_name'] ?? ''));
        $last  = trim((string) ($row['learner_last_name'] ?? ''));

        $result = [
            'line'           => (int) ($row['_line'] ?? 0),
            'admission'      => $adm,
            'learner'        => trim($first . ' ' . $last),
            'learner_action' => 'error',
            'parents'        => [],
            'errors'         => [],
            'warnings'       => [],
        ];

        if ($adm === '')   $result['errors'][] = 'Admission number is missing.';
        if ($first === '') $result['errors'][] = 'Learner first name is missing.';
        if ($last === '')  $result['errors'][] = 'Learner last name is missing.';
        if (mb_strlen($adm) > 50) $result['errors'][] = 'Admission number is longer than 50 characters.';
        if (mb_strlen($first) > 100 || mb_strlen($last) > 100) $result['errors'][] = 'A learner name is longer than 100 characters.';

        if ($result['errors']) {
            $counts['rows_error']++;

            return $result;
        }

        $counts['rows_ok']++;

        $key    = Str::lower($adm);
        $repeat = isset($seen[$key]);
        $seen[$key] = true;

        // Learner details
        $fields = ['first_name' => $first, 'last_name' => $last];

        $gradeRaw = trim((string) ($row['grade'] ?? ''));
        if ($gradeRaw !== '') {
            $grade = self::grade($gradeRaw);
            $grade === null
                ? $result['warnings'][] = "Grade \"{$gradeRaw}\" was not recognised, so the grade was left blank."
                : $fields['grade'] = $grade;
        }

        $genderRaw = trim((string) ($row['gender'] ?? ''));
        if ($genderRaw !== '') {
            $gender = self::gender($genderRaw);
            $gender === null
                ? $result['warnings'][] = "Gender \"{$genderRaw}\" was not recognised and was left blank."
                : $fields['gender'] = $gender;
        }

        $dobRaw = trim((string) ($row['date_of_birth'] ?? ''));
        if ($dobRaw !== '') {
            $dob = self::date($dobRaw);
            $dob === null
                ? $result['warnings'][] = "Date of birth \"{$dobRaw}\" was not understood and was left blank."
                : $fields['date_of_birth'] = $dob;
        }

        if ($repeat) {
            $result['learner_action'] = 'merge';
        } elseif (isset($this->students[$key])) {
            $result['learner_action'] = 'update';

            if ($commit) {
                Student::whereKey($this->students[$key]['id'])->update($fields);
            }

            $counts['learners_updated']++;
        } else {
            $result['learner_action'] = 'create';

            $id = null;
            if ($commit) {
                $id = Student::create($fields + [
                    'school_id'        => $this->schoolId,
                    'admission_number' => $adm,
                    'is_active'        => true,
                ])->id;
            }

            $this->students[$key] = ['id' => $id, 'new' => true];
            $counts['learners_created']++;
        }

        $ref = $this->students[$key];

        // Parents
        foreach ([1, 2] as $n) {
            $p = $this->parentData($row, $n, $first, $last, $result['warnings']);

            if ($p === null) {
                continue;
            }

            $display = trim(($p['phone'] ?? '') . ' ' . ($p['email'] ?? ''));

            if ($p['problem']) {
                $result['warnings'][] = $p['problem'];
                $counts['parents_skipped']++;
                $result['parents'][] = ['name' => $p['name'], 'contact' => $display, 'relationship' => $p['relationship'], 'action' => 'skipped', 'link' => null];

                continue;
            }

            [$entry, $status] = $this->resolveParent($p, $commit, $counts, $matched, $createdIds, $result['warnings']);

            if ($entry === null) {
                $counts['parents_skipped']++;
                $result['parents'][] = ['name' => $p['name'], 'contact' => $display, 'relationship' => $p['relationship'], 'action' => 'skipped', 'link' => null];

                continue;
            }

            // Link the parent to the learner
            $pair   = ($ref['id'] ? 's' . $ref['id'] : 'n' . $key) . '|' . $entry['key'];
            $exists = isset($this->linked[$pair]);

            if (! $exists && $ref['id'] && $entry['id']) {
                $exists = DB::table('parent_student')
                    ->where('student_id', $ref['id'])
                    ->where('user_id', $entry['id'])
                    ->exists();
            }

            $this->linked[$pair] = true;

            if ($exists) {
                $counts['links_existing']++;
                $link = 'exists';
            } else {
                if ($commit) {
                    DB::table('parent_student')->insert([
                        'student_id'   => $ref['id'],
                        'user_id'      => $entry['id'],
                        'relationship' => $p['relationship'],
                        'created_at'   => now(),
                        'updated_at'   => now(),
                    ]);
                }

                $counts['links_created']++;
                $link = 'new';
            }

            $result['parents'][] = ['name' => $p['name'], 'contact' => $display, 'relationship' => $p['relationship'], 'action' => $status, 'link' => $link];
        }

        return $result;
    }

    // Returns null when the row has no data for this parent
    private function parentData(array $row, int $n, string $first, string $last, array &$warnings): ?array
    {
        $pf    = trim((string) ($row["parent{$n}_first_name"] ?? ''));
        $pl    = trim((string) ($row["parent{$n}_last_name"] ?? ''));
        $pn    = trim((string) ($row["parent{$n}_name"] ?? ''));
        $rel   = trim((string) ($row["parent{$n}_relationship"] ?? ''));
        $phone = trim((string) ($row["parent{$n}_phone"] ?? ''));
        $email = trim((string) ($row["parent{$n}_email"] ?? ''));

        if ($pf === '' && $pl === '' && $pn === '' && $rel === '' && $phone === '' && $email === '') {
            return null;
        }

        $label = "Parent {$n}";
        $name  = trim($pf . ' ' . $pl);
        if ($name === '') {
            $name = $pn;
        }

        $cleanEmail = null;
        if ($email !== '') {
            $candidate = Str::lower($email);

            if (filter_var($candidate, FILTER_VALIDATE_EMAIL) && mb_strlen($candidate) <= 255) {
                $cleanEmail = $candidate;
            } else {
                $warnings[] = "{$label}: \"{$email}\" is not a valid email address.";
            }
        }

        $cleanPhone = null;
        if ($phone !== '') {
            $normalised = User::normalisePhone($phone);

            if ($normalised && preg_match('/^\+\d{10,15}$/', $normalised)) {
                $cleanPhone = $normalised;
            } else {
                $warnings[] = "{$label}: \"{$phone}\" is not a valid phone number.";
            }
        }

        $problem = null;
        if (! $cleanEmail && ! $cleanPhone) {
            $problem = "{$label} was skipped: there is no valid email or phone number to match or contact them.";
        }

        if ($name === '') {
            $name       = "Parent of {$first} {$last}";
            $warnings[] = "{$label} has no name, so \"{$name}\" was used. You can rename them later.";
        }

        return [
            'name'         => mb_substr($name, 0, 255),
            'email'        => $cleanEmail,
            'phone'        => $cleanPhone,
            'relationship' => self::relationship($rel),
            'problem'      => $problem,
        ];
    }

    // Finds the parent by email, then phone, or creates them. Returns [entry|null, status]
    private function resolveParent(array $p, bool $commit, array &$counts, array &$matched, array &$createdIds, array &$warnings): array
    {
        $match = null;

        if ($p['email'] && isset($this->parentsByEmail[$p['email']])) {
            $match = $this->parentsByEmail[$p['email']];
        }

        if ($p['phone'] && isset($this->parentsByPhone[$p['phone']])) {
            $byPhone = $this->parentsByPhone[$p['phone']];

            if (! $match) {
                $match = $byPhone;
            } elseif ($match['key'] !== $byPhone['key']) {
                $warnings[] = "{$p['name']}: the email belongs to {$match['name']} but the phone belongs to {$byPhone['name']}. Linked to {$match['name']} (email match).";
            }
        }

        if ($match) {
            if ($match['new']) {
                return [$match, 'again'];
            }

            $matched[$match['key']] = true;

            if ($commit) {
                $this->enrich($match, $p);
            }

            return [$match, 'existing'];
        }

        // New parent. Phone-only parents get a placeholder email.
        $email       = $p['email'];
        $placeholder = false;

        if (! $email) {
            $email       = 'p' . preg_replace('/\D+/', '', (string) $p['phone']) . '@noemail.invalid';
            $placeholder = true;
        }

        if (DB::table('users')->where('email', $email)->exists()) {
            $warnings[] = $placeholder
                ? "{$p['name']} was skipped: this phone number is already registered at another school."
                : "{$p['name']} was skipped: {$email} is already used by an account at another school or with another role.";

            return [null, 'skipped'];
        }

        $id = null;

        if ($commit) {
            $user = User::create([
                'name'      => $p['name'],
                'email'     => $email,
                'phone'     => $p['phone'],
                'password'  => $this->unusableHash,
                'school_id' => $this->schoolId,
            ]);
            $user->assignRole('parent');

            $id           = (int) $user->id;
            $createdIds[] = $id;
        }

        $entry = [
            'key'  => $id ? (string) $id : 'n' . (++$this->planned),
            'id'   => $id,
            'name' => $p['name'],
            'new'  => true,
        ];

        if (! $placeholder) {
            $this->parentsByEmail[$email] = $entry;
        }

        if ($p['phone']) {
            $this->parentsByPhone[$p['phone']] = $entry;
        }

        $counts['parents_created']++;

        if ($placeholder) {
            $counts['parents_no_email']++;
        }

        return [$entry, 'new'];
    }

    // Fills in a missing phone or a placeholder email on an existing parent
    private function enrich(array $entry, array $p): void
    {
        $user = User::find($entry['id']);

        if (! $user) {
            return;
        }

        $dirty = false;

        if (! $user->phone && $p['phone']) {
            $user->phone = $p['phone'];
            $dirty = true;
        }

        if ($user->realEmail() === null && $p['email'] && ! DB::table('users')->where('email', $p['email'])->exists()) {
            $user->email = $p['email'];
            $dirty = true;
        }

        if ($dirty) {
            $user->save();
        }
    }
}