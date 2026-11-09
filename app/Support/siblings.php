<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Siblings
{
    // "O'Neil", "o neil" and "ONEIL" all become "oneil"
    public static function surname(?string $name): string
    {
        return Str::of((string) $name)->ascii()->lower()->replaceMatches('/[^a-z]/', '')->toString();
    }

    // Brothers and sisters: the same surname, or they already share a parent
    public static function related(object $a, object $b, array $parentsOf): bool
    {
        $surname = self::surname($a->last_name);

        if ($surname !== '' && $surname === self::surname($b->last_name)) {
            return true;
        }

        return (bool) array_intersect($parentsOf[$a->id] ?? [], $parentsOf[$b->id] ?? []);
    }

    // The learners in $check who are not related to anybody else in $group
    public static function unrelated(Collection $group, Collection $check): Collection
    {
        $group = $group->unique('id')->values();

        if ($group->count() < 2) {
            return collect();
        }

        $parentsOf = DB::table('parent_student')
            ->whereIn('student_id', $group->pluck('id')->all())
            ->get(['student_id', 'user_id'])
            ->groupBy('student_id')
            ->map(fn ($rows) => $rows->pluck('user_id')->all())
            ->all();

        return $check->filter(function ($student) use ($group, $parentsOf) {
            foreach ($group as $other) {
                if ((int) $other->id === (int) $student->id) {
                    continue;
                }

                if (self::related($student, $other, $parentsOf)) {
                    return false;
                }
            }

            return true;
        })->values();
    }
}