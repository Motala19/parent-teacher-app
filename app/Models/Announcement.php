<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class Announcement extends Model
{
    public const TYPES = [
        'general'        => 'General notice',
        'urgent'         => 'Urgent',
        'event'          => 'Event',
        'newsletter'     => 'Newsletter',
        'reminder'       => 'Reminder',
        'homework'       => 'Homework',
        'assignment'     => 'Assignment',
        'project'        => 'Project',
        'test'           => 'Test',
        'question_paper' => 'Question paper',
    ];

    // school = parents and teachers, classes = parents of the chosen classes (and subjects),
    // teachers = all teachers, smt = SMT members only
    public const AUDIENCES = ['school', 'classes', 'teachers', 'smt'];

    protected $fillable = [
        'school_id',
        'user_id',
        'audience',
        'audience_label',
        'title',
        'body',
        'type',
        'attachment',
        'is_published',
        'published_at',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::deleting(function (Announcement $announcement) {
            $announcement->attachments()->get()->each->delete();
        });
    }

    // Teachers cannot send newsletters, urgent notices or events (admin only)
    public static function teacherTypes(): array
    {
        return Arr::only(self::TYPES, [
            'homework', 'assignment', 'project', 'test', 'question_paper', 'reminder', 'general',
        ]);
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? ucfirst((string) $this->type);
    }

    public function badgeClass(): string
    {
        return match ($this->type) {
            'urgent'                             => 'bg-red-100 text-red-800',
            'homework', 'assignment', 'project'  => 'bg-blue-100 text-blue-800',
            'test', 'question_paper', 'reminder' => 'bg-yellow-100 text-yellow-800',
            'newsletter'                         => 'bg-purple-100 text-purple-800',
            default                              => 'bg-gray-100 text-gray-800',
        };
    }

    // "Whole school", "Grade 5", "5A, 5B", "Mathematics · Grade 5", "Teachers only"
    public function audienceLabel(): string
    {
        if ($this->audience_label) {
            return $this->audience_label;
        }

        return match ($this->audience) {
            'teachers' => 'Teachers only',
            'smt'      => 'SMT only',
            'classes'  => 'Selected classes',
            default    => 'Whole school',
        };
    }

    // Builds "Grade 5", "Grades 5, 6", "5A, 6B" or "Grade 5, 6A" from the chosen classes
    public static function labelForClasses(Collection $selected, Collection $allClasses): string
    {
        $allByGrade = $allClasses->groupBy(fn ($c) => (string) $c->grade);
        $fullGrades = [];
        $singles    = [];

        foreach ($selected->groupBy(fn ($c) => (string) $c->grade) as $grade => $items) {
            $total = $allByGrade->get((string) $grade, collect())->count();

            if ((string) $grade !== '' && $total > 0 && $items->count() === $total) {
                $fullGrades[] = (string) $grade;
            } else {
                foreach ($items as $c) {
                    $singles[] = $c->name;
                }
            }
        }

        usort($fullGrades, fn ($a, $b) => Subject::gradeRank($a) <=> Subject::gradeRank($b));

        $parts = [];

        if ($fullGrades) {
            $parts[] = (count($fullGrades) === 1 ? 'Grade ' : 'Grades ') . implode(', ', $fullGrades);
        }

        if (count($singles) > 8) {
            $parts[] = count($singles) . ' classes';
        } elseif ($singles) {
            $parts[] = implode(', ', $singles);
        }

        return implode(', ', $parts) ?: 'Selected classes';
    }

    // What a parent may see:
    //  - whole-school posts
    //  - class posts where a child is in the class (and takes the subject, for subject posts)
    public function scopeForParent($query, User $parent)
    {
        $childIds = $parent->children()->pluck('students.id')->all();

        return $query
            ->where('announcements.school_id', $parent->school_id)
            ->where('announcements.is_published', true)
            ->where(function ($q) use ($childIds) {
                $q->where('announcements.audience', 'school')
                  ->orWhere(function ($c) use ($childIds) {
                      $c->where('announcements.audience', 'classes')
                        ->whereExists(function ($sub) use ($childIds) {
                            $sub->select(DB::raw(1))
                                ->from('announcement_targets as tg')
                                ->join('students as st', 'st.class_id', '=', 'tg.class_id')
                                ->whereColumn('tg.announcement_id', 'announcements.id')
                                ->whereIn('st.id', $childIds)
                                ->where(function ($t) {
                                    $t->whereNull('tg.subject_id')
                                      ->orWhereExists(function ($ss) {
                                          $ss->select(DB::raw(1))
                                             ->from('student_subject as ss')
                                             ->whereColumn('ss.student_id', 'st.id')
                                             ->whereColumn('ss.subject_id', 'tg.subject_id');
                                      });
                                });
                        });
                  });
            });
    }

    // What a teacher may see: school posts, teacher posts, SMT posts (if SMT),
    // and class posts for classes they are the class teacher of or teach a subject in
    public function scopeForStaff($query, User $teacher)
    {
        return $query
            ->where('announcements.school_id', $teacher->school_id)
            ->where('announcements.is_published', true)
            ->where(function ($q) use ($teacher) {
                $q->whereIn('announcements.audience', ['school', 'teachers']);

                if ($teacher->is_smt) {
                    $q->orWhere('announcements.audience', 'smt');
                }

                $q->orWhere(function ($c) use ($teacher) {
                    $c->where('announcements.audience', 'classes')
                      ->whereExists(function ($sub) use ($teacher) {
                          $sub->select(DB::raw(1))
                              ->from('announcement_targets as tg')
                              ->whereColumn('tg.announcement_id', 'announcements.id')
                              ->where(function ($w) use ($teacher) {
                                  $w->whereIn('tg.class_id', DB::table('class_user')->where('user_id', $teacher->id)->select('class_id'))
                                    ->orWhereExists(function ($cs) use ($teacher) {
                                        $cs->select(DB::raw(1))
                                           ->from('class_subject as cs')
                                           ->whereColumn('cs.class_id', 'tg.class_id')
                                           ->where('cs.teacher_id', $teacher->id)
                                           ->where(function ($m) {
                                               $m->whereNull('tg.subject_id')->orWhereColumn('cs.subject_id', 'tg.subject_id');
                                           });
                                    });
                              });
                      });
                });
            });
    }

    // Used to decide who may open this announcement's files
    public function isVisibleTo(User $user): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        if ((int) $user->school_id !== (int) $this->school_id) {
            return false;
        }

        if ($user->hasRole('school-admin') || (int) $user->id === (int) $this->user_id) {
            return true;
        }

        if (! $this->is_published) {
            return false;
        }

        return match ($this->audience) {
            'school'   => true,
            'teachers' => $user->hasRole('teacher'),
            'smt'      => $user->hasRole('teacher') && (bool) $user->is_smt,
            'classes'  => $user->hasRole('parent')
                ? static::query()->whereKey($this->id)->forParent($user)->exists()
                : ($user->hasRole('teacher') && static::query()->whereKey($this->id)->forStaff($user)->exists()),
            default    => false,
        };
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function targets(): HasMany
    {
        return $this->hasMany(AnnouncementTarget::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }
}