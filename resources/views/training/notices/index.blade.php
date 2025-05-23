@extends('layout')

@section('title', 'Training Notices')

@section('content')
<div class="container mt-4">
    <h2>📢 Training Notices</h2>

    <a href="{{ route('training.notices.create') }}" class="btn btn-sm btn-primary mb-3">➕ Create Notice</a>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($notices->count())
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>📌 Title</th>
                    <th>Status</th>
                    <th>📅 Created At</th>
                </tr>
            </thead>
            <tbody>
                @foreach($notices as $notice)
                    <tr>
                        <td>{{ $notice->title }}</td>
                        <td>{{ ucfirst($notice->status) }}</td>
                        <td>{{ $notice->created_at->format('d M Y') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p>No notices found yet.</p>
    @endif
</div>
@endsection
