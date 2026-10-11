<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\Request;

class ParentController extends Controller
{
    public function index(Request $request)
    {
        $schools = School::orderBy('name')->get();

        $parents = User::role('parent')
            ->with(['school', 'children.classRoom'])
            ->when($request->school_id, fn ($q, $id) => $q->where('school_id', $id))
            ->when($request->search, function ($q, $s) {
                $q->where(function ($q) use ($s) {
                    $q->where('name', 'like', "%{$s}%")
                      ->orWhere('email', 'like', "%{$s}%")
                      ->orWhere('phone', 'like', "%{$s}%");
                });
            })
            ->orderBy('school_id')->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.parents.index', compact('parents', 'schools'));
    }
}