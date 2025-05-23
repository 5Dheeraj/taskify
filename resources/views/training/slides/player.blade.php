@extends('layout')

@section('title', 'Training Player')

@section('content')
<div class="container mt-5">
    <h3 class="mb-4 text-primary">🎓 Training: <strong>{{ $training->title }}</strong></h3>

    @if($slides->count())
        <div id="slide-container" class="card shadow-lg p-4 rounded-3">
            <h4 id="slide-title" class="text-dark fw-bold mb-3">{{ $slides[0]->order }}. {{ $slides[0]->title }}</h4>

            <div id="slide-media" class="mb-4">
                @if($slides[0]->content_type === 'video' || $slides[0]->content_type === 'mixed')
                    <div class="ratio ratio-16x9">
                        <iframe src="{{ $slides[0]->media_url }}" frameborder="0" allowfullscreen class="rounded-2 shadow-sm"></iframe>
                    </div>
                @endif
            </div>

            <div id="slide-description" class="mb-3 text-secondary" style="line-height: 1.6;">
                {!! nl2br(e($slides[0]->description)) !!}
            </div>

            <form id="mark-as-done-form" method="POST" action="{{ route('training.content.complete') }}" class="mb-4">
                @csrf
                <input type="hidden" name="training_id" value="{{ $training->id }}">
                <input type="hidden" name="content_id" id="content_id_input" value="{{ $slides[0]->id }}">
                <button type="submit" class="btn btn-success w-100">✅ Mark as Done</button>
            </form>

            <div class="d-flex justify-content-between">
                <button id="prevBtn" class="btn btn-outline-secondary px-4" onclick="prevSlide()">⬅️ Previous</button>
                <button id="nextBtn" class="btn btn-primary px-4" onclick="nextSlide()">Next ➡️</button>
            </div>
        </div>
    @else
        <div class="alert alert-warning mt-5">No slides found for this training.</div>
    @endif
</div>

<script>
    const slides = @json($slides);
    let current = 0;

    function renderSlide(index) {
        const slide = slides[index];
        document.getElementById('slide-title').innerText = `${slide.order}. ${slide.title}`;

        const mediaDiv = document.getElementById('slide-media');
        if (slide.content_type === 'video' || slide.content_type === 'mixed') {
            mediaDiv.innerHTML = `<div class="ratio ratio-16x9">
                <iframe src="${slide.media_url}" frameborder="0" allowfullscreen class="rounded-2 shadow-sm"></iframe>
            </div>`;
        } else {
            mediaDiv.innerHTML = '';
        }

        document.getElementById('slide-description').innerHTML = slide.description?.replace(/\n/g, "<br>") ?? '';
        document.getElementById('content_id_input').value = slide.id;

        document.getElementById('prevBtn').disabled = index === 0;
        document.getElementById('nextBtn').disabled = index === slides.length - 1;
    }

    function nextSlide() {
        if (current < slides.length - 1) {
            current++;
            renderSlide(current);
        }
    }

    function prevSlide() {
        if (current > 0) {
            current--;
            renderSlide(current);
        }
    }

    renderSlide(current);
</script>
@endsection
