@extends('layout')

@section('title', 'Start New Discussion')

@section('content')
<div class="container mt-5">
    <h2 class="mb-4">➕ Start New Discussion</h2>

    <form action="{{ route('training.forum.store') }}" method="POST">
        @csrf

        <div class="mb-3">
            <label for="title" class="form-label">Discussion Title</label>
            <input type="text" name="title" id="title" class="form-control" placeholder="Enter a catchy title..." required>
        </div>

        <div class="mb-3">
            <label for="message" class="form-label">Message</label>
            <textarea name="message" id="message" class="form-control" rows="6" placeholder="Share your thoughts..." required></textarea>
        </div>

        <button type="submit" class="btn btn-success">🚀 Post Discussion</button>
    </form>
</div>
@endsection
