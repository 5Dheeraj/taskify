@extends('layout')

@section('title', 'Add Training Slide')

@section('content')
<div class="container mt-5">
    <h2 class="mb-4">➕ Add New Slide to: <strong>{{ $training->title }}</strong></h2>

    <form action="{{ route('training.slides.store') }}" method="POST">
        @csrf

        <input type="hidden" name="training_id" value="{{ $training->id }}">

        <div class="mb-3">
            <label for="title" class="form-label">📘 Slide Title</label>
            <input type="text" name="title" class="form-control" required value="{{ old('title') }}">
        </div>

        <div class="mb-3">
            <label for="description" class="form-label">📝 Description / Content</label>
            <textarea name="description" class="form-control" rows="5">{{ old('description') }}</textarea>
            <small class="text-muted">You can use basic HTML if needed.</small>
        </div>

        <div class="mb-3">
            <label for="content_type" class="form-label">🎥 Content Type</label>
            <select name="content_type" class="form-select" required>
                <option value="">-- Select --</option>
                <option value="text" {{ old('content_type') == 'text' ? 'selected' : '' }}>Text Only</option>
                <option value="video" {{ old('content_type') == 'video' ? 'selected' : '' }}>YouTube Video</option>
                <option value="mixed" {{ old('content_type') == 'mixed' ? 'selected' : '' }}>Text + Video</option>
            </select>
        </div>

        <div class="mb-3">
            <label for="media_url" class="form-label">🔗 Media URL (YouTube)</label>
            <input type="url" name="media_url" class="form-control" value="{{ old('media_url') }}">
            <small class="text-muted">Required if type is video or mixed</small>
        </div>

        <div class="mb-3">
            <label for="order" class="form-label">🔢 Display Order</label>
            <input type="number" name="order" class="form-control" value="{{ old('order', 1) }}" min="1" required>
        </div>

        <div class="mb-3">
            <label for="duration" class="form-label">⏱️ Duration (optional)</label>
            <input type="text" name="duration" class="form-control" value="{{ old('duration') }}">
            <small class="text-muted">Example: 5 mins</small>
        </div>

        <div class="mb-3 form-check">
            <input type="checkbox" name="drip_enabled" class="form-check-input" id="drip_enabled" {{ old('drip_enabled') ? 'checked' : '' }}>
            <label for="drip_enabled" class="form-check-label">🕒 Enable Drip (Delayed release)</label>
        </div>

        <div class="mb-3">
            <label for="visible_after" class="form-label">📅 Visible After Date</label>
            <input type="date" name="visible_after" class="form-control" value="{{ old('visible_after') }}">
        </div>

        <button type="submit" class="btn btn-success">
            💾 Save Slide
        </button>
        <a href="{{ route('training.slides.show', $training->id) }}" class="btn btn-secondary">
            🔙 Back
        </a>
    </form>
</div>
@endsection
