@extends('layouts.app')
@section('title', 'Curriculum & Assessment Framework')
@section('breadcrumb', 'Academics › Curriculum')

@section('content')
<div class="page-header">
    <div class="page-header-text">
        <h1>🇰🇪 Kenyan Curriculum & Assessment Frameworks</h1>
        <p>Comprehensive standardisation for CBC (Competency Based Curriculum) and 8-4-4 Secondary systems</p>
    </div>
</div>

<div class="tabs-nav" style="margin-bottom:20px;border-bottom:2px solid #e2e8f0;display:flex;gap:12px">
    <button class="btn btn-primary" onclick="showCurric('cbc', this)" id="btn-cbc">CBC (Competency Based)</button>
    <button class="btn btn-outline" onclick="showCurric('844', this)" id="btn-844">8-4-4 Grading System</button>
</div>

<!-- CBC Section -->
<div id="section-cbc">
    <!-- 4 Achievement Levels -->
    <div class="card" style="margin-bottom:20px">
        <div class="card-header">
            <h5><i class="bi bi-award"></i> CBC 4-Tier Assessment Rubrics</h5>
        </div>
        <div class="card-body">
            <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));gap:16px">
                <div style="background:#ecfdf5;border:2px solid #10b981;border-radius:10px;padding:16px">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
                        <span class="badge badge-success" style="font-size:1rem;font-weight:700">EE</span>
                        <strong style="color:#065f46">Level 4</strong>
                    </div>
                    <h6 style="color:#047857;margin-bottom:6px">Exceeding Expectations</h6>
                    <p style="font-size:0.8rem;color:#065f46;margin:0">Learner exhibits exemplary performance, creativity, originality and transfers competencies to unfamiliar contexts.</p>
                </div>
                <div style="background:#eff6ff;border:2px solid #3b82f6;border-radius:10px;padding:16px">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
                        <span class="badge badge-primary" style="font-size:1rem;font-weight:700">ME</span>
                        <strong style="color:#1e40af">Level 3</strong>
                    </div>
                    <h6 style="color:#1d4ed8;margin-bottom:6px">Meeting Expectations</h6>
                    <p style="font-size:0.8rem;color:#1e40af;margin:0">Learner meets all standard learning outcomes for the grade level independently and accurately.</p>
                </div>
                <div style="background:#fffbeb;border:2px solid #f59e0b;border-radius:10px;padding:16px">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
                        <span class="badge badge-warning" style="font-size:1rem;font-weight:700">AE</span>
                        <strong style="color:#92400e">Level 2</strong>
                    </div>
                    <h6 style="color:#b45309;margin-bottom:6px">Approaching Expectations</h6>
                    <p style="font-size:0.8rem;color:#92400e;margin:0">Learner performs key tasks with moderate prompting, guidance or scaffolding from teacher.</p>
                </div>
                <div style="background:#fef2f2;border:2px solid #ef4444;border-radius:10px;padding:16px">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
                        <span class="badge badge-danger" style="font-size:1rem;font-weight:700">BE</span>
                        <strong style="color:#991b1b">Level 1</strong>
                    </div>
                    <h6 style="color:#b91c1c;margin-bottom:6px">Below Expectations</h6>
                    <p style="font-size:0.8rem;color:#991b1b;margin:0">Learner requires constant individualized remedial intervention and support to attain basic concepts.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- 7 Core Competencies & Senior School Pathways -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px">
        <div class="card">
            <div class="card-header">
                <h5><i class="bi bi-star"></i> 7 CBC Core Competencies</h5>
            </div>
            <div class="card-body">
                <ol style="padding-left:20px;margin:0;line-height:2">
                    <li><strong>Communication and Collaboration</strong> — Active listening, dialogue & teamwork</li>
                    <li><strong>Critical Thinking and Problem Solving</strong> — Analytical inquiry & logic</li>
                    <li><strong>Imagination and Creativity</strong> — Innovative ideas & artistic expression</li>
                    <li><strong>Citizenship</strong> — Patriotism, law, communal rights & equity</li>
                    <li><strong>Learning to Learn</strong> — Curiosity, study skills & self-reflection</li>
                    <li><strong>Self-efficacy</strong> — Confidence, emotional management & resilience</li>
                    <li><strong>Digital Literacy</strong> — Computing, safety & virtual collaboration</li>
                </ol>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5><i class="bi bi-signpost-split"></i> Senior School Pathways (Grades 10 - 12)</h5>
            </div>
            <div class="card-body">
                <div style="margin-bottom:14px">
                    <span class="badge badge-primary">STEM Pathway (60%)</span>
                    <p style="font-size:0.85rem;color:var(--text-muted);margin:4px 0 0 0">Pure Sciences, Applied Sciences, Technical & Engineering, Careers in Medicine, IT & Construction.</p>
                </div>
                <div style="margin-bottom:14px">
                    <span class="badge badge-teal">Social Sciences Pathway (25%)</span>
                    <p style="font-size:0.85rem;color:var(--text-muted);margin:4px 0 0 0">Humanities, Business Studies, Languages, Law, Public Administration and International Relations.</p>
                </div>
                <div>
                    <span class="badge badge-orange">Arts & Sports Science Pathway (15%)</span>
                    <p style="font-size:0.85rem;color:var(--text-muted);margin:4px 0 0 0">Music, Theatre & Film, Fine Art, Athletics, Professional Sports Management & Performance.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 8-4-4 Section -->
