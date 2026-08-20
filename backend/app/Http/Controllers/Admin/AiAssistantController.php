<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiConversation;
use App\Models\Product;
use App\Models\Setting;
use App\Support\AiSettings;
use App\Support\AppStrings;
use App\Support\Constants;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AiAssistantController extends Controller
{
    public function index(): View
    {
        return view('admin.ai.index', [
            'title' => AppStrings::NAV_AI,
            'settings' => [
                'enabled' => AiSettings::enabled(),
                'guests_allowed' => AiSettings::guestsAllowed(),
                'name' => AiSettings::name(),
                'welcome' => AiSettings::welcome(),
                'system_prompt' => AiSettings::systemPrompt(),
                'max_products' => AiSettings::maxProducts(),
                'model' => AiSettings::model(),
            ],
            'models' => AiSettings::models(),
            'hasApiKey' => AiSettings::hasApiKey(),
            'conversationsCount' => AiConversation::query()->count(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:40'],
            'welcome' => ['required', 'string', 'max:500'],
            'system_prompt' => ['required', 'string', 'max:4000'],
            'max_products' => ['required', 'integer', 'min:2', 'max:8'],
            'model' => ['required', 'string', 'in:'.implode(',', AiSettings::models())],
        ]);

        Setting::setValue(Constants::SETTING_AI_ENABLED, $request->boolean('enabled') ? '1' : '0');
        Setting::setValue(Constants::SETTING_AI_GUESTS_ALLOWED, $request->boolean('guests_allowed') ? '1' : '0');
        Setting::setValue(Constants::SETTING_AI_NAME, $data['name']);
        Setting::setValue(Constants::SETTING_AI_WELCOME, $data['welcome']);
        Setting::setValue(Constants::SETTING_AI_SYSTEM_PROMPT, $data['system_prompt']);
        Setting::setValue(Constants::SETTING_AI_MAX_PRODUCTS, $data['max_products']);
        Setting::setValue(Constants::SETTING_AI_MODEL, $data['model']);

        return back()->with('success', AppStrings::AI_SAVED);
    }

    public function conversations(): View
    {
        $conversations = AiConversation::query()
            ->with(['user', 'messages' => fn ($query) => $query->latest('id')->limit(1)])
            ->withCount('messages')
            ->latest('id')
            ->paginate(Constants::DEFAULT_PAGE_SIZE);

        return view('admin.ai.conversations', [
            'title' => AppStrings::AI_CONVERSATIONS,
            'conversations' => $conversations,
        ]);
    }

    public function show(AiConversation $conversation): View
    {
        $conversation->load(['user', 'messages']);

        $productIds = $conversation->messages
            ->pluck('suggested_product_ids')
            ->filter()
            ->flatten()
            ->unique()
            ->values();

        $products = $productIds->isEmpty()
            ? collect()
            : Product::query()->whereIn('id', $productIds)->get()->keyBy('id');

        return view('admin.ai.show', [
            'title' => 'محادثة #'.$conversation->id,
            'conversation' => $conversation,
            'products' => $products,
        ]);
    }
}
