<?php

namespace App\Http\Controllers;

use App\Models\ChatbotLog;
use App\Models\HelpArticle;
use App\Models\LegalDocument;
use App\Models\PlatformSetting;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SuperAdminLegalController extends Controller
{
    /**
     * Guard: Ensure the authenticated user is Super Admin.
     */
    protected function requireSuperAdmin(): void
    {
        if (!Auth::user()?->isSuperAdmin()) {
            abort(403, 'This area is restricted to platform administrators.');
        }
    }

    // ─── PRIVACY POLICY MANAGEMENT ───────────────────────────────────────────

    public function privacyEdit()
    {
        $this->requireSuperAdmin();

        $document = LegalDocument::where('type', LegalDocument::TYPE_PRIVACY_POLICY)->first();

        return view('superadmin.legal.privacy', compact('document'));
    }

    public function privacyUpdate(Request $request)
    {
        $this->requireSuperAdmin();

        $validated = $request->validate([
            'title'     => 'required|string|max:255',
            'version'   => 'required|string|max:32',
            'is_active' => 'required|boolean',
            'content'   => 'required|string',
        ]);

        $doc = LegalDocument::updateOrCreate(
            ['type' => LegalDocument::TYPE_PRIVACY_POLICY],
            $validated
        );

        AuditLogService::log('legal.privacy_policy_updated', 'platform', 'Updated Privacy Policy (v' . $doc->version . ')');

        return back()->with('success', 'Privacy Policy updated successfully.');
    }

    // ─── TERMS & CONDITIONS MANAGEMENT ────────────────────────────────────────

    public function termsEdit()
    {
        $this->requireSuperAdmin();

        $document = LegalDocument::where('type', LegalDocument::TYPE_TERMS_CONDITIONS)->first();

        return view('superadmin.legal.terms', compact('document'));
    }

    public function termsUpdate(Request $request)
    {
        $this->requireSuperAdmin();

        $validated = $request->validate([
            'title'     => 'required|string|max:255',
            'version'   => 'required|string|max:32',
            'is_active' => 'required|boolean',
            'content'   => 'required|string',
        ]);

        $doc = LegalDocument::updateOrCreate(
            ['type' => LegalDocument::TYPE_TERMS_CONDITIONS],
            $validated
        );

        AuditLogService::log('legal.terms_updated', 'platform', 'Updated Terms & Conditions (v' . $doc->version . ')');

        return back()->with('success', 'Terms and Conditions updated successfully.');
    }

    // ─── HELP ARTICLES (KNOWLEDGE BASE) MANAGEMENT ───────────────────────────

    public function articlesIndex(Request $request)
    {
        $this->requireSuperAdmin();

        $search   = trim($request->input('search', ''));
        $category = trim($request->input('category', ''));

        $query = HelpArticle::query();

        if (!empty($category)) {
            $query->where('category', $category);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('question', 'LIKE', "%{$search}%")
                  ->orWhere('keywords', 'LIKE', "%{$search}%")
                  ->orWhere('answer', 'LIKE', "%{$search}%");
            });
        }

        $articles   = $query->latest()->paginate(15)->withQueryString();
        $categories = HelpArticle::CATEGORIES;

        return view('superadmin.help_articles.index', compact('articles', 'categories', 'search', 'category'));
    }

    public function articlesCreate()
    {
        $this->requireSuperAdmin();

        $categories = HelpArticle::CATEGORIES;

        return view('superadmin.help_articles.create', compact('categories'));
    }

    public function articlesStore(Request $request)
    {
        $this->requireSuperAdmin();

        $validated = $request->validate([
            'category'  => 'required|string|max:64',
            'question'  => 'required|string|max:255',
            'answer'    => 'required|string',
            'keywords'  => 'nullable|string',
            'is_active' => 'required|boolean',
        ]);

        $article = HelpArticle::create($validated);

        AuditLogService::log('help_article.created', 'platform', 'Created help article: ' . $article->question);

        return redirect()->route('superadmin.help-articles.index')
            ->with('success', 'Help article created successfully.');
    }

    public function articlesEdit(HelpArticle $article)
    {
        $this->requireSuperAdmin();

        $categories = HelpArticle::CATEGORIES;

        return view('superadmin.help_articles.edit', compact('article', 'categories'));
    }

    public function articlesUpdate(Request $request, HelpArticle $article)
    {
        $this->requireSuperAdmin();

        $validated = $request->validate([
            'category'  => 'required|string|max:64',
            'question'  => 'required|string|max:255',
            'answer'    => 'required|string',
            'keywords'  => 'nullable|string',
            'is_active' => 'required|boolean',
        ]);

        $article->update($validated);

        AuditLogService::log('help_article.updated', 'platform', 'Updated help article #' . $article->id);

        return redirect()->route('superadmin.help-articles.index')
            ->with('success', 'Help article updated successfully.');
    }

    public function articlesDestroy(HelpArticle $article)
    {
        $this->requireSuperAdmin();

        $question = $article->question;
        $article->delete();

        AuditLogService::log('help_article.deleted', 'platform', 'Deleted help article: ' . $question);

        return redirect()->route('superadmin.help-articles.index')
            ->with('success', 'Help article deleted successfully.');
    }

    // ─── AI ASSISTANT SETTINGS & LOGS ─────────────────────────────────────────

    public function aiSettings()
    {
        $this->requireSuperAdmin();

        $settings = [
            'chatbot_enabled'         => PlatformSetting::get('chatbot_enabled', '1'),
            'chatbot_welcome_message' => PlatformSetting::get(
                'chatbot_welcome_message',
                'Hello! I am the Elimu AI Assistant. Ask me anything about using CloudKazi Elimu (e.g. registering students, fees, or report cards).'
            ),
            'chatbot_prompt_1'        => PlatformSetting::get('chatbot_prompt_1', 'How do I register a student?'),
            'chatbot_prompt_2'        => PlatformSetting::get('chatbot_prompt_2', 'How do I record fees?'),
            'chatbot_prompt_3'        => PlatformSetting::get('chatbot_prompt_3', 'How do I generate report cards?'),
            'chatbot_prompt_4'        => PlatformSetting::get('chatbot_prompt_4', 'How do I generate automatic timetables?'),
            'openai_enabled'          => PlatformSetting::get('openai_enabled', '0'),
            'openai_api_key'          => PlatformSetting::get('openai_api_key', ''),
            'openai_model'            => PlatformSetting::get('openai_model', 'gpt-4o-mini'),
        ];

        $totalQuestionsAsked = ChatbotLog::count();
        $answeredFromKB      = ChatbotLog::where('source', 'knowledge_base')->count();
        $fallbackCount       = ChatbotLog::where('source', 'fallback')->count();
        $helpfulCount        = ChatbotLog::where('was_helpful', true)->count();

        return view('superadmin.ai_assistant.settings', compact(
            'settings',
            'totalQuestionsAsked',
            'answeredFromKB',
            'fallbackCount',
            'helpfulCount'
        ));
    }

    public function updateAiSettings(Request $request)
    {
        $this->requireSuperAdmin();

        $validated = $request->validate([
            'chatbot_enabled'         => 'required|in:0,1',
            'chatbot_welcome_message' => 'required|string|max:500',
            'chatbot_prompt_1'        => 'nullable|string|max:150',
            'chatbot_prompt_2'        => 'nullable|string|max:150',
            'chatbot_prompt_3'        => 'nullable|string|max:150',
            'chatbot_prompt_4'        => 'nullable|string|max:150',
            'openai_enabled'          => 'required|in:0,1',
            'openai_api_key'          => 'nullable|string|max:200',
            'openai_model'            => 'nullable|string|max:50',
        ]);

        foreach ($validated as $key => $value) {
            PlatformSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $value ?? '', 'group' => 'ai_assistant']
            );
        }

        AuditLogService::log('ai_assistant.settings_updated', 'platform', 'Updated AI Assistant configuration settings');

        return back()->with('success', 'AI Assistant settings updated successfully.');
    }

    public function aiLogs(Request $request)
    {
        $this->requireSuperAdmin();

        $search = trim($request->input('search', ''));
        $source = trim($request->input('source', ''));

        $query = ChatbotLog::with(['user', 'matchedArticle'])->latest();

        if (!empty($source)) {
            $query->where('source', $source);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('question', 'LIKE', "%{$search}%")
                  ->orWhere('answer', 'LIKE', "%{$search}%")
                  ->orWhere('ip_address', 'LIKE', "%{$search}%");
            });
        }

        $logs = $query->paginate(25)->withQueryString();

        return view('superadmin.ai_assistant.logs', compact('logs', 'search', 'source'));
    }
}
