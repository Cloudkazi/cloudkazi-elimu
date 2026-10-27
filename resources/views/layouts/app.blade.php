<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — CloudKazi Elimu</title>
    <meta name="description" content="CloudKazi Elimu - School Management Information System">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js" defer></script>

    <!-- App CSS -->
    <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/dashboard.css') }}">
    @stack('styles')
</head>
<body>

<div class="app-wrapper">

    <!-- ===== SIDEBAR ===== -->
    <aside class="sidebar" id="sidebar">
        <!-- Brand -->
        <div class="sidebar-brand">
            <div class="sidebar-brand-icon">🎓</div>
            <div class="sidebar-brand-text">
                <h2>CloudKazi</h2>
                <span>Elimu — SMIS ERP</span>
            </div>
        </div>

        <!-- School Badge -->
        <!-- School Badge -->
        @php
            $activeSchool = session('active_school') ?? ($currentSchool ?? null);
        @endphp
        @if($activeSchool && !auth()->user()?->isSuperAdmin())
        <div class="sidebar-school-badge">
            <div class="school-avatar">
                {{ strtoupper(substr($activeSchool->name ?? 'S', 0, 2)) }}
            </div>
            <div class="school-info">
                <h5>{{ Str::limit($activeSchool->name ?? 'School', 18) }}</h5>
                <span>{{ $activeSchool->curriculum_type ?? 'CBC/8-4-4' }}</span>
            </div>
        </div>
        @endif

        <!-- Navigation -->
        <nav class="sidebar-nav">
            @if(auth()->user()?->isDemoAccount())
                @if(auth()->user()?->isSuperAdmin())
                    <div class="sidebar-section-label">Super Admin HQ</div>
                    <div class="nav-item" style="pointer-events:none; opacity:0.9; cursor:not-allowed;"><i class="bi bi-grid-3x3-gap-fill nav-icon"></i> Platform Overview</div>
                    <div class="nav-item" style="pointer-events:none; opacity:0.9; cursor:not-allowed;"><i class="bi bi-building nav-icon"></i> Tenant Schools</div>
                    <div class="nav-item" style="pointer-events:none; opacity:0.9; cursor:not-allowed;"><i class="bi bi-plus-circle nav-icon"></i> Onboard School</div>
                    <div class="nav-item" style="pointer-events:none; opacity:0.9; cursor:not-allowed;"><i class="bi bi-credit-card nav-icon"></i> Subscriptions & Plans</div>
                    <div class="nav-item" style="pointer-events:none; opacity:0.9; cursor:not-allowed;"><i class="bi bi-people nav-icon"></i> Platform Users</div>
                    <div class="sidebar-section-label">Account</div>
                    <div class="nav-item" style="pointer-events:none; opacity:0.9; cursor:not-allowed;"><i class="bi bi-shield-lock nav-icon"></i> Change Password</div>
                    <form method="POST" action="{{ route('logout') }}" style="margin:0;">
                        @csrf
                        <button type="submit" class="nav-item" style="background:none;border:none;width:100%;text-align:left;color:inherit;cursor:pointer;">
                            <i class="bi bi-box-arrow-left nav-icon"></i> Logout
                        </button>
                    </form>
                @elseif(auth()->user()?->isBursar())
                    <div class="sidebar-section-label">Financial Operations</div>
                    <div class="nav-item" style="pointer-events:none; opacity:0.9; cursor:not-allowed;"><i class="bi bi-speedometer2 nav-icon"></i> Finance Overview</div>
                    <div class="nav-item" style="pointer-events:none; opacity:0.9; cursor:not-allowed;"><i class="bi bi-currency-dollar nav-icon"></i> Fee Structures</div>
                    <div class="nav-item" style="pointer-events:none; opacity:0.9; cursor:not-allowed;"><i class="bi bi-receipt nav-icon"></i> Payments Register</div>
                    <div class="nav-item" style="pointer-events:none; opacity:0.9; cursor:not-allowed;"><i class="bi bi-phone nav-icon"></i> M-Pesa STK & Logs</div>
                    <div class="nav-item" style="pointer-events:none; opacity:0.9; cursor:not-allowed;"><i class="bi bi-file-earmark-spreadsheet nav-icon"></i> Student Invoices</div>
                    <div class="nav-item" style="pointer-events:none; opacity:0.9; cursor:not-allowed;"><i class="bi bi-exclamation-triangle nav-icon"></i> Fee Arrears</div>
                    <div class="nav-item" style="pointer-events:none; opacity:0.9; cursor:not-allowed;"><i class="bi bi-graph-up nav-icon"></i> Financial Reports</div>
                    <div class="sidebar-section-label">Account</div>
                    <div class="nav-item" style="pointer-events:none; opacity:0.9; cursor:not-allowed;"><i class="bi bi-shield-lock nav-icon"></i> Change Password</div>
                    <form method="POST" action="{{ route('logout') }}" style="margin:0;">
                        @csrf
                        <button type="submit" class="nav-item" style="background:none;border:none;width:100%;text-align:left;color:inherit;cursor:pointer;">
                            <i class="bi bi-box-arrow-left nav-icon"></i> Logout
                        </button>
                    </form>
                @elseif(auth()->user()?->isTeacher())
                    <div class="sidebar-section-label">Faculty Portal</div>
                    <div class="nav-item" style="pointer-events:none; opacity:0.9; cursor:not-allowed;"><i class="bi bi-speedometer2 nav-icon"></i> Teacher Dashboard</div>
                    <div class="nav-item" style="pointer-events:none; opacity:0.9; cursor:not-allowed;"><i class="bi bi-calendar-check nav-icon"></i> Class Attendance</div>
                    <div class="nav-item" style="pointer-events:none; opacity:0.9; cursor:not-allowed;"><i class="bi bi-pencil-square nav-icon"></i> Enter Exam Marks</div>
                    <div class="nav-item" style="pointer-events:none; opacity:0.9; cursor:not-allowed;"><i class="bi bi-calendar3-week nav-icon"></i> Master Timetable</div>
                    <div class="nav-item" style="pointer-events:none; opacity:0.9; cursor:not-allowed;"><i class="bi bi-journal-arrow-up nav-icon"></i> Learning Materials</div>
                    <div class="nav-item" style="pointer-events:none; opacity:0.9; cursor:not-allowed;"><i class="bi bi-megaphone nav-icon"></i> School Notices</div>
                    <div class="sidebar-section-label">Account</div>
                    <div class="nav-item" style="pointer-events:none; opacity:0.9; cursor:not-allowed;"><i class="bi bi-shield-lock nav-icon"></i> Change Password</div>
                    <form method="POST" action="{{ route('logout') }}" style="margin:0;">
                        @csrf
                        <button type="submit" class="nav-item" style="background:none;border:none;width:100%;text-align:left;color:inherit;cursor:pointer;">
                            <i class="bi bi-box-arrow-left nav-icon"></i> Logout
                        </button>
                    </form>
                @elseif(auth()->user()?->isParent())
                    <div class="sidebar-section-label">Parent Portal</div>
                    <div class="nav-item" style="pointer-events:none; opacity:0.9; cursor:not-allowed;"><i class="bi bi-person-heart nav-icon"></i> My Children</div>
                    <div class="nav-item" style="pointer-events:none; opacity:0.9; cursor:not-allowed;"><i class="bi bi-megaphone nav-icon"></i> School Notices</div>
                    <div class="sidebar-section-label">Account</div>
                    <div class="nav-item" style="pointer-events:none; opacity:0.9; cursor:not-allowed;"><i class="bi bi-shield-lock nav-icon"></i> Change Password</div>
                    <form method="POST" action="{{ route('logout') }}" style="margin:0;">
                        @csrf
                        <button type="submit" class="nav-item" style="background:none;border:none;width:100%;text-align:left;color:inherit;cursor:pointer;">
                            <i class="bi bi-box-arrow-left nav-icon"></i> Logout
                        </button>
                    </form>
                @elseif(auth()->user()?->isStudent())
                    <div class="sidebar-section-label">Student Portal</div>
                    <div class="nav-item" style="pointer-events:none; opacity:0.9; cursor:not-allowed;"><i class="bi bi-mortarboard nav-icon"></i> My Dashboard</div>
                    <div class="nav-item" style="pointer-events:none; opacity:0.9; cursor:not-allowed;"><i class="bi bi-calendar3-week nav-icon"></i> My Timetable</div>
                    <div class="nav-item" style="pointer-events:none; opacity:0.9; cursor:not-allowed;"><i class="bi bi-book nav-icon"></i> My Library Books</div>
                    <div class="nav-item" style="pointer-events:none; opacity:0.9; cursor:not-allowed;"><i class="bi bi-journal-arrow-down nav-icon"></i> Course Materials</div>
                    <div class="nav-item" style="pointer-events:none; opacity:0.9; cursor:not-allowed;"><i class="bi bi-file-earmark-richtext nav-icon"></i> My Report Cards</div>
                    <div class="sidebar-section-label">Account</div>
                    <div class="nav-item" style="pointer-events:none; opacity:0.9; cursor:not-allowed;"><i class="bi bi-shield-lock nav-icon"></i> Change Password</div>
                    <form method="POST" action="{{ route('logout') }}" style="margin:0;">
                        @csrf
                        <button type="submit" class="nav-item" style="background:none;border:none;width:100%;text-align:left;color:inherit;cursor:pointer;">
                            <i class="bi bi-box-arrow-left nav-icon"></i> Logout
                        </button>
                    </form>
                @elseif(auth()->user()?->isLibrarian())
                    <div class="sidebar-section-label">Library Management</div>
                    <div class="nav-item" style="pointer-events:none; opacity:0.9; cursor:not-allowed;"><i class="bi bi-speedometer2 nav-icon"></i> Library Dashboard</div>
                    <div class="nav-item" style="pointer-events:none; opacity:0.9; cursor:not-allowed;"><i class="bi bi-book nav-icon"></i> Book Catalog</div>
                    <div class="nav-item" style="pointer-events:none; opacity:0.9; cursor:not-allowed;"><i class="bi bi-tags nav-icon"></i> Book Categories</div>
                    <div class="nav-item" style="pointer-events:none; opacity:0.9; cursor:not-allowed;"><i class="bi bi-box-arrow-right nav-icon"></i> Issue Books</div>
                    <div class="nav-item" style="pointer-events:none; opacity:0.9; cursor:not-allowed;"><i class="bi bi-arrow-return-left nav-icon"></i> Return Books</div>
                    <div class="nav-item" style="pointer-events:none; opacity:0.9; cursor:not-allowed;"><i class="bi bi-clock-history nav-icon"></i> Issued Books History</div>
                    <div class="nav-item" style="pointer-events:none; opacity:0.9; cursor:not-allowed;"><i class="bi bi-file-earmark-bar-graph nav-icon"></i> Library Reports</div>
                    <div class="sidebar-section-label">Account</div>
                    <div class="nav-item" style="pointer-events:none; opacity:0.9; cursor:not-allowed;"><i class="bi bi-shield-lock nav-icon"></i> Change Password</div>
                    <form method="POST" action="{{ route('logout') }}" style="margin:0;">
                        @csrf
                        <button type="submit" class="nav-item" style="background:none;border:none;width:100%;text-align:left;color:inherit;cursor:pointer;">
                            <i class="bi bi-box-arrow-left nav-icon"></i> Logout
                        </button>
                    </form>
                @elseif(auth()->user()?->isHostelWarden())
                    <div class="sidebar-section-label">Warden Portal</div>
                    <div class="nav-item" style="pointer-events:none; opacity:0.9; cursor:not-allowed;"><i class="bi bi-speedometer2 nav-icon"></i> Dashboard</div>
                    <div class="sidebar-section-label">Main Menu</div>
                    <div class="nav-item" style="pointer-events:none; opacity:0.9; cursor:not-allowed;"><i class="bi bi-house nav-icon"></i> Hostel &amp; Boarding</div>
                    <div class="nav-item" style="pointer-events:none; opacity:0.9; cursor:not-allowed;"><i class="bi bi-person-badge nav-icon"></i> Students</div>
                    <div class="nav-item" style="pointer-events:none; opacity:0.9; cursor:not-allowed;"><i class="bi bi-megaphone nav-icon"></i> Notices</div>
                    <div class="sidebar-section-label">Account</div>
                    <div class="nav-item" style="pointer-events:none; opacity:0.9; cursor:not-allowed;"><i class="bi bi-shield-lock nav-icon"></i> Change Password</div>
                    <form method="POST" action="{{ route('logout') }}" style="margin:0;">
                        @csrf
                        <button type="submit" class="nav-item" style="background:none;border:none;width:100%;text-align:left;color:inherit;cursor:pointer;">
                            <i class="bi bi-box-arrow-left nav-icon"></i> Logout
                        </button>
                    </form>
                @else
                    <div style="margin: 8px 12px 14px; padding: 6px 10px; background: rgba(245,158,11,0.15); border: 1px solid rgba(245,158,11,0.3); border-radius: 6px; font-size: 0.75rem; color: #f59e0b; display: flex; align-items: center; gap: 6px;">
                        <i class="bi bi-eye"></i> <span>Demo Mode (Read-Only)</span>
                    </div>
                    <div class="sidebar-section-label">Main Menu</div>
                    <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <i class="bi bi-speedometer2 nav-icon"></i> Dashboard
                    </a>
                    <a href="{{ route('students.index') }}" class="nav-item {{ request()->routeIs('students.*') ? 'active' : '' }}">
                        <i class="bi bi-person-badge nav-icon"></i> Students
                    </a>
                    <a href="{{ route('academics.classes') }}" class="nav-item {{ request()->routeIs('academics.*') ? 'active' : '' }}">
                        <i class="bi bi-book nav-icon"></i> Academics
                    </a>
                    <a href="{{ route('academics.teachers') }}" class="nav-item">
                        <i class="bi bi-person-workspace nav-icon"></i> Teachers
                    </a>
                    <a href="{{ route('timetable.index') }}" class="nav-item {{ request()->routeIs('timetable.*') ? 'active' : '' }}">
                        <i class="bi bi-calendar3-week nav-icon"></i> Timetable
                    </a>
                    <a href="{{ route('attendance.index') }}" class="nav-item {{ request()->routeIs('attendance.*') ? 'active' : '' }}">
                        <i class="bi bi-calendar-check nav-icon"></i> Attendance
                    </a>
                    <a href="{{ route('examinations.index') }}" class="nav-item {{ request()->routeIs('examinations.*') ? 'active' : '' }}">
                        <i class="bi bi-pencil-square nav-icon"></i> Examinations
                    </a>
                    <a href="{{ route('finance.index') }}" class="nav-item {{ request()->routeIs('finance.*') ? 'active' : '' }}">
                        <i class="bi bi-cash-stack nav-icon"></i> Finance
                    </a>
                    <a href="{{ route('operations.reports') }}" class="nav-item {{ request()->routeIs('operations.reports') ? 'active' : '' }}">
                        <i class="bi bi-file-earmark-bar-graph nav-icon"></i> Reports
                    </a>

                    <div class="sidebar-section-label">Operations</div>
                    <a href="{{ route('library.index') }}" class="nav-item">
                        <i class="bi bi-bookshelf nav-icon"></i> Library
                    </a>
                    <a href="{{ route('transport.index') }}" class="nav-item">
                        <i class="bi bi-bus-front nav-icon"></i> Transport
                    </a>
                    <a href="{{ route('hostel.index') }}" class="nav-item">
                        <i class="bi bi-house nav-icon"></i> Hostel
                    </a>
                    <a href="{{ route('operations.materials') }}" class="nav-item">
                        <i class="bi bi-journal-arrow-up nav-icon"></i> Learning Materials
                    </a>
                    <a href="{{ route('portal.practice.content') }}" class="nav-item">
                        <i class="bi bi-lightning-charge nav-icon"></i> Practice Content
                    </a>
                    <a href="{{ route('communication.index') }}" class="nav-item">
                        <i class="bi bi-megaphone nav-icon"></i> Notices
                    </a>

                    <div class="sidebar-section-label">Portals</div>
                    <a href="{{ route('parent.dashboard') }}" class="nav-item">
                        <i class="bi bi-person-heart nav-icon"></i> Parent Portal
                    </a>
                    <a href="{{ route('student.dashboard') }}" class="nav-item">
                        <i class="bi bi-mortarboard nav-icon"></i> Student Portal
                    </a>

                    <div class="sidebar-section-label">Account</div>
                    <a href="{{ route('profile.change-password') }}" class="nav-item">
                        <i class="bi bi-shield-lock nav-icon"></i> Change Password
                    </a>
                    <form method="POST" action="{{ route('logout') }}" id="logout-form-demo" style="margin:0;">
                        @csrf
                        <button type="submit" class="nav-item" style="background:none;border:none;width:100%;text-align:left;color:inherit;cursor:pointer;">
                            <i class="bi bi-box-arrow-left nav-icon"></i> Logout
                        </button>
                    </form>
                @endif
            @else
                @if(auth()->user()?->isSuperAdmin())
                <!-- PLATFORM SUPER ADMINISTRATOR (SaaS PLATFORM OWNER) -->
                <div class="sidebar-section-label">Platform HQ</div>
                <a href="{{ route('superadmin.dashboard') }}" class="nav-item {{ request()->routeIs('superadmin.dashboard') ? 'active' : '' }}">
                    <i class="bi bi-grid-3x3-gap-fill nav-icon"></i> Platform Overview
                </a>
                <a href="{{ route('superadmin.schools') }}" class="nav-item {{ request()->routeIs('superadmin.schools*') ? 'active' : '' }}">
                    <i class="bi bi-building nav-icon"></i> Tenant Schools
                </a>
                <a href="{{ route('superadmin.packages') }}" class="nav-item {{ request()->routeIs('superadmin.packages*') ? 'active' : '' }}">
                    <i class="bi bi-box-seam nav-icon"></i> Packages & Plans
                </a>
                <a href="{{ route('superadmin.subscriptions') }}" class="nav-item {{ request()->routeIs('superadmin.subscriptions') ? 'active' : '' }}">
                    <i class="bi bi-credit-card nav-icon"></i> Subscriptions
                </a>
                <a href="{{ route('superadmin.billing') }}" class="nav-item {{ request()->routeIs('superadmin.billing') ? 'active' : '' }}">
                    <i class="bi bi-cash-coin nav-icon"></i> SaaS Billing
                </a>
                <a href="{{ route('superadmin.usage') }}" class="nav-item {{ request()->routeIs('superadmin.usage') ? 'active' : '' }}">
                    <i class="bi bi-pie-chart nav-icon"></i> Population & Usage
                </a>
                <a href="{{ route('superadmin.audit-logs') }}" class="nav-item {{ request()->routeIs('superadmin.audit-logs') ? 'active' : '' }}">
                    <i class="bi bi-shield-check nav-icon"></i> Audit Logs
                </a>
                <a href="{{ route('superadmin.settings') }}" class="nav-item {{ request()->routeIs('superadmin.settings') ? 'active' : '' }}">
                    <i class="bi bi-sliders nav-icon"></i> Platform Settings
                </a>
                <a href="{{ route('superadmin.users') }}" class="nav-item {{ request()->routeIs('superadmin.users') ? 'active' : '' }}">
                    <i class="bi bi-people nav-icon"></i> Platform Users
                </a>

                <div class="sidebar-section-label">System Settings</div>
                <a href="{{ route('superadmin.legal.privacy') }}" class="nav-item {{ request()->routeIs('superadmin.legal.privacy') ? 'active' : '' }}">
                    <i class="bi bi-shield-lock nav-icon"></i> Privacy Policy
                </a>
                <a href="{{ route('superadmin.legal.terms') }}" class="nav-item {{ request()->routeIs('superadmin.legal.terms') ? 'active' : '' }}">
                    <i class="bi bi-file-earmark-text nav-icon"></i> Terms &amp; Conditions
                </a>
                <a href="{{ route('superadmin.help-articles.index') }}" class="nav-item {{ request()->routeIs('superadmin.help-articles*') ? 'active' : '' }}">
                    <i class="bi bi-journal-question nav-icon"></i> Help Articles
                </a>
                <a href="{{ route('superadmin.ai-assistant.settings') }}" class="nav-item {{ request()->routeIs('superadmin.ai-assistant.settings') ? 'active' : '' }}">
                    <i class="bi bi-robot nav-icon"></i> AI Assistant Settings
                </a>
                <a href="{{ route('superadmin.ai-assistant.logs') }}" class="nav-item {{ request()->routeIs('superadmin.ai-assistant.logs') ? 'active' : '' }}">
                    <i class="bi bi-chat-dots nav-icon"></i> Chatbot Logs
                </a>

                <div class="sidebar-section-label">Account</div>
                <a href="{{ route('profile.change-password') }}" class="nav-item">
                    <i class="bi bi-shield-lock nav-icon"></i> Change Password
                </a>
                <a href="{{ route('logout') }}" class="nav-item"
                   onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    <i class="bi bi-box-arrow-left nav-icon"></i> Logout
                </a>

                @elseif(auth()->user()?->isBursar())
                <!-- BURSAR (STRICTLY FINANCE ONLY) -->
                <div class="sidebar-section-label">Financial Operations</div>
                <a href="{{ route('finance.dashboard') }}" class="nav-item">
                    <i class="bi bi-speedometer2 nav-icon"></i> Finance Overview
                </a>
                <a href="{{ route('finance.fees') }}" class="nav-item">
                    <i class="bi bi-currency-dollar nav-icon"></i> Fee Structures
                </a>
                <a href="{{ route('finance.payments') }}" class="nav-item">
                    <i class="bi bi-receipt nav-icon"></i> Payments Register
                </a>
                <a href="{{ route('finance.reconciliation') }}" class="nav-item">
                    <i class="bi bi-arrow-left-right nav-icon"></i> Reconciliation Center
                </a>
                <a href="{{ route('finance.gateway-settings') }}" class="nav-item">
                    <i class="bi bi-gear nav-icon"></i> Gateway Settings
                </a>
                <a href="{{ route('finance.mpesa') }}" class="nav-item">
                    <i class="bi bi-phone nav-icon"></i> M-Pesa STK &amp; Logs
                </a>
                <a href="{{ route('finance.invoices') }}" class="nav-item">
                    <i class="bi bi-file-earmark-spreadsheet nav-icon"></i> Student Invoices
                </a>
                <a href="{{ route('finance.arrears') }}" class="nav-item">
                    <i class="bi bi-exclamation-triangle nav-icon"></i> Fee Arrears
                </a>
                <a href="{{ route('finance.reports') }}" class="nav-item">
                    <i class="bi bi-graph-up nav-icon"></i> Financial Reports
                </a>

                <div class="sidebar-section-label">Account</div>
                <a href="{{ route('profile.change-password') }}" class="nav-item">
                    <i class="bi bi-shield-lock nav-icon"></i> Change Password
                </a>
                <a href="{{ route('logout') }}" class="nav-item"
                   onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    <i class="bi bi-box-arrow-left nav-icon"></i> Logout
                </a>

                @elseif(auth()->user()?->isTeacher())
                <!-- TEACHING FACULTY -->
                <div class="sidebar-section-label">Faculty Portal</div>
                <a href="{{ route('teacher.dashboard') }}" class="nav-item">
                    <i class="bi bi-speedometer2 nav-icon"></i> Teacher Dashboard
                </a>
                <a href="{{ route('attendance.index') }}" class="nav-item">
                    <i class="bi bi-calendar-check nav-icon"></i> Class Attendance
                </a>
                <a href="{{ route('examinations.marks') }}" class="nav-item">
                    <i class="bi bi-pencil-square nav-icon"></i> Enter Exam Marks
                </a>
                <a href="{{ route('academics.timetable') }}" class="nav-item">
                    <i class="bi bi-calendar3-week nav-icon"></i> Master Timetable
                </a>
                <a href="{{ route('operations.materials') }}" class="nav-item">
                    <i class="bi bi-journal-arrow-up nav-icon"></i> Learning Materials
                </a>
                <a href="{{ route('portal.practice.content') }}" class="nav-item {{ request()->routeIs('portal.practice.content*') ? 'active' : '' }}">
                    <i class="bi bi-lightning-charge nav-icon"></i> Practice Content
                </a>
                <a href="{{ route('operations.notices') }}" class="nav-item">
                    <i class="bi bi-megaphone nav-icon"></i> School Notices
                </a>

                <div class="sidebar-section-label">Account</div>
                <a href="{{ route('profile.change-password') }}" class="nav-item">
                    <i class="bi bi-shield-lock nav-icon"></i> Change Password
                </a>
                <a href="{{ route('logout') }}" class="nav-item"
                   onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    <i class="bi bi-box-arrow-left nav-icon"></i> Logout
                </a>

                @elseif(auth()->user()?->isParent())
                <!-- PARENT PORTAL -->
                <div class="sidebar-section-label">Parent Portal</div>
                <a href="{{ route('portal.parent') }}" class="nav-item">
                    <i class="bi bi-person-heart nav-icon"></i> My Children
                </a>
                <a href="{{ route('portal.parent.diary') }}" class="nav-item">
                    <i class="bi bi-journal-text nav-icon"></i> School Diary
                </a>
                <a href="{{ route('portal.parent.performance') }}" class="nav-item">
                    <i class="bi bi-graph-up nav-icon"></i> Academic Performance
                </a>
               
                <div class="sidebar-section-label">Account</div>
                <a href="{{ route('profile.change-password') }}" class="nav-item">
                    <i class="bi bi-shield-lock nav-icon"></i> Change Password
                </a>
                <a href="{{ route('logout') }}" class="nav-item"
                   onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    <i class="bi bi-box-arrow-left nav-icon"></i> Logout
                </a>

                @elseif(auth()->user()?->isStudent())
                <!-- STUDENT PORTAL -->
                <div class="sidebar-section-label">Student Portal</div>
                <a href="{{ route('portal.student') }}" class="nav-item {{ request()->routeIs('portal.student') ? 'active' : '' }}">
                    <i class="bi bi-mortarboard nav-icon"></i> My Dashboard
                </a>
                <a href="{{ route('portal.student') }}#my-library-books" class="nav-item">
                    <i class="bi bi-book nav-icon"></i> My Library Books
                </a>
                <a href="{{ route('portal.student.diary') }}" class="nav-item">
                    <i class="bi bi-journal-text nav-icon"></i> School Diary
                </a>
                <a href="{{ route('portal.student.performance') }}" class="nav-item">
                    <i class="bi bi-graph-up nav-icon"></i> Academic Performance
                </a>
                @if(!isset($practiceAvailable) || $practiceAvailable)
                    <a href="{{ route('portal.practice') }}" class="nav-item">
                        <i class="bi bi-lightning-charge nav-icon"></i> My Practice
                    </a>
                @endif
                <a href="{{ route('academics.timetable') }}" class="nav-item">
                    <i class="bi bi-calendar3-week nav-icon"></i> My Timetable
                </a>
               
                <a href="{{ route('examinations.report-cards') }}" class="nav-item">
                    <i class="bi bi-file-earmark-richtext nav-icon"></i> My Report Cards
                </a>
                <div class="sidebar-section-label">Account</div>
                <a href="{{ route('profile.change-password') }}" class="nav-item">
                    <i class="bi bi-shield-lock nav-icon"></i> Change Password
                </a>
                <a href="{{ route('logout') }}" class="nav-item"
                   onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    <i class="bi bi-box-arrow-left nav-icon"></i> Logout
                </a>

                @elseif(auth()->user()?->isLibrarian())
                <!-- LIBRARIAN (LIBRARY MANAGEMENT ONLY) -->
                <div class="sidebar-section-label">Library Management</div>
                <a href="{{ route('library.index') }}" class="nav-item {{ request()->routeIs('library.index') || request()->routeIs('library.dashboard') ? 'active' : '' }}">
                    <i class="bi bi-speedometer2 nav-icon"></i> Library Dashboard
                </a>
                <a href="{{ route('library.books') }}" class="nav-item {{ request()->routeIs('library.books') || request()->routeIs('operations.library') ? 'active' : '' }}">
                    <i class="bi bi-book nav-icon"></i> Book Catalog
                </a>
                <a href="{{ route('library.categories') }}" class="nav-item {{ request()->routeIs('library.categories') ? 'active' : '' }}">
                    <i class="bi bi-tags nav-icon"></i> Book Categories
                </a>
                <a href="{{ route('library.issue.form') }}" class="nav-item {{ request()->routeIs('library.issue.form') ? 'active' : '' }}">
                    <i class="bi bi-box-arrow-right nav-icon"></i> Issue Books
                </a>
                <a href="{{ route('library.returns') }}" class="nav-item {{ request()->routeIs('library.returns') ? 'active' : '' }}">
                    <i class="bi bi-arrow-return-left nav-icon"></i> Return Books
                </a>
                <a href="{{ route('library.history') }}" class="nav-item {{ request()->routeIs('library.history') ? 'active' : '' }}">
                    <i class="bi bi-clock-history nav-icon"></i> Issued Books History
                </a>
                <a href="{{ route('library.reports') }}" class="nav-item {{ request()->routeIs('library.reports') ? 'active' : '' }}">
                    <i class="bi bi-file-earmark-bar-graph nav-icon"></i> Library Reports
                </a>

                <div class="sidebar-section-label">Account</div>
                <a href="{{ route('profile.change-password') }}" class="nav-item {{ request()->routeIs('profile.change-password') ? 'active' : '' }}">
                    <i class="bi bi-shield-lock nav-icon"></i> Change Password
                </a>
                <a href="{{ route('logout') }}" class="nav-item"
                   onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    <i class="bi bi-box-arrow-left nav-icon"></i> Logout
                </a>

                @elseif(auth()->user()?->isHostelWarden())
                {{-- ===== HOSTEL WARDEN PORTAL ===== --}}
                <div class="sidebar-section-label">Warden Portal</div>
                <a href="{{ route('warden.dashboard') }}" class="nav-item {{ request()->routeIs('warden.dashboard') ? 'active' : '' }}">
                    <i class="bi bi-speedometer2 nav-icon"></i> Dashboard
                </a>

                <div class="sidebar-section-label">Main Menu</div>
                <a href="{{ route('hostel.index') }}" class="nav-item {{ request()->routeIs('hostel.*') || request()->is('operations/hostel*') ? 'active' : '' }}">
                    <i class="bi bi-house nav-icon"></i> Hostel &amp; Boarding
                </a>
                <a href="{{ route('students.index') }}" class="nav-item {{ request()->routeIs('students.*') ? 'active' : '' }}">
                    <i class="bi bi-person-badge nav-icon"></i> Students
                </a>
                <a href="{{ route('operations.notices') }}" class="nav-item {{ request()->routeIs('operations.notices') ? 'active' : '' }}">
                    <i class="bi bi-megaphone nav-icon"></i> Notices
                </a>

                <div class="sidebar-section-label">Account</div>
                <a href="{{ route('profile.change-password') }}" class="nav-item {{ request()->routeIs('profile.change-password') ? 'active' : '' }}">
                    <i class="bi bi-shield-lock nav-icon"></i> Change Password
                </a>
                <a href="{{ route('logout') }}" class="nav-item"
                   onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    <i class="bi bi-box-arrow-left nav-icon"></i> Logout
                </a>

                @else
                <!-- SCHOOL ADMIN, PRINCIPAL, DEPUTY PRINCIPAL (FULL INSTITUTIONAL ERP) -->
                @php
                    $schoolIdForSidebar = auth()->user()?->school_id ?? session('active_school_id');
                @endphp
                <div class="sidebar-section-label">Main Menu</div>
                <a href="{{ route('dashboard') }}" class="nav-item">
                    <i class="bi bi-speedometer2 nav-icon"></i> Dashboard
                </a>
                <a href="{{ route('school.users') }}" class="nav-item">
                    <i class="bi bi-people-fill nav-icon"></i> Staff & Accounts
                </a>

                <!-- Academics -->
                @if(!$schoolIdForSidebar || \App\Services\SchoolFeatureService::isEnabled($schoolIdForSidebar, 'student_management'))
                <div class="nav-toggle nav-item" data-sub="nav-academics">
                    <i class="bi bi-book nav-icon"></i> Academics <span class="chev">▶</span>
                </div>
                <div class="nav-sub" id="nav-academics">
                    <a href="{{ route('academics.classes') }}"><i class="bi bi-diagram-2"></i> Classes</a>
                    <a href="{{ route('academics.subjects') }}"><i class="bi bi-journals"></i> Subjects</a>
                    <a href="{{ route('academics.subject-offerings') }}"><i class="bi bi-journal-check"></i> Subject Offerings</a>
                    @if(!$schoolIdForSidebar || \App\Services\SchoolFeatureService::isEnabled($schoolIdForSidebar, 'timetable'))
                    <a href="{{ route('academics.timetable') }}"><i class="bi bi-calendar3-week"></i> Timetable</a>
                    @endif
                    <a href="{{ route('academics.curriculum') }}"><i class="bi bi-book-half"></i> Curriculum</a>
                    <a href="{{ route('portal.practice.content') }}"><i class="bi bi-lightning-charge"></i> Practice Content</a>
                </div>

                <!-- Students -->
                <div class="nav-toggle nav-item" data-sub="nav-students">
                    <i class="bi bi-person-badge nav-icon"></i> Students <span class="chev">▶</span>
                </div>
                <div class="nav-sub" id="nav-students">
                    <a href="{{ route('students.index') }}"><i class="bi bi-list-ul"></i> All Students</a>
                    <a href="{{ route('students.create') }}"><i class="bi bi-person-plus"></i> Enroll Student</a>
                    <a href="{{ route('students.import') }}"><i class="bi bi-file-earmark-arrow-up"></i> Import Students CSV</a>
                    <a href="{{ route('students.promotions') }}"><i class="bi bi-arrow-up-circle"></i> Promotions</a>
                    <a href="{{ route('students.documents') }}"><i class="bi bi-file-earmark-text"></i> Documents</a>
                </div>
                @endif

                <!-- Teachers -->
                @if(!$schoolIdForSidebar || \App\Services\SchoolFeatureService::isEnabled($schoolIdForSidebar, 'teacher_management'))
                <div class="nav-toggle nav-item" data-sub="nav-teachers">
                    <i class="bi bi-person-workspace nav-icon"></i> Teachers <span class="chev">▶</span>
                </div>
                <div class="nav-sub" id="nav-teachers">
                    <a href="{{ route('academics.teachers') }}"><i class="bi bi-list-ul"></i> All Teachers</a>
                    <a href="{{ route('academics.teacher-subjects') }}"><i class="bi bi-journal-bookmark"></i> Assignments</a>
                    @if(!$schoolIdForSidebar || \App\Services\SchoolFeatureService::isEnabled($schoolIdForSidebar, 'attendance'))
                    <a href="{{ route('attendance.teachers') }}"><i class="bi bi-clock-history"></i> Staff Attendance</a>
                    @endif
                </div>
                @endif

                <!-- Attendance -->
                @if(!$schoolIdForSidebar || \App\Services\SchoolFeatureService::isEnabled($schoolIdForSidebar, 'attendance'))
                <a href="{{ route('attendance.index') }}" class="nav-item">
                    <i class="bi bi-calendar-check nav-icon"></i> Attendance
                </a>
                @endif

                <!-- Examinations -->
                @if(!$schoolIdForSidebar || \App\Services\SchoolFeatureService::isEnabled($schoolIdForSidebar, 'examinations'))
                <div class="nav-toggle nav-item" data-sub="nav-exams">
                    <i class="bi bi-pencil-square nav-icon"></i> Examinations <span class="chev">▶</span>
                </div>
                <div class="nav-sub" id="nav-exams">
                    <a href="{{ route('examinations.index') }}"><i class="bi bi-list-check"></i> Exams</a>
                    <a href="{{ route('examinations.marks') }}"><i class="bi bi-input-cursor"></i> Enter Marks</a>
                    <a href="{{ route('examinations.results') }}"><i class="bi bi-bar-chart"></i> Results</a>
                    <a href="{{ route('examinations.report-cards') }}"><i class="bi bi-file-earmark-richtext"></i> Report Cards</a>
                </div>
                @endif

                <!-- Finance -->
                @if(!$schoolIdForSidebar || \App\Services\SchoolFeatureService::isEnabled($schoolIdForSidebar, 'finance'))
                <div class="nav-toggle nav-item" data-sub="nav-finance">
                    <i class="bi bi-cash-stack nav-icon"></i> Finance <span class="chev">▶</span>
                </div>
                <div class="nav-sub" id="nav-finance">
                    <a href="{{ route('finance.fees') }}"><i class="bi bi-currency-dollar"></i> Fee Structures</a>
                    <a href="{{ route('finance.payments') }}"><i class="bi bi-receipt"></i> Payments</a>
                    <a href="{{ route('finance.reconciliation') }}"><i class="bi bi-arrow-left-right"></i> Reconciliation</a>
                    <a href="{{ route('finance.gateway-settings') }}"><i class="bi bi-gear"></i> Gateway Settings</a>
                    <a href="{{ route('finance.mpesa') }}"><i class="bi bi-phone"></i> M-Pesa</a>
                    <a href="{{ route('finance.invoices') }}"><i class="bi bi-file-earmark-spreadsheet"></i> Invoices</a>
                    <a href="{{ route('finance.reports') }}"><i class="bi bi-graph-up"></i> Reports</a>
                </div>
                @endif

                <div class="sidebar-section-label">Operations</div>

                <!-- Library -->
                @if(!$schoolIdForSidebar || \App\Services\SchoolFeatureService::isEnabled($schoolIdForSidebar, 'library'))
                <div class="nav-toggle nav-item" data-sub="nav-library">
                    <i class="bi bi-bookshelf nav-icon"></i> Library <span class="chev">▶</span>
                </div>
                <div class="nav-sub" id="nav-library">
                    <a href="{{ route('operations.library') }}"><i class="bi bi-book"></i> Books Catalog</a>
                    <a href="{{ route('operations.library-issue') }}"><i class="bi bi-box-arrow-right"></i> Issue / Return</a>
                </div>
                @endif

                <!-- Transport -->
                @if(!$schoolIdForSidebar || \App\Services\SchoolFeatureService::isEnabled($schoolIdForSidebar, 'transport'))
                <div class="nav-toggle nav-item" data-sub="nav-transport">
                    <i class="bi bi-bus-front nav-icon"></i> Transport <span class="chev">▶</span>
                </div>
                <div class="nav-sub" id="nav-transport">
                    <a href="{{ route('operations.transport') }}"><i class="bi bi-geo-alt"></i> Routes</a>
                    <a href="{{ route('operations.vehicles') }}"><i class="bi bi-truck"></i> Vehicles</a>
                </div>
                @endif

                <!-- Hostel -->
                @if(!$schoolIdForSidebar || \App\Services\SchoolFeatureService::isEnabled($schoolIdForSidebar, 'hostel'))
                <a href="{{ route('operations.hostel') }}" class="nav-item">
                    <i class="bi bi-house nav-icon"></i> Hostel
                </a>
                @endif

                <!-- HR -->
                @if(!$schoolIdForSidebar || \App\Services\SchoolFeatureService::isEnabled($schoolIdForSidebar, 'teacher_management'))
                <div class="nav-toggle nav-item" data-sub="nav-hr">
                    <i class="bi bi-people-fill nav-icon"></i> Human Resources <span class="chev">▶</span>
                </div>
                <div class="nav-sub" id="nav-hr">
                    <a href="{{ route('operations.staff') }}"><i class="bi bi-person-lines-fill"></i> Staff Directory</a>
                    <a href="{{ route('operations.leave') }}"><i class="bi bi-calendar-x"></i> Leave Management</a>
                    <a href="{{ route('operations.payroll') }}"><i class="bi bi-wallet2"></i> Payroll</a>
                </div>
                @endif

                <!-- Materials & Practice -->
                @if(!$schoolIdForSidebar || \App\Services\SchoolFeatureService::isEnabled($schoolIdForSidebar, 'student_management'))
                <a href="{{ route('operations.materials') }}" class="nav-item">
                    <i class="bi bi-journal-arrow-up nav-icon"></i> Learning Materials
                </a>
                <a href="{{ route('portal.practice.content') }}" class="nav-item {{ request()->routeIs('portal.practice.content*') ? 'active' : '' }}">
                    <i class="bi bi-lightning-charge nav-icon"></i> Practice Content
                </a>
                @endif

                <!-- Notices -->
                @if(!$schoolIdForSidebar || \App\Services\SchoolFeatureService::isEnabled($schoolIdForSidebar, 'communication'))
                <a href="{{ route('operations.notices') }}" class="nav-item">
                    <i class="bi bi-megaphone nav-icon"></i> Notices
                </a>
                @endif

                <!-- Settings -->
                <div class="sidebar-section-label">Settings</div>
                <a href="{{ route('academics.settings') }}" class="nav-item">
                    <i class="bi bi-gear nav-icon"></i> School Settings
                </a>
                <a href="{{ route('profile.change-password') }}" class="nav-item">
                    <i class="bi bi-shield-lock nav-icon"></i> Change Password
                </a>
                <a href="{{ route('logout') }}" class="nav-item"
                   onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    <i class="bi bi-box-arrow-left nav-icon"></i> Logout
                </a>
                @endif
            @endif
            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display:none">
                @csrf
            </form>
        </nav>

        <!-- User info at bottom -->
        <div class="sidebar-user">
            <div class="user-avatar-sm">
                {{ strtoupper(substr(auth()->user()?->name ?? 'U', 0, 2)) }}
            </div>
            <div class="user-info">
                <h6>{{ auth()->user()?->name ?? 'User' }}</h6>
                <span>{{ ucfirst(str_replace('_', ' ', auth()->user()?->role?->name ?? 'User')) }}</span>
            </div>
        </div>
    </aside>

    <!-- ===== MAIN CONTENT ===== -->
    <div class="main-content">

        <!-- Topbar -->
        <header class="topbar">
            <button id="mobile-menu-toggle" class="topbar-btn" style="display:none">
                <i class="bi bi-list" style="font-size:20px"></i>
            </button>

            <div class="topbar-breadcrumb">
                <span>CloudKazi Elimu</span>
                <span>›</span>
                <span class="current">@yield('breadcrumb', 'Dashboard')</span>
            </div>

            <div class="topbar-spacer"></div>

            <div class="topbar-actions">
                @if(auth()->user()?->isDemoAccount())
                <div class="demo-header-actions" style="display:inline-flex;align-items:center;gap:8px;margin-right:12px;">
                    <a href="{{ route('school.register') }}" class="btn btn-sm" id="btn-header-register-school" style="background:#059669;color:#ffffff;border:none;padding:6px 14px;font-weight:600;display:inline-flex;align-items:center;gap:6px;border-radius:6px;text-decoration:none;font-size:0.82rem;white-space:nowrap;box-shadow:0 1px 3px rgba(0,0,0,0.12);">
                        <i class="bi bi-building-add"></i> Register School
                    </a>
                    <form method="POST" action="{{ route('logout') }}" id="header-logout-form-demo" style="margin:0;display:inline;">
                        @csrf
                        <button type="submit" class="btn btn-sm" id="btn-header-logout" style="background:#dc2626;color:#ffffff;border:none;padding:6px 14px;font-weight:600;display:inline-flex;align-items:center;gap:6px;border-radius:6px;cursor:pointer;font-size:0.82rem;white-space:nowrap;box-shadow:0 1px 3px rgba(0,0,0,0.12);">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </button>
                    </form>
                </div>
                @endif

                @if(!auth()->user()?->isSuperAdmin())
                    @if(!empty($currentAcademicYearName) && $currentAcademicYearName !== 'Not Configured')
                    <!-- Academic Year Badge -->
                    <span class="badge badge-info" style="font-size:0.75rem;padding:5px 10px;">
                        📅 AY: {{ $currentAcademicYearName }}
                    </span>

                    <!-- Term Badge -->
                    <span class="badge badge-teal" style="font-size:0.75rem;padding:5px 10px;">
                        {{ $currentAcademicTermName }}
                    </span>
                    @elseif(!empty(session('academic_year')))
                    <span class="badge badge-info" style="font-size:0.75rem;padding:5px 10px;">
                        📅 AY: {{ session('academic_year') }}
                    </span>
                    <span class="badge badge-teal" style="font-size:0.75rem;padding:5px 10px;">
                        {{ session('current_term') }}
                    </span>
                    @endif
                @endif

                <!-- Notifications -->
                <a href="#" class="topbar-btn">
                    <i class="bi bi-bell"></i>
                    <span class="topbar-notif-dot"></span>
                </a>

                <!-- Help -->
                <a href="#" class="topbar-btn">
                    <i class="bi bi-question-circle"></i>
                </a>

                <!-- User menu -->
                @if(auth()->user()?->isLibrarian() || auth()->user()?->isBursar() || auth()->user()?->isTeacher() || auth()->user()?->isStudent() || auth()->user()?->isParent() || auth()->user()?->isHostelWarden())
                <a href="{{ route('profile.change-password') }}" class="topbar-btn">
                    <i class="bi bi-person-circle" style="font-size:18px"></i>
                </a>
                @else
                <a href="{{ route('academics.settings') }}" class="topbar-btn">
                    <i class="bi bi-person-circle" style="font-size:18px"></i>
                </a>
                @endif
            </div>
        </header>

        <!-- Flash Messages -->
        <div style="padding: 0 24px; margin-top: 16px;">
            @if(session('success'))
                <div class="alert alert-success" data-dismiss="true">
                    <i class="bi bi-check-circle-fill"></i>
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger" data-dismiss="true">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    {{ session('error') }}
                </div>
            @endif
            @if(session('warning'))
                <div class="alert alert-warning" data-dismiss="true">
                    <i class="bi bi-exclamation-circle-fill"></i>
                    {{ session('warning') }}
                </div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger">
                    <i class="bi bi-x-circle-fill"></i>
                    <div>
                        @foreach($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <!-- Page Content -->
        <main class="page-content">
            @yield('content')
        </main>

        <!-- Footer -->
        <footer style="padding: 16px 24px; border-top: 1px solid var(--mc-border); background: var(--mc-surface); text-align: center;">
            <div style="color: var(--mc-text-muted); font-size: 0.78rem; display: flex; flex-direction: column; gap: 6px; align-items: center;">
                @if(!auth()->user()?->isSuperAdmin() && !empty($currentAcademicYearName) && $currentAcademicYearName !== 'Not Configured')
                <div style="font-weight: 500; color: var(--mc-text-primary);">
                    📅 AY: {{ $currentAcademicYearName }} &nbsp;|&nbsp; {{ $currentAcademicTermName }}
                </div>
                @endif
                <div>
                    &copy; {{ date('Y') }} CloudKazi Elimu — School Management Information System &nbsp;|&nbsp;
                    <span style="color: var(--neon-green-primary);">Powered by Kenya EdTech</span>
                    &nbsp;|&nbsp;
                    <a href="{{ route('privacy.policy') }}" style="color:var(--text-muted); font-size:0.78rem;" target="_blank">Privacy Policy</a>
                    &nbsp;&middot;&nbsp;
                    <a href="{{ route('terms.conditions') }}" style="color:var(--text-muted); font-size:0.78rem;" target="_blank">Terms &amp; Conditions</a>
                    &nbsp;&middot;&nbsp;
                    <a href="{{ route('help.center') }}" style="color:var(--text-muted); font-size:0.78rem;" target="_blank">Help Center</a>
                </div>
            </div>
        </footer>
    </div>
</div>

<!-- App JS -->
<script src="{{ asset('assets/js/app.js') }}"></script>

<!-- Floating AI Help Assistant Widget (Authenticated Pages) -->
@include('partials.ai-chatbot')

@stack('scripts')

</body>
</html>
