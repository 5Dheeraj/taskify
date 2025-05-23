<?php

namespace App\Http\Controllers;

use App\Models\TrainingNotice;
use Illuminate\Http\Request;

class TrainingNoticeController extends Controller
{
    // Show all notices
    public function index()
    {
        $workspace_id = session()->get('workspace_id');

        $notices = TrainingNotice::where('workspace_id', $workspace_id)->latest()->get();

        return view('training.notices.index', compact('notices'));
    }

    // Show create notice form
    public function create()
    {
        return view('training.notices.create');
    }

    // Store the created notice
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'status' => 'required|in:active,archived',
        ]);

        $validated['workspace_id'] = session()->get('workspace_id');
        $validated['admin_id'] = auth()->id();

        TrainingNotice::create($validated);

        return redirect()->route('training.notices.index')->with('success', 'Notice published successfully.');
    }
}
public function show($id)
{
    $notice = TrainingNotice::where('workspace_id', session()->get('workspace_id'))
                ->findOrFail($id);

    return view('training.notices.show', compact('notice'));
}