<div id="section-844" style="display:none">
    <div class="card">
        <div class="card-header">
            <h5><i class="bi bi-bar-chart-steps"></i> KNEC 12-Point 8-4-4 Standard Grading Scale</h5>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Grade</th>
                        <th>Points</th>
                        <th>Standard Score Range (%)</th>
                        <th>Performance Classification</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td><span class="badge badge-success" style="font-size:1rem">A</span></td><td><strong>12</strong></td><td>80% – 100%</td><td>Excellent / Distinction</td></tr>
                    <tr><td><span class="badge badge-success">A-</span></td><td><strong>11</strong></td><td>75% – 79%</td><td>Distinction</td></tr>
                    <tr><td><span class="badge badge-primary">B+</span></td><td><strong>10</strong></td><td>70% – 74%</td><td>Credit (Very Good)</td></tr>
                    <tr><td><span class="badge badge-primary">B</span></td><td><strong>9</strong></td><td>65% – 69%</td><td>Credit (Good)</td></tr>
                    <tr><td><span class="badge badge-primary">B-</span></td><td><strong>8</strong></td><td>60% – 64%</td><td>Credit</td></tr>
                    <tr><td><span class="badge badge-warning">C+</span></td><td><strong>7</strong></td><td>55% – 59%</td><td>Satisfactory (Direct University Entry)</td></tr>
                    <tr><td><span class="badge badge-warning">C</span></td><td><strong>6</strong></td><td>50% – 54%</td><td>Average</td></tr>
                    <tr><td><span class="badge badge-warning">C-</span></td><td><strong>5</strong></td><td>45% – 49%</td><td>Average</td></tr>
                    <tr><td><span class="badge badge-gray">D+</span></td><td><strong>4</strong></td><td>40% – 44%</td><td>Pass</td></tr>
                    <tr><td><span class="badge badge-gray">D</span></td><td><strong>3</strong></td><td>35% – 39%</td><td>Weak Pass</td></tr>
                    <tr><td><span class="badge badge-danger">D-</span></td><td><strong>2</strong></td><td>30% – 34%</td><td>Very Weak</td></tr>
                    <tr><td><span class="badge badge-danger">E</span></td><td><strong>1</strong></td><td>0% – 29%</td><td>Fail</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function showCurric(tab, btn) {
    document.getElementById('section-cbc').style.display = tab === 'cbc' ? 'block' : 'none';
    document.getElementById('section-844').style.display = tab === '844' ? 'block' : 'none';
    document.getElementById('btn-cbc').className = tab === 'cbc' ? 'btn btn-primary' : 'btn btn-outline';
    document.getElementById('btn-844').className = tab === '844' ? 'btn btn-primary' : 'btn btn-outline';
}
</script>
@endsection
