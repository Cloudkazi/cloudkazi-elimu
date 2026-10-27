@extends('layouts.guest')
@section('title', 'CloudKazi Elimu — Smart School Management System')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/login.css') }}">
@endpush

@section('content')
<div class="login-page-wrapper">

    <!-- Futuristic Cyber Background & Atmospheric Layers -->
    <div class="cyber-bg-viewport" aria-hidden="true">
        <div class="cyber-water-droplets"></div>
        <div class="cyber-grid-overlay"></div>
        <div class="cyber-glow-orb-top"></div>
        <div class="cyber-glow-orb-bottom"></div>
        <div class="cyber-vignette"></div>
    </div>

    <!-- Main SaaS Landing Page Grid Container -->
    <div class="landing-saas-container container-fluid px-3 px-md-4 px-xl-5">
        <div class="row align-items-center g-4 g-xl-5">

            <!-- ====================================================
                 LEFT MARKETING SECTION (~50% Screen on Desktop)
                 ==================================================== -->
            <div class="col-12 col-lg-6 landing-marketing-col">
                <!-- Platform Badge -->
                <div class="landing-badge">
                    <span class="landing-badge-dot" aria-hidden="true"></span>
                    <span>Next-Gen EdTech for Kenya 🇰🇪</span>
                </div>

                <!-- Brand Header & Tagline -->
                <h1 class="landing-brand-title">
                    CloudKazi <span class="text-neon">Elimu</span>
                </h1>
                <h2 class="landing-tagline">
                    Smart School Management System
                </h2>

                <!-- Description -->
                <p class="landing-description">
                    CloudKazi Elimu is a comprehensive school management platform designed for Kenyan schools. Manage students, academics, finance, examinations, communication, and reporting from a single centralized system.
                </p>

                <!-- Feature Cards (8 Pillars) -->
                <div class="landing-feature-grid" role="list" aria-label="Key Platform Features">
                    <div class="landing-feature-card" role="listitem">
                        <span class="feature-card-icon" aria-hidden="true">🎓</span>
                        <span class="feature-card-title">Student Information Management</span>
                    </div>
                    <div class="landing-feature-card" role="listitem">
                        <span class="feature-card-icon" aria-hidden="true">📚</span>
                        <span class="feature-card-title">Academic &amp; CBC Management</span>
                    </div>
                    <div class="landing-feature-card" role="listitem">
                        <span class="feature-card-icon" aria-hidden="true">💰</span>
                        <span class="feature-card-title">Fee Collection &amp; Finance Tracking</span>
                    </div>
                    <div class="landing-feature-card" role="listitem">
                        <span class="feature-card-icon" aria-hidden="true">📅</span>
                        <span class="feature-card-title">Intelligent Timetable Generator</span>
                    </div>
                    <div class="landing-feature-card" role="listitem">
                        <span class="feature-card-icon" aria-hidden="true">📝</span>
                        <span class="feature-card-title">Examinations &amp; Report Cards</span>
                    </div>
                    <div class="landing-feature-card" role="listitem">
                        <span class="feature-card-icon" aria-hidden="true">👨‍👩‍👧</span>
                        <span class="feature-card-title">Parent &amp; Student Portals</span>
                    </div>
                    <div class="landing-feature-card" role="listitem">
                        <span class="feature-card-icon" aria-hidden="true">📊</span>
                        <span class="feature-card-title">Analytics &amp; School Reports</span>
                    </div>
                    <div class="landing-feature-card" role="listitem">
                        <span class="feature-card-icon" aria-hidden="true">📱</span>
                        <span class="feature-card-title">Mobile Friendly Access</span>
                    </div>
                </div>

                <!-- Statistics Section -->
                <div class="landing-stats-grid" aria-label="Platform Highlights">
                    <div class="stat-item">
                        <div class="stat-value">100%</div>
                        <div class="stat-label">Cloud Based</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">CBC &amp; 8-4-4</div>
                        <div class="stat-label">Full Support</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">Multi-School</div>
                        <div class="stat-label">Enterprise Ready</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">RBAC</div>
                        <div class="stat-label">Role-Based Access</div>
                    </div>
                </div>

                <!-- Call to Action Section -->
                <div class="landing-cta-banner">
                    <p class="landing-cta-text">
                        <i class="bi bi-stars cta-sparkle" aria-hidden="true"></i>
                        <span>Start managing your school efficiently today.</span>
                    </p>
                    <a href="{{ route('register') }}" class="btn-landing-cta" id="ctaRegisterBtn">
                        <span>Get Started</span>
                        <i class="bi bi-arrow-right" aria-hidden="true"></i>
                    </a>
                </div>
            </div>

            <!-- ====================================================
                 RIGHT LOGIN CARD SECTION (~50% Screen on Desktop)
                 ==================================================== -->
            <div class="col-12 col-lg-6 d-flex justify-content-center landing-login-col">
                <div class="glass-card-perspective">
                    <!-- Main Glassmorphism Authentication Card -->
                    <main class="glass-login-card" id="glassLoginCard" role="main">
                        <div class="glass-reflection-overlay" aria-hidden="true"></div>

                        <!-- ----------------------------------------
                             LEFT PANEL (40%): Lamp, Beam & Mascot
                             ---------------------------------------- -->
                        <section class="glass-left-panel" id="glassLeftPanel" aria-label="Futuristic Illumination Chamber">
                            <div class="left-chamber-bg" aria-hidden="true"></div>

                            <!-- Ceiling Anchor -->
                            <div class="lamp-ceiling-mount" aria-hidden="true"></div>

                            <!-- Hanging Lamp Assembly -->
                            <div class="lamp-assembly">
                                <div class="lamp-cable" aria-hidden="true"></div>

                                <!-- Interactive Pull Cord -->
                                <div class="lamp-pull-cord-wrap" id="lampPullCord" role="button" tabindex="0" title="Pull cord to illuminate" aria-label="Pull cord to illuminate">
                                    <div class="lamp-pull-cord-line"></div>
                                    <div class="lamp-pull-cord-bead"></div>
                                </div>

                                <!-- Industrial Cyber Lamp Shade -->
                                <div class="lamp-shade" aria-hidden="true">
                                    <div class="lamp-shade-rim"></div>
                                </div>

                                <!-- Glowing Neon Bulb / Emitter -->
                                <div class="lamp-bulb" aria-hidden="true"></div>
                            </div>

                            <!-- Cone-Shaped Animated Light Beam -->
                            <div class="light-beam-cone" aria-hidden="true"></div>
                            <div class="light-beam-cone-core" aria-hidden="true"></div>

                            <!-- Floating Light Motes inside Beam -->
                            <div class="light-particles-container" aria-hidden="true">
                                <span class="beam-mote"></span>
                                <span class="beam-mote"></span>
                                <span class="beam-mote"></span>
                                <span class="beam-mote"></span>
                            </div>

                            <!-- Floating Circular Mascot Avatar -->
                            <div class="mascot-stage">
                                <div class="mascot-avatar" id="mascotAvatar" title="Elimu Mascot • Click me!" role="img" aria-label="CloudKazi Elimu Mascot Avatar">
                                    <div class="mascot-antenna" aria-hidden="true">
                                        <span class="mascot-antenna-beacon"></span>
                                    </div>
                                    <div class="mascot-face">
                                        <div class="mascot-eyes">
                                            <span class="mascot-eye"></span>
                                            <span class="mascot-eye"></span>
                                        </div>
                                        <div class="mascot-cheeks" aria-hidden="true">
                                            <span class="mascot-cheek"></span>
                                            <span class="mascot-cheek"></span>
                                        </div>
                                        <div class="mascot-mouth" aria-hidden="true"></div>
                                    </div>
                                </div>
                                <div class="mascot-floor-shadow" aria-hidden="true"></div>
                            </div>

                            <!-- Left Panel Brand Tag -->
                            <div class="left-brand-caption">
                                <span class="brand-dot"></span> CloudKazi Elimu
                            </div>
                        </section>

                        <!-- ----------------------------------------
                             RIGHT PANEL (60%): Authentication Form
                             ---------------------------------------- -->
                        <section class="glass-right-panel" aria-labelledby="loginHeading">
                            <!-- Header -->
                            <header class="auth-header">
                                <h2 class="login-title" id="loginHeading">
                                    <span class="text-welcome">Welcome</span> <span class="text-back">Back.</span>
                                </h2>
                                <p class="login-subtitle" id="loginSubtitle">
                                    <i class="bi bi-lightbulb-fill hint-icon" aria-hidden="true"></i> Pull the cord to illuminate your path
                                </p>
                            </header>

                            <div class="login-credentials-help" role="note" aria-label="Student and parent login details">
                                <div><strong>Students:</strong> Username: admission number. Password: parent/guardian's registered phone number.</div>
                                <div><strong>Parents:</strong> Username: registered email. Password: your registered phone number.</div>
                            </div>

                            <!-- Feedback & Status Alerts -->
                            @if(session('error'))
                                <div class="auth-glass-alert alert-danger" role="alert">
                                    <i class="bi bi-exclamation-triangle-fill"></i>
                                    <span>{{ session('error') }}</span>
                                </div>
                            @endif

                            @if(session('success'))
                                <div class="auth-glass-alert alert-success" role="alert">
                                    <i class="bi bi-check-circle-fill"></i>
                                    <span>{{ session('success') }}</span>
                                </div>
                            @endif

                            <!-- Form -->
                            <form action="{{ route('login') }}" method="POST" class="auth-form" id="loginForm" novalidate>
                                @csrf

                                <!-- Email / Admission Number Field -->
                                <div class="form-group-field">
                                    <label for="email" class="field-label">Email or admission number</label>
                                    <div class="glass-input-group">
                                        <span class="input-icon-lead" aria-hidden="true">
                                            <i class="bi bi-person"></i>
                                        </span>
                                        <input type="text" 
                                               name="email" 
                                               id="email" 
                                               class="glass-input @error('email') is-invalid @enderror" 
                                               placeholder="staff@school.ac.ke or adm no." 
                                               value="{{ old('email') }}" 
                                               required 
                                               autocomplete="username"
                                               autofocus>
                                    </div>
                                    @error('email')
                                        <div class="field-error-msg" role="alert">
                                            <i class="bi bi-exclamation-circle-fill"></i> {{ $message }}
                                        </div>
                                    @enderror
                                </div>

                                <!-- Password Field -->
                                <div class="form-group-field">
                                    <div class="field-label">
                                        <label for="password">Password</label>
                                        <a href="{{ route('password.request') }}" class="forgot-link">Forgot password?</a>
                                    </div>
                                    <div class="glass-input-group">
                                        <span class="input-icon-lead" aria-hidden="true">
                                            <i class="bi bi-lock"></i>
                                        </span>
                                        <input type="password" 
                                               name="password" 
                                               id="password" 
                                               class="glass-input @error('password') is-invalid @enderror" 
                                               placeholder="Enter your password" 
                                               required
                                               autocomplete="current-password">
                                        <button type="button" 
                                                class="password-toggle-btn" 
                                                id="passwordToggle" 
                                                aria-label="Show password">
                                            <i class="bi bi-eye" id="passwordToggleIcon"></i>
                                        </button>
                                    </div>
                                    @error('password')
                                        <div class="field-error-msg" role="alert">
                                            <i class="bi bi-exclamation-circle-fill"></i> {{ $message }}
                                        </div>
                                    @enderror
                                </div>

                                <!-- Remember Me Option -->
                                <div class="remember-row">
                                    <label class="cyber-checkbox-label" for="remember">
                                        <input type="checkbox" name="remember" id="remember" class="cyber-checkbox-input" {{ old('remember') ? 'checked' : '' }}>
                                        <span>Remember me</span>
                                    </label>
                                </div>

                                <!-- Submit Button -->
                                <button type="submit" class="btn-neon-submit" id="submitBtn">
                                    <span>Sign In</span>
                                    <i class="bi bi-arrow-right btn-arrow-icon" aria-hidden="true"></i>
                                </button>

                                <!-- Restore Registration Functionality -->
                                <div class="auth-register-prompt">
                                    <span class="register-prompt-text">Don't have an account?</span>
                                    <a href="{{ route('register') }}" class="btn-create-school-account" id="createSchoolAccountBtn">
                                        <i class="bi bi-building-add me-2" aria-hidden="true"></i> Create School Account
                                    </a>
                                </div>
                            </form>

                            <!-- Social Login Options -->
                            <div class="social-divider" aria-hidden="true">
                                <span class="social-divider-text">or continue with</span>
                            </div>

                            <div class="social-grid">
                                <!-- Google Button -->
                                <button type="button" class="btn-social-glass" aria-label="Sign in with Google">
                                    <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true">
                                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                                    </svg>
                                    <span>Google</span>
                                </button>

                                <!-- GitHub Button -->
                                <button type="button" class="btn-social-glass" aria-label="Sign in with GitHub">
                                    <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true">
                                        <path d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57A12.02 12.02 0 0024 12c0-6.63-5.37-12-12-12z"/>
                                    </svg>
                                    <span>GitHub</span>
                                </button>
                            </div>

                            <!-- Footer Prompt -->
                            <div class="auth-footer-prompt">
                                Sign up at <a href="https://g.page/r/review" target="_blank" rel="noopener noreferrer" class="neon-link">Google reviews</a>
                            </div>

                            <!-- Quick Demo Accounts Drawer -->
                            <div class="demo-quick-bar">
                                <div class="demo-bar-title">
                                    <span><i class="bi bi-shield-check"></i> Demo Quick Login</span>
                                    <small style="color:var(--text-muted);font-size:0.72rem;">Click role to fill</small>
                                </div>
                                <div class="demo-chips">
                                    <button type="button" class="demo-chip" onclick="fillDemo('admin@demo.com','Password@123!')">
                                        <span>🏫 Admin</span>
                                    </button>
                                    <button type="button" class="demo-chip" onclick="fillDemo('teacher@demo.com','Password@123!')">
                                        <span>📚 Teacher</span>
                                    </button>
                                    <button type="button" class="demo-chip" onclick="fillDemo('student@demo.com','Password@123!')">
                                        <span>🎓 Student</span>
                                    </button>
                                    <button type="button" class="demo-chip" onclick="fillDemo('parent@demo.com','Password@123!')">
                                        <span>👨‍👩‍👧 Parent</span>
                                    </button>
                                </div>
                            </div>
                        </section>
                    </main>
                </div>
            </div>

        </div>

        <!-- ====================================================
             GLOBAL FOOTER SECTION
             ==================================================== -->
        <footer class="landing-global-footer" role="contentinfo">
            <p class="footer-brand-line">
                CloudKazi Elimu &copy; {{ date('Y') }} &nbsp;|&nbsp; School Management System for Kenyan Schools 🇰🇪
            </p>
            <div class="footer-nav-links">
                <a href="{{ route('privacy.policy') }}" class="footer-nav-link">
                    <i class="bi bi-shield-check"></i> Privacy Policy
                </a>
                <span style="color: rgba(255,255,255,0.2);">&bull;</span>
                <a href="{{ route('terms.conditions') }}" class="footer-nav-link">
                    <i class="bi bi-file-earmark-text"></i> Terms &amp; Conditions
                </a>
                <span style="color: rgba(255,255,255,0.2);">&bull;</span>
                <a href="{{ route('help.center') }}" class="footer-nav-link">
                    <i class="bi bi-question-circle"></i> Help Center
                </a>
                <span style="color: rgba(255,255,255,0.2);">&bull;</span>
                <a href="{{ route('register') }}" class="footer-nav-link" style="color: var(--neon-green-bright);">
                    <i class="bi bi-building"></i> Register School
                </a>
            </div>
        </footer>
    </div>
</div>

@include('partials.ai-chatbot')

@push('scripts')
<script>
    document.body.classList.add('auth-glass-body');
</script>
<script src="{{ asset('assets/js/login.js') }}"></script>
@endpush
@endsection
