<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Jobs\SendParentInvitations;
use App\Models\Student;
use App\Models\User;
use App\Support\ParentLinker;
use App\Support\Siblings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ParentController extends Controller
{
    private function schoolId(): int
    {
        $id = auth()->user()->school_id;
        abort_if(is_null($id), 403, 'Your account is not linked to a school.');
        return $id;
    }

    private function own(User $parent): User
    {
        abort_unless($parent->school_id === $this->schoolId() && $parent->hasRole('parent'), 404);
        return $parent;
    }

    private function students()
    {
        return Student::with('classRoom')
            ->where('school_id', $this->schoolId())
            ->orderBy('last_name')->orderBy('first_name')
            ->get();
    }

    private function studentRule(): array
    {
        return [Rule::exists('students', 'id')->where('school_id', $this->schoolId())];
    }

    private function chosen(array $ids)
    {
        return Student::where('school_id', $this->schoolId())->whereIn('id', $ids)->get();
    }

    public function index(Request $request)
    {
        $parents = User::role('parent')
            ->with('children.classRoom')
            ->where('school_id', $this->schoolId())
            ->when($request->search, function ($q, $s) {
                $q->where(function ($q) use ($s) {
                    $q->where('name', 'like', "%{$s}%")
                      ->orWhere('email', 'like', "%{$s}%")
                      ->orWhere('phone', 'like', "%{$s}%");
                });
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('school.parents.index', compact('parents'));
    }

    public function create()
    {
        return view('school.parents.create', ['students' => $this->students()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'          => 'required|string|max:255',
            'email'         => 'nullable|email|required_without:phone',
            'phone'         => 'nullable|string|max:30|required_without:email',
            'relationship'  => ['required', Rule::in(['mother', 'father', 'guardian', 'other'])],
            'student_ids'   => 'nullable|array',
            'student_ids.*' => $this->studentRule(),
        ]);

        $result = ParentLinker::attach(
            $this->schoolId(),
            $data,
            $this->chosen($data['student_ids'] ?? []),
            $request->only(['duplicate_choice', 'confirm_siblings'])
        );

        if ($result['status'] === 'conflict') {
            return back()->withInput()->with('conflict', $result['conflict']);
        }

        $parent = $result['parent'];

        return redirect()->route('school.parents.index')
            ->with('success', $result['reused']
                ? "An existing parent ({$parent->name}) was used, so no duplicate was created."
                : "Parent {$parent->name} created and linked.")
            ->with('temp_password', $result['temp'] ? "{$parent->email} / {$result['temp']}" : null);
    }

    public function edit(User $parent)
    {
        $this->own($parent)->load('children');

        return view('school.parents.edit', [
            'parent'    => $parent,
            'students'  => $this->students(),
            'linkedIds' => $parent->children->pluck('id')->all(),
        ]);
    }

    public function update(Request $request, User $parent)
    {
        $this->own($parent)->load('children');

        $data = $request->validate([
            'name'          => 'required|string|max:255',
            'email'         => ['nullable', 'email', 'required_without:phone', Rule::unique('users', 'email')->ignore($parent->id)],
            'phone'         => 'nullable|string|max:30|required_without:email',
            'relationship'  => ['required', Rule::in(['mother', 'father', 'guardian', 'other'])],
            'student_ids'   => 'nullable|array',
            'student_ids.*' => $this->studentRule(),
        ]);

        // Newly ticked learners must be brothers and sisters of the others
        $selected = $this->chosen($data['student_ids'] ?? []);
        $pending  = $selected->reject(fn ($s) => $parent->children->contains('id', $s->id));

        if ($pending->isNotEmpty() && $selected->count() > 1 && ! $request->boolean('confirm_siblings')) {
            $bad = Siblings::unrelated($selected, $pending);

            if ($bad->isNotEmpty()) {
                return back()->withInput()->with('conflict', ParentLinker::siblingConflict($parent->name, $selected, $bad));
            }
        }

        $parent->name  = $data['name'];
        $parent->email = ! empty($data['email']) ? strtolower($data['email']) : $parent->email;
        $parent->phone = $data['phone'] ?? null;

        $temp = null;
        if ($request->boolean('reset_password')) {
            $temp = Str::random(10);
            $parent->password = Hash::make($temp);
        }
        $parent->save();

        // Existing links keep their relationship; newly ticked learners get the chosen one
        $existing = $parent->children->mapWithKeys(fn ($c) => [$c->id => $c->pivot->relationship]);
        $links = $selected
            ->mapWithKeys(fn ($s) => [$s->id => ['relationship' => $existing->get($s->id, $data['relationship'])]])
            ->all();
        $parent->children()->sync($links);

        return redirect()->route('school.parents.index')
            ->with('success', 'Parent updated successfully.')
            ->with('temp_password', $temp ? "{$parent->email} / {$temp}" : null);
    }

    public function invite(User $parent)
    {
        $this->own($parent);
        abort_unless($parent->realEmail(), 404);

        SendParentInvitations::dispatch([$parent->id], true);

        return back()->with('success', 'An invitation email is on its way to ' . $parent->name . '.');
    }

    public function destroy(User $parent)
    {
        $this->own($parent);

        $parent->children()->detach();
        $parent->delete();

        return redirect()->route('school.parents.index')
            ->with('success', 'Parent deleted successfully.');
    }
}