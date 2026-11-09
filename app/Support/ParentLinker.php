<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Collection;

class ParentLinker
{
    private static function fullName($student): string
    {
        return $student->first_name . ' ' . $student->last_name;
    }

    public static function siblingConflict(string $parentName, Collection $group, Collection $bad): array
    {
        return [
            'type'     => 'siblings',
            'parent'   => $parentName,
            'learners' => $bad->map(fn ($s) => self::fullName($s))->values()->all(),
            'others'   => $group->reject(fn ($s) => $bad->contains('id', $s->id))->map(fn ($s) => self::fullName($s))->values()->all(),
        ];
    }

    // $data: name, email, phone, relationship. $flags: duplicate_choice ("existing:ID" or "new"), confirm_siblings
    // Returns ['status' => 'linked', 'parent' => User, 'temp' => ?string, 'reused' => bool]
    //      or ['status' => 'conflict', 'conflict' => [...]]
    public static function attach(int $schoolId, array $data, Collection $students, array $flags = []): array
    {
        $choice = (string) ($flags['duplicate_choice'] ?? '');
        $parent = null;

        // The admin chose "link the parent who is already in the system"
        if (str_starts_with($choice, 'existing:')) {
            $parent = User::role('parent')->where('users.school_id', $schoolId)->find((int) substr($choice, 9));
        }

        // The same email or phone number is the same person
        $parent ??= User::findParentByContact($schoolId, $data['email'] ?? null, $data['phone'] ?? null);

        // The same name with different contact details is probably the same person entered twice
        if (! $parent && $choice !== 'new') {
            $same = User::role('parent')
                ->where('users.school_id', $schoolId)
                ->where('users.name', trim($data['name']))
                ->with('children')
                ->get();

            if ($same->isNotEmpty()) {
                return ['status' => 'conflict', 'conflict' => [
                    'type'    => 'duplicate',
                    'name'    => trim($data['name']),
                    'parents' => $same->map(fn ($p) => [
                        'id'       => $p->id,
                        'name'     => $p->name,
                        'phone'    => $p->phone,
                        'email'    => $p->realEmail(),
                        'children' => $p->children->map(fn ($c) => self::fullName($c))->all(),
                    ])->all(),
                ]];
            }
        }

        // Parents are only shared between brothers and sisters
        $known   = $parent ? $parent->children()->get() : collect();
        $pending = $students->reject(fn ($s) => $known->contains('id', $s->id))->values();
        $group   = $known->concat($pending);

        if ($pending->isNotEmpty() && $group->count() > 1 && empty($flags['confirm_siblings'])) {
            $bad = Siblings::unrelated($group, $pending);

            if ($bad->isNotEmpty()) {
                return ['status' => 'conflict', 'conflict' => self::siblingConflict($parent?->name ?? $data['name'], $group, $bad)];
            }
        }

        $temp   = null;
        $reused = (bool) $parent;

        if (! $parent) {
            [$parent, $temp] = User::findOrCreateParent($schoolId, $data['name'], $data['email'] ?? null, $data['phone'] ?? null);
            $reused = $temp === null;
        }

        $parent->children()->syncWithoutDetaching(
            $pending->mapWithKeys(fn ($s) => [$s->id => ['relationship' => $data['relationship']]])->all()
        );

        return ['status' => 'linked', 'parent' => $parent, 'temp' => $temp, 'reused' => $reused];
    }
}