<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\ClassRoom;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StudentController extends Controller
{
    private function schoolId(): int
    {
        $id = auth()->user()->school_id;
        abort_if(is_null($id), 403, 'Your account is not linked to a school.');
        return $id;
    }

    private function own(Student $student): Student
    {
        abort_unless($student->school_id === $this->schoolId(), 404);
        return $student;
    }

    private function classes()
    {
        return ClassRoom::where('school_id', $this->schoolId())
            ->orderBy('grade')->orderBy('name')->get();
    }

    private function rules(?Student $student = null): array
    {
        return [
            'first_name'       => 'required|string|max:100',
            'last_name'        => 'required|string|max:100',
            'admission_number' => [
                'nullable', 'string', 'max:50',
                Rule::unique('students', 'admission_number')
                    ->where('school_id', $this->schoolId())
                    ->ignore($student?->id),
            ],
            'grade'         => ['nullable', Rule::in(Subject::GRADES)],
            'class_id'      => ['nullable', Rule::exists('classes', 'id')->where('school_id', $this->schoolId())],
            'date_of_birth' => 'nullable|date|before:today',
            'gender'        => ['nullable', Rule::in(['male', 'female', 'other'])],
        ];
    }

    private function fillGrade(array $data): array
    {
        if (empty($data['grade']) && ! empty($data['class_id'])) {
            $data['grade'] = ClassRoom::find($data['class_id'])?->grade;
        }

        return $data;
    }

    public function index(Request $request)
    {
       $students = Student::with(['classRoom', 'parents'])
            ->withCount('parents')
            ->where('school_id', $this->schoolId())
            ->when($request->class_id, fn ($q, $id) => $q->where('class_id', $id))
            ->when($request->search, function ($q, $s) {
                $q->where(function ($q) use ($s) {
                    $q->where('first_name', 'like', "%{$s}%")
                      ->orWhere('last_name', 'like', "%{$s}%")
                      ->orWhere('admission_number', 'like', "%{$s}%");
                });
            })
            ->orderBy('last_name')->orderBy('first_name')
            ->paginate(20)
            ->withQueryString();

        return view('school.students.index', [
            'students' => $students,
            'classes'  => $this->classes(),
        ]);
    }

    public function create()
    {
        return view('school.students.create', ['classes' => $this->classes()]);
    }

    public function store(Request $request)
    {
        $data = $this->fillGrade($request->validate($this->rules()));

        $student = Student::create($data + [
            'school_id' => $this->schoolId(),
            'is_active' => true,
        ]);

        $student->applyDefaultSubjects();

        return redirect()->route('school.students.index')
            ->with('success', 'Learner added and subjects assigned.');
    }

    public function edit(Student $student)
    {
        $this->own($student)->load(['parents', 'subjects']);

        return view('school.students.edit', [
            'student'     => $student,
            'classes'     => $this->classes(),
            'allSubjects' => Subject::where('school_id', $this->schoolId())
                                    ->where('is_active', true)
                                    ->orderBy('name')
                                    ->get(),
        ]);
    }

    public function update(Request $request, Student $student)
    {
        $this->own($student);

        $data = $this->fillGrade($request->validate($this->rules($student)));
        $data['is_active'] = $request->boolean('is_active');

        $classChanged = (int) $student->class_id !== (int) ($data['class_id'] ?? 0);

        $student->update($data);

        if ($classChanged) {
            $student->refresh()->applyDefaultSubjects();
        }

        return redirect()->route('school.students.index')
            ->with('success', 'Learner updated successfully.');
    }

    public function destroy(Student $student)
    {
        $this->own($student)->delete();

        return redirect()->route('school.students.index')
            ->with('success', 'Learner deleted successfully.');
    }
}