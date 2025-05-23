@extends('layout')

@section('title', 'Edit Slide')

@section('content')
<div class="container mt-5">
    <h2 class="mb-4">✏️ Edit Slide: <strong>{{ $slide->title }}</strong></h2>

    <form action="{{ route('training.slides.update', $slide->id) }}" method="POST">
        @csrf
        @method('PUT')

        <input type="hidden" name="training_id" value="{{ $slide->training_id }}">

        <div class="mb-3">
            <label for="title" class="form-label">📘 Slide Title</label>
            <input type="text" name="title" class="form-control" required value="{{ old('title', $slide->title) }}">
        </div>

        <div class="mb-3">
            <label for="description" class="form-label">📝 Description / Content</label>
            <textarea name="description" class="form-control" rows="5">{{ old('description', $slide->description) }}</textarea>
            <small class="text-muted">Basic HTML allowed.</small>
        </div>

        <div class="mb-3">
            <label for="content_type" class="form-label">🎥 Content Type</label>
            <select name="content_type" class="form-select" required>
                <option value="text" {{ old('content_type', $slide->content_type) == 'text' ? 'selected' : '' }}>Text Only</option>
                <option value="video" {{ old('content_type', $slide->content_type) == 'video' ? 'selected' : '' }}>YouTube Video</option>
                <option value="mixed" {{ old('content_type', $slide->content_type) == 'mixed' ? 'selected' : '' }}>Text + Video</option>
            </select>
        </div>

        <div class="mb-3">
            <label for="media_url" class="form-label">🔗 Media URL (YouTube)</label>
            <input type="url" name="media_url" class="form-control" value="{{ old('media_url', $slide->media_url) }}">
        </div>

        <div class="mb-3">
            <label for="order" class="form-label">🔢 Display Order</label>
            <input type="number" name="order" class="form-control" min="1" required value="{{ old('order', $slide->order) }}">
        </div>

        <div class="mb-3">
            <label for="duration" class="form-label">⏱️ Duration (optional)</label>
            <input type="text" name="duration" class="form-control" value="{{ old('duration', $slide->duration) }}">
        </div>

        <div class="mb-3 form-check">
            <input type="checkbox" name="drip_enabled" class="form-check-input" id="drip_enabled"
                {{ old('drip_enabled', $slide->drip_enabled) ? 'checked' : '' }}>
            <label class="form-check-label" for="drip_enabled">🕒 Enable Drip (Delayed Release)</label>
        </div>

        <div class="mb-3">
            <label for="visible_after" class="form-label">📅 Visible After</label>
            <input type="date" name="visible_after" class="form-control"
                value="{{ old('visible_after', $slide->visible_after ? \Carbon\Carbon::parse($slide->visible_after)->format('Y-m-d') : '') }}">
        </div>

        <button type="submit" class="btn btn-primary">💾 Update Slide</button>
        <a href="{{ route('training.slides.show', $slide->training_id) }}" class="btn btn-secondary">🔙 Back</a>
    </form>
</div>
@endsection
