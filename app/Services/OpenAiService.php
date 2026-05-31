<?php

namespace App\Services;

use App\Models\SiteSetting;
use App\Support\SiteName;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class OpenAiService
{
    public static function isConfigured(): bool
    {
        return self::apiKey() !== '';
    }

    public static function apiKey(): string
    {
        return trim((string) SiteSetting::get('openai_api_key', ''));
    }

    public static function model(): string
    {
        $model = trim((string) SiteSetting::get('openai_model', 'gpt-4o-mini'));

        return $model !== '' ? $model : 'gpt-4o-mini';
    }

    public function chat(string $system, string $user, int $maxTokens = 2500): string
    {
        $key = self::apiKey();
        if ($key === '') {
            throw new RuntimeException('OpenAI API anahtarı tanımlı değil. Site ayarları → Entegrasyonlar bölümünden ekleyin.');
        }

        try {
            $response = Http::withToken($key)
                ->timeout(90)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => self::model(),
                    'messages' => [
                        ['role' => 'system', 'content' => $system],
                        ['role' => 'user', 'content' => $user],
                    ],
                    'temperature' => 0.65,
                    'max_tokens' => $maxTokens,
                ])
                ->throw()
                ->json();
        } catch (RequestException $e) {
            $message = $e->response?->json('error.message') ?? $e->getMessage();
            throw new RuntimeException('OpenAI isteği başarısız: '.$message, 0, $e);
        }

        $content = $response['choices'][0]['message']['content'] ?? '';

        return trim((string) $content);
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array{meta_title: string, meta_description: string}
     */
    public function suggestMeta(string $type, array $context): array
    {
        $site = SiteName::get();
        $json = $this->chat(
            'Sen Türkçe e-ticaret SEO uzmanısın. Yalnızca geçerli JSON döndür: {"meta_title":"...","meta_description":"..."}. meta_title en fazla 60 karakter, meta_description 140-160 karakter. Anahtar kelimeleri doğal kullan.',
            $this->contextBlock($type, $context, $site)."\n\nGörev: Bu kayıt için Google arama sonucu meta başlık ve açıklama üret.",
            400,
        );

        return $this->parseMetaJson($json);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function generateField(string $type, string $field, array $context): string
    {
        $site = SiteName::get();
        $instructions = $this->fieldInstructions($type, $field);

        $content = $this->chat(
            $instructions."\n\nSite: {$site}. Türkçe yaz. Gereksiz önsöz ekleme.",
            $this->contextBlock($type, $context, $site)."\n\nGörev: Sadece istenen alan içeriğini üret.",
            $this->maxTokensForField($field),
        );

        return $this->stripCodeFences($content);
    }

    private function fieldInstructions(string $type, string $field): string
    {
        $htmlNote = 'İzinli HTML: p, h2, h3, ul, ol, li, strong, a. Script ve style yasak.';

        return match ($field) {
            'meta_title', 'meta_description' => 'Yalnızca düz metin döndür, JSON değil.',
            'tags' => 'Virgülle ayrılmış 5-8 Türkçe anahtar kelime döndür. Başka metin ekleme.',
            'short_description' => 'Tek paragraf, en fazla 250 karakter, düz metin.',
            'excerpt' => '2-3 cümle özet, düz metin, en fazla 300 karakter.',
            'description', 'content' => "SEO uyumlu içerik. {$htmlNote}",
            'site_description', 'contact_page_intro', 'tagline', 'promo_text', 'cookie_text' => 'Kısa, net düz metin; HTML kullanma.',
            default => 'İstenen alan için uygun içerik üret.',
        };
    }

    private function maxTokensForField(string $field): int
    {
        return match ($field) {
            'tags', 'short_description', 'excerpt', 'meta_title', 'meta_description' => 400,
            'site_description', 'tagline', 'promo_text' => 500,
            default => 2800,
        };
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function contextBlock(string $type, array $context, string $site): string
    {
        $lines = ["Tür: {$type}", "Site: {$site}"];

        foreach ($context as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            if (is_array($value)) {
                $value = implode(', ', $value);
            }
            $plain = RichContent::plainText((string) $value);
            if ($plain === '') {
                continue;
            }
            $lines[] = ucfirst(str_replace('_', ' ', $key)).': '.Str::limit($plain, 1200, '…');
        }

        return implode("\n", $lines);
    }

    /**
     * @return array{meta_title: string, meta_description: string}
     */
    private function parseMetaJson(string $raw): array
    {
        $raw = $this->stripCodeFences($raw);
        if (preg_match('/\{[\s\S]*\}/', $raw, $m)) {
            $decoded = json_decode($m[0], true);
            if (is_array($decoded)) {
                return [
                    'meta_title' => Str::limit((string) ($decoded['meta_title'] ?? ''), 70, ''),
                    'meta_description' => Str::limit((string) ($decoded['meta_description'] ?? ''), 320, ''),
                ];
            }
        }

        throw new RuntimeException('OpenAI meta yanıtı işlenemedi.');
    }

    private function stripCodeFences(string $text): string
    {
        $text = trim($text);
        if (preg_match('/^```(?:json|html)?\s*([\s\S]*?)```$/i', $text, $m)) {
            return trim($m[1]);
        }

        return $text;
    }
}
