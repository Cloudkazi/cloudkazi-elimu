/**
 * CloudKazi Elimu - Premium Glassmorphism Login Interactions
 * Vanilla JavaScript (No jQuery)
 */

document.addEventListener('DOMContentLoaded', () => {
    initPullCord();
    initPasswordToggle();
    initInputFocusEffects();
    initCard3DParallax();
    initButtonRipple();
    initMascotGaze();
});

/**
 * 1. Interactive Hanging Pull Cord
 * Clicking pulls down the cord, toggles high-intensity neon illumination,
 * and emits a pulse wave.
 */
function initPullCord() {
    const pullCord = document.getElementById('lampPullCord');
    const leftPanel = document.getElementById('glassLeftPanel');
    const loginCard = document.getElementById('glassLoginCard');
    const subtitleText = document.getElementById('loginSubtitle');

    if (!pullCord || !leftPanel) return;

    let isHighPower = false;

    pullCord.addEventListener('click', (e) => {
        e.preventDefault();

        // Cord spring animation
        pullCord.style.transform = 'translateY(22px)';
        playCyberClickSound();

        setTimeout(() => {
            pullCord.style.transform = 'translateY(0px)';
        }, 180);

        // Toggle illumination mode
        isHighPower = !isHighPower;

        if (isHighPower) {
            leftPanel.classList.add('lamp-illuminated');
            loginCard?.classList.add('hyper-glow');
            if (subtitleText) {
                subtitleText.innerHTML = '<i class="bi bi-lightning-charge-fill text-neon me-1"></i> Path illuminated! Maximum lumens engaged.';
            }
        } else {
            leftPanel.classList.remove('lamp-illuminated');
            loginCard?.classList.remove('hyper-glow');
            if (subtitleText) {
                subtitleText.innerHTML = '<i class="bi bi-lightbulb-fill text-neon me-1"></i> Pull the cord to illuminate your path';
            }
        }
    });
}

/**
 * Subtle synthesized cyber click sound using Web Audio API
 */
function playCyberClickSound() {
    try {
        const AudioContext = window.AudioContext || window.webkitAudioContext;
        if (!AudioContext) return;
        const ctx = new AudioContext();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();

        osc.type = 'sine';
        osc.frequency.setValueAtTime(880, ctx.currentTime);
        osc.frequency.exponentialRampToValueAtTime(220, ctx.currentTime + 0.08);

        gain.gain.setValueAtTime(0.12, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.08);

        osc.connect(gain);
        gain.connect(ctx.destination);

        osc.start();
        osc.stop(ctx.currentTime + 0.09);
    } catch (_) {
        // Audio might be restricted until user gesture; ignore gracefully
    }
}

/**
 * 2. Password Visibility Toggle
 */
function initPasswordToggle() {
    const toggleBtn = document.getElementById('passwordToggle');
    const passwordInput = document.getElementById('password');
    const toggleIcon = document.getElementById('passwordToggleIcon');

    if (!toggleBtn || !passwordInput) return;

    toggleBtn.addEventListener('click', () => {
        const isPassword = passwordInput.type === 'password';
        passwordInput.type = isPassword ? 'text' : 'password';

        if (toggleIcon) {
            toggleIcon.className = isPassword ? 'bi bi-eye-slash' : 'bi bi-eye';
            toggleBtn.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
        }
    });
}

/**
 * 3. Input Group Focus Glow Animations
 */
function initInputFocusEffects() {
    const inputs = document.querySelectorAll('.glass-input');

    inputs.forEach(input => {
        const group = input.closest('.glass-input-group');
        if (!group) return;

        input.addEventListener('focus', () => {
            group.classList.add('is-focused');
        });

        input.addEventListener('blur', () => {
            group.classList.remove('is-focused');
        });
    });
}

/**
 * 4. 3D Mouse Movement Parallax Tilt
 * Softly tilts the glass card in 3D space based on mouse position.
 */
