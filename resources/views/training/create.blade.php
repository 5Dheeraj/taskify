@extends('layout')

@section('title', 'Create Training')

@section('content')
<div class="container mt-5">
    <h2 class="mb-4">
        <img src="{{ asset('assets/img/icons/unicons/cc-primary.png') }}" width="24" class="me-2">
        Create New Training
    </h2>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form action="{{ route('training.store') }}" method="POST">
        @csrf

        <div class="form-group mb-3">
            <label for="title">Training Title *</label>
            <input type="text" class="form-control" name="title" required>
        </div>

        <div class="form-group mb-3">
            <label for="description">Description</label>
            <textarea class="form-control" name="description" rows="4"></textarea>
        </div>

        <div class="form-group mb-3">
            <label for="level">Level</label>
            <input type="text" class="form-control" name="level" placeholder="Beginner / Intermediate / Advanced">
        </div>

        <div class="form-group mb-3">
            <label for="course_type">Course Type</label>
            <select class="form-control" name="course_type">
                <option value="mandatory">Mandatory</option>
                <option value="optional">Optional</option>
            </select>
        </div>

        <div class="form-group mb-3">
            <label for="language">Language</label>
            <input type="text" class="form-control" name="language" placeholder="English / Hindi">
        </div>

        <div class="form-group mb-3">
            <label for="status">Status *</label>
            <select class="form-control" name="status" required>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
        </div>

        <div class="form-group mb-4">
            <label for="assigned_users">Assign to Users</label>
            <div class="row">
                @forelse ($users as $user)
                    @php
                        $roleNames = $user->getRoleNames()->implode(', ');
                        $fullName = trim($user->first_name . ' ' . $user->last_name);
                        $displayName = $fullName ?: ($user->name ?: 'Unnamed');
                    @endphp
                    <div class="col-md-4">
                        <div class="form-check mb-2">
                            <input type="checkbox" class="form-check-input" name="assigned_users[]" value="{{ $user->id }}" id="user_{{ $user->id }}" checked>
                            <label class="form-check-label" for="user_{{ $user->id }}">
                                {{ $displayName }} ({{ $roleNames }}) – {{ $user->email }}
                            </label>
                        </div>
                    </div>
                @empty
                    <p class="text-muted">No users found in this workspace.</p>
                @endforelse
            </div>
        </div>

        <button type="submit" class="btn btn-success">✅ Create Training</button>
        <a href="{{ route('training.index') }}" class="btn btn-secondary">Back</a>
    </form>
</div>
@endsection
