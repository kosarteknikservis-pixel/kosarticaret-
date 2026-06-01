<?php

namespace App\Http\Controllers\Admin;

use App\Mail\CampaignMail;
use App\Http\Controllers\Controller;
use App\Models\EmailCampaign;
use App\Models\EmailTemplate;
use App\Models\NewsletterSubscriber;
use App\Support\EmailTemplateParams;
use App\Support\MailSettings;
use App\Support\RichContent;
use App\Support\SafeMailHtml;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class NewsletterController extends Controller
{
    public function index(): View
    {
        return view('admin.newsletter.index', [
            'subscribers' => NewsletterSubscriber::query()->where('active', true)->latest()->paginate(50),
            'campaigns' => EmailCampaign::query()->withCount('recipients')->latest()->paginate(15, ['*'], 'campaign_page'),
            'templates' => EmailTemplate::query()->where('active', true)->orderBy('name')->get(),
        ]);
    }

    public function storeCampaign(Request $request): RedirectResponse
    {
        $data = $this->campaignData($request);
        $data['user_id'] = auth()->id();
        $data['status'] = 'draft';

        $campaign = EmailCampaign::query()->create($data);

        return redirect()
            ->route('admin.newsletter.index')
            ->with('success', "Kampanya taslağı oluşturuldu: {$campaign->title}");
    }

    public function testCampaign(Request $request, EmailCampaign $campaign): RedirectResponse
    {
        $data = $request->validate([
            'test_email' => ['required', 'email', 'max:255'],
        ]);

        try {
            MailSettings::apply();
            Mail::to($data['test_email'])->send(new CampaignMail($campaign));
        } catch (\Throwable $e) {
            return back()->withErrors(['campaign' => 'Test e-postası gönderilemedi: '.$e->getMessage()]);
        }

        return back()->with('success', 'Test kampanya e-postası gönderildi.');
    }

    public function previewCampaign(EmailCampaign $campaign): View
    {
        return view('emails.campaign', [
            'campaign' => $campaign,
            'params' => EmailTemplateParams::campaign($campaign->title),
        ]);
    }

    public function editCampaign(EmailCampaign $campaign): View
    {
        return view('admin.newsletter.campaign-edit', [
            'campaign' => $campaign,
            'templates' => EmailTemplate::query()->where('active', true)->orderBy('name')->get(),
        ]);
    }

    public function updateCampaign(Request $request, EmailCampaign $campaign): RedirectResponse
    {
        if ($campaign->status === 'sent') {
            return back()->withErrors(['campaign' => 'Gönderilmiş kampanyalar düzenlenemez. Yeni bir kampanya oluşturun.']);
        }

        $campaign->update($this->campaignData($request));

        return redirect()
            ->route('admin.newsletter.campaigns.edit', $campaign)
            ->with('success', 'Kampanya güncellendi.');
    }

    public function destroyCampaign(EmailCampaign $campaign): RedirectResponse
    {
        $campaign->delete();

        return redirect()
            ->route('admin.newsletter.index')
            ->with('success', 'Kampanya silindi.');
    }

    public function sendCampaign(EmailCampaign $campaign): RedirectResponse
    {
        if ($campaign->status === 'sent') {
            return back()->withErrors(['campaign' => 'Bu kampanya daha önce gönderilmiş.']);
        }

        $emails = NewsletterSubscriber::query()
            ->where('active', true)
            ->pluck('email')
            ->filter()
            ->unique()
            ->values();

        if ($emails->isEmpty()) {
            return back()->withErrors(['campaign' => 'Aktif bülten abonesi yok.']);
        }

        $sent = 0;
        $failed = 0;
        MailSettings::apply();

        foreach ($emails as $email) {
            $recipient = $campaign->recipients()->create(['email' => $email]);
            try {
                Mail::to($email)->send(new CampaignMail($campaign));
                $recipient->update(['status' => 'sent', 'sent_at' => now()]);
                $sent++;
            } catch (\Throwable $e) {
                $recipient->update(['status' => 'failed', 'error' => $e->getMessage()]);
                $failed++;
            }
        }

        $campaign->update([
            'status' => 'sent',
            'recipients_count' => $emails->count(),
            'sent_count' => $sent,
            'failed_count' => $failed,
            'sent_at' => now(),
        ]);

        return back()->with('success', "Kampanya gönderimi tamamlandı. Başarılı: {$sent}, hatalı: {$failed}.");
    }

    /** @return array<string, mixed> */
    private function campaignData(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'subject' => ['required', 'string', 'max:180'],
            'preheader' => ['nullable', 'string', 'max:220'],
            'body' => ['required', 'string', 'max:5000'],
            'image_url' => ['nullable', 'url', 'max:500'],
            'button_label' => ['nullable', 'string', 'max:80'],
            'button_url' => ['nullable', 'url', 'max:500'],
            'audience' => ['required', 'string', 'in:newsletter'],
        ]);

        $data['body_is_html'] = RichContent::isHtml($data['body']);
        $data['body'] = $data['body_is_html']
            ? SafeMailHtml::sanitize($data['body'])
            : trim($data['body']);

        return $data;
    }
}
