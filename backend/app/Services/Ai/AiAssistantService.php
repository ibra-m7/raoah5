<?php

namespace App\Services\Ai;

use App\Enums\AiMessageRole;
use App\Exceptions\AiAssistantException;
use App\Http\Resources\ProductResource;
use App\Models\AiConversation;
use App\Models\Product;
use App\Models\User;
use App\Support\AiSettings;
use App\Support\AppStrings;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class AiAssistantService
{
    public function __construct(
        private readonly GeminiClient $gemini,
    ) {}

    /**
     * @return array{enabled: bool, guests_allowed: bool, name: string, welcome: string, max_products: int}
     */
    public function config(): array
    {
        return [
            'enabled' => AiSettings::enabled(),
            'guests_allowed' => AiSettings::guestsAllowed(),
            'name' => AiSettings::name(),
            'welcome' => AiSettings::welcome(),
            'max_products' => AiSettings::maxProducts(),
        ];
    }

    /**
     * @return array{conversation_id: int, guest_token: string|null, reply: string, products: array<int, mixed>, name: string}
     */
    public function chat(
        string $message,
        ?User $user,
        ?string $guestToken,
        ?int $conversationId,
        string $intent = 'chat',
        ?string $productId = null,
    ): array {
        if (! AiSettings::enabled()) {
            throw new AiAssistantException('المساعد الذكي متوقف مؤقتاً من لوحة التحكم.', 503);
        }

        if ($user === null && ! AiSettings::guestsAllowed()) {
            throw new AiAssistantException('سجّل دخولك لاستخدام المساعد الذكي.', 401);
        }

        if (! AiSettings::hasApiKey()) {
            throw new AiAssistantException('المساعد غير جاهز. أضف مفتاح Gemini في إعدادات الخادم.', 503);
        }

        $guestToken = $this->normalizeGuestToken($guestToken, $user);
        $conversation = $this->resolveConversation($user, $guestToken, $conversationId);
        $candidates = $this->candidateProducts($message, $productId);
        $history = $this->historyPayload($conversation);

        $system = implode("\n\n", [
            AiSettings::systemPrompt(),
            $this->outputContract(),
            $this->catalogBlock($candidates),
        ]);

        $userPrompt = $intent === 'complement'
            ? $this->complementPrompt($message, $productId)
            : $message;

        try {
            $raw = $this->gemini->generateJson($system, $userPrompt, $history);
        } catch (RuntimeException $e) {
            Log::warning('ai.chat.gemini', ['reason' => $e->getMessage()]);
            throw new AiAssistantException('تعذّر الرد الآن. حاول بعد لحظات.', 502);
        } catch (Throwable $e) {
            Log::error('ai.chat.failed', ['message' => $e->getMessage()]);
            throw new AiAssistantException('تعذّر الرد الآن. حاول بعد لحظات.', 502);
        }

        $parsed = $this->parseReply($raw);
        $products = $this->resolveProducts($parsed['product_ids'], $candidates, $message);

        $conversation->messages()->create([
            'role' => AiMessageRole::User,
            'content' => $message,
        ]);

        $conversation->messages()->create([
            'role' => AiMessageRole::Assistant,
            'content' => $parsed['reply'],
            'suggested_product_ids' => $products->pluck('id')->values()->all(),
        ]);

        return [
            'conversation_id' => $conversation->id,
            'guest_token' => $guestToken,
            'reply' => $parsed['reply'],
            'products' => ProductResource::collection($products)->resolve(),
            'name' => AiSettings::name(),
        ];
    }

    private function normalizeGuestToken(?string $guestToken, ?User $user): ?string
    {
        $token = trim((string) $guestToken);
        if ($token !== '') {
            return Str::limit($token, 64, '');
        }

        return $user === null ? (string) Str::uuid() : null;
    }

    private function resolveConversation(?User $user, ?string $guestToken, ?int $conversationId): AiConversation
    {
        if ($conversationId) {
            $existing = AiConversation::query()
                ->where('id', $conversationId)
                ->where(function ($query) use ($user, $guestToken) {
                    if ($user) {
                        $query->orWhere('user_id', $user->id);
                    }
                    if ($guestToken) {
                        $query->orWhere('guest_token', $guestToken);
                    }
                })
                ->first();

            if ($existing) {
                if ($user && $existing->user_id === null) {
                    $existing->update(['user_id' => $user->id]);
                }

                return $existing;
            }
        }

        return AiConversation::query()->create([
            'user_id' => $user?->id,
            'guest_token' => $guestToken,
        ]);
    }

    /**
     * @return list<array{role: string, content: string}>
     */
    private function historyPayload(AiConversation $conversation): array
    {
        return $conversation->messages()
            ->latest('id')
            ->take(10)
            ->get()
            ->reverse()
            ->values()
            ->map(fn ($message) => [
                'role' => $message->role?->value ?? 'user',
                'content' => (string) $message->content,
            ])
            ->all();
    }

    /**
     * @return Collection<int, Product>
     */
    private function candidateProducts(string $message, ?string $productId): Collection
    {
        $relations = ['images', 'primaryImage', 'category', 'complementaryProducts.images', 'complementaryProducts.primaryImage'];

        $matched = Product::query()
            ->active()
            ->with($relations)
            ->search($message)
            ->orderByDesc('is_featured')
            ->limit(24)
            ->get();

        $featured = Product::query()
            ->active()
            ->with($relations)
            ->featured()
            ->limit(12)
            ->get();

        $recent = Product::query()
            ->active()
            ->with($relations)
            ->latest('id')
            ->limit(20)
            ->get();

        $priority = collect();
        if ($productId) {
            $source = Product::query()->active()->with($relations)->find($productId);
            if ($source) {
                $priority = $priority->push($source);
                $priority = $priority->concat(
                    $source->complementaryProducts->where('is_active', true)
                );
                $priority = $priority->concat(
                    Product::query()
                        ->active()
                        ->with($relations)
                        ->where('category_id', $source->category_id)
                        ->where('id', '!=', $source->id)
                        ->limit(8)
                        ->get()
                );
            }
        }

        return $priority
            ->concat($matched)
            ->concat($featured)
            ->concat($recent)
            ->unique('id')
            ->take(60)
            ->values();
    }

    /**
     * @param  Collection<int, Product>  $candidates
     */
    private function catalogBlock(Collection $candidates): string
    {
        if ($candidates->isEmpty()) {
            return 'كتالوج المنتجات المتاح الآن: لا توجد منتجات نشطة.';
        }

        $lines = $candidates->map(function (Product $product) {
            $keywords = collect($product->keywords ?? [])->take(4)->implode('، ');
            $category = $product->category?->name ?? 'عام';

            return sprintf(
                '[%d] %s | %.2f '.AppStrings::CURRENCY.' | %s%s',
                $product->id,
                $product->name,
                (float) $product->effective_price,
                $category,
                $keywords !== '' ? ' | '.$keywords : ''
            );
        })->implode("\n");

        return "كتالوج المنتجات المتاح للاقتراح (اختر المعرّفات فقط من هنا):\n".$lines;
    }

    private function outputContract(): string
    {
        $max = AiSettings::maxProducts();

        return <<<TXT
صيغة الرد إلزامية: أرجعي JSON فقط بهذا الشكل:
{"reply":"نص عربي قصير وواضح","product_ids":[1,2,3]}
- reply للعميل فقط، بدون ذكر المعرّفات أو JSON.
- product_ids أرقام من الكتالوج المرفق فقط، بحد أقصى {$max} منتجات.
- عندما يطلب العميل منتجات أو يصف احتياجاً، أرجعي عدة منتجات مناسبة في شبكة عرض (2 إلى {$max}).
- لا تختلقي معرّفات غير موجودة في القائمة.
TXT;
    }

    private function complementPrompt(string $message, ?string $productId): string
    {
        $hint = $productId ? ' المنتج المضاف معرّفه '.$productId.'.' : '';

        return 'أكّدي إضافة المنتج للسلة بجملة قصيرة، ثم اقترحي منتجات مكملة حقيقية من الكتالوج.'.$hint."\n".$message;
    }

    /**
     * @return array{reply: string, product_ids: list<int>}
     */
    private function parseReply(string $raw): array
    {
        $text = trim($raw);
        if (preg_match('/\{.*\}/s', $text, $matches) === 1) {
            $text = $matches[0];
        }

        $data = json_decode($text, true);
        $reply = is_array($data) && is_string($data['reply'] ?? null)
            ? trim((string) $data['reply'])
            : '';

        $ids = [];
        if (is_array($data) && isset($data['product_ids']) && is_array($data['product_ids'])) {
            foreach ($data['product_ids'] as $id) {
                if (is_numeric($id)) {
                    $ids[] = (int) $id;
                }
            }
        }

        if ($reply === '') {
            $reply = 'تفضل هذه اختيارات من متجرنا، ويمكنك فتح أي منتج للتفاصيل.';
        }

        return [
            'reply' => $reply,
            'product_ids' => array_values(array_unique($ids)),
        ];
    }

    /**
     * @param  list<int>  $ids
     * @param  Collection<int, Product>  $candidates
     * @return Collection<int, Product>
     */
    private function resolveProducts(array $ids, Collection $candidates, string $message): Collection
    {
        $max = AiSettings::maxProducts();
        $allowed = $candidates->keyBy('id');

        $picked = collect($ids)
            ->unique()
            ->map(fn (int $id) => $allowed->get($id))
            ->filter()
            ->values();

        if ($picked->isEmpty() && $ids !== []) {
            $picked = Product::query()
                ->active()
                ->with(['images', 'primaryImage', 'category'])
                ->whereIn('id', $ids)
                ->get();
        }

        if ($picked->isEmpty() && $this->looksLikeProductQuery($message)) {
            $picked = $candidates->take($max);
        }

        return $picked->take($max)->values();
    }

    private function looksLikeProductQuery(string $message): bool
    {
        $needles = ['منتج', 'منظف', 'عرض', 'سعر', 'أبحث', 'ابي', 'أبي', 'أريد', 'وريني', 'اقترح', 'خصم', 'سلة'];
        foreach ($needles as $needle) {
            if (mb_stripos($message, $needle) !== false) {
                return true;
            }
        }

        return mb_strlen(trim($message)) > 12;
    }
}
