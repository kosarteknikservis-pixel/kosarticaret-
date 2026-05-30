<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class LocaleController extends Controller
{
    public function __invoke(string $locale): RedirectResponse
    {
        if (in_array($locale, config('kosar.locales', ['tr', 'en']), true)) {
            session(['locale' => $locale]);
        }

        return back();
    }
}
