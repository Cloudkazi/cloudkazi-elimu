@extends('layouts.guest')
@section('title', 'Privacy Policy — CloudKazi Elimu')

@section('content')
<div class="legal-page-wrapper py-5">
    <div class="container" style="max-width: 1040px; margin: 0 auto; padding: 0 20px;">
        
        <!-- Header Banner -->
        <div class="legal-header-card mb-4 p-4 p-md-5 rounded-4 shadow-sm" style="background: linear-gradient(135deg, #0f2240 0%, #1a3a5c 100%); color: #ffffff; border-bottom: 3px solid #e8922a;">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="badge" style="background: #e8922a; color:#ffffff; font-weight:600; font-size:0.8rem;">LEGAL COMPLIANCE</span>
                        <span class="badge bg-secondary" style="font-size:0.8rem;">Version {{ $document->version ?? '2.1' }}</span>
                        <span class="badge bg-success-subtle text-success" style="font-size:0.8rem;">Active</span>
                    </div>
                    <h1 class="h2 fw-bold text-white mb-2">{{ $document->title ?? 'CloudKazi Elimu Privacy Policy' }}</h1>
                    <p class="text-white-50 mb-0" style="font-size:0.95rem;">
                        Kenyan Data Protection Act (2019) Compliant School Management ERP
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-light btn-sm" onclick="window.print()">
                        <i class="bi bi-printer"></i> Print Policy
                    </button>
                    <a href="{{ route('login') }}" class="btn btn-warning btn-sm fw-semibold" style="background:#e8922a; border-color:#e8922a; color:#ffffff;">
                        <i class="bi bi-box-arrow-in-right"></i> Sign In
                    </a>
                </div>
            </div>
            <div class="mt-3 pt-3 border-top border-white-50 d-flex flex-wrap justify-content-between text-white-50" style="font-size:0.8rem;">
                <span><i class="bi bi-calendar-check"></i> Last Updated: {{ $document->updated_at ? $document->updated_at->format('M d, Y') : date('M d, Y') }}</span>
                <span><i class="bi bi-shield-check"></i> Certified Data Processor for Educational Institutions</span>
            </div>
        </div>

        <div class="row g-4">
            <!-- Sidebar Navigation / Table of Contents -->
            <div class="col-lg-4 d-none d-lg-block">
                <div class="sticky-top p-4 rounded-3 shadow-sm bg-white border" style="top: 24px;">
                    <h6 class="fw-bold text-uppercase mb-3" style="font-size:0.82rem; color:var(--primary, #1a3a5c); letter-spacing:0.5px;">
                        <i class="bi bi-list-ul"></i> Quick Navigation
                    </h6>
                    <nav class="legal-nav-list d-flex flex-column gap-2" style="font-size:0.86rem;">
                        <a href="#intro" class="text-decoration-none text-muted py-1 border-bottom"><i class="bi bi-chevron-right text-warning"></i> 1. Introduction &amp; Overview</a>
                        <a href="#roles" class="text-decoration-none text-muted py-1 border-bottom"><i class="bi bi-chevron-right text-warning"></i> 2. Controller vs. Processor</a>
                        <a href="#data-collection" class="text-decoration-none text-muted py-1 border-bottom"><i class="bi bi-chevron-right text-warning"></i> 3. Information We Collect</a>
                        <a href="#student-info" class="text-decoration-none text-muted py-1 ps-3 border-bottom">&bull; Student Records</a>
                        <a href="#parent-info" class="text-decoration-none text-muted py-1 ps-3 border-bottom">&bull; Parent &amp; Guardian Info</a>
                        <a href="#teacher-info" class="text-decoration-none text-muted py-1 ps-3 border-bottom">&bull; Teacher &amp; Staff Records</a>
                        <a href="#payment-info" class="text-decoration-none text-muted py-1 ps-3 border-bottom">&bull; M-Pesa &amp; Fee Records</a>
                        <a href="#data-usage" class="text-decoration-none text-muted py-1 border-bottom"><i class="bi bi-chevron-right text-warning"></i> 4. How We Use Data</a>
                        <a href="#retention" class="text-decoration-none text-muted py-1 border-bottom"><i class="bi bi-chevron-right text-warning"></i> 5. Data Retention</a>
                        <a href="#security" class="text-decoration-none text-muted py-1 border-bottom"><i class="bi bi-chevron-right text-warning"></i> 6. Security Measures</a>
                        <a href="#integrations" class="text-decoration-none text-muted py-1 border-bottom"><i class="bi bi-chevron-right text-warning"></i> 7. Third-Party Integrations</a>
                        <a href="#user-rights" class="text-decoration-none text-muted py-1 border-bottom"><i class="bi bi-chevron-right text-warning"></i> 8. User &amp; Student Rights</a>
                        <a href="#contact" class="text-decoration-none text-muted py-1"><i class="bi bi-chevron-right text-warning"></i> 9. Contact Information</a>
                    </nav>

                    <hr class="my-4">

                    <div class="bg-light p-3 rounded-3" style="font-size:0.8rem;">
                        <strong class="d-block text-dark mb-1"><i class="bi bi-question-circle text-primary"></i> Need Help?</strong>
                        <p class="text-muted mb-2">Have questions about student data privacy or institutional compliance?</p>
                        <a href="{{ route('help.center') }}" class="btn btn-outline-primary btn-sm w-100">Visit Help Center</a>
                    </div>
                </div>
            </div>

            <!-- Policy Content Document -->
            <div class="col-lg-8">
                <div class="legal-content-card p-4 p-md-5 bg-white rounded-3 shadow-sm border" style="line-height: 1.75; font-size: 0.95rem; color: #334155;">
                    @if($document && !empty($document->content))
                        {!! $document->content !!}
                    @else
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> Policy document is currently being updated by the legal compliance department.
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
