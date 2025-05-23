@extends('layout')

@section('title', 'Training Assignment')

@section('content')
<div class="container mt-4">
    <h2>📄 Assignment: {{ $training->title }}</h2>

    <div class="mb-3">
        <p><strong>Instructions:</strong> {{ $training->assignment_instruction ?? 'Please complete the assignment as per the guidelines.' }}</p>
        <p><strong>Deadline:</strong> {{ \Carbon\Carbon::parse($training->completion_deadline)->format('d M Y') }}</p>
    </div>

    @if($submission)
        <div class="alert alert-info">
            ✅ You have already submitted the assignment.
        </div>
        <p><strong>Status:</strong> {{ ucfirst($submission->status) }}</p>
        <p><strong>Submitted on:</strong> {{ $submission->created_at->format('d M Y, h:i A') }}</p>
        <p><strong>Your File:</strong> <a href="{{ asset('storage/' . $submission->file_path) }}" target="_blank">📎 View Submission</a></p>
    @else
        <form action="{{ route('training.assignment.submit', $training->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="mb-3">
                <label for="assignment" class="form-label">Upload Assignment (PDF/Image)</label>
                <input type="file" name="assignment" id="assignment" class="form-control" accept=".pdf,image/*" required>
            </div>
            <button type="submit" class="btn btn-primary">📤 Submit Assignment</button>
        </form>
    @endif

    <div class="mt-3">
        <a href="{{ route('training.show', $training->id) }}" class="btn btn-secondary">⬅️ Back to Training</a>
    </div>
</div>
@endsection
