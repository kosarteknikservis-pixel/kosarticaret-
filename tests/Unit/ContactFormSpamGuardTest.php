<?php

namespace Tests\Unit;

use App\Support\ContactFormSpamGuard;
use Illuminate\Http\Request;
use Tests\TestCase;

class ContactFormSpamGuardTest extends TestCase
{
    public function test_honeypot_blocks_contact_submission(): void
    {
        $this->startSession();
        ContactFormSpamGuard::beginForm('contact');

        $request = Request::create('/iletisim', 'POST', [
            '_form_token' => session('public_form.contact.token'),
            'website_url' => 'https://spam.test',
            'ad_soyad' => 'Test',
            'eposta' => 'test@example.com',
            'konu' => 'Konu',
            'mesaj' => 'Mesaj',
        ]);
        $request->headers->set('User-Agent', 'Mozilla/5.0');

        $result = ContactFormSpamGuard::assess($request, 'contact');

        $this->assertTrue($result['blocked']);
        $this->assertSame('honeypot', $result['reason']);
    }

    public function test_spam_keyword_blocks_review_silently(): void
    {
        $this->startSession();
        $token = ContactFormSpamGuard::beginForm('review');

        $request = Request::create('/urun/test/yorum', 'POST', [
            '_form_token' => $token,
            'author_name' => 'Bot',
            'email' => 'bot@example.com',
            'rating' => 5,
            'body' => 'We offer SEO service and backlink for your website.',
        ]);
        $request->headers->set('User-Agent', 'Mozilla/5.0');

        $result = ContactFormSpamGuard::assess($request, 'review');

        $this->assertTrue($result['blocked']);
        $this->assertTrue($result['silent']);
    }

    public function test_gibberish_review_content_is_blocked_silently(): void
    {
        $result = ContactFormSpamGuard::assessContent('review', [
            'author_name' => 'asdasd',
            'email' => 'test@example.com',
            'title' => 'asdasdasd',
            'body' => 'asdasdasdasdasdasd asdasdasd',
        ]);

        $this->assertTrue($result['blocked']);
        $this->assertTrue($result['silent']);
    }
}
