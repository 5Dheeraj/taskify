@extends('layout')

@section('title', 'Training Details')

@section('content')
<div class="container mt-5">
    <div class="card shadow rounded">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h4 class="mb-0">
                <img src="{{ asset('assets/img/icons/unicons/chart.png') }}" width="24" class="me-2">
                {{ $training->title }}
            </h4>
            @if(auth()->user()->hasRole('admin') || auth()->user()->hasRole('company_owner'))
                <div class="d-flex">
                    <a href="{{ route('training.content.create', $training->id) }}" class="btn btn-light btn-sm me-2">
                        ➕ Add Content
                    </a>
                    <a href="{{ route('training.slides.show', $training->id) }}" class="btn btn-outline-warning btn-sm">
                        📑 Manage Slides
                    </a>
                </div>
            @endif
        </div>

        <div class="card-body">
            <div class="mb-3">
                <strong>Description:</strong>
                <div class="border p-2 bg-light rounded">{{ $training->description ?? 'N/A' }}</div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6"><strong>🧩 Level:</strong> {{ ucfirst($training->level) }}</div>
                <div class="col-md-6"><strong>🧭 Type:</strong> {{ ucfirst($training->course_type) }}</div>
                <div class="col-md-6"><strong>🌐 Language:</strong> {{ $training->language }}</div>
                <div class="col-md-6"><strong>📅 Deadline:</strong> {{ $training->completion_deadline ?? 'N/A' }}</div>
            </div>

            <div class="mb-4">
                <strong>📊 Completion Progress:</strong>
                <div class="progress" style="height: 22px;">
                    <div class="progress-bar bg-success" role="progressbar" style="width: {{ $progressPercentage }}%;" aria-valuenow="{{ $progressPercentage }}" aria-valuemin="0" aria-valuemax="100">
                        {{ $progressPercentage }}%
                    </div>
                </div>
            </div>

            @php
                $hasSlides = \App\Models\TrainingSlide::where('training_id', $training->id)->exists();
            @endphp

            @if($hasSlides)
                <h5 class="mt-4">
                    <img src="{{ asset('assets/img/icons/unicons/presentation.png') }}" width="20" class="me-2">
                    Slide-Based Training
                </h5>
                <a href="{{ route('training.slides.player', $training->id) }}" class="btn btn-info mb-4">
                    ▶️ Launch Slide Player
                </a>
            @endif

            <h5 class="mt-4">
                <img src="{{ asset('assets/img/icons/unicons/chart-success.png') }}" width="20" class="me-2">
                Modules
            </h5>

            @if($contents->count())
                <ul class="list-group mb-4">
                    @foreach($contents as $content)
                        <li class="list-group-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <strong>{{ $content->order }}. {{ $content->title }}</strong><br>

                                    @if($content->content_type === 'video')
                                        <div class="ratio ratio-16x9 mt-2">
                                            <iframe
                                                src="{{ $content->media_url }}?rel=0&modestbranding=1&controls=1"
                                                title="Training Video"
                                                frameborder="0"
                                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                                allowfullscreen>
                                            </iframe>
                                        </div>
                                    @endif

                                    <small>{{ ucfirst($content->content_type) }} | Duration: {{ $content->duration ?? 'N/A' }}</small>

                                    @if(auth()->user()->hasRole('admin') || auth()->user()->hasRole('company_owner'))
                                        <div class="mt-2">
                                            <a href="{{ route('training.content.edit', $content->id) }}" class="btn btn-warning btn-sm me-1">✏️ Edit</a>
                                            <form action="{{ route('training.content.delete', $content->id) }}" method="POST" class="d-inline-block" onsubmit="return confirm('Are you sure you want to delete this content?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm">🗑 Delete</button>
                                            </form>
                                        </div>
                                    @endif
                                </div>

                                <div class="text-end">
                                    @if(in_array($content->id, $completedContents))
                                        <span class="badge bg-success mt-2">✅ Completed</span>
                                    @else
                                        <form action="{{ route('training.content.complete') }}" method="POST" class="mt-2">
                                            @csrf
                                            <input type="hidden" name="training_id" value="{{ $training->id }}">
                                            <input type="hidden" name="content_id" value="{{ $content->id }}">
                                            <button type="submit" class="btn btn-outline-primary btn-sm">✔️ Mark as Done</button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="alert alert-info">📭 No training content available yet.</div>
            @endif
        </div>
    </div>
</div>
@endsection

{{-- ===================== Assignment Section ===================== --}}
@if($assignments->count())
<div class="mt-5">
    <h5><img src="{{ asset('assets/img/icons/unicons/folder.png') }}" width="20" class="me-2">Assignments</h5>
    <ul class="list-group">
        @foreach($assignments as $assignment)
            <li class="list-group-item d-flex justify-content-between align-items-center">
                {{ $assignment->title }}
                <a href="{{ asset('storage/assignments/' . $assignment->file) }}" class="btn btn-sm btn-primary" target="_blank">📥 Download</a>
            </li>
        @endforeach
    </ul>
</div>
@endif

{{-- ===================== Quiz Section ===================== --}}
@if($quizAvailable)
<div class="mt-5">
    <h5><img src="{{ asset('assets/img/icons/unicons/clipboard.png') }}" width="20" class="me-2">Quiz</h5>
    @if($quizCompleted)
        <div class="alert alert-success">✅ Quiz Completed! Your score: {{ $quizScore }}%</div>
    @else
        <a href="{{ route('training.quiz.index', $training->id) }}" class="btn btn-warning">🎯 Start Quiz</a>
    @endif
</div>
@endif

{{-- ===================== Certificate Section ===================== --}}
@if($progressPercentage == 100 && $quizCompleted)
<div class="mt-5">
    <h5><img src="{{ asset('assets/img/icons/unicons/certificate.png') }}" width="20" class="me-2">Certificate</h5>
    <a href="{{ route('training.certificate.download', $training->id) }}" class="btn btn-success">🎓 Download Certificate</a>
</div>
@endif
