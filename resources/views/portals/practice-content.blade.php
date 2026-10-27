@extends('layouts.app')
@section('title', 'Practice Content')
@section('breadcrumb', 'Learning › Practice Content')

@section('content')
<div class="page-header">
    <div class="page-header-text">
        <h1>Learning Practice Content</h1>
        <p>Add school-managed chapters and questions. Content entered here is school-authored and is not represented as an official national question bank.</p>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap">
        <a href="{{ route('academics.index') }}" class="btn btn-outline">
            <i class="bi bi-arrow-left"></i> Academics Hub
        </a>
        <a href="{{ route('operations.materials') }}" class="btn btn-outline">
            <i class="bi bi-journal-arrow-up"></i> Learning Materials
        </a>
    </div>
</div>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())
<div class="alert alert-danger"><ul style="margin:0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:20px;margin-bottom:20px">
    <section class="card">
        <div class="card-header"><h2 style="font-size:1rem;margin:0">Create chapter</h2></div>
        <form method="POST" action="{{ route('portal.practice.content.chapters.store') }}" class="card-body">
            @csrf
            <label class="form-label">Grade / class</label>
            <select name="school_class_id" class="form-select" required>
                <option value="">Select class</option>
                @foreach($classes as $class)<option value="{{ $class->id }}">{{ $class->name }}</option>@endforeach
            </select>
            <label class="form-label" style="margin-top:12px">Subject</label>
            <select name="subject_id" class="form-select" required>
                <option value="">Select subject</option>
                @foreach($subjects as $subject)<option value="{{ $subject->id }}">{{ $subject->name }}</option>@endforeach
            </select>
            <label class="form-label" style="margin-top:12px">Chapter title</label>
            <input name="title" class="form-control" maxlength="255" required>
            <label class="form-label" style="margin-top:12px">Description</label>
            <textarea name="description" class="form-control" rows="3" maxlength="2000"></textarea>
            <label class="form-label" style="margin-top:12px">Sequence number</label>
            <input name="sequence" type="number" class="form-control" value="1" min="1" required>
            <label style="display:flex;gap:8px;align-items:center;margin:14px 0"><input type="checkbox" name="is_published" value="1"> Publish for students</label>
            <button class="btn btn-primary" type="submit">Save chapter</button>
        </form>
    </section>

    <section class="card">
        <div class="card-header"><h2 style="font-size:1rem;margin:0">Add practice question</h2></div>
        <form method="POST" action="{{ route('portal.practice.content.questions.store') }}" class="card-body">
            @csrf
            <label class="form-label">Chapter</label>
            <select name="learning_chapter_id" class="form-select" required>
                <option value="">Select chapter</option>
                @foreach($chapters as $chapter)<option value="{{ $chapter->id }}">{{ $chapter->schoolClass?->name }} · {{ $chapter->subject?->name }} · {{ $chapter->title }}</option>@endforeach
            </select>
            <label class="form-label" style="margin-top:12px">Question</label>
            <textarea name="prompt" class="form-control" rows="3" maxlength="5000" required></textarea>
            <label class="form-label" style="margin-top:12px">Topic (optional)</label>
            <input name="topic" class="form-control" maxlength="255">
            <label class="form-label" style="margin-top:12px">Section (optional)</label>
            <input name="section" class="form-control" maxlength="255">
            <label class="form-label" style="margin-top:12px">Question format</label>
            <select name="question_type" class="form-select" id="practice-question-type" required>
                <option value="multiple_choice">Multiple choice</option>
                <option value="short_answer">Short answer (exact match)</option>
            </select>
            <div id="practice-options">
                <label class="form-label" style="margin-top:12px">Options (at least two)</label>
                @for($option = 1; $option <= 4; $option++)
                <input name="options[]" class="form-control" style="margin-bottom:6px" placeholder="Option {{ $option }}">
                @endfor
            </div>
            <label class="form-label" style="margin-top:12px">Correct answer</label>
            <input name="correct_answer" class="form-control" maxlength="2000" required>
            <label class="form-label" style="margin-top:12px">Explanation</label>
            <textarea name="explanation" class="form-control" rows="2" maxlength="3000"></textarea>
            <label class="form-label" style="margin-top:12px">Difficulty</label>
            <select name="difficulty" class="form-select"><option>easy</option><option selected>medium</option><option>hard</option></select>
            <label style="display:flex;gap:8px;align-items:center;margin:14px 0"><input type="checkbox" name="is_published" value="1"> Publish for students</label>
            <button class="btn btn-primary" type="submit">Save question</button>
        </form>
    </section>
</div>

