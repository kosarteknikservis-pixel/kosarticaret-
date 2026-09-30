<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\SiteSetting;
use App\Models\SupportChatMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SupportAssistantCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        SiteSetting::query()->updateOrCreate(['key' => 'openai_api_key'], ['value' => 'sk-test']);
        SiteSetting::query()->updateOrCreate(['key' => 'ai_assistant_enabled'], ['value' => '1']);
        Cache::flush();

        Product::query()->create([
            'slug' => 'test-hidrofor',
            'name' => 'Test Hidrofor 5 Kat 12 Daire',
            'price' => 1500,
            'stock' => 5,
            'is_active' => true,
        ]);
    }

    /** @param  array<string, mixed>  $args */
    private function toolCall(string $name, array $args): array
    {
        return ['choices' => [['message' => ['role' => 'assistant', 'content' => null, 'tool_calls' => [[
            'id' => 'call_'.$name,
            'type' => 'function',
            'function' => ['name' => $name, 'arguments' => json_encode($args, JSON_UNESCAPED_UNICODE)],
        ]]]]], 'usage' => ['total_tokens' => 10]];
    }

    private function text(string $content): array
    {
        return ['choices' => [['message' => ['role' => 'assistant', 'content' => $content]]], 'usage' => ['total_tokens' => 10]];
    }

    #[Test]
    public function assistant_adds_to_cart_prefills_checkout_and_redacts_personal_data(): void
    {
        Http::fake(['api.openai.com/*' => Http::sequence()
            ->push($this->toolCall('add_to_cart', ['product' => 'test-hidrofor']))
            ->push($this->text('Ürünü sepete ekledim. Ad soyad, telefon, e-posta, il, ilçe ve açık adresinizi yazar mısınız?'))
            ->push($this->toolCall('prepare_checkout', [
                'ad' => 'Ahmet', 'soyad' => 'Yılmaz', 'telefon' => '0532 111 22 33', 'eposta' => 'Ahmet@Example.com',
                'il' => 'istanbul', 'ilce' => 'kadikoy', 'adres' => 'Caferağa Mah. Moda Cad. No:5 D:3',
            ]))
            ->push($this->text('Bilgileriniz aktarıldı, ödeme sayfasına yönlendiriyorum.')),
        ]);

        $this->postJson(route('support-chat.message'), ['message' => 'Test hidroforu satın alacağım', 'page' => '/'])
            ->assertOk()
            ->assertJsonPath('cart_count', 1)
            ->assertJsonPath('checkout_url', null);

        $this->postJson(route('support-chat.message'), [
            'message' => 'Ahmet Yılmaz, 0532 111 22 33, Ahmet@Example.com, İstanbul Kadıköy, Caferağa Mah. Moda Cad. No:5 D:3',
            'page' => '/',
        ])
            ->assertOk()
            ->assertJsonPath('checkout_url', route('support-chat.checkout'));

        $this->get(route('support-chat.checkout'))
            ->assertRedirect(route('checkout.show'))
            ->assertSessionHasInput('il', 'İSTANBUL')
            ->assertSessionHasInput('ilce', 'KADIKÖY')
            ->assertSessionHasInput('telefon', '05321112233')
            ->assertSessionHasInput('eposta', 'ahmet@example.com');

        $this->get(route('checkout.show'))
            ->assertOk()
            ->assertSee('value="ahmet@example.com"', false)
            ->assertSee('Caferağa Mah. Moda Cad. No:5 D:3');

        $userMessages = SupportChatMessage::query()->where('role', 'user')->pluck('content');
        $this->assertSame('Test hidroforu satın alacağım', $userMessages[0]);
        $this->assertSame('[Teslimat bilgileri gizlendi]', $userMessages[1]);
    }

    #[Test]
    public function invalid_checkout_details_are_reported_back_without_redirect(): void
    {
        Http::fake(['api.openai.com/*' => Http::sequence()
            ->push($this->toolCall('add_to_cart', ['product' => 'test-hidrofor']))
            ->push($this->toolCall('prepare_checkout', [
                'ad' => 'Ayşe', 'soyad' => 'Kaya', 'telefon' => '123', 'eposta' => 'ayse@example.com',
                'il' => 'İzmir', 'ilce' => 'Kadıköy', 'adres' => 'Alsancak Mah. 1453 Sok. No:7',
            ]))
            ->push($this->text('Telefon ve ilçe bilgisini kontrol eder misiniz?')),
        ]);

        $this->postJson(route('support-chat.message'), ['message' => 'Alacağım', 'page' => '/'])
            ->assertOk()
            ->assertJsonPath('checkout_url', null);

        $this->assertNull(session('support_chat_checkout'));
        Http::assertSent(fn ($request) => str_contains(json_encode($request->data(), JSON_UNESCAPED_UNICODE), 'Kadıköy ilçesi İZMİR ilinde bulunamadı')
            && str_contains(json_encode($request->data(), JSON_UNESCAPED_UNICODE), 'Cep telefonu 11 haneli olmalı'));
    }

    #[Test]
    public function partial_details_are_kept_and_only_missing_fields_are_reported(): void
    {
        Http::fake(['api.openai.com/*' => Http::sequence()
            ->push($this->toolCall('add_to_cart', ['product' => 'test-hidrofor']))
            ->push($this->toolCall('prepare_checkout', ['ad' => 'Mehmet Demir', 'telefon' => '5321112233', 'il' => 'Ankara']))
            ->push($this->text('Eksik: e-posta, ilçe, açık adres.'))
            ->push($this->text('Kalan eksikler: e-posta, ilçe, açık adres.'))
            ->push($this->toolCall('prepare_checkout', ['eposta' => 'mehmet@example.com', 'ilce' => 'Çankaya', 'adres' => 'Kızılay Mah. Atatürk Blv. No:10 D:4']))
            ->push($this->text('Bilgileriniz tamam, ödeme sayfasına yönlendiriyorum.')),
        ]);

        $this->postJson(route('support-chat.message'), ['message' => 'Alacağım. Mehmet Demir 5321112233 Ankara', 'page' => '/'])
            ->assertOk()
            ->assertJsonPath('checkout_url', null);

        Http::assertSent(function ($request) {
            $result = collect($request->data()['messages'] ?? [])
                ->where('role', 'tool')
                ->map(fn ($m) => json_decode($m['content'], true))
                ->first(fn ($r) => isset($r['eksik']));

            return ($result['eksik'] ?? null) === ['E-posta', 'İlçe', 'Açık adres']
                && ($result['alinan'] ?? null) === ['Ad', 'Soyad', 'Cep telefonu', 'İl'];
        });

        $this->postJson(route('support-chat.message'), ['message' => 'Kargo ne kadar sürer?', 'page' => '/'])->assertOk();
        Http::assertSent(fn ($request) => str_contains(json_encode($request->data(), JSON_UNESCAPED_UNICODE), 'SİPARİŞ FORMU (devam ediyor): Alınan: Ad, Soyad, Cep telefonu, İl. Eksik: E-posta, İlçe, Açık adres'));

        $this->postJson(route('support-chat.message'), ['message' => 'mehmet@example.com, Çankaya, Kızılay Mah. Atatürk Blv. No:10 D:4', 'page' => '/'])
            ->assertOk()
            ->assertJsonPath('checkout_url', route('support-chat.checkout'));

        $this->get(route('support-chat.checkout'))
            ->assertSessionHasInput('ad', 'Mehmet')
            ->assertSessionHasInput('soyad', 'Demir')
            ->assertSessionHasInput('telefon', '05321112233')
            ->assertSessionHasInput('il', 'ANKARA')
            ->assertSessionHasInput('ilce', 'ÇANKAYA');
    }

    #[Test]
    public function card_numbers_are_masked_before_storage(): void
    {
        Http::fake(['api.openai.com/*' => Http::response($this->text('Kart bilgisini ödeme sayfasındaki güvenli ekrana girebilirsiniz.'))]);

        $this->postJson(route('support-chat.message'), ['message' => 'Kartım 4111 1111 1111 1111, son kullanma 12/28', 'page' => '/'])
            ->assertOk();

        $stored = SupportChatMessage::query()->where('role', 'user')->value('content');
        $this->assertStringNotContainsString('4111', $stored);
        $this->assertStringContainsString('[kart numarası gizlendi]', $stored);
        Http::assertSent(fn ($request) => ! str_contains(json_encode($request->data()), '4111 1111'));
    }
}
