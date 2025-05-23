<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Training;
use App\Models\TrainingNotice;
use App\Models\TrainingContent;
use App\Models\TrainingProgress;
use App\Models\TrainingAssignment;
use App\Models\TrainingQuiz;
use App\Models\TrainingQuizResult;
use App\Models\TrainingSlide;
use App\Models\TrainingAccess;
use App\Models\User;
use App\Models\Workspace;

class TrainingController extends Controller
{
   public function index()
{
    $workspace_id = session()->get('workspace_id');
    $user = getAuthenticatedUser(); // ✅ recommended in SaaS setup

    if (!$user) {
        abort(403, 'Unauthorized');
    }

    if ($user->hasRole('admin') || $user->hasRole('company_owner')) {
        $trainings = Training::where('workspace_id', $workspace_id)
            ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
            ->orderBy('created_at', 'desc')
            ->get();
    } else {
        $trainings = Training::join('training_accesses', 'training_accesses.training_id', '=', 'trainings.id')
            ->where('training_accesses.user_id', $user->id)
            ->where('trainings.status', 'active')
            ->where('trainings.workspace_id', $workspace_id)
            ->select('trainings.*')
            ->orderBy('trainings.created_at', 'desc')
            ->get();
    }

    $notices = TrainingNotice::where('workspace_id', $workspace_id)
        ->orderBy('created_at', 'desc')
        ->get();

    return view('training.index', compact('trainings', 'notices'));
        }

    public function create()
    {
        $this->authorizeAccess();

        $workspace_id = session()->get('workspace_id');
        $workspace = Workspace::find($workspace_id);
        $users = $workspace ? $workspace->users : collect();

        return view('training.create', compact('users'));
    }

