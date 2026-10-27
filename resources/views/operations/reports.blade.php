@extends('layouts.app')
@section('title', 'Institutional Reports')
@section('breadcrumb', 'Operations › Institutional Reports')

@section('content')
<div class="page-header">
    <div class="page-header-text">
        <h1>📊 Institutional Analytics & Executive Report</h1>
        <p>Comprehensive demographic statistics, curriculum distribution, and financial health breakdown</p>
    </div>
</div>

<div class="stats-grid stats-grid-4" style="margin-bottom:24px">
    <div class="stat-card stat-blue">
        <div class="stat-icon">👩‍🎓</div>
        <div class="stat-body">
            <div class="stat-label">Total Enrollment</div>
            <div class="stat-value">{{ $totalStudents }}</div>
            <div class="stat-trend up">{{ $maleStudents }} Boys / {{ $femaleStudents }} Girls</div>
        </div>
    </div>
    <div class="stat-card stat-purple">
        <div class="stat-icon">🇰🇪</div>
        <div class="stat-body">
            <div class="stat-label">CBC vs 8-4-4 Ratio</div>
            <div class="stat-value">{{ $cbcStudents }} / {{ $eightFourFourStudents }}</div>
            <div class="stat-trend up">CBC / 8-4-4 Cohorts</div>
        </div>
    </div>
    <div class="stat-card stat-green">
        <div class="stat-icon">💰</div>
        <div class="stat-body">
            <div class="stat-label">Revenue Collected</div>
            <div class="stat-value">KES {{ number_format($totalCollected) }}</div>
            <div class="stat-trend up">Fee collections</div>
        </div>
    </div>
    <div class="stat-card stat-orange">
        <div class="stat-icon">⚠️</div>
        <div class="stat-body">
            <div class="stat-label">Unpaid Arrears</div>
            <div class="stat-value">KES {{ number_format($totalBalance) }}</div>
            <div class="stat-trend down">Pending recovery</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5><i class="bi bi-bar-chart"></i> Class Enrollment Distribution</h5>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Grade / Class</th>
                    <th>Curriculum</th>
                    <th>Enrolled Learners</th>
                    <th>Capacity Share</th>
                </tr>
            </thead>
            <tbody>
                @forelse($classEnrollment as $ce)
                <tr>
                    <td><strong>{{ $ce->name }}</strong></td>
                    <td><span class="badge badge-{{ $ce->curriculum === 'CBC' ? 'primary' : 'teal' }}">{{ $ce->curriculum }}</span></td>
                    <td><strong>{{ $ce->students_count }}</strong> students</td>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px">
                            <div style="flex:1;background:#e2e8f0;height:8px;border-radius:4px;overflow:hidden">
                                <div style="background:var(--primary);height:100%;width:{{ $totalStudents > 0 ? min(100, round(($ce->students_count / $totalStudents) * 100)) : 0 }}%"></div>
                            </div>
                            <span style="font-size:0.8rem">{{ $totalStudents > 0 ? round(($ce->students_count / $totalStudents) * 100) : 0 }}%</span>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="no-data">No enrollment data available.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
