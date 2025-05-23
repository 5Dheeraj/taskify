<?php

namespace App\Http\Controllers;

use App\Models\Training;
use App\Models\TrainingContent;
use App\Models\TrainingAssignment;
use App\Models\TrainingUserStatus;
use App\Models\TrainingQuiz;
use App\Models\TrainingQuizResult;
use App\Models\TrainingAssignmentSubmission;
use App\Models\TrainingUserCertificate;
use App\Models\TrainingNotice;
use App\Models\TrainingAccess;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TrainingController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $workspace_id = session()->get('workspace_id');

        $trainings = Training::where('workspace_id', $workspace_id)
            ->where(function ($q) use ($user) {
                $q->where('admin_id', $user->id)
                  ->orWhereHas('accesses', function ($query) use ($user) {
                      $query->where('user_id', $user->id)
                            ->orWhereIn('role_id', $user->roles->pluck('id'));
                  });
            })
            ->with('contents', 'assignment', 'quizzes', 'notices')
            ->get();

        return view('training.index', compact('trainings'));
    }

    public function create()
    {
        $roles = Role::all();
        $users = User::where('workspace_id', session()->get('workspace_id'))->get();

        return view('training.create', compact('roles', 'users'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required',
            'description' => 'nullable',
            'start_date' => 'required|date',
            'completion_deadline' => 'required|date|after_or_equal:start_date',
            'assigned_users' => 'nullable|array',
            'assigned_users.*' => 'exists:users,id',
            'assigned_roles' => 'nullable|array',
            'assigned_roles.*' => 'exists:roles,id',
        ]);

        $training = Training::create([
            'workspace_id' => session()->get('workspace_id'),
            'admin_id' => auth()->id(),
            'title' => $request->title,
            'description' => $request->description,
            'start_date' => $request->start_date,
            'completion_deadline' => $request->completion_deadline,
            'status' => 'draft',
        ]);

        // Assign access to specific users
        if ($request->filled('assigned_users')) {
            foreach ($request->assigned_users as $userId) {
                TrainingAccess::create([
                    'workspace_id' => session()->get('workspace_id'),
                    'training_id' => $training->id,
                    'user_id' => $userId,
                ]);
            }
        }

        // Assign access to specific roles
        if ($request->filled('assigned_roles')) {
            foreach ($request->assigned_roles as $roleId) {
                TrainingAccess::create([
                    'workspace_id' => session()->get('workspace_id'),
                    'training_id' => $training->id,
                    'role_id' => $roleId,
                ]);
            }
        }

        return redirect()->route('training.index')->with('success', 'Training created successfully.');
    }

    public function show($id)
    {
        $user = Auth::user();
        $workspace_id = session()->get('workspace_id');

        $training = Training::with('contents', 'assignment', 'quizzes', 'notices')->findOrFail($id);

        $hasAccess = TrainingAccess::where('training_id', $training->id)
            ->where('workspace_id', $workspace_id)
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id);

                if ($user->roles->isNotEmpty()) {
                    $query->orWhereIn('role_id', $user->roles->pluck('id'));
                }
            })->exists();

        if (!$hasAccess && $user->id !== $training->admin_id) {
            abort(403, 'You do not have access to view this training.');
        }

        return view('training.show', compact('training'));
    }

    public function edit($id)
    {
        $training = Training::findOrFail($id);
        return view('training.edit', compact('training'));
    }

    public function update(Request $request, $id)
    {
        $training = Training::findOrFail($id);

        $training->update($request->only([
            'title',
            'description',
            'start_date',
            'completion_deadline',
            'status'
        ]));

        return redirect()->route('training.index')->with('success', 'Training updated successfully.');
    }

    public function destroy($id)
    {
        Training::destroy($id);
        return back()->with('success', 'Training deleted');
    }
}
