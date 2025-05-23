<?php

namespace App\Http\Controllers;

use App\Models\TrainingContent;
use App\Models\TrainingProgress;
use App\Models\Training;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class TrainingContentController extends Controller
{
    // 📋 Show All Content for a Training
    public function index($trainingId)
    {
        $training = Training::findOrFail($trainingId);
        $contents = TrainingContent::where('training_id', $trainingId)->orderBy('order')->get();
        return view('training.content.index', compact('training', 'contents'));
    }

    // ✅ Show Create Content Form
    public function create($trainingId)
    {
        $trainingId = Training::findOrFail($trainingId)->id;
        return view('training.content.create', compact('trainingId'));
    }

    // ✅ Store New Content with YouTube Duration Fetch
    public function store(Request $request, $id)
    {
        $validated = $request->validate([
            'title'         => 'required|string|max:255',
            'content_type'  => 'required|in:video,pdf',
            'content_url'   => 'required|url',
            'order'         => 'required|integer|min:1',
            'duration'      => 'nullable|string|max:50',
            'visible_after' => 'nullable|date',
        ]);

        $validated['training_id']   = $id;
        $validated['workspace_id']  = session('workspace_id');
        $validated['admin_id']      = auth()->id();
        $validated['drip_enabled']  = $request->has('drip_enabled') ? 1 : 0;
        $validated['media_url']     = $validated['content_url'];

        if ($validated['content_type'] === 'video' && empty($validated['duration'])) {
            $validated['duration'] = $this->getYoutubeVideoDuration($validated['content_url']);
        }

        TrainingContent::create($validated);

        return redirect()->route('training.show', $id)
                         ->with('success', 'Training content uploaded successfully.');
    }

    // ✅ Mark Content as Completed
    public function markContentCompleted(Request $request)
    {
        $request->validate([
            'training_id' => 'required|exists:trainings,id',
            'content_id'  => 'required|exists:training_contents,id',
        ]);

        $user = auth()->user();
        $workspace_id = session('workspace_id');

        $existing = DB::table('training_progresses')
            ->where('workspace_id', $workspace_id)
            ->where('admin_id', $user->id)
            ->where('training_id', $request->training_id)
            ->where('content_id', $request->content_id)
            ->first();

        if (!$existing) {
            DB::table('training_progresses')->insert([
                'workspace_id' => $workspace_id,
                'admin_id'     => $user->id,
                'training_id'  => $request->training_id,
                'content_id'   => $request->content_id,
                'status'       => 'completed',
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }

        return back()->with('success', 'Content marked as complete!');
    }

    // ⌛ Fetch YouTube Video Duration
    private function getYoutubeVideoDuration($url)
    {
        $apiKey = env('YOUTUBE_API_KEY');

        preg_match('/(?:v=|embed\/|youtu.be\/)([\w-]{11})/', $url, $matches);
        $videoId = $matches[1] ?? null;

        if (!$videoId || !$apiKey) {
            return null;
        }

        $apiUrl = "https://www.googleapis.com/youtube/v3/videos?id={$videoId}&part=contentDetails&key={$apiKey}";
        $response = Http::get($apiUrl);

        if ($response->successful() && isset($response['items'][0]['contentDetails']['duration'])) {
            return $this->convertYoutubeDuration($response['items'][0]['contentDetails']['duration']);
        }

        return null;
    }

    // ⏳ Convert ISO 8601 Duration to Minutes
    private function convertYoutubeDuration($iso)
    {
        try {
            $interval = new \DateInterval($iso);
            $minutes = ($interval->h * 60) + $interval->i;
            if ($interval->s > 0) {
                $minutes += 1;
            }
            return (string) $minutes;
        } catch (\Exception $e) {
            return null;
        }
    }

    // ✏️ Edit Content
    public function edit($id)
    {
        $content = TrainingContent::findOrFail($id);
        return view('training.content.edit', compact('content'));
    }

    // 🔁 Update Content
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content_url' => 'required|url',
            'order' => 'required|integer|min:1',
            'duration' => 'nullable|string|max:50',
            'visible_after' => 'nullable|date',
        ]);

        $content = TrainingContent::findOrFail($id);
        $validated['media_url'] = $validated['content_url'];
        $content->update($validated);

        return redirect()->route('training.show', $content->training_id)->with('success', 'Content updated successfully.');
    }

    // ❌ Delete Content
    public function destroy($id)
    {
        $content = TrainingContent::findOrFail($id);
        $trainingId = $content->training_id;
        $content->delete();

        return redirect()->route('training.show', $trainingId)->with('success', 'Content deleted successfully.');
    }
}
