<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'school_id',
        'is_smt',
        'position',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_notifications' => 'boolean',
        'email_verified_at'   => 'datetime',
        'password'            => 'hashed',
        'is_smt'              => 'boolean',
        'invited_at'          => 'datetime',
        
    ];

    /* ---------------------------------------------------------
       Phone, email and role helpers
       --------------------------------------------------------- */

    // Turns 082 123 4567 and +27821234567 into the same value
    public static function normalisePhone(?string $phone): ?string
    {
        if (! $phone) return null;

        $d = preg_replace('/\D+/', '', $phone);
        if ($d === '') return null;

        if (str_starts_with($d, '00')) $d = substr($d, 2);
        if (str_starts_with($d, '0'))  $d = '27' . substr($d, 1);
        if (strlen($d) === 9)          $d = '27' . $d;

        return '+' . $d;
    }

    public function setPhoneAttribute($value): void
    {
        $this->attributes['phone'] = static::normalisePhone($value);
    }

    // Phone-only parents have a placeholder email that must never be shown
    public function realEmail(): ?string
    {
        return str_ends_with((string) $this->email, '@noemail.invalid') ? null : $this->email;
    }

    // True only if the person has not switched emails off AND has a real email address
    public function wantsEmail(): bool
    {
        return (bool) ($this->email_notifications ?? true) && $this->realEmail() !== null;
    }

    public function roleLabel(): string
    {
        return match (true) {
            $this->hasRole('super-admin')  => 'Super admin',
            $this->hasRole('school-admin') => 'School admin',
            $this->hasRole('teacher')      => 'Teacher',
            $this->hasRole('parent')       => 'Parent',
            default                        => 'User',
        };
    }

    public function isStaff(): bool
    {
        return $this->hasAnyRole(['teacher', 'school-admin']);

    }

    public const SMT_POSITIONS = ['Principal', 'Deputy Principal', 'Head of Department', 'Other SMT member'];

    // Teachers flagged as School Management Team members
    public function scopeSmtMembers($query)
    {
        return $query->role('teacher')->where('users.is_smt', true);
    }

    /* ---------------------------------------------------------
       Relationships
       --------------------------------------------------------- */

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    // Parents <-> Students
    public function children(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'parent_student', 'user_id', 'student_id')
                    ->withPivot('relationship')
                    ->withTimestamps();
    }

    // Classes this teacher is the CLASS TEACHER of
    public function classes(): BelongsToMany
    {
        return $this->belongsToMany(ClassRoom::class, 'class_user', 'user_id', 'class_id')
                    ->withPivot('is_class_teacher')
                    ->withTimestamps();
    }

    // Subjects this teacher teaches (one row per class they teach it in)
    public function teachingSubjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'class_subject', 'teacher_id', 'subject_id')
                    ->withPivot('class_id')
                    ->withTimestamps();
    }

    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function receivedMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'receiver_id');
    }

    public function announcements(): HasMany
    {
        return $this->hasMany(Announcement::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function getDashboardRoute(): string
    {
        if ($this->hasRole('super-admin')) return 'admin.dashboard';
        if ($this->hasRole('school-admin')) return 'school.dashboard';
        if ($this->hasRole('teacher')) return 'teacher.dashboard';
        if ($this->hasRole('parent')) return 'parent.dashboard';

        return 'dashboard';
    }

    /* ---------------------------------------------------------
       Parents: find an existing one or create one
       Returns [parent, temporaryPassword|null]
       --------------------------------------------------------- */

    public static function findOrCreateParent(int $schoolId, string $name, ?string $email, ?string $phone): array
    {
        $email = $email ? strtolower(trim($email)) : null;
        $phone = static::normalisePhone($phone);

        $parent = static::role('parent')
            ->where('school_id', $schoolId)
            ->where(function ($q) use ($email, $phone) {
                if ($email) $q->orWhere('email', $email);
                if ($phone) $q->orWhere('phone', $phone);
            })
            ->first();

        if ($parent) {
            return [$parent, null];
        }

        // Phone-only parents get a placeholder email until a real one is added
        if (! $email) {
            $email = 'p' . preg_replace('/\D+/', '', (string) $phone) . '@noemail.invalid';
        }

        if (static::where('email', $email)->exists()) {
            throw ValidationException::withMessages([
                'email' => 'This email is already used by another account.',
            ]);
        }

        $temp = Str::random(10);

        $parent = static::create([
            'name'      => $name,
            'email'     => $email,
            'phone'     => $phone,
            'password'  => Hash::make($temp),
            'school_id' => $schoolId,
        ]);
        $parent->assignRole('parent');

        return [$parent, $temp];
    }

    /* ---------------------------------------------------------
       TEACHERS
       Class teacher  = a row in class_user (owns the class)
       Subject teacher = rows in class_subject (teaches a subject in a class,
                         linked only to the learners who take that subject)
       --------------------------------------------------------- */

    // Classes this teacher is the class teacher of
    public function myClasses()
    {
        return ClassRoom::where('classes.school_id', $this->school_id)
            ->whereIn('classes.id', DB::table('class_user')->where('user_id', $this->id)->select('class_id'));
    }

    public function isClassTeacher(): bool
    {
        return DB::table('class_user')->where('user_id', $this->id)->exists();
    }

    public function isClassTeacherOf(int $classId): bool
    {
        return DB::table('class_user')->where('user_id', $this->id)->where('class_id', $classId)->exists();
    }

    // Only class teachers may add or remove learners
    public function managesClass(ClassRoom $class): bool
    {
        return $this->isClassTeacherOf($class->id);
    }

    // The class + subject pairs assigned to this teacher (active subjects and classes only)
    public function subjectAssignments()
    {
        return ClassSubject::query()
            ->where('class_subject.teacher_id', $this->id)
            ->whereHas('subject', fn ($q) => $q->where('subjects.is_active', true)->where('subjects.school_id', $this->school_id))
            ->whereHas('classRoom', fn ($q) => $q->where('classes.is_active', true)->where('classes.school_id', $this->school_id));
    }

    public function teachesSubjectIn(int $classId, int $subjectId): bool
    {
        return DB::table('class_subject')
            ->where('teacher_id', $this->id)
            ->where('class_id', $classId)
            ->where('subject_id', $subjectId)
            ->exists();
    }

    // Class teacher of the class, or teaches any subject in it
    public function teachesInClass(int $classId): bool
    {
        return $this->isClassTeacherOf($classId)
            || DB::table('class_subject')->where('teacher_id', $this->id)->where('class_id', $classId)->exists();
    }

    // Every class this teacher is connected to, in either way (used for filters)
    public function involvedClasses()
    {
        return ClassRoom::where('classes.school_id', $this->school_id)->where(function ($q) {
            $q->whereIn('classes.id', DB::table('class_user')->where('user_id', $this->id)->select('class_id'))
              ->orWhereIn('classes.id', DB::table('class_subject')->where('teacher_id', $this->id)->select('class_id'));
        });
    }

    // Every learner this teacher teaches:
    //  - the whole class, if they are its class teacher
    //  - otherwise only the learners who take the subject they teach in that class
    public function myStudents()
    {
        return Student::where('students.school_id', $this->school_id)->where(function ($q) {
            $q->whereIn('students.class_id', DB::table('class_user')->where('user_id', $this->id)->select('class_id'))
              ->orWhereExists(function ($sub) {
                  $sub->select(DB::raw(1))
                      ->from('class_subject')
                      ->join('student_subject', 'student_subject.subject_id', '=', 'class_subject.subject_id')
                      ->whereColumn('class_subject.class_id', 'students.class_id')
                      ->whereColumn('student_subject.student_id', 'students.id')
                      ->where('class_subject.teacher_id', $this->id);
              });
        });
    }

    /* ---------------------------------------------------------
       MESSAGING RULES
       --------------------------------------------------------- */

    // Colleagues: other teachers and school admins at the same school
    public function messageableStaff()
    {
        return static::role(['teacher', 'school-admin'])
            ->where('users.school_id', $this->school_id)
            ->where('users.id', '!=', $this->id);
    }

    // Parents of the learners this teacher teaches. A school admin may message every parent.
    public function messageableParents()
    {
        $query = static::role('parent')->where('users.school_id', $this->school_id);

        if ($this->hasRole('school-admin')) {
            return $query;
        }

        return $query->whereHas('children', function ($q) {
            $q->whereIn('students.id', $this->myStudents()->select('students.id'));
        });
    }

    // For a parent: the class teacher, the teachers of the subjects their child takes, and the school office
    public function messageableSchoolStaff()
    {
        $childIds = $this->children()->pluck('students.id');
        $classIds = $this->children()->whereNotNull('students.class_id')->pluck('students.class_id')->unique();

        return static::where('users.school_id', $this->school_id)
            ->where(function ($q) use ($classIds, $childIds) {
                $q->whereHas('roles', fn ($r) => $r->where('name', 'school-admin'))
                  ->orWhere(function ($t) use ($classIds, $childIds) {
                      $t->whereHas('roles', fn ($r) => $r->where('name', 'teacher'))
                        ->where(function ($x) use ($classIds, $childIds) {
                            $x->whereIn('users.id', DB::table('class_user')->whereIn('class_id', $classIds)->select('user_id'))
                              ->orWhereExists(function ($sub) use ($childIds) {
                                  $sub->select(DB::raw(1))
                                      ->from('class_subject')
                                      ->join('student_subject', 'student_subject.subject_id', '=', 'class_subject.subject_id')
                                      ->join('students', 'students.id', '=', 'student_subject.student_id')
                                      ->whereColumn('class_subject.teacher_id', 'users.id')
                                      ->whereColumn('class_subject.class_id', 'students.class_id')
                                      ->whereIn('student_subject.student_id', $childIds);
                              });
                        });
                  });
            });
    }

    public function canMessage(User $other): bool
    {
        if ((int) $other->id === (int) $this->id || (int) $other->school_id !== (int) $this->school_id) {
            return false;
        }

        // Colleagues
        if ($this->isStaff() && $other->isStaff()) {
            return true;
        }

        // Staff -> parent
        if ($this->isStaff() && $other->hasRole('parent')) {
            return $this->messageableParents()->whereKey($other->id)->exists();
        }

        // Parent -> staff
        if ($this->hasRole('parent') && $other->isStaff()) {
            return $this->messageableSchoolStaff()->whereKey($other->id)->exists();
        }

        return false;
    }

    // The parent's children that a message between these two people can be "about"
    public function sharedStudentsWith(User $other)
    {
        $parent = $this->hasRole('parent') ? $this : $other;
        $staff  = $this->hasRole('parent') ? $other : $this;

        if (! $parent->hasRole('parent') || ! $staff->isStaff()) {
            return collect();
        }

        $children = $parent->children()->with('classRoom');

        if (! $staff->hasRole('school-admin')) {
            $children->whereIn('students.id', $staff->myStudents()->select('students.id'));
        }

        return $children->get();
    }

    public function unreadMessageCount(): int
    {
        return $this->receivedMessages()
            ->where('is_read', false)
            ->where('hidden_by_receiver', false)
            ->count();
    }
}