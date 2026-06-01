<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use App\Support\RichContent;
use App\Support\SafeMailHtml;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class EmailTemplateController extends Controller
{
    public function index(): View
    {
        EmailTemplate::ensureDefaults();

        return view('admin.email-templates.index', [
            'templates' => EmailTemplate::query()->orderBy('name')->get(),
        ]);
    }

    public function edit(EmailTemplate $emailTemplate): View
    {
        return view('admin.email-templates.edit', ['template' => $emailTemplate]);
    }

    public function create(): View
    {
        return view('admin.email-templates.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['name' => ['required', 'string', 'max:120']]);

        $data = $this->validatedTemplateData($request);
        $data['key'] = $this->uniqueCustomKey($request->string('name')->toString());
        $data['name'] = $request->string('name')->trim()->toString();
        $data['active'] = true;

        $template = EmailTemplate::query()->create($data);

        return redirect()
            ->route('admin.email-templates.edit', $template)
            ->with('success', 'Yeni e-posta şablonu oluşturuldu.');
    }

    public function preview(EmailTemplate $emailTemplate): View
    {
        return view('emails.template-preview', [
            'template' => $emailTemplate,
            'params' => [
                'site_name' => config('kosar.name', config('app.name')),
                'order_number' => 'KOS-10001',
                'customer_name' => 'Değerli müşterimiz',
                'status' => 'Hazırlanıyor',
                'payment_status' => 'Başarılı',
                'total' => '1.250,00 ₺',
                'tracking_number' => 'TRK123456789',
                'tracking_text' => 'Kargo takip numaranız: TRK123456789',
                'tracking_url' => route('tracking.show'),
                'campaign_title' => 'Örnek kampanya',
                'home_url' => route('home'),
            ],
        ]);
    }

    public function update(Request $request, EmailTemplate $emailTemplate): RedirectResponse
    {
        $data = $this->validatedTemplateData($request);
        $data['active'] = $request->boolean('active');

        $emailTemplate->update($data);

        return redirect()
            ->route('admin.email-templates.edit', $emailTemplate)
            ->with('success', 'E-posta şablonu güncellendi.');
    }

    /** @return array<string, mixed> */
    private function validatedTemplateData(Request $request): array
    {
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:120'],
            'subject' => ['required', 'string', 'max:180'],
            'preheader' => ['nullable', 'string', 'max:220'],
            'title' => ['required', 'string', 'max:160'],
            'body' => ['required', 'string', 'max:5000'],
            'button_label' => ['nullable', 'string', 'max:80'],
            'button_url' => ['nullable', 'string', 'max:500'],
            'footer_note' => ['nullable', 'string', 'max:1000'],
            'show_items' => ['sometimes', 'boolean'],
            'show_tracking' => ['sometimes', 'boolean'],
        ]);

        $data['settings'] = [
            'show_items' => $request->boolean('show_items'),
            'show_tracking' => $request->boolean('show_tracking'),
        ];
        $data['body_is_html'] = RichContent::isHtml($data['body']);
        $data['body'] = $data['body_is_html']
            ? SafeMailHtml::sanitize($data['body'])
            : trim($data['body']);

        unset($data['show_items'], $data['show_tracking']);

        return $data;
    }

    private function uniqueCustomKey(string $name): string
    {
        $base = 'custom_'.Str::slug($name ?: 'sablon');
        $key = $base;
        $index = 2;

        while (EmailTemplate::query()->where('key', $key)->exists()) {
            $key = $base.'_'.$index;
            $index++;
        }

        return $key;
    }
}
