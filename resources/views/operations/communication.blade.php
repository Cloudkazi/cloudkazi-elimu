@extends('layouts.app')
@section('title', 'Communication & Notices')
@section('breadcrumb', 'Operations › Communication')

@section('content')
<div class="page-header">
    <div class="page-header-text">
        <h1>📢 Communication & Bulk SMS Gateway</h1>
        <p>Broadcast notices to parents, teachers, and students, or dispatch instant alerts via SMS</p>
    </div>
    <div style="display:flex;gap:10px">
        <button class="btn btn-primary" onclick="document.getElementById('modal-announce').style.display='flex'">
            <i class="bi bi-megaphone"></i> New Notice
        </button>
        <button class="btn btn-outline" onclick="document.getElementById('modal-sms').style.display='flex'">
            <i class="bi bi-chat-dots"></i> Dispatch SMS
        </button>
    </div>
</div>

<div style="display:grid;grid-template-columns:1.4fr 1fr;gap:20px">
    <!-- Active Notices Board -->
    <div class="card">
        <div class="card-header">
            <h5><i class="bi bi-newspaper"></i> School Noticeboard</h5>
        </div>
        <div class="card-body" style="padding:0">
            @forelse($announcements as $ann)
            <div style="padding:16px 20px;border-bottom:1px solid #f1f5f9">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px">
                    <h6 style="margin:0;font-size:1rem;color:var(--primary)">{{ $ann->title }}</h6>
                    <span class="badge badge-outline" style="border:1px solid #cbd5e1">{{ $ann->target_audience }}</span>
                </div>
                <p style="margin:0 0 8px 0;font-size:0.875rem;color:var(--text-dark);line-height:1.5">{{ $ann->content }}</p>
                <div style="font-size:0.75rem;color:var(--text-muted)">
                    Published on {{ \Carbon\Carbon::parse($ann->published_at)->format('d M Y, h:i A') }}
                </div>
            </div>
            @empty
            <div class="no-data">No announcements published yet.</div>
            @endforelse
        </div>
        @if($announcements->hasPages())
            <div class="card-footer">{{ $announcements->links() }}</div>
        @endif
    </div>

    <!-- SMS Dispatch Log -->
    <div class="card">
        <div class="card-header">
            <h5><i class="bi bi-phone"></i> Recent SMS Dispatches</h5>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Recipient</th>
                        <th>Message</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($smsLogs as $sms)
                    <tr>
                        <td>
                            <div style="font-weight:600;font-size:0.85rem">{{ $sms->recipient_name ?: $sms->recipient_phone }}</div>
                            <div style="font-size:0.75rem;color:var(--text-muted)">{{ $sms->recipient_phone }}</div>
                        </td>
                        <td>
                            <div style="font-size:0.8rem;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                                {{ $sms->message }}
                            </div>
                        </td>
                        <td>
                            <span class="badge badge-success">{{ ucfirst($sms->status) }}</span>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="3" class="no-data">No SMS records logged yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Post Notice -->
<div id="modal-announce" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.6);z-index:999;align-items:center;justify-content:center">
    <div class="card" style="width:100%;max-width:520px;background:#fff;border-radius:12px">
        <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
            <h5><i class="bi bi-megaphone"></i> Publish Noticeboard Announcement</h5>
            <button onclick="document.getElementById('modal-announce').style.display='none'" style="background:none;border:none;font-size:1.5rem;cursor:pointer">&times;</button>
        </div>
        <form action="{{ route('communication.announcements.store') }}" method="POST">
            @csrf
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label">Notice Title *</label>
                    <input type="text" name="title" class="form-control" placeholder="e.g. Midterm Parent Consultation Meeting" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Target Audience *</label>
                    <select name="target_audience" class="form-control" required>
                        <option value="All">All School Community</option>
                        <option value="Parents">Parents Only</option>
                        <option value="Teachers">Teaching Staff Only</option>
                        <option value="Students">Students Only</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Message Content *</label>
                    <textarea name="content" class="form-control" rows="4" placeholder="Enter notice description..." required></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Publish Date *</label>
                    <input type="date" name="published_at" class="form-control" value="{{ date('Y-m-d') }}" required>
                </div>
            </div>
            <div class="card-footer" style="display:flex;justify-content:flex-end;gap:10px">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('modal-announce').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary">Broadcast Notice</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Send SMS -->
<div id="modal-sms" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.6);z-index:999;align-items:center;justify-content:center">
    <div class="card" style="width:100%;max-width:520px;background:#fff;border-radius:12px">
        <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
            <h5><i class="bi bi-send"></i> Dispatch Direct SMS Alert</h5>
            <button onclick="document.getElementById('modal-sms').style.display='none'" style="background:none;border:none;font-size:1.5rem;cursor:pointer">&times;</button>
        </div>
        <form action="{{ route('communication.send_sms') }}" method="POST">
            @csrf
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label">Recipient Phone *</label>
                    <input type="text" name="recipient_phone" class="form-control" placeholder="+2547..." required>
                </div>
                <div class="form-group">
                    <label class="form-label">Recipient Name</label>
                    <input type="text" name="recipient_name" class="form-control" placeholder="e.g. Parent of Brian">
                </div>
                <div class="form-group">
                    <label class="form-label">Message Type</label>
                    <select name="message_type" class="form-control">
                        <option value="Fee Reminder">Fee Reminder</option>
                        <option value="Attendance Alert">Attendance Absence Alert</option>
                        <option value="Academic Notice">Academic Performance Alert</option>
                        <option value="General Announcement">General School Notice</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">SMS Message Content * (Max 160 chars per SMS)</label>
                    <textarea name="message" class="form-control" rows="3" maxlength="160" placeholder="Type SMS content..." required></textarea>
                </div>
            </div>
            <div class="card-footer" style="display:flex;justify-content:flex-end;gap:10px">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('modal-sms').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary">Dispatch SMS</button>
            </div>
        </form>
    </div>
</div>
@endsection
