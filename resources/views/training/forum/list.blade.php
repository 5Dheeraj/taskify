@extends('layout')

@section('title', 'Training Discussion Forum')

@section('content')
<div class="container mt-5">
    <h2 class="mb-4">💬 Training Forum</h2>

    <a href="{{ route('training.forum.create') }}" class="btn btn-primary mb-3">➕ Start New Discussion</a>

    @if ($posts->count())
        <div class="list-group">
            @foreach ($posts as $post)
                <div class="list-group-item mb-3">
                    <h5 class="mb-1">{{ $post->title }}</h5>
                    <p class="mb-1">{{ Str::limit($post->message, 100) }}</p>
                    <small class="text-muted">Posted by {{ $post->user->name }} on {{ $post->created_at->format('d M Y') }}</small>
                    <div class="mt-2">
                        <a href="{{ route('training.forum.show', $post->id) }}" class="btn btn-sm btn-outline-secondary">🔎 View Discussion</a>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="alert alert-warning">No discussions started yet. Be the first one! 🚀</div>
    @endif
</div>
@endsection
