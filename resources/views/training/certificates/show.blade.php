@extends('layout')

@section('title', 'Training Certificate')

@section('content')
<div class="container mt-5 text-center">
    <h2>🏆 Congratulations!</h2>
    <p>You have successfully completed the training:</p>
    <h3 class="mb-4">📚 {{ $training->title }}</h3>

    <div class="certificate-box p-4" style="border: 5px double #4CAF50; background: #f9f9f9;">
        <h1 style="font-family: 'Georgia', serif;">Certificate of Completion</h1>
        <p>This certificate is proudly presented to</p>
        <h2>{{ auth()->user()->name }}</h2>
        <p>for successfully completing the course</p>
        <h4>"{{ $training->title }}"</h4>
        <p>on {{ now()->format('d F, Y') }}</p>
        <p><em>Issued by {{ config('app.name') }}</em></p>
    </div>

    <a href="{{ route('training.certificate.download', $training->id) }}" class="btn btn-primary mt-4">⬇️ Download Certificate</a>

    <div class="mt-3">
        <a href="{{ route('training.my') }}" class="btn btn-secondary">⬅️ Back to My Trainings</a>
    </div>
</div>
@endsection
