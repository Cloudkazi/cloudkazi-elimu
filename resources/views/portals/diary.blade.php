@extends('layouts.app')
@section('title', 'School Diary')
@section('breadcrumb', 'Portals › School Diary')

@section('content')
<div class="page-header">
    <div class="page-header-text">
        <h1>School Diary</h1>
        <p>{{ $student ? $student->full_name . ' · ' . ($student->schoolClass?->name ?? '') : 'Your linked student diary' }} — notices, assignments, and examination dates.</p>
    </div>
    <a href="{{ $parentMode ? route('portal.parent') : route('portal.student') }}" class="btn btn-outline">Back to portal</a>
</div>

@if($parentMode && $children->isEmpty())
<div class="card"><div class="card-body">No children are linked to your parent account.</div></div>
@elseif($parentMode && $children->isNotEmpty())
<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:18px" aria-label="Select child">
    @foreach($children as $child)
    <a class="btn btn-sm {{ $student && $student->id === $child->id ? 'btn-primary' : 'btn-outline' }}"
       href="{{ route('portal.parent.diary', ['student_id' => $child->id, 'view' => request('view'), 'month' => request('month')]) }}">
        {{ $child->full_name }} · {{ $child->schoolClass?->name }}
    </a>
    @endforeach
</div>
@endif

@if($student)
<div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:14px">
    <div style="display:flex;gap:8px">
        <button type="button" class="btn btn-sm btn-outline" onclick="setDiaryView('list')">Chronological list</button>
        <button type="button" class="btn btn-sm btn-outline" onclick="setDiaryView('calendar')">Calendar</button>
    </div>
    <span style="font-size:.8rem;color:var(--text-muted)">Entries are filtered for {{ $parentMode ? 'parents' : 'students' }} and this student's class.</span>
</div>

<section id="diary-list" class="card">
    <div class="card-header"><h2 style="font-size:1rem;margin:0">Chronological diary</h2></div>
    <div class="card-body">
        @forelse($entries as $entry)
        <article id="diary-entry-{{ $entry['announcement_id'] ?? $entry['id'] }}" style="padding:14px 0;border-bottom:1px solid #e2e8f0">
            <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;flex-wrap:wrap">
                <div>
                    <span class="badge badge-primary">{{ $entry['category'] }}</span>
                    <strong style="display:block;margin-top:6px">{{ $entry['title'] }}</strong>
                    <small style="color:var(--text-muted)">{{ $entry['date']->format('D, d M Y') }}
                        @if(!empty($entry['end_date']) && !$entry['end_date']->isSameDay($entry['date']))
                            – {{ $entry['end_date']->format('d M Y') }}
                        @endif
                    </small>
                </div>
                @if($entry['announcement_id'])
                    @if(!$entry['is_read'])
                    <form method="POST" action="{{ route('portal.diary.announcement.read', $entry['announcement_id']) }}">
                        @csrf
                        <button class="btn btn-sm btn-outline" type="submit">Mark as read</button>
                    </form>
                    @else
                    <span class="badge badge-success">Read</span>
                    @endif
                @endif
            </div>
            <p style="margin:10px 0 0;white-space:pre-wrap">{{ $entry['details'] }}</p>
            @if($entry['attachment'])
            <a class="btn btn-sm btn-outline" style="margin-top:10px" href="{{ route('portal.diary.attachment', ['material' => substr($entry['id'], 11)]) }}">
                <i class="bi bi-paperclip"></i> Download attachment
            </a>
            @endif
        </article>
        @empty
        <p style="color:var(--text-muted);margin:0">No diary entries are available at this time.</p>
        @endforelse
    </div>
</section>

<section id="diary-calendar" class="card" style="display:none;margin-top:18px">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
        <a class="btn btn-sm btn-outline" href="{{ route($parentMode ? 'portal.parent.diary' : 'portal.student.diary', array_filter(['student_id' => $student->id, 'month' => $calendarMonth->copy()->subMonth()->format('Y-m')])) }}">Previous month</a>
        <h2 style="font-size:1rem;margin:0">{{ $calendarMonth->format('F Y') }}</h2>
        <a class="btn btn-sm btn-outline" href="{{ route($parentMode ? 'portal.parent.diary' : 'portal.student.diary', array_filter(['student_id' => $student->id, 'month' => $calendarMonth->copy()->addMonth()->format('Y-m')])) }}">Next month</a>
    </div>
    <div class="table-responsive"><table style="table-layout:fixed">
        <thead><tr>@foreach(['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'] as $day)<th>{{ $day }}</th>@endforeach</tr></thead>
        <tbody>
        @foreach($calendarWeeks as $week)
            <tr>
            @foreach($week as $cell)
                <td style="vertical-align:top;height:90px;padding:6px">
                    @if($cell)
                        <strong>{{ $cell['day'] }}</strong>
                        @foreach($cell['entries']->take(2) as $entry)
                            <div style="font-size:.7rem;line-height:1.2;margin-top:5px;color:var(--primary)">{{ $entry['category'] }}: {{ Str::limit($entry['title'], 24) }}</div>
                        @endforeach
                        @if($cell['entries']->count() > 2)<small>+{{ $cell['entries']->count() - 2 }} more</small>@endif
                    @endif
                </td>
            @endforeach
            </tr>
        @endforeach
        </tbody>
    </table></div>
</section>
@endif

<script>
function setDiaryView(view) {
    document.getElementById('diary-list').style.display = view === 'list' ? 'block' : 'none';
    document.getElementById('diary-calendar').style.display = view === 'calendar' ? 'block' : 'none';
    sessionStorage.setItem('school-diary-view', view);
}
const preferredView = sessionStorage.getItem('school-diary-view');
if (preferredView === 'calendar') setDiaryView('calendar');
</script>
@endsection
