<?php

namespace App\Http\Controllers;

use App\Models\ChatbotLog;
use App\Models\PlatformSetting;
use App\Services\AIHelpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AIHelpController extends Controller
{
    /**
     * Ask a question to the AI Help Assistant.
     */
    public function ask(Request $request, AIHelpService $aiHelpService): JsonResponse
    {
        // Check if chatbot is disabled via Platform Settings
        $chatbotEnabled = PlatformSetting::get('chatbot_enabled', '1');
        if ($chatbotEnabled === '0' || $chatbotEnabled === false) {
            return response()->json([
                'success' => false,
                'answer'  => 'The AI Help Assistant is currently undergoing scheduled maintenance. Please check the Help Center or contact support.',
                'source'  => 'disabled',
            ], 503);
        }

        $validated = $request->validate([
            'question' => 'required|string|min:2|max:300',
        ]);

        $userId = Auth::id();
        $sessionId = $request->session()->getId();
        $ipAddress = $request->ip();

        $result = $aiHelpService->answerQuestion(
            $validated['question'],
            $userId,
            $sessionId,
            $ipAddress
        );

        return response()->json($result);
    }

    /**
     * Get suggested questions for quick-select chips.
     */
    public function suggestions(AIHelpService $aiHelpService): JsonResponse
    {
        $customPrompt1 = PlatformSetting::get('chatbot_prompt_1');
        $customPrompt2 = PlatformSetting::get('chatbot_prompt_2');
        $customPrompt3 = PlatformSetting::get('chatbot_prompt_3');
        $customPrompt4 = PlatformSetting::get('chatbot_prompt_4');

        $suggestions = array_filter([$customPrompt1, $customPrompt2, $customPrompt3, $customPrompt4]);

        if (empty($suggestions)) {
            $suggestions = $aiHelpService->getDefaultSuggestions();
        }

        $welcomeMessage = PlatformSetting::get(
            'chatbot_welcome_message',
            'Hello! I am the Elimu AI Assistant. Ask me anything about using CloudKazi Elimu (e.g. registering students, fees, or report cards).'
        );

        return response()->json([
            'welcome_message' => $welcomeMessage,
            'suggestions'     => array_values($suggestions),
        ]);
    }

    /**
     * Record feedback (helpful / unhelpful) for a response.
     */
    public function feedback(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'log_id'      => 'required|integer|exists:chatbot_logs,id',
            'was_helpful' => 'required|boolean',
        ]);

        $log = ChatbotLog::find($validated['log_id']);
        if ($log) {
            $log->update(['was_helpful' => $validated['was_helpful']]);
        }

        return response()->json(['success' => true]);
    }
}
