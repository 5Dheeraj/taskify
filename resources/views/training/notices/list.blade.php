@extends('layout')

@section('title', 'Training Notices')

@section('content')
<div class="container mt-5">
    <h2 class="mb-4">📢 Latest Training Notices</h2>

    @if ($notices->count())
        <div class="list-group">
            @foreach ($notices as $notice)
                <div class="list-group-item mb-3">
                    <h5 class="mb-1">{{ $notice->title }}</h5>
                    <p class="mb-1">{{ Str::limit($notice->message, 150) }}</p>
                    <small class="text-muted">Posted on {{ $notice->created_at->format('d M Y') }}</small>
                </div>
            @endforeach
        </div>
    @else
        <div class="alert alert-warning">No notices available at the moment. Stay tuned! 📻</div>
    @endif

    <div class="mt-4">
        <a href="{{ route('training.my') }}" class="btn btn-secondary">⬅️ Back to My Trainings</a>
    </div>
</div>
@endsection
