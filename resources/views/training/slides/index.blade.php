@extends('layout')

@section('title', 'Training Slides')

@section('content')
<div class="container mt-5">
    <h2 class="mb-4">
        📑 Slides for Training: <strong>{{ $training->title }}</strong>
    </h2>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="{{ route('training.slides.create', $training->id) }}" class="btn btn-primary">
            ➕ Add New Slide
        </a>
        <a href="{{ route('training.slides.player', $training->id) }}" class="btn btn-success">
            🎬 View in Player Mode
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @php
        $adminUser = \Auth::guard('admin')->user();
    @endphp

    <div class="alert alert-info">
        🔐 <strong>Debug Info:</strong><br>
        Admin User is: <strong>{{ $adminUser ? 'YES' : 'NO' }}</strong><br>
        @if($adminUser)
            Name: {{ $adminUser->name }} (ID: {{ $adminUser->id }})<br>
            Roles: {{ implode(', ', $adminUser->getRoleNames()->toArray()) }}<br>
            Permissions: {{ implode(', ', $adminUser->getAllPermissions()->pluck('name')->toArray()) }}<br>
            Can Edit Training: <strong>{{ $adminUser->hasPermissionTo('edit training') ? 'YES' : 'NO' }}</strong><br>
            Can Delete Training: <strong>{{ $adminUser->hasPermissionTo('delete training') ? 'YES' : 'NO' }}</strong>
        @else
            ❌ Admin user not found from auth('admin') guard.
        @endif
    </div>

    @if($slides->count())
        <table class="table table-bordered table-striped">
            <thead class="table-light">
                <tr>
                    <th>🔢 Order</th>
                    <th>📘 Title</th>
                    <th>🎥 Type</th>
                    <th>⏱️ Duration</th>
                    <th>🕒 Drip</th>
                    <th>⚙️ Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($slides as $slide)
                    <tr>
                        <td>{{ $slide->order }}</td>
                        <td>{{ $slide->title }}</td>
                        <td>{{ ucfirst($slide->content_type) }}</td>
                        <td>{{ $slide->duration ?? 'N/A' }}</td>
                        <td>
                            {{ $slide->drip_enabled ? 'Yes' : 'No' }}
                            @if($slide->visible_after)
                                <br><small>{{ \Carbon\Carbon::parse($slide->visible_after)->format('d M Y') }}</small>
                            @endif
                        </td>
                        <td>
                            @if($adminUser && $adminUser->hasPermissionTo('edit training'))
                                <a href="{{ route('training.slides.edit', $slide->id) }}" class="btn btn-sm btn-warning">✏️ Edit</a>
                            @endif

                            @if($adminUser && $adminUser->hasPermissionTo('delete training'))
                                <form action="{{ route('training.slides.delete', $slide->id) }}" method="POST" style="display:inline-block;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure to delete this slide?')">
                                        🗑️ Delete
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="alert alert-info">No slides added for this training yet.</div>
    @endif
</div>
@endsection
