<?php



namespace App\Http\Controllers\Shop;



use App\Http\Controllers\Controller;

use App\Models\ContactMessage;

use App\Models\SiteSetting;

use App\Support\Seo;

use Illuminate\Http\RedirectResponse;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\Mail;

use Illuminate\View\View;



class ContactController extends Controller

{

    public function show(): View

    {

        $breadcrumbs = [

            ['name' => 'Ana Sayfa', 'url' => route('home')],

            ['name' => 'İletişim'],

        ];



        $metaTitle = SiteSetting::get('contact_meta_title', 'İletişim — '.config('kosar.name'));

        $metaDescription = Seo::description([

            SiteSetting::get('contact_meta_description'),

            SiteSetting::get('contact_page_intro'),

            'Bize ulaşın — telefon, e-posta ve iletişim formu.',

        ]);



        return view('shop.contact.index', [

            'breadcrumbs' => $breadcrumbs,

            'metaTitle' => $metaTitle,

            'metaDescription' => $metaDescription,

            'canonical' => route('contact.show'),

            'jsonLd' => [

                Seo::contactPage(),

                Seo::breadcrumbs($breadcrumbs),

            ],

        ]);

    }



    public function store(Request $request): RedirectResponse

    {

        $data = $request->validate([

            'ad_soyad' => ['required', 'string', 'max:120'],

            'eposta' => ['required', 'email'],

            'telefon' => ['nullable', 'string', 'max:30'],

            'konu' => ['required', 'string', 'max:150'],

            'mesaj' => ['required', 'string', 'max:3000'],

        ]);



        ContactMessage::query()->create([

            'name' => $data['ad_soyad'],

            'email' => $data['eposta'],

            'phone' => $data['telefon'] ?? null,

            'subject' => $data['konu'],

            'body' => $data['mesaj'],

        ]);



        $to = SiteSetting::get('contact_email', config('kosar.contact.email'));



        try {

            Mail::raw(

                "Ad: {$data['ad_soyad']}\nE-posta: {$data['eposta']}\nTel: ".($data['telefon'] ?? '-')."\nKonu: {$data['konu']}\n\n{$data['mesaj']}",

                fn ($m) => $m->to($to)->subject('[Kosar İletişim] '.$data['konu'])->replyTo($data['eposta'], $data['ad_soyad']),

            );

        } catch (\Throwable $e) {

            Log::info('contact form', $data);

        }



        return back()->with('success', 'Mesajınız alındı. En kısa sürede dönüş yapacağız.');

    }

}


