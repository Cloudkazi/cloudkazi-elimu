@extends('layouts.app')
@section('title', 'My Practice')
@section('breadcrumb', 'Student Portal › My Practice')

@section('content')
<div class="page-header">
    <div class="page-header-text">
        <h1>MY PRACTICE</h1>
        <p>{{ $student->schoolClass?->name ?? 'My Class' }} — practice questions and track your progress.</p>
    </div>
    <a href="{{ route('portal.student') }}" class="btn btn-outline">Back to portal</a>
</div>

@if($practiceAvailable)
<div class="card" style="margin-bottom:20px">
    <div class="card-body" style="display:flex;align-items:center;gap:14px;flex-wrap:wrap">
        <label for="authorized-grade"><strong>Grade</strong></label>
        <select id="authorized-grade" class="form-select" style="width:auto" disabled>
            <option>{{ $student->schoolClass?->name ?? 'My class' }}</option>
        </select>
        <strong>Subject</strong>
        <a class="btn btn-sm {{ !$subjectId ? 'btn-primary' : 'btn-outline' }}" href="{{ route('portal.practice') }}">All subjects</a>
        @foreach($subjects as $subject)
        <a class="btn btn-sm {{ (int)$subjectId === (int)$subject->id ? 'btn-primary' : 'btn-outline' }}"
           href="{{ route('portal.practice', ['subject_id' => $subject->id]) }}">{{ $subject->name }}</a>
        @endforeach
    </div>
</div>
@else
<div class="card"><div class="card-body">
    <h2 style="font-size:1.1rem">Practice is temporarily unavailable</h2>
    <p style="margin-bottom:0">The school has not finished setting up the practice feature. Please contact your school administrator.</p>
</div></div>
@endif

@if($practiceAvailable)
@if(session('practice_feedback'))
    @php
        $feedback = session('practice_feedback');
    @endphp
    <div class="card" style="margin-bottom:20px;border-left:4px solid {{ $feedback['correct'] ? '#16a34a' : '#dc2626' }}">
        <div class="card-body">
            <strong style="color:{{ $feedback['correct'] ? '#16a34a' : '#dc2626' }}">
                {{ $feedback['correct'] ? 'Correct answer!' : 'Not quite — review the answer below.' }}
            </strong>
            <p style="margin:8px 0"><strong>Answer:</strong> {{ $feedback['answer'] }}</p>
            @if($feedback['explanation'])
            <p style="margin:0">{{ $feedback['explanation'] }}</p>
            @endif
        </div>
    </div>
@endif

@forelse($chapters as $chapter)
    @php
        $chapterProgress = $progress->get($chapter->id);
        $feedbackId = (int) data_get(session('practice_feedback'), 'question_id', 0);
    @endphp
    <section class="card" style="margin-bottom:20px">
        <div class="card-header" style="display:flex;justify-content:space-between;gap:12px;align-items:center;flex-wrap:wrap">
            <div>
                <h2 style="font-size:1.05rem;margin:0">{{ $chapter->sequence }}. {{ $chapter->title }}</h2>
                <div style="font-size:.8rem;color:var(--text-muted)">{{ $chapter->subject?->name }} · {{ $chapter->questions->count() }} practice questions</div>
                @if($chapter->description)<p style="margin:6px 0 0">{{ $chapter->description }}</p>@endif
            </div>
            <div style="min-width:160px">
                <div style="display:flex;justify-content:space-between;font-size:.78rem"><span>Mastery</span><strong>{{ number_format((float)($chapterProgress?->mastery_percent ?? 0), 0) }}%</strong></div>
                <div style="height:8px;background:#e2e8f0;border-radius:8px;overflow:hidden">
                    <div style="width:{{ min(100, (float)($chapterProgress?->mastery_percent ?? 0)) }}%;height:100%;background:#2563eb"></div>
                </div>
                <div style="font-size:.75rem;margin-top:5px">{{ $chapterProgress?->completed_questions_count ?? 0 }}/{{ $chapter->questions->count() }} questions practiced · {{ number_format((float)($chapterProgress?->completion_percent ?? 0), 0) }}% complete</div>
                <small>{{ $chapterProgress?->attempts_count ?? 0 }} attempts · {{ $chapterProgress?->correct_count ?? 0 }} correct</small>
            </div>
        </div>
        <div class="card-body">
            @forelse($chapter->questions as $question)
                @php $lastAttempt = $latestAttempts->get($question->id); @endphp
                <article id="practice-question-{{ $question->id }}" style="padding:16px 0;border-bottom:1px solid #e2e8f0">
                    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:8px">
                        <strong>Question {{ $loop->iteration }}</strong>
                        @if($question->topic)<span class="badge badge-primary">{{ $question->topic }}</span>@endif
                        @if($question->section)<span class="badge badge-secondary">{{ $question->section }}</span>@endif
                        <span class="badge badge-secondary">{{ ucfirst($question->difficulty) }}</span>
                        @if($lastAttempt)
                            <span class="badge {{ $lastAttempt->is_correct ? 'badge-success' : 'badge-danger' }}">
                                Last attempt: {{ $lastAttempt->is_correct ? 'Correct' : 'Review' }}
                            </span>
                        @endif
                    </div>
                    <p style="white-space:pre-wrap">{{ $question->prompt }}</p>
                    <form method="POST" action="{{ route('portal.practice.answer', $question) }}">
                        @csrf
                        @if($question->question_type === 'multiple_choice')
                            <div style="display:grid;gap:8px;margin:12px 0">
                                @foreach($question->options ?? [] as $option)
                                <label style="display:flex;gap:8px;align-items:flex-start">
                                    <input type="radio" name="answer" value="{{ $option }}" required>
                                    <span>{{ $option }}</span>
                                </label>
                                @endforeach
                            </div>
                        @else
                            <label class="form-label" for="answer-{{ $question->id }}">Your answer</label>
                            <input class="form-control" id="answer-{{ $question->id }}" name="answer" required maxlength="2000">
                        @endif
                        <button type="submit" class="btn btn-primary btn-sm">Check answer</button>
                    </form>
                    @if($feedbackId === $question->id && $feedback['explanation'])
                    <p style="margin:10px 0 0"><strong>Explanation:</strong> {{ $feedback['explanation'] }}</p>
                    @endif
                </article>
            @empty
                <p style="color:var(--text-muted)">No practice questions published in this chapter yet.</p>
            @endforelse
        </div>
    </section>
@empty
    <div class="card"><div class="card-body">
        <h2 style="font-size:1.1rem">Practice content is not available yet</h2>
        <p style="margin-bottom:0">Your school has not published practice chapters for your class yet. Ask a teacher to add curriculum content.</p>
    </div></div>
@endforelse
@endif
@endsection