<section class="card">
    <div class="card-header"><h2 style="font-size:1rem;margin:0">Existing chapters</h2></div>
    <div class="table-responsive"><table>
        <thead><tr><th>Class</th><th>Subject</th><th>Chapter</th><th>Questions</th><th>Visibility / editing</th></tr></thead>
        <tbody>
        @forelse($chapters as $chapter)
            <tr>
                <td>{{ $chapter->schoolClass?->name }}</td>
                <td>{{ $chapter->subject?->name }}</td>
                <td>{{ $chapter->sequence }}. {{ $chapter->title }}</td>
                <td>{{ $chapter->questions->count() }}</td>
                <td>
                    {{ $chapter->is_published ? 'Published' : 'Draft' }}
                    <details style="margin-top:6px">
                        <summary style="cursor:pointer;color:var(--primary)">Edit chapter / questions</summary>
                        <form method="POST" action="{{ route('portal.practice.content.chapters.update', $chapter) }}" style="padding:10px 0">
                            @csrf @method('PUT')
                            <label class="form-label">Title</label>
                            <input name="title" class="form-control" value="{{ $chapter->title }}" required>
                            <label class="form-label" style="margin-top:8px">Description</label>
                            <textarea name="description" class="form-control" rows="2">{{ $chapter->description }}</textarea>
                            <label class="form-label" style="margin-top:8px">Sequence</label>
                            <input name="sequence" type="number" class="form-control" min="1" value="{{ $chapter->sequence }}" required>
                            <label style="display:flex;gap:8px;align-items:center;margin:10px 0"><input type="checkbox" name="is_published" value="1" @checked($chapter->is_published)> Published</label>
                            <button class="btn btn-primary btn-sm">Update chapter</button>
                        </form>
                        @foreach($chapter->questions as $question)
                        <details style="padding:8px 0;border-top:1px solid #e2e8f0">
                            <summary style="cursor:pointer">{{ Str::limit($question->prompt, 70) }} · {{ $question->is_published ? 'Published' : 'Draft' }}</summary>
                            <form method="POST" action="{{ route('portal.practice.content.questions.update', $question) }}" style="padding:10px 0">
                                @csrf @method('PUT')
                                <label class="form-label">Question</label>
                                <textarea name="prompt" class="form-control" rows="3" required>{{ $question->prompt }}</textarea>
                                <label class="form-label" style="margin-top:8px">Topic (optional)</label>
                                <input name="topic" class="form-control" value="{{ $question->topic }}">
                                <label class="form-label" style="margin-top:8px">Section (optional)</label>
                                <input name="section" class="form-control" value="{{ $question->section }}">
                                <label class="form-label" style="margin-top:8px">Format</label>
                                <select name="question_type" class="form-select">
                                    <option value="multiple_choice" @selected($question->question_type === 'multiple_choice')>Multiple choice</option>
                                    <option value="short_answer" @selected($question->question_type === 'short_answer')>Short answer</option>
                                </select>
                                @foreach($question->options ?? [] as $option)
                                    <input name="options[]" class="form-control" style="margin-top:6px" value="{{ $option }}">
                                @endforeach
                                @if($question->question_type === 'multiple_choice' && count($question->options ?? []) < 2)
                                    <input name="options[]" class="form-control" style="margin-top:6px" placeholder="Add another option">
                                    <input name="options[]" class="form-control" style="margin-top:6px" placeholder="Add another option">
                                @endif
                                <label class="form-label" style="margin-top:8px">Correct answer</label>
                                <input name="correct_answer" class="form-control" value="{{ $question->correct_answer }}" required>
                                <label class="form-label" style="margin-top:8px">Explanation</label>
                                <textarea name="explanation" class="form-control" rows="2">{{ $question->explanation }}</textarea>
                                <label class="form-label" style="margin-top:8px">Difficulty</label>
                                <select name="difficulty" class="form-select">
                                    @foreach(['easy', 'medium', 'hard'] as $difficulty)
                                    <option value="{{ $difficulty }}" @selected($question->difficulty === $difficulty)>{{ ucfirst($difficulty) }}</option>
                                    @endforeach
                                </select>
                                <label style="display:flex;gap:8px;align-items:center;margin:10px 0"><input type="checkbox" name="is_published" value="1" @checked($question->is_published)> Published</label>
                                <button class="btn btn-primary btn-sm">Update question</button>
                            </form>
                        </details>
                        @endforeach
                    </details>
                </td>
            </tr>
        @empty
            <tr><td colspan="5">No chapters created yet.</td></tr>
        @endforelse
        </tbody>
    </table></div>
</section>
<script>
document.getElementById('practice-question-type').addEventListener('change', function () {
    document.getElementById('practice-options').style.display = this.value === 'multiple_choice' ? 'block' : 'none';
});
</script>
@endsection
