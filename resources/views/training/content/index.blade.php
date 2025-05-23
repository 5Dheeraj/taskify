@extends('layout')

@section('title', 'Training Content')

@section('content')
<div class="container mt-5">
    <h2 class="mb-4">
        <img src="{{ asset('assets/img/icons/unicons/chart.png') }}" width="24" class="me-2">
        Training Content for: <strong>{{ $training->title }}</strong>
    </h2>

    <a href="{{ route('training.content.create', $training->id) }}" class="btn btn-sm btn-primary mb-4">
        ➕ Add New Content
    </a>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($contents->count())
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>📚 Type</th>
                    <th>📄 Title</th>
                    <th>📝 Description</th>
                    <th>🎬 Media / Text</th>
                    <th>📅 Created</th>
                    <th>⚙️ Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($contents as $content)
                    <tr>
                        <td>{{ ucfirst($content->content_type) }}</td>
                        <td>{{ $content->title }}</td>
                        <td>{{ Str::limit($content->description, 50) }}</td>
                        <td>
                            @if($content->content_type === 'video')
                                <a href="{{ $content->media_url }}" target="_blank">🎥 Watch Video</a>
                            @elseif($content->content_type === 'text')
                                <div>{{ Str::limit(strip_tags($content->media_url), 50) }}</div>
                            @elseif($content->content_type === 'slide')
                                <a href="{{ $content->media_url }}" target="_blank">📑 View Slide</a>
                            @endif
                        </td>
                        <td>{{ $content->created_at->format('d M Y') }}</td>
                        <td>
                            <a href="{{ route('training.content.edit', [$training->id, $content->id]) }}" class="btn btn-sm btn-warning">✏️ Edit</a>

                            <form action="{{ route('training.content.destroy', [$training->id, $content->id]) }}" method="POST" style="display:inline-block;">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">🗑️ Delete</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="alert alert-warning">No content modules found for this training.</div>
    @endif
</div>
@endsection
