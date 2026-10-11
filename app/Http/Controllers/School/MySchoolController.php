<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MySchoolController extends Controller
{
    private function school()
    {
        $school = auth()->user()->school;
        abort_if(is_null($school), 403, 'Your account is not linked to a school.');
        return $school;
    }

    public function edit()
    {
        return view('school.my-school.edit', ['school' => $this->school()]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => 'nullable|email',
            'phone'   => 'nullable|string|max:20',
            'address' => 'nullable|string',
        ]);

        $this->school()->update($data);

        return redirect()->route('school.my-school.edit')
            ->with('success', 'School details updated.');
    }
}