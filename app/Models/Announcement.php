<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Announcement extends Model
{
    public const TYPES = [
        'general'        => 'General notice',
        'urgent'         => 'Urgent',
        'event'          => 'Event',
        'newsletter'     => 'Newsletter',
        'timetable'      => 'Timetable',
        'stationery'     => 'Stationery list',
        'year_plan'      => 'Year plan',
        'reminder'       => 'Reminder',
        'homework'       => 'Homework',
        'assignment'     => 'Assignment',
        'project'        => 'Project',
        'test'           => 'Test',
        'question_paper' => 'Question paper',
    ];

    // Only the school admin posts these. The file is the point, so one is compulsory.
    public const ADMIN_DOCUMENTS = ['timetable', 'stationery', 'year_plan'];

    // Types that carry a date, and the ones where the date is compulsory
    public const DUE_TYPES    = ['homework', 'assignment', 'project', 'test'];
    public const DUE_REQUIRED = ['assignment', 'project'];

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
        'due_date',
        'attachment',
        'is_published',
        'published_at',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
        'due_date'     => 'date',
    ];

    protected static function booted(): void
    {
        static::deleting(function (Announcement $announcement) {
            $announcement->attachments()->get()->each->delete();
        });
    }

    // Teachers cannot send newsletters, urgent notices, events or school documents
    public static function teacherTypes(): array
    {
        return Arr::only(self::TYPES, [
            'homework', 'assignment', 'project', 'test', 'question_paper', 'reminder', 'general',
        ]);
    }

    /* ---------------------------------------------------------
       Due dates
       --------------------------------------------------------- */

    public static function takesDue(?string $type): bool
    {
        return in_array($type, self::DUE_TYPES, true);
    }

    // Validation rules for the due date field. On edit, an unchanged past date is allowed.
    public static function dueRules(?self $existing = null, ?string $submitted = null): array
    {
        $rules = ['nullable', 'date', 'required_if:type,' . implode(',', self::DUE_REQUIRED)];

        $unchanged = $existing && $existing->due_date && $submitted === $existing->due_date->toDateString();

        if (! $unchanged) {
            $rules[] = 'after_or_equal:today';
        }

        return $rules;
    }

    public static function dueMessages(): array
    {
        return [
            'due_date.required_if'    => 'Set a due date for assignments and projects.',
            'due_date.date'           => 'That is not a valid date.',
            'due_date.after_or_equal' => 'The date cannot be in the past.',
        ];
    }

    // Only keep the date for types that use one
    public static function dueValue(?string $type, $value): ?string
    {
        return self::takesDue($type) && $value ? (string) $value : null;
    }

    public function dueWord(): string
    {
        return $this->type === 'test' ? 'Test' : 'Due';
    }

    // Days until the date. 0 = today, negative = overdue.
    public function daysUntilDue(): ?int
    {
        if (! $this->due_date) {
            return null;
        }

        return (int) today()->diffInDays($this->due_date->copy()->startOfDay(), false);
    }

    // overdue, today, soon (1 or 2 days) or later
    public function dueState(): ?string
    {
        $days = $this->daysUntilDue();

        if ($days === null) {
            return null;
        }

        return match (true) {
            $days < 0   => 'overdue',
            $days === 0 => 'today',
            $days <= 2  => 'soon',
            default     => 'later',
        };
    }

    public function dueLabel(): ?string
    {
        $days = $this->daysUntilDue();

        if ($days === null) {
            return null;
        }

        $word = $this->dueWord();

        if ($days < 0) {
            return $this->type === 'test'
                ? 'Test was ' . $this->due_date->format('D j M')
                : 'Overdue · was due ' . $this->due_date->format('D j M');
        }

        if ($days === 0) return $word . ' today';
        if ($days === 1) return $word . ' tomorrow';

        return $word . ' ' . $this->due_date->format('D j M');
    }

    // Still to do: has a date and it is today, in the future, or less than a few days overdue
    public function scopeDueSoon($query, int $graceDays = 3)
    {
        return $query
            ->whereIn('announcements.type', self::DUE_TYPES)
            ->whereNotNull('announcements.due_date')
            ->whereDate('announcements.due_date', '>=', today()->subDays($graceDays)->toDateString());
    }

    public function scopeNotDoneBy($query, User $user)
    {
        return $query->whereNotExists(function ($q) use ($user) {
            $q->select(DB::raw(1))
              ->from('announcement_completions as ac')
              ->whereColumn('ac.announcement_id', 'announcements.id')
              ->where('ac.user_id', $user->id);
        });
    }

    public function scopeDoneBy($query, User $user)
    {
        return $query->whereExists(function ($q) use ($user) {
            $q->select(DB::raw(1))
              ->from('announcement_completions as ac')
              ->whereColumn('ac.announcement_id', 'announcements.id')
              ->where('ac.user_id', $user->id);
        });
    }

    // A short plain-text preview for lists
    public function excerpt(int $limit = 150): string
    {
        return Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags((string) $this->body))), $limit);
    }

    /* ---------------------------------------------------------
       Display
       --------------------------------------------------------- */

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? ucfirst((string) $this->type);
    }

    public function badgeClass(): string
    {
        return match ($this->type) {
            'urgent'                                    => 'bg-red-100 text-red-800',
            'homework', 'assignment', 'project'         => 'bg-blue-100 text-blue-800',
            'test', 'question_paper', 'reminder'        => 'bg-yellow-100 text-yellow-800',
            'newsletter'                                => 'bg-purple-100 text-purple-800',
            default                                     => 'bg-gray-100 text-gray-800',
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

    /* ---------------------------------------------------------
       Who may see what
       --------------------------------------------------------- */

    // What a parent may see:
    //  - whole-school posts
    //  - posts for a class one of their children is in
    //  - posts for a whole grade one of their children is in
    //  - and, when the post is about a subject, only if that child takes the subject
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
                                ->crossJoin('students as st')
                                ->whereColumn('tg.announcement_id', 'announcements.id')
                                ->whereIn('st.id', $childIds)
                                ->where(function ($m) {
                                    $m->whereColumn('st.class_id', 'tg.class_id')
                                      ->orWhere(function ($g) {
                                          $g->whereNotNull('tg.grade')
                                            ->where(function ($gg) {
                                                $gg->whereColumn('st.grade', 'tg.grade')
                                                   ->orWhereExists(function ($cl) {
                                                       $cl->select(DB::raw(1))
                                                          ->from('classes as cl')
                                                          ->whereColumn('cl.id', 'st.class_id')
                                                          ->whereColumn('cl.grade', 'tg.grade');
                                                   });
                                            });
                                      });
                                })
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

    // What a teacher may see: school posts, teacher posts, SMT posts (if SMT), and class or grade posts
    // for classes they are the class teacher of, or where they teach the subject
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
                                    })
                                    ->orWhereExists(function ($gs) use ($teacher) {
                                        $gs->select(DB::raw(1))
                                           ->from('class_subject as gcs')
                                           ->join('classes as gcl', 'gcl.id', '=', 'gcs.class_id')
                                           ->whereNotNull('tg.grade')
                                           ->whereColumn('gcl.grade', 'tg.grade')
                                           ->where('gcs.teacher_id', $teacher->id)
                                           ->where(function ($m) {
                                               $m->whereNull('tg.subject_id')->orWhereColumn('gcs.subject_id', 'tg.subject_id');
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

    /* ---------------------------------------------------------
       Relationships
       --------------------------------------------------------- */

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

    public function completions(): HasMany
    {
        return $this->hasMany(AnnouncementCompletion::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }
}