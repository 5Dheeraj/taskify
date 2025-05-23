@extends('layout')

@section('title', 'Add Training Content')

@section('content')
<div class="container mt-5">
    <h2 class="mb-4">
        <img src="{{ asset('assets/img/icons/unicons/cc-primary.png') }}" width="24" class="me-2">
        Add New Training Content
    </h2>

    {{-- ✅ Form action now includes trainingId --}}
    <form method="POST" action="{{ route('training.content.store', $trainingId) }}">
        @csrf
        <input type="hidden" name="training_id" value="{{ $trainingId }}">

        <div class="mb-3">
            <label for="title" class="form-label">📚 Title</label>
            <input type="text" name="title" class="form-control" required value="{{ old('title') }}">
        </div>

        <div class="mb-3">
            <label for="content_type" class="form-label">🎬 Content Type</label>
            <select name="content_type" class="form-select" required>
                <option value="">Select Type</option>
                <option value="video" {{ old('content_type') == 'video' ? 'selected' : '' }}>Video</option>
                <option value="pdf" {{ old('content_type') == 'pdf' ? 'selected' : '' }}>PDF/Slide</option>
            </select>
        </div>

        <div class="mb-3">
            <label for="content_url" class="form-label">🔗 Content URL</label>
            <input type="url" name="content_url" class="form-control" required value="{{ old('content_url') }}">
        </div>

        <div class="mb-3">
            <label for="order" class="form-label">🔢 Display Order</label>
            <input type="number" name="order" class="form-control" required min="1" value="{{ old('order', 1) }}">
        </div>

        <div class="mb-3">
            <label for="duration" class="form-label">⏱️ Duration (Minutes)</label>
            <input type="text" name="duration" class="form-control" value="{{ old('duration') }}">
            <small class="text-muted">Leave empty for YouTube auto-fetch</small>
        </div>

        <div class="mb-3">
            <label for="visible_after" class="form-label">⏳ Visible After (Optional)</label>
            <input type="date" name="visible_after" class="form-control" value="{{ old('visible_after') }}">
        </div>

        <div class="mb-3 form-check">
            <input type="checkbox" name="drip_enabled" class="form-check-input" id="drip_enabled">
            <label class="form-check-label" for="drip_enabled">Enable Drip (Content release delay)</label>
        </div>

        <button type="submit" class="btn btn-success">
            <img src="{{ asset('assets/img/icons/unicons/cc-success.png') }}" width="20"> Save Content
        </button>
    </form>
</div>
@endsection
