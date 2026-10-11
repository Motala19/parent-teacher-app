<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SubjectController extends Controller
{
    private function schoolId(): int
    {
        $id = auth()->user()->school_id;
        abort_if(is_null($id), 403, 'Your account is not linked to a school.');
        return $id;
    }

    private function own(Subject $subject): Subject
    {
        abort_unless($subject->school_id === $this->schoolId(), 404);
        return $subject;
    }

    private function rules(?Subject $subject = null): array
    {
        return [
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('subjects', 'name')->where('school_id', $this->schoolId())->ignore($subject?->id),
            ],
            'code'     => 'nullable|string|max:20',
            'grades'   => 'nullable|array',
            'grades.*' => [Rule::in(Subject::GRADES)],
        ];
    }

    public function index()
    {
        $subjects = Subject::withCount('classes')
            ->where('school_id', $this->schoolId())
            ->orderBy('name')
            ->paginate(20);

        return view('school.subjects.index', compact('subjects'));
    }

    public function create()
    {
        return view('school.subjects.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());

        $subject = Subject::create([
            'school_id' => $this->schoolId(),
            'name'      => $data['name'],
            'code'      => $data['code'] ?? null,
            'is_active' => true,
        ]);
        $subject->syncGrades($data['grades'] ?? []);

        return redirect()->route('school.subjects.index')
            ->with('success', 'Subject added successfully.');
    }

    public function edit(Subject $subject)
    {
        $this->own($subject);

        return view('school.subjects.edit', [
            'subject'        => $subject,
            'selectedGrades' => $subject->gradeList(),
        ]);
    }

    public function update(Request $request, Subject $subject)
    {
        $this->own($subject);

        $data = $request->validate($this->rules($subject));

        $subject->update([
            'name'      => $data['name'],
            'code'      => $data['code'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);
        $subject->syncGrades($data['grades'] ?? []);

        return redirect()->route('school.subjects.index')
            ->with('success', 'Subject updated successfully.');
    }

    public function destroy(Subject $subject)
    {
        $this->own($subject)->delete();

        return redirect()->route('school.subjects.index')
            ->with('success', 'Subject deleted successfully.');
    }
}