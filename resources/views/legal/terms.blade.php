@extends('layouts.guest')
@section('title', 'Terms and Conditions — CloudKazi Elimu')

@section('content')
<div class="legal-page-wrapper py-5">
    <div class="container" style="max-width: 1040px; margin: 0 auto; padding: 0 20px;">
        
        <!-- Header Banner -->
        <div class="legal-header-card mb-4 p-4 p-md-5 rounded-4 shadow-sm" style="background: linear-gradient(135deg, #0f2240 0%, #1a3a5c 100%); color: #ffffff; border-bottom: 3px solid #e8922a;">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="badge" style="background: #e8922a; color:#ffffff; font-weight:600; font-size:0.8rem;">TERMS OF SERVICE</span>
                        <span class="badge bg-secondary" style="font-size:0.8rem;">Version {{ $document->version ?? '2.1' }}</span>
                        <span class="badge bg-success-subtle text-success" style="font-size:0.8rem;">Legally Binding</span>
                    </div>
                    <h1 class="h2 fw-bold text-white mb-2">{{ $document->title ?? 'Terms and Conditions of Service' }}</h1>
                    <p class="text-white-50 mb-0" style="font-size:0.95rem;">
                        SaaS Agreement for Educational Institutions, Administrators, Teachers, Parents &amp; Students
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-light btn-sm" onclick="window.print()">
                        <i class="bi bi-printer"></i> Print Terms
                    </button>
                    <a href="{{ route('login') }}" class="btn btn-warning btn-sm fw-semibold" style="background:#e8922a; border-color:#e8922a; color:#ffffff;">
                        <i class="bi bi-box-arrow-in-right"></i> Sign In
                    </a>
                </div>
            </div>
            <div class="mt-3 pt-3 border-top border-white-50 d-flex flex-wrap justify-content-between text-white-50" style="font-size:0.8rem;">
                <span><i class="bi bi-calendar-check"></i> Last Updated: {{ $document->updated_at ? $document->updated_at->format('M d, Y') : date('M d, Y') }}</span>
                <span><i class="bi bi-bank"></i> Governed by the Laws of the Republic of Kenya</span>
            </div>
        </div>

        <div class="row g-4">
            <!-- Sidebar Navigation / Table of Contents -->
            <div class="col-lg-4 d-none d-lg-block">
                <div class="sticky-top p-4 rounded-3 shadow-sm bg-white border" style="top: 24px;">
                    <h6 class="fw-bold text-uppercase mb-3" style="font-size:0.82rem; color:var(--primary, #1a3a5c); letter-spacing:0.5px;">
                        <i class="bi bi-list-ul"></i> Agreement Clauses
                    </h6>
                    <nav class="legal-nav-list d-flex flex-column gap-2" style="font-size:0.86rem;">
                        <a href="#acceptance" class="text-decoration-none text-muted py-1 border-bottom"><i class="bi bi-chevron-right text-warning"></i> 1. Acceptance of Terms</a>
                        <a href="#user-responsibilities" class="text-decoration-none text-muted py-1 border-bottom"><i class="bi bi-chevron-right text-warning"></i> 2. User Responsibilities</a>
                        <a href="#school-responsibilities" class="text-decoration-none text-muted py-1 border-bottom"><i class="bi bi-chevron-right text-warning"></i> 3. School Responsibilities</a>
                        <a href="#data-ownership" class="text-decoration-none text-muted py-1 border-bottom"><i class="bi bi-chevron-right text-warning"></i> 4. Data Ownership</a>
                        <a href="#subscription" class="text-decoration-none text-muted py-1 border-bottom"><i class="bi bi-chevron-right text-warning"></i> 5. Subscriptions &amp; Quotas</a>
                        <a href="#account-security" class="text-decoration-none text-muted py-1 border-bottom"><i class="bi bi-chevron-right text-warning"></i> 6. Account Security</a>
                        <a href="#prohibited" class="text-decoration-none text-muted py-1 border-bottom"><i class="bi bi-chevron-right text-warning"></i> 7. Prohibited Activities</a>
                        <a href="#payments" class="text-decoration-none text-muted py-1 border-bottom"><i class="bi bi-chevron-right text-warning"></i> 8. Payment Obligations</a>
                        <a href="#sla" class="text-decoration-none text-muted py-1 border-bottom"><i class="bi bi-chevron-right text-warning"></i> 9. System Availability (SLA)</a>
                        <a href="#ip" class="text-decoration-none text-muted py-1 border-bottom"><i class="bi bi-chevron-right text-warning"></i> 10. Intellectual Property</a>
                        <a href="#termination" class="text-decoration-none text-muted py-1 border-bottom"><i class="bi bi-chevron-right text-warning"></i> 11. Termination &amp; Off-boarding</a>
                        <a href="#liability" class="text-decoration-none text-muted py-1 border-bottom"><i class="bi bi-chevron-right text-warning"></i> 12. Limitation of Liability</a>
                        <a href="#disputes" class="text-decoration-none text-muted py-1"><i class="bi bi-chevron-right text-warning"></i> 13. Dispute Resolution</a>
                    </nav>

                    <hr class="my-4">

                    <div class="bg-light p-3 rounded-3" style="font-size:0.8rem;">
                        <strong class="d-block text-dark mb-1"><i class="bi bi-file-earmark-lock text-primary"></i> Data Protection</strong>
                        <p class="text-muted mb-2">Learn how we process and protect student records under Kenyan law.</p>
                        <a href="{{ route('privacy.policy') }}" class="btn btn-outline-primary btn-sm w-100">Read Privacy Policy</a>
                    </div>
                </div>
            </div>

            <!-- Terms Content Document -->
            <div class="col-lg-8">
                <div class="legal-content-card p-4 p-md-5 bg-white rounded-3 shadow-sm border" style="line-height: 1.75; font-size: 0.95rem; color: #334155;">
                    @if($document && !empty($document->content))
                        {!! $document->content !!}
                    @else
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> Terms and conditions document is currently being prepared.
                        </div>
                    @endif
                </div>

                <!-- Footer Links Partial -->
                @include('partials.footer-links')
            </div>
        </div>

    </div>
</div>

<!-- Floating AI Chatbot Widget -->
@include('partials.ai-chatbot')

<style>
.legal-content-card h2 {
    font-size: 1.3rem;
    font-weight: 700;
    color: var(--primary, #1a3a5c);
    margin-top: 1.8rem;
    margin-bottom: 0.8rem;
    padding-bottom: 0.4rem;
    border-bottom: 2px solid #f1f5f9;
}
.legal-content-card h3 {
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--primary-light, #2a5291);
    margin-top: 1.4rem;
    margin-bottom: 0.5rem;
}
.legal-content-card ul {
    padding-left: 1.4rem;
    margin-bottom: 1.2rem;
}
.legal-content-card li {
    margin-bottom: 0.35rem;
}
.legal-content-card p {
    margin-bottom: 1rem;
}
.legal-nav-list a:hover {
    color: var(--primary, #1a3a5c) !important;
    font-weight: 600;
}
@media print {
    .legal-nav-list, #elimu-ai-widget, .btn { display: none !important; }
    .col-lg-8 { width: 100% !important; }
}
</style>
@endsection
