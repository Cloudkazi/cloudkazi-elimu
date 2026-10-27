<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feature Unavailable — CloudKazi Elimu</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
            font-family: 'Inter', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .card {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 48px;
            max-width: 560px;
            width: 100%;
            text-align: center;
            box-shadow: 0 32px 80px rgba(0,0,0,0.5);
        }
        .icon-wrap {
            width: 80px; height: 80px;
            background: rgba(99,102,241,0.15);
            border: 2px solid rgba(99,102,241,0.3);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 24px;
            font-size: 36px; color: #818cf8;
        }
        h1 { color: #f1f5f9; font-size: 1.75rem; font-weight: 700; margin-bottom: 12px; }
        .subtitle { color: #94a3b8; font-size: 0.95rem; line-height: 1.6; margin-bottom: 32px; }
        .info-box {
            background: rgba(99,102,241,0.08);
            border: 1px solid rgba(99,102,241,0.2);
            border-radius: 12px;
            padding: 20px; margin-bottom: 32px; text-align: left;
        }
        .info-row { display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid rgba(255,255,255,0.06); }
        .info-row:last-child { border-bottom: none; }
        .info-label { color: #64748b; font-size: 0.85rem; }
        .info-value { color: #e2e8f0; font-size: 0.9rem; font-weight: 600; }
        .badge-feature {
            display: inline-block;
            background: rgba(99,102,241,0.2); color: #c7d2fe;
            border: 1px solid rgba(99,102,241,0.3);
            border-radius: 20px; padding: 4px 12px;
            font-size: 0.8rem; font-weight: 600;
        }
        .actions { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }
        .btn {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 12px 24px; border-radius: 10px;
            font-size: 0.9rem; font-weight: 600;
            text-decoration: none; border: none; cursor: pointer; transition: all 0.2s;
        }
        .btn-primary { background: #4f46e5; color: #fff; }
        .btn-primary:hover { background: #4338ca; transform: translateY(-1px); }
        .btn-outline { background: transparent; color: #94a3b8; border: 1px solid rgba(255,255,255,0.15); }
        .btn-outline:hover { background: rgba(255,255,255,0.08); color: #f1f5f9; }
        .support-note { margin-top: 24px; color: #475569; font-size: 0.82rem; }
        .support-note a { color: #818cf8; text-decoration: none; }
    </style>
</head>
<body>
<div class="card">
    <div class="icon-wrap"><i class="bi bi-lock-fill"></i></div>
    <h1>Feature Unavailable</h1>
    <p class="subtitle">
        This feature is currently disabled or not included in your institution's subscription plan.
        Please contact your school administrator or CloudKazi support to enable it.
    </p>

    <div class="info-box">
        <div class="info-row">
            <span class="info-label">Module / Feature</span>
            <span class="info-value"><span class="badge-feature">{{ $featureName ?? ucfirst(str_replace('_', ' ', $featureKey ?? 'Feature')) }}</span></span>
        </div>
        <div class="info-row">
            <span class="info-label">Status</span>
            <span class="info-value text-danger">Disabled</span>
        </div>
        <div class="info-row">
            <span class="info-label">Action Required</span>
            <span class="info-value">Upgrade Plan or Enable Override</span>
        </div>
    </div>

    <div class="actions">
        <a href="{{ route('dashboard') }}" class="btn btn-outline">
            <i class="bi bi-arrow-left"></i> Return to Dashboard
        </a>
        <a href="mailto:support@cloudkazi.com" class="btn btn-primary">
            <i class="bi bi-envelope-fill"></i> Request Access
        </a>
    </div>

    <p class="support-note">
        CloudKazi Elimu &bull; <a href="mailto:support@cloudkazi.com">support@cloudkazi.com</a>
    </p>
</div>
</body>
</html>
