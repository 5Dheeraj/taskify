@extends('layout') 

@section('title', 'Create Training Notice')

@section('content')
<div class="container mt-4">
    <h2>📝 Create New Notice</h2>

    <form action="{{ route('training.notices.store') }}" method="POST">
        @csrf

        <div class="mb-3">
            <label for="title" class="form-label">Notice Title</label>
            <input type="text" name="title" id="title" class="form-control" required>
        </div>

        <div class="mb-3">
            <label for="message" class="form-label">Notice Message</label>
            <textarea name="message" id="message" class="form-control" rows="5" required></textarea>
        </div>

        <div class="mb-3">
            <label for="status" class="form-label">Status</label>
            <select name="status" id="status" class="form-control">
                <option value="active">Active</option>
                <option value="archived">Archived</option>
            </select>
        </div>

        <button type="submit" class="btn btn-success">📤 Publish Notice</button>
    </form>
</div>
@endsection