    public function store(Request $request)
    {
        $this->authorizeAccess();

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'level' => 'nullable|string|max:100',
            'course_type' => 'nullable|in:mandatory,optional',
            'category_id' => 'nullable|integer',
            'language' => 'nullable|string|max:50',
            'status' => 'required|in:active,inactive',
            'assigned_users' => 'nullable|array',
        ]);

        $training = Training::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'level' => $validated['level'] ?? null,
            'course_type' => $validated['course_type'] ?? null,
            'category_id' => $validated['category_id'] ?? null,
            'language' => $validated['language'] ?? null,
            'status' => $validated['status'],
            'workspace_id' => session()->get('workspace_id'),
            'admin_id' => auth('admin')->id(),
        ]);

        $this->syncTrainingAccess($training->id, $request);

        return redirect()->route('training.index')->with('success', 'Training created successfully.');
    }

    public function edit($id)
    {
        $this->authorizeAccess();

        $workspace_id = session()->get('workspace_id');
        $training = Training::where('workspace_id', $workspace_id)->findOrFail($id);
        $workspace = Workspace::find($workspace_id);
        $users = $workspace ? $workspace->users : collect();

        $assignedUserIds = TrainingAccess::where('training_id', $training->id)
            ->pluck('user_id')
            ->toArray();

        return view('training.edit', compact('training', 'users', 'assignedUserIds'));
    }

    public function update(Request $request, $id)
    {
        $this->authorizeAccess();

        $training = Training::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'level' => 'nullable|string|max:100',
            'course_type' => 'nullable|in:mandatory,optional',
            'category_id' => 'nullable|integer',
            'language' => 'nullable|string|max:50',
            'status' => 'required|in:active,inactive',
            'assigned_users' => 'nullable|array',
        ]);

        $training->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'level' => $validated['level'] ?? null,
            'course_type' => $validated['course_type'] ?? null,
            'category_id' => $validated['category_id'] ?? null,
            'language' => $validated['language'] ?? null,
            'status' => $validated['status'],
        ]);

        $this->syncTrainingAccess($training->id, $request);

        return redirect()->route('training.index')->with('success', 'Training updated successfully.');
    }

    public function destroy($id)
    {
        $this->authorizeAccess();

        $workspace_id = session()->get('workspace_id');
        $training = Training::where('workspace_id', $workspace_id)->findOrFail($id);

        TrainingAccess::where('training_id', $training->id)->delete();
        TrainingContent::where('training_id', $training->id)->delete();
        TrainingProgress::where('training_id', $training->id)->delete();
        TrainingAssignment::where('training_id', $training->id)->delete();
        TrainingSlide::where('training_id', $training->id)->delete();
        TrainingQuiz::where('training_id', $training->id)->delete();
        TrainingQuizResult::where('training_id', $training->id)->delete();
        TrainingNotice::where('training_id', $training->id)->delete();

        $training->delete();

        return redirect()->route('training.index')->with('success', 'Training deleted successfully.');
    }

    public function show($id)
    {
        $workspace_id = session()->get('workspace_id');
        $user = auth('admin')->user();

        if (!$user) {
            abort(403, 'User not authenticated.');
        }

        $training = Training::where('workspace_id', $workspace_id)
            ->where('status', 'active')
            ->findOrFail($id);

        $hasAccess = TrainingAccess::where('training_id', $training->id)
            ->where('user_id', $user->id)
            ->exists();

        if (!$hasAccess) {
            abort(403, 'You do not have access to this training.');
        }

        $contents = TrainingContent::where('training_id', $training->id)
            ->orderBy('order', 'asc')
            ->get();

        $completedContents = TrainingProgress::where('workspace_id', $workspace_id)
            ->where('admin_id', $user->id)
            ->where('training_id', $training->id)
            ->where('status', 'completed')
            ->pluck('content_id')
            ->toArray();

        $completedCount = TrainingProgress::where('workspace_id', $workspace_id)
            ->where('admin_id', $user->id)
            ->where('training_id', $training->id)
            ->where('status', 'completed')
            ->count();

        $totalCount = TrainingContent::where('workspace_id', $workspace_id)
            ->where('training_id', $training->id)
            ->count();

        $progressPercentage = $totalCount > 0 ? round(($completedCount / $totalCount) * 100) : 0;

        $assignments = TrainingAssignment::where('training_id', $training->id)->get();
        $quizAvailable = TrainingQuiz::where('training_id', $training->id)->exists();
        $quizCompleted = TrainingQuizResult::where('training_id', $training->id)
            ->where('user_id', $user->id)
            ->exists();
        $quizScore = TrainingQuizResult::where('training_id', $training->id)
            ->where('user_id', $user->id)
            ->value('score');

        $hasSlides = TrainingSlide::where('training_id', $training->id)->exists();

        return view('training.show', compact(
            'training',
            'contents',
            'completedContents',
            'progressPercentage',
            'assignments',
            'quizAvailable',
            'quizCompleted',
            'quizScore',
            'hasSlides'
        ));
    }

    public function markContentCompleted(Request $request)
    {
        $validated = $request->validate([
            'training_id' => 'required|exists:trainings,id',
            'content_id' => 'required|exists:training_contents,id',
        ]);

        TrainingProgress::updateOrCreate(
            [
                'workspace_id' => session()->get('workspace_id'),
                'admin_id' => auth('admin')->id(),
                'training_id' => $validated['training_id'],
                'content_id' => $validated['content_id'],
            ],
            [
                'status' => 'completed',
            ]
        );

        return back()->with('success', 'Content marked as completed!');
    }

   private function authorizeAccess()
{
    $user = getAuthenticatedUser(); // ✅ yahi use karna hai
    if (!$user) {
        abort(403, 'Unauthorized');
    }
    }

    private function syncTrainingAccess($training_id, $request)
    {
        TrainingAccess::where('training_id', $training_id)->delete();

        if ($request->has('assigned_users')) {
            foreach ($request->assigned_users as $user_id) {
                TrainingAccess::create([
                    'training_id' => $training_id,
                    'user_id' => $user_id,
                    'workspace_id' => session()->get('workspace_id'),
                    'admin_id' => auth('admin')->id(),
                ]);
            }
        }
    }
}
