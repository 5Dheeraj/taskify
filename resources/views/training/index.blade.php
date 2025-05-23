@extends('layout')

@section('title', 'All Trainings')

@section('content')
<div class="container mt-5">
    <h2 class="mb-4 d-flex align-items-center">
        <img src="{{ asset('assets/img/icons/unicons/chart.png') }}" width="24" class="me-2"> 
        <span>📋 Training List</span>
    </h2>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @can('create_training')
        <a href="{{ route('training.create') }}" class="btn btn-primary mb-3">➕ Create New Training</a>
    @endcan

    @if ($trainings->count())
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>📘 Title</th>
                        <th>🧹 Level</th>
                        <th>📚 Type</th>
                        <th>🌐 Language</th>
                        <th>🗕️ Deadline</th>
                        <th>⚙️ Status</th>
                        <th>📁 Slides</th>
                        <th>📊 Summary</th>
                        <th>🛠️ Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($trainings as $training)
                        <tr>
                            <td>{{ $training->title }}</td>
                            <td>{{ ucfirst($training->level) }}</td>
                            <td>{{ ucfirst($training->content_type) }}</td>
                            <td>{{ $training->language ?? 'N/A' }}</td>
                            <td>{{ $training->completion_deadline ?? 'N/A' }}</td>
                            <td>
                                @if ($training->status == 'active')
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-secondary">Inactive</span>
                                @endif
                            </td>
                              @php
    $user = getAuthenticatedUser();
@endphp
<td>
    @if ($user && ($user->hasRole('admin') || $user->can('access_training_module')))
        <a href="{{ route('training.slides.show', $training->id) }}" class="btn btn-sm btn-info">View</a>
    @endif
                            </td>
                            <td>
                                <a href="{{ route('training.show', $training->id) }}" class="btn btn-sm btn-primary">Summary</a>
                            </td>
                            <td>
                                <div class="d-flex gap-2">
@php
    $user = getAuthenticatedUser(); // helper se current user
@endphp

@if ($user && ($user->hasRole('admin') || $user->id === $training->admin_id))
    <a href="{{ route('training.edit', $training->id) }}" class="btn btn-warning btn-sm">Edit</a>

    <form action="{{ route('training.destroy', $training->id) }}" method="POST" style="display:inline;">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">Delete</button>
    </form>
@endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <p class="text-muted">No training modules found.</p>
    @endif
</div>
@endsection
