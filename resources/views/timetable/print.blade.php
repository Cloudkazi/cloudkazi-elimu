<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Timetable — {{ $stream->schoolClass?->name }} ({{ $stream->name }})</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 20px; color: #1e293b; }
        .header { text-align: center; border-bottom: 2px solid #0f172a; padding-bottom: 12px; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 1.5rem; text-transform: uppercase; letter-spacing: 1px; }
        .header p { margin: 4px 0 0 0; font-size: 0.9rem; color: #64748b; }
        .meta { display: flex; justify-content: space-between; margin-bottom: 16px; font-size: 0.9rem; font-weight: 600; }
        table { width: 100%; border-collapse: collapse; text-align: center; }
        th, td { border: 1px solid #94a3b8; padding: 8px 4px; font-size: 0.8rem; }
        th { background-color: #f1f5f9; font-weight: 700; }
        .day-col { font-weight: bold; background: #f8fafc; text-align: left; padding-left: 10px; width: 80px; }
        .break-cell { background: #f1f5f9; font-weight: bold; color: #64748b; letter-spacing: 1px; font-size: 0.75rem; }
        .subject { font-weight: bold; color: #1e3a8a; }
        .teacher { font-size: 0.7rem; color: #64748b; }
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>

<div class="no-print" style="margin-bottom: 20px; text-align: right;">
    <button onclick="window.print()" style="padding: 8px 16px; background: #2563eb; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">
        🖨️ Print Timetable
    </button>
</div>

<div class="header">
    <h1>{{ session('active_school')?->name ?? 'CLOUDKAZI ELIMU ACADEMY' }}</h1>
    <p>OFFICIAL CLASS TIMETABLE — {{ strtoupper($stream->schoolClass?->name ?? '') }} (STREAM: {{ strtoupper($stream->name) }})</p>
</div>

<div class="meta">
    <div>ACADEMIC YEAR: {{ date('Y') }} | TERM: 1</div>
    <div>CLASS TEACHER: {{ $stream->classTeacher?->name ?? 'UNASSIGNED' }}</div>
</div>

@php
    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

    $timetableSlots = $timetable ? $timetable->slots->where('day_of_week', 'Monday')->sortBy('period_number') : collect();
    if ($timetableSlots->isEmpty() && $timetable) {
        $timetableSlots = $timetable->slots->groupBy('period_number')->map->first()->sortBy('period_number');
    }

    $periods = [];
    if ($timetableSlots->isNotEmpty()) {
        foreach ($timetableSlots as $slot) {
            $timeLabel = \Carbon\Carbon::parse($slot->start_time)->format('H:i') . ' - ' . \Carbon\Carbon::parse($slot->end_time)->format('H:i');
            $periods[$slot->period_number] = [
                'time' => $timeLabel,
                'name' => $slot->is_break ? ($slot->break_label ?: 'BREAK') : 'Lesson ' . $slot->period_number,
                'break' => (bool)$slot->is_break,
            ];
        }
    } else {
        $periods = [
            1 => ['time' => '08:00 - 08:45', 'name' => 'Lesson 1', 'break' => false],
            2 => ['time' => '08:45 - 09:30', 'name' => 'Lesson 2', 'break' => false],
            3 => ['time' => '09:30 - 10:15', 'name' => 'Lesson 3', 'break' => false],
            4 => ['time' => '10:15 - 10:45', 'name' => 'TEA BREAK', 'break' => true],
            5 => ['time' => '10:45 - 11:30', 'name' => 'Lesson 4', 'break' => false],
            6 => ['time' => '11:30 - 12:15', 'name' => 'Lesson 5', 'break' => false],
            7 => ['time' => '12:15 - 13:00', 'name' => 'Lesson 6', 'break' => false],
            8 => ['time' => '13:00 - 14:00', 'name' => 'LUNCH BREAK', 'break' => true],
            9 => ['time' => '14:00 - 14:45', 'name' => 'Lesson 7', 'break' => false],
            10 => ['time' => '14:45 - 15:30', 'name' => 'Lesson 8', 'break' => false],
            11 => ['time' => '15:30 - 16:30', 'name' => 'GAMES & CLUBS', 'break' => true],
        ];
    }
@endphp

<table>
    <thead>
        <tr>
            <th class="day-col">Day</th>
            @foreach($periods as $pNum => $pMeta)
                <th style="{{ !empty($pMeta['break']) ? 'background:#f1f5f9;' : '' }}">
                    <div>{{ $pMeta['name'] }}</div>
                    <div style="font-size:0.65rem;font-weight:normal;color:#64748b">{{ $pMeta['time'] }}</div>
                </th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach($days as $day)
        <tr>
            <td class="day-col">{{ $day }}</td>
            @foreach($periods as $pNum => $pMeta)
                @if(!empty($pMeta['break']))
                    <td class="break-cell">{{ $pMeta['name'] }}</td>
                @else
                    @php $slot = $streamMatrix[$day][$pNum] ?? null; @endphp
                    <td>
                        @if($slot && !empty($slot['subject_name']))
                            <div class="subject">{{ $slot['subject_name'] ?? $slot['subject_code'] ?? 'Lesson' }}</div>
                            <div class="teacher">{{ $slot['teacher_name'] ?? '' }}</div>
                            @if(!empty($slot['classroom_name']))
                                <div style="font-size:0.65rem;color:#64748b">{{ $slot['classroom_name'] }}</div>
                            @endif
                        @else
                            <span>—</span>
                        @endif
                    </td>
                @endif
            @endforeach
        </tr>
        @endforeach
    </tbody>
</table>

<div style="margin-top: 30px; display: flex; justify-content: space-between; font-size: 0.85rem;">
    <div><strong>Principal's Signature:</strong> ___________________________</div>
    <div><strong>Date Issued:</strong> {{ date('d F Y') }}</div>
</div>

</body>
</html>
