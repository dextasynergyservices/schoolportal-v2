<?php

declare(strict_types=1);

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function index(Request $request): View
    {
        $teacher = auth()->user();
        $classIds = $teacher->assignedClasses()->pluck('id');

        $classes = SchoolClass::whereIn('id', $classIds)
            ->where('is_active', true)
            ->withCount('students')
            ->with('level:id,name')
            ->orderBy('name')
            ->get();

        // Default to first class if none selected
        $selectedClassId = $request->input('class_id', $classes->first()?->id);

        $query = User::where('role', 'student')
            ->where('is_active', true)
            ->whereHas('studentProfile', fn ($q) => $q->whereIn('class_id', $classIds));

        if ($selectedClassId) {
            $query->whereHas('studentProfile', fn ($q) => $q->where('class_id', $selectedClassId));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('username', 'like', "%{$search}%"));
        }

        $students = $query->with('studentProfile.class:id,name')
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('teacher.students.index', compact('students', 'classes', 'selectedClassId'));
    }
}
