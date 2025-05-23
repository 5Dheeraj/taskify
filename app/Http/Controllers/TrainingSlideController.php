<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Training;
use App\Models\TrainingSlide;

class TrainingSlideController extends Controller
{
    // ✅ Show all slides for a training
    public function show($id)
    {
        $this->checkSlideAccess(); // 🛡️ Permission check

        $training = Training::findOrFail($id);
        $slides = TrainingSlide::where('training_id', $id)->orderBy('order')->get();

        return view('training.slides.show', compact('training', 'slides'));
    }

    // ✅ Show form to create a new slide
    public function create($id)
    {
        $this->checkSlideAccess(); // 🛡️ Permission check

        $training = Training::findOrFail($id);
        return view('training.slides.create', compact('training'));
    }

    // ✅ Store the new slide
    public function store(Request $request)
    {
        $this->checkSlideAccess(); // 🛡️ Permission check

        $request->validate([
            'training_id'   => 'required|exists:trainings,id',
            'title'         => 'required|string|max:255',
            'description'   => 'nullable|string',
            'content_type'  => 'required|in:text,video,mixed',
            'media_url'     => 'nullable|url',
            'order'         => 'required|integer|min:1',
            'duration'      => 'nullable|string|max:50',
            'visible_after' => 'nullable|date',
        ]);

        $data = $request->only([
            'training_id', 'title', 'description', 'content_type',
            'media_url', 'order', 'duration', 'visible_after'
        ]);

        $data['workspace_id'] = session('workspace_id');
        $data['admin_id'] = auth()->id();
        $data['drip_enabled'] = $request->has('drip_enabled') ? 1 : 0;

        TrainingSlide::create($data);

        return redirect()->route('training.slides.show', $data['training_id'])
                         ->with('success', 'Slide added successfully.');
    }

    // ✅ Edit slide form
    public function edit($id)
    {
        $this->checkSlideAccess(); // 🛡️ Permission check

        $slide = TrainingSlide::findOrFail($id);
        return view('training.slides.edit', compact('slide'));
    }

    // ✅ Update slide
    public function update(Request $request, $id)
    {
        $this->checkSlideAccess(); // 🛡️ Permission check

        $slide = TrainingSlide::findOrFail($id);

        $request->validate([
            'title'         => 'required|string|max:255',
            'description'   => 'nullable|string',
            'content_type'  => 'required|in:text,video,mixed',
            'media_url'     => 'nullable|url',
            'order'         => 'required|integer|min:1',
            'duration'      => 'nullable|string|max:50',
            'visible_after' => 'nullable|date',
        ]);

        $data = $request->only([
            'title', 'description', 'content_type',
            'media_url', 'order', 'duration', 'visible_after'
        ]);

        $data['drip_enabled'] = $request->has('drip_enabled') ? 1 : 0;

        $slide->update($data);

        return redirect()->route('training.slides.show', $slide->training_id)
                         ->with('success', 'Slide updated successfully.');
    }

    // ✅ Delete slide
    public function destroy($id)
    {
        $this->checkSlideAccess(); // 🛡️ Permission check

        $slide = TrainingSlide::findOrFail($id);
        $trainingId = $slide->training_id;
        $slide->delete();

        return redirect()->route('training.slides.show', $trainingId)
                         ->with('success', 'Slide deleted successfully.');
    }

    // ✅ Show slide player for users
    public function player($id)
    {
        $training = Training::findOrFail($id);
        $slides = TrainingSlide::where('training_id', $id)->orderBy('order')->get();

        return view('training.slides.player', compact('training', 'slides'));
    }

    // ✅ Common permission check for all slide-related pages
private function checkSlideAccess()
{
    $user = auth()->user();

    // Debug fallback if role/permission traits are missing
    if (!$user) {
        abort(403, 'User not authenticated.');
    }

    // If user is admin OR has explicit permission, allow
    if ($user->hasRole('admin')) {
        return;
    }

    if ($user->can('access_training_module')) {
        return;
    }

    // Default fallback
    abort(403, 'You do not have permission to access training slides.');
}
}