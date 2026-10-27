@php
    $chatbotEnabled = \App\Models\PlatformSetting::get('chatbot_enabled', '1');
@endphp

@if($chatbotEnabled !== '0' && $chatbotEnabled !== false)
<!-- ========================================================
     CloudKazi Elimu - Floating AI Help Assistant Widget
     ======================================================== -->
<div id="elimu-ai-widget" class="elimu-ai-widget">
    <!-- Floating Trigger Button -->
    <button type="button" id="elimu-ai-trigger" class="elimu-ai-btn shadow-lg" aria-label="Open Elimu AI Assistant" title="Need help? Ask Elimu AI">
        <span class="ai-avatar-icon">
            <i class="bi bi-robot"></i>
        </span>
        <span class="ai-btn-text d-none d-md-inline ms-1">Need Help?</span>
        <span class="ai-online-beacon" title="Online"></span>
    </button>

    <!-- Chat Window Popup -->
    <div id="elimu-ai-window" class="elimu-ai-window shadow-lg d-none" role="dialog" aria-modal="true" aria-labelledby="elimu-ai-title">
        <!-- Header -->
        <div class="elimu-ai-header">
            <div class="d-flex align-items-center gap-2">
                <div class="ai-badge-avatar">
                    <i class="bi bi-robot"></i>
                </div>
                <div>
                    <h6 class="mb-0 fw-bold text-white" id="elimu-ai-title" style="font-size:0.95rem;">Elimu AI Assistant</h6>
                    <small class="text-white-50 d-flex align-items-center gap-1" style="font-size:0.75rem;">
                        <span class="beacon-dot"></span> CloudKazi Knowledge Engine
                    </small>
                </div>
            </div>
            <div class="d-flex align-items-center gap-1">
                <button type="button" class="btn btn-sm btn-icon text-white-50 hover-white" id="elimu-ai-clear" title="Clear chat conversation">
                    <i class="bi bi-arrow-clockwise"></i>
                </button>
                <button type="button" class="btn btn-sm btn-icon text-white-50 hover-white" id="elimu-ai-close" title="Close assistant">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        </div>

        <!-- Chat History -->
        <div class="elimu-ai-body" id="elimu-ai-messages">
            <!-- Initial Welcome Message -->
            <div class="chat-msg bot-msg">
                <div class="msg-avatar"><i class="bi bi-robot"></i></div>
                <div class="msg-content">
                    <p class="mb-1" id="elimu-ai-welcome-text">
                        Hello! 👋 I'm your <strong>CloudKazi Elimu</strong> assistant. Ask me how to use the system, navigate modules, or troubleshoot any school tasks.
                    </p>
                    <div class="msg-meta"><span class="badge bg-light text-dark">Ready</span></div>
                </div>
            </div>

            <!-- Suggested Quick Chips -->
            <div class="ai-suggestions-container my-2" id="elimu-ai-suggestions">
                <span class="text-muted d-block mb-1" style="font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">
                    Suggested Questions
                </span>
                <div class="d-flex flex-wrap gap-1" id="elimu-chips-list">
                    <button type="button" class="ai-chip" data-q="How do I register a student?">
                        <i class="bi bi-person-plus"></i> How do I register a student?
                    </button>
                    <button type="button" class="ai-chip" data-q="How do I record fees?">
                        <i class="bi bi-cash-stack"></i> How do I record fees?
                    </button>
                    <button type="button" class="ai-chip" data-q="How do I generate report cards?">
                        <i class="bi bi-file-earmark-bar-graph"></i> How do I generate report cards?
                    </button>
                    <button type="button" class="ai-chip" data-q="How do I generate automatic timetables?">
                        <i class="bi bi-calendar3-week"></i> Automatic Timetable
                    </button>
                </div>
            </div>

            <!-- Typing Indicator (Hidden by default) -->
            <div class="chat-msg bot-msg d-none" id="elimu-ai-typing">
                <div class="msg-avatar"><i class="bi bi-robot"></i></div>
                <div class="msg-content typing-bubble">
                    <span class="typing-dot"></span>
                    <span class="typing-dot"></span>
                    <span class="typing-dot"></span>
                </div>
            </div>
        </div>

        <!-- Input Bar -->
        <div class="elimu-ai-footer">
            <form id="elimu-ai-form" class="d-flex align-items-center gap-2" onsubmit="return false;">
                <input type="text" id="elimu-ai-input" class="form-control" placeholder="Ask a question about the system..." maxlength="300" autocomplete="off">
                <button type="submit" id="elimu-ai-send" class="btn btn-primary btn-sm px-3" aria-label="Send">
                    <i class="bi bi-send-fill"></i>
                </button>
            </form>
            <div class="d-flex justify-content-between align-items-center mt-1 px-1" style="font-size:0.72rem; color:var(--text-muted, #94a3b8);">
                <span><i class="bi bi-shield-lock"></i> Safe &amp; Private</span>
                <a href="{{ route('help.center') }}" target="_blank" style="color:var(--primary-light, #2a5291); text-decoration:none;">
                    Browse Knowledge Base &rarr;
                </a>
            </div>
        </div>
    </div>
