<?php

namespace App\Http\Controllers;

use App\Models\HelpArticle;
use App\Models\LegalDocument;
use Illuminate\Http\Request;

class LegalController extends Controller
{
    /**
     * Display the public Privacy Policy page.
     */
    public function privacyPolicy()
    {
        $document = LegalDocument::getPrivacyPolicy();

        return view('legal.privacy-policy', compact('document'));
    }

    /**
     * Display the public Terms and Conditions page.
     */
    public function terms()
    {
        $document = LegalDocument::getTermsAndConditions();

        return view('legal.terms', compact('document'));
    }

    /**
     * Display the public Help Center & Knowledge Base page.
     */
    public function helpCenter(Request $request)
    {
        $search = trim($request->input('search', ''));
        $category = trim($request->input('category', ''));

        $query = HelpArticle::active();

        if (!empty($category)) {
            $query->where('category', $category);
        }

        if (!empty($search)) {
            $query->search($search);
        }

        $articles = $query->orderBy('view_count', 'desc')
                          ->orderBy('created_at', 'desc')
                          ->paginate(12)
                          ->withQueryString();

        $categories = HelpArticle::CATEGORIES;
        $popularArticles = HelpArticle::active()->orderBy('view_count', 'desc')->take(5)->get();

        return view('legal.help-center', compact('articles', 'categories', 'popularArticles', 'search', 'category'));
    }
}
