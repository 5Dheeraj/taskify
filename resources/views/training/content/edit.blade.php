@extends('layout')

@section('title', 'Edit Training Content')

@section('content')
<div class="container mt-5">
    <h2 class="mb-4">
        <img src="{{ asset('assets/img/icons/unicons/cc-warning.png') }}" width="24" class="me-2">
        Edit Training Content
    </h2>

    <form method="POST" action="{{ route('training.content.update', $content->id) }}">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label for="title" class="form-label">📚 Title</label>
            <input type="text" name="title" class="form-control" required value="{{ old('title', $content->title) }}">
        </div>

        <div class="mb-3">
            <label for="content_url" class="form-label">🔗 Content URL</label>
            <input type="url" name="content_url" class="form-control" required value="{{ old('content_url', $content->media_url) }}">
        </div>

        <div class="mb-3">
            <label for="order" class="form-label">🔢 Display Order</label>
            <input type="number" name="order" class="form-control" required min="1" value="{{ old('order', $content->order) }}">
        </div>

        <div class="mb-3">
            <label for="duration" class="form-label">⏱️ Duration (Minutes)</label>
            <input type="text" name="duration" class="form-control" value="{{ old('duration', $content->duration) }}">
        </div>

        <div class="mb-3">
            <label for="visible_after" class="form-label">⏳ Visible After (Optional)</label>
            <input type="date" name="visible_after" class="form-control" 
                value="{{ old('visible_after', $content->visible_after ? \Carbon\Carbon::parse($content->visible_after)->format('Y-m-d') : '') }}">
        </div>

        <button type="submit" class="btn btn-primary">
            <img src="{{ asset('assets/img/icons/unicons/cc-primary.png') }}" width="20"> Update Content
        </button>
    </form>
</div>
@endsection
