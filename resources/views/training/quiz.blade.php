@extends('layout')

@section('title', 'Training Quiz')

@section('content')
<div class="container mt-4">
    <h2>🧠 Quiz: {{ $training->title }}</h2>

    @if($quizResult)
        <div class="alert alert-info">
            <strong>📊 Attempted!</strong><br>
            ✅ Correct: {{ $quizResult->correct }} / {{ $quiz->questions->count() }}<br>
            🎯 Score: {{ $quizResult->percentage }}%
        </div>
        @if($quizResult->passed)
            <div class="alert alert-success">🎉 You passed the quiz!</div>
        @else
            <div class="alert alert-danger">❌ You did not pass. Please try again if allowed.</div>
        @endif
    @else
        <form action="{{ route('training.quiz.submit', $training->id) }}" method="POST">
            @csrf
            @foreach($quiz->questions as $index => $question)
                <div class="mb-4">
                    <strong>Q{{ $index + 1 }}: {{ $question->text }}</strong><br>
                    @if($question->type == 'mcq')
                        @foreach(json_decode($question->options) as $option)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="answers[{{ $question->id }}][]" value="{{ $option }}">
                                <label class="form-check-label">{{ $option }}</label>
                            </div>
                        @endforeach
                    @elseif($question->type == 'true_false')
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="answers[{{ $question->id }}]" value="true" required>
                            <label class="form-check-label">True</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="answers[{{ $question->id }}]" value="false">
                            <label class="form-check-label">False</label>
                        </div>
                    @endif
                </div>
            @endforeach

            <button type="submit" class="btn btn-success">✅ Submit Quiz</button>
        </form>
    @endif

    <div class="mt-3">
        <a href="{{ route('training.show', $training->id) }}" class="btn btn-secondary">⬅️ Back to Training</a>
    </div>
</div>
@endsection
