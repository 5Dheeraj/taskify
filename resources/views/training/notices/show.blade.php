@extends('layout')

@section('title', 'View Training Notice')

@section('content')
<div class="container mt-4">
    <h2>📄 Notice Details</h2>

    <div class="card mt-3">
        <div class="card-body">
            <h4 class="card-title">{{ $notice->title }}</h4>
            <h6 class="card-subtitle mb-2 text-muted">
                Status: {{ ucfirst($notice->status) }} |
                Date: {{ $notice->created_at->format('d M Y') }}
            </h6>

            <p class="card-text mt-4" style="white-space: pre-line;">
                {{ $notice->message }}
            </p>
        </div>
    </div>

    <a href="{{ route('training.notices.index') }}" class="btn btn-secondary mt-3">🔙 Back to Notices</a>
</div>
@endsection
