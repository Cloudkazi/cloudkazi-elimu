@extends('layouts.guest')
@section('title', 'Help Center & Knowledge Base — CloudKazi Elimu')

@section('content')
<div class="help-center-wrapper py-5">
    <div class="container" style="max-width: 1040px; margin: 0 auto; padding: 0 20px;">
        
        <!-- Search Hero Section -->
        <div class="p-4 p-md-5 rounded-4 shadow-sm mb-4 text-center text-white" style="background: linear-gradient(135deg, #0f2240 0%, #1a3a5c 100%); border-bottom: 3px solid #e8922a;">
            <div class="d-inline-flex align-items-center gap-2 mb-2">
                <span class="badge" style="background: #e8922a; color:#fff; font-weight:600;">KNOWLEDGE BASE</span>
                <span class="badge bg-secondary">System Guides &amp; FAQs</span>
            </div>
            <h1 class="h2 fw-bold text-white mb-2">How can we help you today?</h1>
            <p class="text-white-50 mb-4" style="max-width: 600px; margin: 0 auto;">
                Search our comprehensive guides on student admissions, fee payments, CBC grading, report cards, and timetable scheduling.
            </p>

            <!-- Search Form -->
            <form action="{{ route('help.center') }}" method="GET" class="d-flex justify-content-center" style="max-width: 640px; margin: 0 auto;">
                @if(!empty($category))
                    <input type="hidden" name="category" value="{{ $category }}">
                @endif
                <div class="input-group shadow">
                    <span class="input-group-text bg-white border-0 ps-3 text-muted">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" name="search" class="form-control border-0 py-3" placeholder="Search keywords (e.g. register student, fee payment, report cards)..." value="{{ $search ?? '' }}">
                    @if(!empty($search))
                        <a href="{{ route('help.center', ['category' => $category]) }}" class="input-group-text bg-white border-0 text-muted" title="Clear search">
                            <i class="bi bi-x-circle"></i>
                        </a>
                    @endif
                    <button class="btn btn-warning px-4 fw-semibold text-white" type="submit" style="background:#e8922a; border-color:#e8922a;">
                        Search
                    </button>
                </div>
            </form>

            <!-- Quick Suggestions -->
            <div class="mt-3 d-flex flex-wrap justify-content-center gap-2 align-items-center" style="font-size: 0.8rem;">
                <span class="text-white-50">Popular searches:</span>
                <a href="{{ route('help.center', ['search' => 'register student']) }}" class="badge text-white bg-white-10 text-decoration-none">Register student</a>
                <a href="{{ route('help.center', ['search' => 'fee payments']) }}" class="badge text-white bg-white-10 text-decoration-none">Fee payments</a>
                <a href="{{ route('help.center', ['search' => 'cbc assessment']) }}" class="badge text-white bg-white-10 text-decoration-none">CBC assessment</a>
                <a href="{{ route('help.center', ['search' => 'report cards']) }}" class="badge text-white bg-white-10 text-decoration-none">Report cards</a>
            </div>
        </div>

        <!-- Category Pills Filter -->
        <div class="category-pills-bar d-flex flex-wrap gap-2 mb-4 justify-content-start align-items-center pb-2 overflow-x-auto">
            <a href="{{ route('help.center', ['search' => $search]) }}" class="btn btn-sm {{ empty($category) ? 'btn-primary' : 'btn-outline-secondary bg-white' }}" style="{{ empty($category) ? 'background:#1a3a5c; border-color:#1a3a5c;' : '' }}">
                <i class="bi bi-grid-fill"></i> All Topics
            </a>
            @foreach($categories as $catKey => $catLabel)
                <a href="{{ route('help.center', ['category' => $catKey, 'search' => $search]) }}" class="btn btn-sm {{ $category === $catKey ? 'btn-primary' : 'btn-outline-secondary bg-white' }}" style="{{ $category === $catKey ? 'background:#1a3a5c; border-color:#1a3a5c;' : '' }}">
                    {{ $catKey }}
                </a>
            @endforeach
        </div>

        <div class="row g-4">
            <!-- Main Content Area -->
            <div class="col-lg-8">
                @if(!empty($search) || !empty($category))
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="text-muted mb-0">
                            Showing results 
                            @if(!empty($search)) for "<strong>{{ $search }}</strong>" @endif
                            @if(!empty($category)) in <strong>{{ $category }}</strong> @endif
                            ({{ $articles->total() }})
                        </h6>
                        <a href="{{ route('help.center') }}" class="btn btn-sm btn-link text-decoration-none p-0 text-muted">Clear filters</a>
                    </div>
                @endif

                @if($articles->count() > 0)
                    <div class="accordion help-accordion d-flex flex-column gap-3" id="helpAccordion">
                        @foreach($articles as $index => $article)
                            <div class="accordion-item rounded-3 shadow-sm border overflow-hidden">
                                <h2 class="accordion-header" id="heading-{{ $article->id }}">
                                    <button class="accordion-button {{ $index !== 0 ? 'collapsed' : '' }} py-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-{{ $article->id }}" aria-expanded="{{ $index === 0 ? 'true' : 'false' }}" aria-controls="collapse-{{ $article->id }}">
                                        <div class="d-flex flex-column align-items-start gap-1 pe-2">
                                            <span class="badge bg-light text-primary border" style="font-size:0.7rem; font-weight:600;">
                                                {{ $article->category }}
                                            </span>
                                            <span class="fw-semibold text-dark" style="font-size:0.95rem;">
                                                {{ $article->question }}
                                            </span>
                                        </div>
                                    </button>
                                </h2>
                                <div id="collapse-{{ $article->id }}" class="accordion-collapse collapse {{ $index === 0 ? 'show' : '' }}" aria-labelledby="heading-{{ $article->id }}" data-bs-parent="#helpAccordion">
                                    <div class="accordion-body pt-2 pb-3 text-secondary" style="font-size:0.92rem; line-height:1.7;">
                                        <p class="mb-2">{{ $article->answer }}</p>
                                        @if($article->keywords)
                                            <div class="mt-2 pt-2 border-top d-flex flex-wrap gap-1 align-items-center">
                                                <small class="text-muted me-1" style="font-size:0.75rem;"><i class="bi bi-tags"></i> Related keywords:</small>
                                                @foreach(array_slice(explode(',', $article->keywords), 0, 5) as $kw)
                                                    <span class="badge bg-secondary-subtle text-secondary" style="font-size:0.72rem;">{{ trim($kw) }}</span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Pagination -->
                    <div class="mt-4 d-flex justify-content-center">
                        {{ $articles->links() }}
                    </div>
                @else
                    <div class="card p-5 text-center bg-white rounded-3 shadow-sm border">
                        <div class="mb-3 text-warning display-5">
                            <i class="bi bi-search"></i>
                        </div>
                        <h5 class="fw-bold">No help articles found</h5>
                        <p class="text-muted mb-3">We couldn't find any articles matching your search query. You can ask our AI Help Assistant or try searching other keywords.</p>
                        <div>
                            <button type="button" class="btn btn-warning text-white fw-semibold" onclick="document.getElementById('elimu-ai-trigger').click()">
                                <i class="bi bi-robot"></i> Ask Elimu AI Assistant
                            </button>
                            <a href="{{ route('help.center') }}" class="btn btn-outline-secondary ms-2">Reset Search</a>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Right Column Cards -->
            <div class="col-lg-4">
                <!-- AI Assistant Promo Card -->
                <div class="card mb-4 rounded-3 shadow-sm border overflow-hidden" style="background: linear-gradient(135deg, #1a3a5c 0%, #0f2240 100%); color: #ffffff;">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <div class="p-2 rounded-circle" style="background: rgba(232, 146, 42, 0.2); color: #e8922a;">
                                <i class="bi bi-robot fs-4"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold text-white">Elimu AI Assistant</h6>
                                <small class="text-white-50">Instant Answers 24/7</small>
                            </div>
                        </div>
                        <p class="text-white-50 mb-3" style="font-size:0.85rem;">
                            Get immediate step-by-step guidance on registering students, collecting M-Pesa fees, setting timetable rules, and printing report cards.
                        </p>
                        <button type="button" class="btn btn-warning w-100 fw-semibold text-white" onclick="document.getElementById('elimu-ai-trigger').click()" style="background:#e8922a; border-color:#e8922a;">
                            <i class="bi bi-chat-dots-fill"></i> Ask a Question Now
                        </button>
                    </div>
                </div>

                <!-- Popular Articles Card -->
                <div class="card rounded-3 shadow-sm border bg-white mb-4">
                    <div class="card-header bg-transparent py-3 border-bottom">
                        <h6 class="mb-0 fw-bold text-dark" style="font-size:0.9rem;">
                            <i class="bi bi-star-fill text-warning me-1"></i> Frequently Asked Questions
                        </h6>
                    </div>
                    <div class="list-group list-group-flush">
                        @foreach($popularArticles as $pop)
                            <a href="{{ route('help.center', ['search' => $pop->question]) }}" class="list-group-item list-group-item-action py-3">
                                <div class="d-flex w-100 justify-content-between align-items-center mb-1">
                                    <small class="badge bg-light text-primary border">{{ $pop->category }}</small>
                                    <small class="text-muted" style="font-size:0.75rem;"><i class="bi bi-eye"></i> {{ $pop->view_count }}</small>
                                </div>
                                <div class="text-dark fw-medium" style="font-size:0.86rem;">
                                    {{ $pop->question }}
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>

                <!-- Contact Support Info -->
                <div class="card rounded-3 shadow-sm border bg-white p-3">
                    <h6 class="fw-bold mb-2" style="font-size:0.88rem;"><i class="bi bi-headset text-primary me-1"></i> Need Dedicated Support?</h6>
                    <p class="text-muted mb-2" style="font-size:0.82rem;">Our technical support desk is available Monday through Friday (8:00 AM – 5:00 PM EAT).</p>
                    <div class="d-flex flex-column gap-1" style="font-size:0.82rem;">
                        <span class="text-muted"><i class="bi bi-envelope"></i> support@cloudkazi.co.ke</span>
                        <span class="text-muted"><i class="bi bi-telephone"></i> +254 700 000 000</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer Links Partial -->
        @include('partials.footer-links')

    </div>
</div>

<!-- Floating AI Chatbot Widget -->
@include('partials.ai-chatbot')

<style>
.bg-white-10 {
    background-color: rgba(255, 255, 255, 0.15);
    transition: background 0.15s ease;
}
.bg-white-10:hover {
    background-color: rgba(255, 255, 255, 0.3);
}
.help-accordion .accordion-button:not(.collapsed) {
    background-color: #f8fafc;
    color: var(--primary, #1a3a5c);
    box-shadow: none;
}
.help-accordion .accordion-button:focus {
    box-shadow: none;
    border-color: rgba(0,0,0,0.125);
}
</style>
@endsection
