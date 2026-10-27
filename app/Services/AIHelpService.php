<?php

namespace App\Services;

use App\Models\ChatbotLog;
use App\Models\HelpArticle;
use App\Models\PlatformSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AIHelpService
{
    /**
     * Stopwords to ignore during tokenized keyword matching.
     */
    protected array $stopwords = [
        'a', 'about', 'an', 'and', 'are', 'as', 'at', 'be', 'by', 'can', 'could', 'did', 'do',
        'does', 'for', 'from', 'get', 'how', 'i', 'in', 'is', 'it', 'me', 'my', 'of', 'on',
        'or', 'please', 'the', 'to', 'want', 'what', 'when', 'where', 'which', 'who', 'why',
        'will', 'with', 'would', 'you', 'your',
    ];

    /**
     * Answer a user question about how CloudKazi Elimu works.
     *
     * @param string $question
     * @param int|null $userId
     * @param string|null $sessionId
     * @param string|null $ipAddress
     * @return array
     */
    public function answerQuestion(
        string $question,
        ?int $userId = null,
        ?string $sessionId = null,
        ?string $ipAddress = null
    ): array {
        // 1. Sanitize user input
        $sanitizedQuestion = $this->sanitizeInput($question);

        if (empty($sanitizedQuestion)) {
            return [
                'success'            => false,
                'question'           => $question,
                'answer'             => 'Please type a valid question regarding how to use CloudKazi Elimu.',
                'category'           => 'General',
                'matched_article_id' => null,
                'suggestions'        => $this->getDefaultSuggestions(),
                'source'             => 'fallback',
                'log_id'             => null,
            ];
        }

        // 2. Search local help articles knowledge base
        $articleMatch = $this->searchHelpArticles($sanitizedQuestion);

        if ($articleMatch) {
            $article = $articleMatch['article'];

            // Increment article view count
            $article->increment('view_count');

            $suggestions = $this->getRelatedSuggestions($article->category, $article->id);

            // Log activity
            $log = $this->logActivity(
                $sanitizedQuestion,
                $article->answer,
                $article->id,
                'knowledge_base',
                $userId,
                $sessionId,
                $ipAddress
            );

            return [
                'success'            => true,
                'question'           => $sanitizedQuestion,
                'answer'             => $article->answer,
                'category'           => $article->category,
                'matched_article_id' => $article->id,
                'suggestions'        => $suggestions,
                'source'             => 'knowledge_base',
                'log_id'             => $log->id,
            ];
        }

        // 3. Optional OpenAI Integration Fallback
        if ($this->isOpenAiEnabled()) {
            $openAiResponse = $this->queryOpenAi($sanitizedQuestion);
            if ($openAiResponse) {
                $log = $this->logActivity(
                    $sanitizedQuestion,
                    $openAiResponse,
                    null,
                    'openai',
                    $userId,
                    $sessionId,
                    $ipAddress
                );

                return [
                    'success'            => true,
                    'question'           => $sanitizedQuestion,
                    'answer'             => $openAiResponse,
                    'category'           => 'AI Generated',
                    'matched_article_id' => null,
                    'suggestions'        => $this->getDefaultSuggestions(),
                    'source'             => 'openai',
                    'log_id'             => $log->id,
                ];
            }
        }

        // 4. Default fallback when no answer is found
        $fallbackAnswer = 'I could not find an answer. Please contact your administrator.';

        $log = $this->logActivity(
            $sanitizedQuestion,
            $fallbackAnswer,
            null,
            'fallback',
            $userId,
            $sessionId,
            $ipAddress
        );

        return [
            'success'            => false,
            'question'           => $sanitizedQuestion,
            'answer'             => $fallbackAnswer,
            'category'           => 'Unresolved',
            'matched_article_id' => null,
            'suggestions'        => $this->getDefaultSuggestions(),
            'source'             => 'fallback',
            'log_id'             => $log->id,
        ];
    }

    /**
     * Sanitize and normalize input string.
     */
    protected function sanitizeInput(string $input): string
    {
        // Strip tags, special control characters, and limit length
        $clean = strip_tags($input);
        $clean = preg_replace('/[\x00-\x1F\x7F]/u', '', $clean);
        $clean = trim($clean);

        return Str::limit($clean, 300, '');
    }

    /**
     * Search help articles using multiple matching strategies:
     * 1. Exact or near-exact question match
     * 2. Full-text or tokenized keyword & question scoring
     */
    protected function searchHelpArticles(string $query): ?array
    {
        $normalizedQuery = strtolower($query);

        // Strategy A: Exact or near-exact match on question
        $exactMatch = HelpArticle::active()
            ->whereRaw('LOWER(question) = ?', [$normalizedQuery])
            ->first();

        if ($exactMatch) {
            return ['article' => $exactMatch, 'score' => 200];
        }

        // Strategy B: Substring containment in question
        $substringMatch = HelpArticle::active()
            ->whereRaw('LOWER(question) LIKE ?', ['%' . $normalizedQuery . '%'])
            ->first();

        if ($substringMatch) {
            return ['article' => $substringMatch, 'score' => 150];
        }

        // Strategy C: Tokenized keyword and question similarity scoring
        $tokens = $this->extractKeywords($query);

        if (empty($tokens)) {
            return null;
        }

        $articles = HelpArticle::active()->get();
        $bestMatch = null;
        $highestScore = 0;

        foreach ($articles as $article) {
            $score = 0;
            $articleQuestion = strtolower($article->question);
            $articleKeywords = strtolower($article->keywords ?? '');
            $articleAnswer   = strtolower($article->answer);
            $articleCategory = strtolower($article->category);

            // Check how many tokens match in question, keywords, category, and answer
            foreach ($tokens as $token) {
                // Keyword match (high value)
                if (str_contains($articleKeywords, $token)) {
                    $score += 35;
                }

                // Question word match
                if (str_contains($articleQuestion, $token)) {
                    $score += 25;
                }

                // Category match
                if (str_contains($articleCategory, $token)) {
                    $score += 20;
                }

                // Answer body match
                if (str_contains($articleAnswer, $token)) {
                    $score += 10;
                }
            }

            // Bonus for multi-word phrase matching
            if (count($tokens) >= 2) {
                $phrase = implode(' ', $tokens);
                if (str_contains($articleQuestion, $phrase)) {
                    $score += 50;
                }
                if (str_contains($articleKeywords, $phrase)) {
                    $score += 40;
                }
            }

            if ($score > $highestScore) {
                $highestScore = $score;
                $bestMatch = $article;
            }
        }

        // Minimum threshold to consider a match valid
        if ($highestScore >= 35 && $bestMatch) {
            return ['article' => $bestMatch, 'score' => $highestScore];
        }

        return null;
    }

    /**
     * Extract meaningful search keywords from user input.
     */
    public function extractKeywords(string $text): array
    {
        // Normalize text
        $clean = strtolower($text);
        $clean = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $clean);
        $words = preg_split('/\s+/', $clean, -1, PREG_SPLIT_NO_EMPTY);

        $filtered = [];
        foreach ($words as $word) {
            $w = trim($word);
            if (strlen($w) >= 2 && !in_array($w, $this->stopwords, true)) {
                $filtered[] = $w;
            }
        }

        return array_values(array_unique($filtered));
    }

    /**
     * Check if OpenAI integration is enabled and configured.
     */
    public function isOpenAiEnabled(): bool
    {
        $settingEnabled = PlatformSetting::get('openai_enabled', false);
        $apiKey = config('services.openai.api_key') ?: PlatformSetting::get('openai_api_key');

        return (!empty($apiKey) && ($settingEnabled == true || $settingEnabled == '1'));
    }

    /**
     * Query OpenAI Chat Completions API with ERP contextual prompt.
     */
    protected function queryOpenAi(string $question): ?string
    {
        $apiKey = config('services.openai.api_key') ?: PlatformSetting::get('openai_api_key');
        $model  = config('services.openai.model', 'gpt-4o-mini');

        if (empty($apiKey)) {
            return null;
        }

        $systemPrompt = <<<'SYS'
You are the CloudKazi Elimu AI Help Assistant. CloudKazi Elimu is a modern Kenyan school management SaaS system (ERP) supporting CBC (Competency-Based Curriculum) and 8-4-4 curriculums, M-Pesa Safaricom Daraja fee payments, student admissions, automatic timetable generator, staff allocations, parent and student portals, and examinations.
Answer the user's question concisely with direct navigation steps inside the CloudKazi Elimu system (e.g., "Navigate to Students > Admissions > Add Student"). Be courteous, accurate, and brief (2-4 sentences max). If you do not know the exact path in CloudKazi Elimu, advise them to contact their school administrator.
SYS;

        try {
            $response = Http::withToken($apiKey)
                ->timeout(10)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model'       => $model,
                    'messages'    => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $question],
                    ],
                    'max_tokens'  => 180,
                    'temperature' => 0.3,
                ]);

            if ($response->successful()) {
                $content = $response->json('choices.0.message.content');
                return trim($content ?? '');
            }

            Log::warning('OpenAI API request failed: ' . $response->body());
        } catch (\Throwable $e) {
            Log::error('OpenAI API exception in AIHelpService: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Retrieve related question suggestions for a given category.
     */
    public function getRelatedSuggestions(string $category, ?int $excludeArticleId = null): array
    {
        $query = HelpArticle::active()->where('category', $category);

        if ($excludeArticleId) {
            $query->where('id', '!=', $excludeArticleId);
        }

        $suggestions = $query->inRandomOrder()->limit(3)->pluck('question')->toArray();

        if (empty($suggestions)) {
            return $this->getDefaultSuggestions();
        }

        return $suggestions;
    }

    /**
     * Get default prompt questions to show in the widget.
     */
    public function getDefaultSuggestions(): array
    {
        return [
            'How do I register a student?',
            'How do I record fees?',
            'How do I generate report cards?',
            'How do I generate automatic timetables?',
        ];
    }

    /**
     * Log chatbot interaction.
     */
    protected function logActivity(
        string $question,
        string $answer,
        ?int $matchedArticleId,
        string $source,
        ?int $userId,
        ?string $sessionId,
        ?string $ipAddress
    ): ChatbotLog {
        return ChatbotLog::create([
            'user_id'            => $userId,
            'session_id'         => $sessionId,
            'ip_address'         => $ipAddress,
            'question'           => $question,
            'answer'             => $answer,
            'matched_article_id' => $matchedArticleId,
            'source'             => $source,
        ]);
    }
}
