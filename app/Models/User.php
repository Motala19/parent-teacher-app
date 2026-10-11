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
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /* ---------------------------------------------------------
       Phone and email helpers
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

    // Teachers <-> Classes
    public function classes(): BelongsToMany
    {
        return $this->belongsToMany(ClassRoom::class, 'class_user', 'user_id', 'class_id')
                    ->withPivot('is_class_teacher')
                    ->withTimestamps();
    }

    // Subjects this teacher teaches (in any class)
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
       Teachers: their classes
       --------------------------------------------------------- */

    // Classes this teacher is linked to, as class teacher or as a subject teacher
    public function myClasses()
    {
        return ClassRoom::where('school_id', $this->school_id)->where(function ($q) {
            $q->whereIn('id', DB::table('class_user')->where('user_id', $this->id)->select('class_id'))
              ->orWhereIn('id', DB::table('class_subject')->where('teacher_id', $this->id)->select('class_id'));
        });
    }

    // Only teachers linked through class_user may add or remove learners
    public function managesClass(ClassRoom $class): bool
    {
        return DB::table('class_user')
            ->where('user_id', $this->id)
            ->where('class_id', $class->id)
            ->exists();
    }

    /* ---------------------------------------------------------
       Messaging rules
       --------------------------------------------------------- */

    // Colleagues: other teachers and school admins at the same school
    public function messageableStaff()
    {
        return static::role(['teacher', 'school-admin'])
            ->where('users.school_id', $this->school_id)
            ->where('users.id', '!=', $this->id);
    }

    // Parents this teacher may message (a child in one of their classes).
    // A school admin may message every parent at the school.
    public function messageableParents()
    {
        $query = static::role('parent')->where('users.school_id', $this->school_id);

        if ($this->hasRole('school-admin')) {
            return $query;
        }

        $classIds = $this->myClasses()->pluck('id');

        return $query->whereHas('children', fn ($q) => $q->whereIn('students.class_id', $classIds));
    }

    // For a parent: their child's teachers, plus the school admin(s)
    public function messageableSchoolStaff()
    {
        $classIds = $this->children()
            ->whereNotNull('students.class_id')
            ->pluck('students.class_id')
            ->unique();

        return static::where('users.school_id', $this->school_id)
            ->where(function ($q) use ($classIds) {
                $q->whereHas('roles', fn ($r) => $r->where('name', 'school-admin'))
                  ->orWhere(function ($t) use ($classIds) {
                      $t->whereHas('roles', fn ($r) => $r->where('name', 'teacher'))
                        ->where(function ($x) use ($classIds) {
                            $x->whereIn('users.id', DB::table('class_user')->whereIn('class_id', $classIds)->select('user_id'))
                              ->orWhereIn('users.id', DB::table('class_subject')->whereIn('class_id', $classIds)->whereNotNull('teacher_id')->select('teacher_id'));
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

    // The parent's children that a message can be "about"
    public function sharedStudentsWith(User $other)
    {
        $parent = $this->hasRole('parent') ? $this : $other;
        $staff  = $this->hasRole('parent') ? $other : $this;

        if (! $parent->hasRole('parent') || ! $staff->isStaff()) {
            return collect();
        }

        $children = $parent->children();

        if (! $staff->hasRole('school-admin')) {
            $children->whereIn('students.class_id', $staff->myClasses()->pluck('id'));
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