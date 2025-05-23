<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TrainingForum;

class ForumController extends Controller
{
    // Forum Discussions List
    public function index()
    {
        $workspace_id = session()->get('workspace_id');
        $discussions = TrainingForum::where('workspace_id', $workspace_id)
                        ->orderBy('created_at', 'desc')
                        ->get();

        return view('training.forum.index', compact('discussions'));
    }

    // Create New Discussion Form
    public function create()
    {
        return view('training.forum.create');
    }

    // Store New Discussion Post
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        TrainingForum::create([
            'workspace_id' => session()->get('workspace_id'),
            'user_id' => auth()->id(),
            'title' => $request->title,
            'message' => $request->message,
        ]);

        return redirect()->route('training.forum.index')->with('success', 'Discussion posted successfully!');
    }

    // Show Single Discussion with Replies
    public function show($id)
    {
        $discussion = TrainingForum::with('replies.user')->findOrFail($id);

        return view('training.forum.show', compact('discussion'));
    }

    // Post a Reply to Discussion
    public function reply(Request $request, $id)
    {
        $request->validate([
            'message' => 'required|string',
        ]);

        $discussion = TrainingForum::findOrFail($id);

        $discussion->replies()->create([
            'user_id' => auth()->id(),
            'message' => $request->message,
        ]);

        return back()->with('success', 'Reply posted successfully!');
    }
}
