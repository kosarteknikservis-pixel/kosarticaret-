<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\SiteSetting;
use App\Models\SupportChatMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SupportAssistantCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private const DETAILS = [
        'ad' => 'Ahmet', 'soyad' => 'Yılmaz', 'telefon' => '0532 111 22 33', 'eposta' => 'Ahmet@Example.com',
        'il' => 'istanbul', 'ilce' => 'kadikoy', 'adres' => 'Caferağa Mah. Moda Cad. No:5 D:3',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        foreach ([
            'openai_api_key' => 'sk-test',
            'ai_assistant_enabled' => '1',
            'payment_checkout_enabled' => 'kredi_karti,havale,kapida_odeme',
            'payment_gateway' => 'mock',
            'sms_enabled' => '0',
        ] as $key => $value) {
            SiteSetting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }
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

    private function chat(string $message): \Illuminate\Testing\TestResponse
    {
        return $this->postJson(route('support-chat.message'), ['message' => $message, 'page' => '/'])->assertOk();
    }

    /** @return array<string, mixed>|null */
    private function lastToolResult(string $key): ?array
    {
        $found = null;
        foreach (Http::recorded() as [$request]) {
            foreach ($request->data()['messages'] ?? [] as $message) {
                if (($message['role'] ?? '') === 'tool') {
                    $decoded = json_decode($message['content'], true);
                    if (is_array($decoded) && array_key_exists($key, $decoded)) {
                        $found = $decoded;
                    }
                }
            }
        }

        return $found;
    }

    #[Test]
    public function assistant_creates_card_order_after_confirmation_and_redirects_to_payment(): void
    {
        Http::fake(['api.openai.com/*' => Http::sequence()
            ->push($this->toolCall('add_to_cart', ['product' => 'test-hidrofor']))
            ->push($this->text('Ürünü sepete ekledim. Bilgilerinizi yazar mısınız?'))
            ->push($this->toolCall('update_order_details', [...self::DETAILS, 'odeme_yontemi' => 'kredi_karti']))
            ->push($this->text('Toplam 1.500,00 ₺. Siparişi onaylıyor musunuz?'))
            ->push($this->toolCall('create_order', ['customer_confirmed' => true]))
            ->push($this->text('Siparişiniz oluşturuldu, ödeme sayfasına yönlendiriyorum.')),
        ]);

        $this->chat('Test hidroforu satın alacağım')->assertJsonPath('cart_count', 1);

        $this->chat('Ahmet Yılmaz, 0532 111 22 33, Ahmet@Example.com, İstanbul Kadıköy, Caferağa Mah. Moda Cad. No:5 D:3, kartla ödeyeceğim')
            ->assertJsonPath('order_action', null);
        $this->assertSame(0, Order::query()->count());
        $summary = $this->lastToolResult('ozet');
        $this->assertTrue($summary['hazir']);
        $this->assertSame('1.500,00 ₺', $summary['ozet']['toplam']);
        $this->assertSame(route('pages.show', 'mesafeli-satis-sozlesmesi'), $summary['sozlesmeler']['mesafeli_satis']);

        $response = $this->chat('Evet onaylıyorum')
            ->assertJsonPath('cart_count', 0)
            ->assertJsonPath('order_action.redirect', true);

        $order = Order::query()->sole();
        $this->assertSame('kredi_karti', $order->payment_method);
        $this->assertSame('odeme_bekliyor', $order->status);
        $this->assertSame('05321112233', $order->phone);
        $this->assertSame('ahmet@example.com', $order->email);
        $this->assertEquals(1500, (float) $order->total);
        $this->assertSame($order->order_number, $response->json('order_number'));
        $this->assertStringContainsString($order->order_number, $response->json('order_action.url'));
        $this->assertSame('KADIKÖY', $order->shipping_address['teslimat']['ilce']);

        $userMessages = SupportChatMessage::query()->where('role', 'user')->orderBy('id')->pluck('content');
        $this->assertSame('Test hidroforu satın alacağım', $userMessages[0]);
        $this->assertSame('[Teslimat bilgileri gizlendi]', $userMessages[1]);
    }

    #[Test]
    public function order_is_not_created_when_summary_was_not_shown_to_customer(): void
    {
        Http::fake(['api.openai.com/*' => Http::sequence()
            ->push($this->toolCall('add_to_cart', ['product' => 'test-hidrofor']))
            ->push($this->toolCall('update_order_details', [...self::DETAILS, 'odeme_yontemi' => 'kredi_karti']))
            ->push($this->toolCall('create_order', ['customer_confirmed' => true]))
            ->push($this->text('Önce özeti onaylamanız gerekiyor.')),
        ]);

        $this->chat('Test hidroforu alacağım: Ahmet Yılmaz 05321112233 ahmet@example.com İstanbul Kadıköy Caferağa Mah. Moda Cad. No:5 kart')
            ->assertJsonPath('order_action', null);

        $this->assertSame(0, Order::query()->count());
        $this->assertSame('ozet_onaylanmadi', $this->lastToolResult('hata')['hata']);
    }

    #[Test]
    public function partial_details_are_kept_and_only_missing_fields_are_reported(): void
    {
        Http::fake(['api.openai.com/*' => Http::sequence()
            ->push($this->toolCall('add_to_cart', ['product' => 'test-hidrofor']))
            ->push($this->toolCall('update_order_details', ['ad' => 'Mehmet Demir', 'telefon' => '5321112233', 'il' => 'Ankara']))
            ->push($this->text('Eksik: e-posta, ilçe, açık adres, ödeme yöntemi.'))
            ->push($this->text('Kalan eksikler: e-posta, ilçe, açık adres, ödeme yöntemi.'))
            ->push($this->toolCall('update_order_details', ['eposta' => 'mehmet@example.com', 'ilce' => 'Çankaya', 'adres' => 'Kızılay Mah. Atatürk Blv. No:10 D:4', 'odeme_yontemi' => 'havale']))
            ->push($this->text('Özeti kontrol eder misiniz?')),
        ]);

        $this->chat('Alacağım. Mehmet Demir 5321112233 Ankara');
        $result = $this->lastToolResult('eksik');
        $this->assertSame(['E-posta', 'İlçe', 'Açık adres', 'Ödeme yöntemi'], $result['eksik']);
        $this->assertSame(['Ad', 'Soyad', 'Cep telefonu', 'İl'], $result['alinan']);
        $this->assertSame(['kredi_karti', 'havale', 'kapida_odeme'], array_column($result['odeme_secenekleri'], 'id'));

        $this->chat('Kargo ne kadar sürer?');
        Http::assertSent(fn ($request) => str_contains(json_encode($request->data(), JSON_UNESCAPED_UNICODE), 'SİPARİŞ (bilgi toplanıyor): Alınan: Ad, Soyad, Cep telefonu, İl. Eksik: E-posta, İlçe, Açık adres, Ödeme yöntemi'));

        $this->chat('mehmet@example.com, Çankaya, Kızılay Mah. Atatürk Blv. No:10 D:4, havale');
        $summary = $this->lastToolResult('ozet');
        $this->assertSame('Havale / EFT', $summary['ozet']['odeme_yontemi']);
        $this->assertStringContainsString('ÇANKAYA / ANKARA', $summary['ozet']['teslimat']);
        $this->assertSame(0, Order::query()->count());

        $this->get(route('support-chat.checkout'))
            ->assertSessionHasInput('ad', 'Mehmet')
            ->assertSessionHasInput('soyad', 'Demir')
            ->assertSessionHasInput('telefon', '05321112233')
            ->assertSessionHasInput('ilce', 'ÇANKAYA');
    }

    #[Test]
    public function invalid_details_are_reported_with_reason_and_suggestion(): void
    {
        Http::fake(['api.openai.com/*' => Http::sequence()
            ->push($this->toolCall('add_to_cart', ['product' => 'test-hidrofor']))
            ->push($this->toolCall('update_order_details', [
                'ad' => 'Ayşe', 'soyad' => 'Kaya', 'telefon' => '123', 'eposta' => 'ayse@example.com',
                'il' => 'İzmir', 'ilce' => 'Karşıyak', 'adres' => 'Bostanlı Mah. 1800 Sok. No:7', 'odeme_yontemi' => 'kredi_karti',
            ]))
            ->push($this->text('Telefon ve ilçe bilgisini kontrol eder misiniz?')),
        ]);

        $this->chat('Alacağım');

        $hatali = implode(' | ', $this->lastToolResult('hatali')['hatali']);
        $this->assertStringContainsString('Cep telefonu 05 ile başlayan 11 hane olmalı', $hatali);
        $this->assertStringContainsString('Karşıyak ilçesi İZMİR ilinde bulunamadı; bunu mu kastettiniz: KARŞIYAKA', $hatali);
        $this->assertSame(0, Order::query()->count());
    }

    #[Test]
    public function cash_on_delivery_order_is_created_after_sms_code(): void
    {
        foreach (['sms_enabled' => '1', 'sms_provider' => 'netgsm', 'netgsm_usercode' => 'u', 'netgsm_password' => 'p', 'netgsm_header' => 'KOSAR'] as $key => $value) {
            SiteSetting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }
        Cache::flush();

        Http::fake([
            'api.netgsm.com.tr/*' => Http::response('00 123456789'),
            'api.openai.com/*' => Http::sequence()
                ->push($this->toolCall('add_to_cart', ['product' => 'test-hidrofor']))
                ->push($this->toolCall('update_order_details', [...self::DETAILS, 'odeme_yontemi' => 'kapida_odeme']))
                ->push($this->text('Kapıda ödeme ücretiyle toplam 1.529,90 ₺. Onaylıyor musunuz?'))
                ->push($this->toolCall('create_order', ['customer_confirmed' => true]))
                ->push($this->text('Telefonunuza gelen 6 haneli kodu yazın.')),
        ]);

        $this->chat('Test hidroforu kapıda ödemeyle alacağım, bilgilerim: Ahmet Yılmaz 0532 111 22 33 ahmet@example.com İstanbul Kadıköy Caferağa Mah. Moda Cad. No:5 D:3');
        $this->assertSame('29,90 ₺', $this->lastToolResult('ozet')['ozet']['kapida_odeme_ucreti']);

        $this->chat('Evet')->assertJsonPath('awaiting_code', true);
        $this->assertSame(0, Order::query()->count());

        $sms = collect(Http::recorded())->first(fn (array $pair) => str_contains($pair[0]->url(), 'netgsm'))[0];
        parse_str((string) parse_url($sms->url(), PHP_URL_QUERY), $query);
        $this->assertSame('905321112233', $query['gsmno']);
        preg_match('/\b(\d{6})\b/', $query['message'], $match);
        $code = $match[1];
        $openAiCalls = collect(Http::recorded())->filter(fn (array $pair) => str_contains($pair[0]->url(), 'openai'))->count();

        $this->chat($code === '111111' ? '222222' : '111111')
            ->assertJsonPath('awaiting_code', true)
            ->assertJsonPath('reply', 'Kod hatalı görünüyor. Telefonunuza gelen 6 haneli kodu tekrar yazar mısınız? Kod gelmediyse "kodu tekrar gönder" yazabilirsiniz.');

        $response = $this->chat($code)->assertJsonPath('awaiting_code', false)->assertJsonPath('order_action.redirect', false);

        $order = Order::query()->sole();
        $this->assertSame('kapida_odeme', $order->payment_method);
        $this->assertSame('hazirlaniyor', $order->status);
        $this->assertEquals(1529.90, (float) $order->total);
        $this->assertStringContainsString($order->order_number, $response->json('reply'));
        $this->assertSame($openAiCalls, collect(Http::recorded())->filter(fn (array $pair) => str_contains($pair[0]->url(), 'openai'))->count());
    }

    #[Test]
    public function card_numbers_are_masked_before_storage(): void
    {
        Http::fake(['api.openai.com/*' => Http::response($this->text('Kart bilgisini ödeme sayfasındaki güvenli ekrana girebilirsiniz.'))]);

        $this->chat('Kartım 4111 1111 1111 1111, son kullanma 12/28');

        $stored = SupportChatMessage::query()->where('role', 'user')->value('content');
        $this->assertStringNotContainsString('4111', $stored);
        $this->assertStringContainsString('[kart numarası gizlendi]', $stored);
        Http::assertSent(fn (HttpRequest $request) => ! str_contains(json_encode($request->data()), '4111 1111'));
    }
}