</div>

<style>
/* -------- Elimu AI Widget CSS -------- */
.elimu-ai-widget {
    position: fixed;
    bottom: 24px;
    right: 24px;
    z-index: 9999;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
}
.elimu-ai-btn {
    background: linear-gradient(135deg, #1a3a5c 0%, #0f2240 100%);
    color: #ffffff;
    border: 2px solid rgba(232, 146, 42, 0.4);
    border-radius: 50px;
    padding: 12px 20px;
    font-weight: 600;
    font-size: 0.9rem;
    display: inline-flex;
    align-items: center;
    cursor: pointer;
    position: relative;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}
.elimu-ai-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px -5px rgba(26, 58, 92, 0.4), 0 0 0 3px rgba(232, 146, 42, 0.25) !important;
    border-color: #e8922a;
    color: #ffffff;
}
.elimu-ai-btn .ai-avatar-icon {
    font-size: 1.25rem;
    line-height: 1;
    color: #e8922a;
}
.ai-online-beacon {
    position: absolute;
    top: 4px;
    right: 4px;
    width: 11px;
    height: 11px;
    background-color: #10b981;
    border: 2px solid #ffffff;
    border-radius: 50%;
    animation: beaconPulse 2s infinite;
}
@keyframes beaconPulse {
    0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
    70% { transform: scale(1.05); box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
}

.elimu-ai-window.d-none {
    display: none !important;
}
.elimu-ai-window {
    width: 380px;
    height: 520px;
    max-height: calc(100vh - 100px);
    background: #ffffff;
    border-radius: 16px;
    border: 1px solid rgba(226, 232, 240, 0.9);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    position: absolute;
    bottom: calc(100% + 14px);
    right: 0;
    transition: transform 0.2s ease, opacity 0.2s ease;
    animation: slideUpFade 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}
@keyframes slideUpFade {
    from { opacity: 0; transform: translateY(12px) scale(0.98); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

/* Header */
.elimu-ai-header {
    background: linear-gradient(135deg, #0f2240 0%, #1a3a5c 100%);
    padding: 14px 16px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 2px solid #e8922a;
}
.ai-badge-avatar {
    width: 36px;
    height: 36px;
    background: rgba(232, 146, 42, 0.2);
    border: 1px solid rgba(232, 146, 42, 0.5);
    color: #e8922a;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.15rem;
}
.beacon-dot {
    width: 7px;
    height: 7px;
    background: #10b981;
    border-radius: 50%;
    display: inline-block;
}
.hover-white:hover { color: #ffffff !important; }

/* Body */
.elimu-ai-body {
    flex: 1;
    overflow-y: auto;
    padding: 16px;
    background: #f8fafc;
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.chat-msg {
    display: flex;
    gap: 10px;
    max-width: 88%;
}
.user-msg {
    align-self: flex-end;
    flex-direction: row-reverse;
}
.bot-msg {
    align-self: flex-start;
}
.msg-avatar {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: #1a3a5c;
    color: #e8922a;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
    flex-shrink: 0;
}
.user-msg .msg-avatar {
    background: #e8922a;
    color: #ffffff;
}
.msg-content {
    background: #ffffff;
    padding: 10px 14px;
    border-radius: 14px;
    border-top-left-radius: 4px;
    font-size: 0.85rem;
    line-height: 1.5;
    color: #1e293b;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}
.user-msg .msg-content {
    background: #1a3a5c;
    color: #ffffff;
    border-radius: 14px;
    border-top-right-radius: 4px;
    border: none;
}
.msg-meta {
    font-size: 0.72rem;
    margin-top: 4px;
}

/* Suggestion Chips */
.ai-suggestions-container {
    background: rgba(241, 245, 249, 0.8);
    border: 1px dashed #cbd5e1;
    border-radius: 12px;
    padding: 10px;
}
.ai-chip {
    background: #ffffff;
    border: 1px solid #cbd5e1;
    color: #1a3a5c;
    font-size: 0.76rem;
    font-weight: 500;
    padding: 5px 10px;
    border-radius: 20px;
    cursor: pointer;
    transition: all 0.15s ease;
    text-align: left;
}
.ai-chip:hover {
    background: #1a3a5c;
    color: #ffffff;
    border-color: #1a3a5c;
}
.ai-chip i { color: #e8922a; margin-right: 4px; }
.ai-chip:hover i { color: #ffffff; }

/* Typing animation */
.typing-bubble {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 8px 14px;
}
.typing-dot {
    width: 6px;
    height: 6px;
    background: #94a3b8;
    border-radius: 50%;
    animation: typingBlink 1.4s infinite ease-in-out both;
}
.typing-dot:nth-child(1) { animation-delay: -0.32s; }
.typing-dot:nth-child(2) { animation-delay: -0.16s; }
@keyframes typingBlink {
    0%, 80%, 100% { transform: scale(0); opacity: 0.4; }
    40% { transform: scale(1); opacity: 1; background: #e8922a; }
}

/* Footer */
.elimu-ai-footer {
    padding: 12px 14px;
    background: #ffffff;
    border-top: 1px solid #e2e8f0;
}
#elimu-ai-input {
    border-radius: 20px;
    font-size: 0.85rem;
    padding: 8px 14px;
    border: 1px solid #cbd5e1;
}
#elimu-ai-input:focus {
    border-color: #1a3a5c;
    box-shadow: 0 0 0 3px rgba(26, 58, 92, 0.15);
}
#elimu-ai-send {
    border-radius: 50%;
    width: 36px;
    height: 36px;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #e8922a;
    border-color: #e8922a;
}
#elimu-ai-send:hover {
    background: #c97820;
    border-color: #c97820;
}

/* Response Actions & Feedback */
.msg-feedback-bar {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 6px;
    font-size: 0.72rem;
    color: #64748b;
}
.feedback-btn {
    background: transparent;
    border: none;
    cursor: pointer;
    padding: 2px 4px;
    color: #94a3b8;
    transition: color 0.15s ease;
}
.feedback-btn:hover { color: #1a3a5c; }
.feedback-btn.active-helpful { color: #10b981; font-weight: bold; }
.feedback-btn.active-unhelpful { color: #ef4444; font-weight: bold; }

/* Mobile viewport adjustments */
@media (max-width: 576px) {
    .elimu-ai-widget {
        bottom: 16px;
        right: 16px;
    }
    .elimu-ai-window {
        width: calc(100vw - 32px);
        height: calc(100vh - 100px);
        right: 0;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const trigger = document.getElementById('elimu-ai-trigger');
    const win     = document.getElementById('elimu-ai-window');
    const close   = document.getElementById('elimu-ai-close');
    const clear   = document.getElementById('elimu-ai-clear');
    const form    = document.getElementById('elimu-ai-form');
    const input   = document.getElementById('elimu-ai-input');
    const messages= document.getElementById('elimu-ai-messages');
    const typing  = document.getElementById('elimu-ai-typing');
    const sendBtn = document.getElementById('elimu-ai-send');

    if (!trigger || !win) return;

    fetch('{{ route("ai-help.suggestions") }}', {
        headers: { 'Accept': 'application/json' }
    })
    .then(res => res.ok ? res.json() : null)
    .then(data => {
        if (!data) return;
        const welcome = document.getElementById('elimu-ai-welcome-text');
        if (welcome && data.welcome_message) {
            welcome.textContent = data.welcome_message;
        }
        const chips = document.getElementById('elimu-chips-list');
        if (chips && Array.isArray(data.suggestions) && data.suggestions.length) {
            chips.innerHTML = data.suggestions.map(function (q) {
                return '<button type="button" class="ai-chip" data-q="' + escapeHtml(q) + '">' + escapeHtml(q) + '</button>';
            }).join('');
        }
    })
    .catch(function () {});

    // Toggle Chat Window
    trigger.addEventListener('click', function () {
        const isHidden = win.classList.contains('d-none');
        if (isHidden) {
            win.classList.remove('d-none');
            input.focus();
            scrollToBottom();
        } else {
            win.classList.add('d-none');
        }
    });

    close.addEventListener('click', function () {
        win.classList.add('d-none');
    });

    // Clear Chat
    clear.addEventListener('click', function () {
        const firstMsg = messages.querySelector('.chat-msg');
        const chipsContainer = document.getElementById('elimu-ai-suggestions');
        messages.innerHTML = '';
        if (firstMsg) messages.appendChild(firstMsg);
        if (chipsContainer) messages.appendChild(chipsContainer);
        messages.appendChild(typing);
        scrollToBottom();
    });

    // Suggested Chips Click
    document.addEventListener('click', function (e) {
        const chip = e.target.closest('.ai-chip');
        if (chip) {
            const question = chip.getAttribute('data-q');
            if (question) {
                input.value = question;
                submitQuestion(question);
            }
        }
    });

    // Form Submit
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        const text = input.value.trim();
        if (text) {
            submitQuestion(text);
        }
    });

    function scrollToBottom() {
        setTimeout(() => {
            messages.scrollTop = messages.scrollHeight;
        }, 50);
    }

    function appendUserMessage(text) {
        const msgDiv = document.createElement('div');
        msgDiv.className = 'chat-msg user-msg';
        msgDiv.innerHTML = `
            <div class="msg-avatar"><i class="bi bi-person-fill"></i></div>
            <div class="msg-content">
                <p class="mb-0">${escapeHtml(text)}</p>
            </div>
        `;
        messages.insertBefore(msgDiv, typing);
        scrollToBottom();
    }

    function appendBotMessage(data) {
        const msgDiv = document.createElement('div');
        msgDiv.className = 'chat-msg bot-msg';

        let categoryBadge = data.category ? `<span class="badge bg-secondary-subtle text-secondary me-2">${escapeHtml(data.category)}</span>` : '';
        let feedbackHtml = '';

        if (data.log_id) {
            feedbackHtml = `
                <div class="msg-feedback-bar">
                    ${categoryBadge}
                    <span>Helpful?</span>
                    <button type="button" class="feedback-btn" onclick="rateAnswer(${data.log_id}, 1, this)" title="Yes, helpful">
                        <i class="bi bi-hand-thumbs-up"></i>
                    </button>
                    <button type="button" class="feedback-btn" onclick="rateAnswer(${data.log_id}, 0, this)" title="Not helpful">
                        <i class="bi bi-hand-thumbs-down"></i>
                    </button>
                </div>
            `;
        }

        let suggestionsHtml = '';
        if (data.suggestions && data.suggestions.length > 0) {
            suggestionsHtml = `
                <div class="mt-2 pt-2 border-top">
                    <small class="text-muted d-block mb-1 fw-bold">Related:</small>
                    <div class="d-flex flex-wrap gap-1">
                        ${data.suggestions.map(s => `<button type="button" class="ai-chip py-1 px-2" data-q="${escapeHtml(s)}">${escapeHtml(s)}</button>`).join('')}
                    </div>
                </div>
            `;
        }

        msgDiv.innerHTML = `
            <div class="msg-avatar"><i class="bi bi-robot"></i></div>
            <div class="msg-content">
                <p class="mb-0">${escapeHtml(data.answer).replace(/\n/g, '<br>')}</p>
                ${suggestionsHtml}
                ${feedbackHtml}
            </div>
        `;

        messages.insertBefore(msgDiv, typing);
        scrollToBottom();
    }

    function submitQuestion(questionText) {
        input.value = '';
        input.disabled = true;
        sendBtn.disabled = true;

        appendUserMessage(questionText);

        // Show typing indicator
        typing.classList.remove('d-none');
        scrollToBottom();

        const tokenMeta = document.querySelector('meta[name="csrf-token"]');
        const csrfToken = tokenMeta ? tokenMeta.getAttribute('content') : '';

        fetch('{{ route("ai-help.ask") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ question: questionText })
        })
        .then(response => {
            if (!response.ok) {
                if (response.status === 429) {
                    throw new Error('Too many requests. Please wait a moment before asking again.');
                }
                throw new Error('Could not retrieve an answer at this time.');
            }
            return response.json();
        })
        .then(data => {
            typing.classList.add('d-none');
            appendBotMessage(data);
        })
        .catch(err => {
            typing.classList.add('d-none');
            appendBotMessage({
                answer: err.message || 'I could not find an answer. Please contact your administrator.',
                category: 'Notice',
                suggestions: []
            });
        })
        .finally(() => {
            input.disabled = false;
            sendBtn.disabled = false;
            input.focus();
            scrollToBottom();
        });
    }

    function escapeHtml(string) {
        if (!string) return '';
        const entityMap = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;'
        };
        return String(string).replace(/[&<>"']/g, function (s) {
            return entityMap[s];
        });
    }

    // Global helper for feedback
    window.rateAnswer = function (logId, isHelpful, btnElem) {
        const tokenMeta = document.querySelector('meta[name="csrf-token"]');
        const csrfToken = tokenMeta ? tokenMeta.getAttribute('content') : '';

        fetch('{{ route("ai-help.feedback") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ log_id: logId, was_helpful: isHelpful })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const parent = btnElem.closest('.msg-feedback-bar');
                if (parent) {
                    parent.innerHTML = isHelpful 
                        ? '<span class="text-success"><i class="bi bi-check-circle"></i> Thanks for the feedback!</span>'
                        : '<span class="text-muted"><i class="bi bi-check-circle"></i> Feedback noted.</span>';
                }
            }
        });
    };
});
</script>
@endif