function initCard3DParallax() {
    const card = document.getElementById('glassLoginCard');
    if (!card) return;

    // Only apply on non-touch desktop screens
    if (window.matchMedia('(pointer: coarse)').matches) return;

    let targetRotX = 0;
    let targetRotY = 0;
    let currentRotX = 0;
    let currentRotY = 0;
    let rafId = null;

    function onMouseMove(e) {
        const rect = card.getBoundingClientRect();
        const cardCenterX = rect.left + rect.width / 2;
        const cardCenterY = rect.top + rect.height / 2;

        const deltaX = (e.clientX - cardCenterX) / (window.innerWidth / 2);
        const deltaY = (e.clientY - cardCenterY) / (window.innerHeight / 2);

        // Clamp rotation angles for a sleek, subtle feel
        targetRotY = Math.max(-6, Math.min(6, deltaX * 6));
        targetRotX = Math.max(-6, Math.min(6, -deltaY * 6));

        if (!rafId) {
            rafId = requestAnimationFrame(updateTilt);
        }
    }

    function updateTilt() {
        currentRotX += (targetRotX - currentRotX) * 0.1;
        currentRotY += (targetRotY - currentRotY) * 0.1;

        card.style.transform = `rotateX(${currentRotX.toFixed(2)}deg) rotateY(${currentRotY.toFixed(2)}deg)`;

        if (Math.abs(targetRotX - currentRotX) > 0.01 || Math.abs(targetRotY - currentRotY) > 0.01) {
            rafId = requestAnimationFrame(updateTilt);
        } else {
            rafId = null;
        }
    }

    function onMouseLeave() {
        targetRotX = 0;
        targetRotY = 0;
        if (!rafId) {
            rafId = requestAnimationFrame(updateTilt);
        }
    }

    window.addEventListener('mousemove', onMouseMove, { passive: true });
    window.addEventListener('mouseleave', onMouseLeave);
}

/**
 * 5. Neon Button Click Ripple Effect
 */
function initButtonRipple() {
    const submitBtn = document.getElementById('submitBtn');
    if (!submitBtn) return;

    submitBtn.addEventListener('click', function (e) {
        const rect = this.getBoundingClientRect();
        const circle = document.createElement('span');
        const diameter = Math.max(rect.width, rect.height);
        const radius = diameter / 2;

        circle.style.width = circle.style.height = `${diameter}px`;
        circle.style.left = `${e.clientX - rect.left - radius}px`;
        circle.style.top = `${e.clientY - rect.top - radius}px`;
        circle.classList.add('ripple-circle');

        const existingRipple = this.querySelector('.ripple-circle');
        if (existingRipple) {
            existingRipple.remove();
        }

        this.appendChild(circle);

        setTimeout(() => {
            circle.remove();
        }, 600);
    });
}

/**
 * 6. Interactive Mascot Eyes & Gaze Tracking
 */
function initMascotGaze() {
    const mascot = document.getElementById('mascotAvatar');
    const eyes = document.querySelectorAll('.mascot-eye');

    if (!mascot || eyes.length === 0) return;

    window.addEventListener('mousemove', (e) => {
        const rect = mascot.getBoundingClientRect();
        const mascotX = rect.left + rect.width / 2;
        const mascotY = rect.top + rect.height / 2;

        const angle = Math.atan2(e.clientY - mascotY, e.clientX - mascotX);
        const distance = Math.min(3, Math.hypot(e.clientX - mascotX, e.clientY - mascotY) / 100);

        const offsetX = Math.cos(angle) * distance;
        const offsetY = Math.sin(angle) * distance;

        eyes.forEach(eye => {
            eye.style.transform = `translate(${offsetX.toFixed(1)}px, ${offsetY.toFixed(1)}px)`;
        });
    }, { passive: true });

    // Mascot click fun interaction
    mascot.addEventListener('click', () => {
        mascot.style.transform = 'scale(1.15) rotate(-5deg)';
        playCyberClickSound();
        setTimeout(() => {
            mascot.style.transform = '';
        }, 300);
    });
}

/**
 * 7. Demo Account Autofill Helper
 */
window.fillDemo = function (email, password) {
    const emailField = document.getElementById('email');
    const passwordField = document.getElementById('password');

    if (emailField) {
        emailField.value = email;
        const group = emailField.closest('.glass-input-group');
        group?.classList.add('is-focused');
        setTimeout(() => group?.classList.remove('is-focused'), 600);
    }

    if (passwordField) {
        passwordField.value = password;
        const group = passwordField.closest('.glass-input-group');
        group?.classList.add('is-focused');
        setTimeout(() => group?.classList.remove('is-focused'), 600);
    }

    // Optional toast notification if toast system exists
    if (typeof showToast === 'function') {
        showToast(`Filled credentials for ${email}`, 'info');
    }
};
