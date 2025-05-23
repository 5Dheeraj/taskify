<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TrainingProgressController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $workspace_id = session('workspace_id');

        // Fetch total contents per training
        $totalContents = DB::table('training_contents')
            ->select('training_id', DB::raw('COUNT(*) as total'))
            ->where('workspace_id', $workspace_id)
            ->groupBy('training_id')
            ->get()
            ->keyBy('training_id');

        // Fetch completed contents per training
        $completedContents = DB::table('training_progresses')
            ->select('training_id', DB::raw('COUNT(content_id) as completed'))
            ->where('workspace_id', $workspace_id)
            ->where('admin_id', $user->id)
            ->where('status', 'completed')
            ->groupBy('training_id')
            ->get()
            ->keyBy('training_id');

        return view('training.progress.index', compact('totalContents', 'completedContents'));
    }
}
