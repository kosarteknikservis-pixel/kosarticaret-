<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Services\OpenAiService;
use App\Support\MetaSuggestion;
use App\Support\RichContent;
use App\Support\SlugHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class AiController extends Controller
{
    private const ENTITIES = ['products', 'categories', 'brands', 'blog_posts', 'pages'];

    private const TYPES = ['product', 'category', 'brand', 'blog', 'page', 'settings'];

    public function slug(Request $request): JsonResponse
    {
        $data = $request->validate([
            'text' => ['required', 'string', 'max:255'],
            'entity' => ['required', 'string', 'in:'.implode(',', self::ENTITIES)],
            'exclude_id' => ['nullable', 'integer', 'min:1'],
        ]);

        return response()->json([
            'slug' => SlugHelper::assign(
                $data['entity'],
                null,
                $data['text'],
                $data['exclude_id'] ?? null,
            ),
        ]);
    }

    public function meta(Request $request, OpenAiService $openAi): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', 'string', 'in:'.implode(',', self::TYPES)],
            'use_ai' => ['sometimes', 'boolean'],
            'context' => ['required', 'array'],
        ]);

        $context = $this->enrichContext($data['type'], $data['context']);
        $suggestion = MetaSuggestion::suggest($data['type'], $context);

        if ($request->boolean('use_ai')) {
            try {
                $suggestion = $openAi->suggestMeta($data['type'], $context);
            } catch (RuntimeException $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
        }

        return response()->json($suggestion);
    }

    public function generate(Request $request, OpenAiService $openAi): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', 'string', 'in:'.implode(',', self::TYPES)],
            'field' => ['required', 'string', 'max:64'],
            'context' => ['required', 'array'],
        ]);

        if (! OpenAiService::isConfigured()) {
            return response()->json([
                'message' => 'OpenAI API anahtarı tanımlı değil. Site ayarları → Entegrasyonlar.',
            ], 422);
        }

        $context = $this->enrichContext($data['type'], $data['context']);

        try {
            $content = $openAi->generateField($data['type'], $data['field'], $context);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        if (in_array($data['field'], ['description', 'content'], true)) {
            $content = RichContent::normalize($content) ?? '';
        }

        return response()->json(['content' => $content]);
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function enrichContext(string $type, array $context): array
    {
        if (! empty($context['brand_id']) && empty($context['brand_name'])) {
            $brand = Brand::query()->find($context['brand_id']);
            if ($brand) {
                $context['brand_name'] = $brand->name;
            }
        }

        return $context;
    }
}
