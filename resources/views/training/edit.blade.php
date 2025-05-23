@extends('layout')

@section('title', 'Edit Training')

@section('content')
<div class="container mt-5">
    <h2 class="mb-4">
        <img src="{{ asset('assets/img/icons/unicons/edit.png') }}" width="24" class="me-2">
        Edit Training – {{ $training->title }}
    </h2>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form action="{{ route('training.update', $training->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="form-group mb-3">
            <label for="title">Training Title *</label>
            <input type="text" class="form-control" name="title" value="{{ $training->title }}" required>
        </div>

        <div class="form-group mb-3">
            <label for="description">Description</label>
            <textarea class="form-control" name="description" rows="4">{{ $training->description }}</textarea>
        </div>

        <div class="form-group mb-3">
            <label for="level">Level</label>
            <input type="text" class="form-control" name="level" value="{{ $training->level }}">
        </div>

        <div class="form-group mb-3">
            <label for="course_type">Course Type</label>
            <select class="form-control" name="course_type">
                <option value="mandatory" {{ $training->course_type == 'mandatory' ? 'selected' : '' }}>Mandatory</option>
                <option value="optional" {{ $training->course_type == 'optional' ? 'selected' : '' }}>Optional</option>
            </select>
        </div>

        <div class="form-group mb-3">
            <label for="language">Language</label>
            <input type="text" class="form-control" name="language" value="{{ $training->language }}">
        </div>

        <div class="form-group mb-3">
            <label for="status">Status *</label>
            <select class="form-control" name="status" required>
                <option value="active" {{ $training->status == 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ $training->status == 'inactive' ? 'selected' : '' }}>Inactive</option>
            </select>
        </div>

        <div class="form-group mb-4">
            <label for="assigned_users">Assign to Users</label>
            <div class="row">
                @foreach ($users as $user)
                    @php
                        $role = $user->roles->first();
                        $roleLabel = $role ? $role->name : 'No Role';
                    @endphp
                    <div class="col-md-4">
                        <div class="form-check mb-2">
                            <input type="checkbox"
                                   class="form-check-input"
                                   name="assigned_users[]"
                                   value="{{ $user->id }}"
                                   id="user_{{ $user->id }}"
                                   {{ in_array($user->id, $assignedUserIds) ? 'checked' : '' }}>
                            <label class="form-check-label" for="user_{{ $user->id }}">
                                {{ $user->name ?? 'Unnamed' }} ({{ $roleLabel }}) – {{ $user->email }}
                            </label>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <button type="submit" class="btn btn-primary">💾 Update Training</button>
        <a href="{{ route('training.index') }}" class="btn btn-secondary">Back</a>
    </form>
</div>
@endsection
