@extends('layout')

@section('title', 'Slide List')

@section('content')
<div class="container mt-5">

    @php
        $user = auth('admin')->user();
        $workspace_id = session()->get('workspace_id');
        $hasAccess = false;

        if ($user) {
            $hasAccess = \App\Models\TrainingAccess::where('training_id', $training->id)
                ->where('workspace_id', $workspace_id)
                ->where(function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                    if ($user->roles->isNotEmpty()) {
                        $query->orWhereIn('role_id', $user->roles->pluck('id'));
                    }
                })->exists();
        }
    @endphp

    @if ($user && ($user->id === $training->admin_id || $hasAccess))

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>
                <img src="{{ asset('assets/img/icons/unicons/chart.png') }}" width="26" class="me-2">
                Slides for: <strong>{{ $training->title }}</strong>
            </h2>
            <div>
                <a href="{{ route('training.slides.create', $training->id) }}" class="btn btn-primary me-2">
                    ➕ Add New Slide
                </a>
                <a href="{{ route('training.slides.player', $training->id) }}" class="btn btn-outline-success">
                    🎬 View in Player
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if($slides->count())
            <div class="table-responsive">
                <table class="table table-bordered table-hover shadow-sm">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>📘 Title</th>
                            <th>🔢 Order</th>
                            <th>🧩 Type</th>
                            <th>⏱ Duration</th>
                            <th>⏳ Visible After</th>
                            <th>⚙️ Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($slides as $slide)
                        <tr>
                            <td>{{ $slide->id }}</td>
                            <td>{{ $slide->title }}</td>
                            <td>{{ $slide->order }}</td>
                            <td>{{ ucfirst($slide->content_type) }}</td>
                            <td>{{ $slide->duration ?? 'N/A' }}</td>
                            <td>{{ $slide->visible_after ? \Carbon\Carbon::parse($slide->visible_after)->format('d M Y') : 'N/A' }}</td>
                            <td>
                                <a href="{{ route('training.slides.edit', $slide->id) }}" class="btn btn-sm btn-warning me-1">
                                    ✏️ Edit
                                </a>
                                <form action="{{ route('training.slides.delete', $slide->id) }}" method="POST" class="d-inline-block" onsubmit="return confirm('Are you sure you want to delete this slide?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        🗑 Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="alert alert-info">ℹ️ No slides found for this training yet.</div>
        @endif

    @else
        <div class="alert alert-danger text-center mt-5">
            🚫 You do not have permission to view these slides.
        </div>
    @endif

</div>
@endsection
