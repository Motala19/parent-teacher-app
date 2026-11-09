<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\ClassRoom;
use App\Models\Import;
use App\Models\Message;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    public function index()
    {
        $id = auth()->user()->school_id;
        abort_if(is_null($id), 403, 'Your account is not linked to a school.');

        $activeLearners = fn () => Student::where('school_id', $id)->where('is_active', true);
        $week           = now()->subDays(7);

        $stats = [
            'classes'  => ClassRoom::where('school_id', $id)->where('is_active', true)->count(),
            'teachers' => User::role('teacher')->where('school_id', $id)->count(),
            'learners' => $activeLearners()->count(),
            'parents'  => User::role('parent')->where('school_id', $id)->count(),
            // Counts only. School admins never read parent and teacher messages from the dashboard.
            'notices'  => Announcement::where('school_id', $id)->where('created_at', '>=', $week)->count(),
            'messages' => Message::where('school_id', $id)->where('created_at', '>=', $week)->count(),
        ];

        // Things that need fixing, each with a link to where to fix them
        $noClass   = $activeLearners()->whereNull('class_id')->count();
        $noParents = $activeLearners()->doesntHave('parents')->count();

        $noTeacherClass = ClassRoom::where('school_id', $id)->where('is_active', true)
            ->whereNotIn('id', DB::table('class_user')->select('class_id'))
            ->count();

        $noTeacherSubject = DB::table('class_subject')
            ->join('classes', 'classes.id', '=', 'class_subject.class_id')
            ->where('classes.school_id', $id)
            ->where('classes.is_active', true)
            ->whereNull('class_subject.teacher_id')
            ->count();

        $noEmail = User::role('parent')->where('school_id', $id)->where('email', 'like', '%@noemail.invalid')->count();

        $attention = collect([
            [
                'count' => $noParents,
                'label' => Str::plural('learner', $noParents) . ' without a linked parent',
                'hint'  => "Parents can't receive notices or messages until they are linked.",
                'url'   => route('school.students.index', ['no_parents' => 1]),
            ],
            [
                'count' => $noClass,
                'label' => Str::plural('learner', $noClass) . ' not in a class yet',
                'hint'  => 'Class teachers add learners to their classes from the Add Learners page.',
                'url'   => route('school.students.index', ['class_id' => 'none']),
            ],
            [
                'count' => $noTeacherClass,
                'label' => Str::plural('class', $noTeacherClass) . ' without a class teacher',
                'hint'  => 'Without one, nobody can add learners to the class and parents have no one to message.',
                'url'   => route('school.classes.index'),
            ],
            [
                'count' => $noTeacherSubject,
                'label' => Str::plural('class subject', $noTeacherSubject) . ' with no teacher',
                'hint'  => 'Assign a teacher under Subjects so parents can reach them.',
                'url'   => route('school.subjects.index'),
            ],
            [
                'count' => $noEmail,
                'label' => Str::plural('parent', $noEmail) . ' with no email address',
                'hint'  => "They can't log in or be invited until an email is added.",
                'url'   => route('school.parents.index', ['search' => 'noemail.invalid']),
            ],
        ])->filter(fn ($item) => $item['count'] > 0)->values();

        // Active learners per grade
        $byGrade = Student::where('school_id', $id)->where('is_active', true)
            ->selectRaw('grade, COUNT(*) AS total')
            ->groupBy('grade')
            ->get()
            ->sortBy(fn ($row) => Subject::gradeRank($row->grade))
            ->values();

        $maxGrade = max(1, (int) $byGrade->max('total'));

        // Tests, assignments and projects in the next two weeks
        $upcoming = Announcement::with('user')
            ->where('school_id', $id)
            ->where('is_published', true)
            ->whereIn('type', Announcement::DUE_TYPES)
            ->whereNotNull('due_date')
            ->whereDate('due_date', '>=', today()->toDateString())
            ->whereDate('due_date', '<=', today()->addDays(14)->toDateString())
            ->orderBy('due_date')
            ->take(6)
            ->get();

        $recent = Announcement::with('user')
            ->withCount('attachments')
            ->where('school_id', $id)
            ->latest()
            ->take(5)
            ->get();

        $lastImport = Import::where('school_id', $id)->latest()->first();

        return view('dashboards.school', compact('stats', 'attention', 'byGrade', 'maxGrade', 'upcoming', 'recent', 'lastImport'));
    }
}