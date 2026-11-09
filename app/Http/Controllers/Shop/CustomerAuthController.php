<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Seo;
use App\Services\AnalyticsTracker;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CustomerAuthController extends Controller
{
    public function showLogin(): View
    {
        return view('shop.auth.login', [
            'metaTitle' => 'Giriş',
            ...Seo::noIndexMeta(),
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            if (Auth::user()?->is_admin) {
                Auth::logout();

                return back()->withErrors(['email' => 'Mağaza girişi için müşteri hesabı kullanın. Yönetim paneli: /yonetim/giris']);
            }

            app(AnalyticsTracker::class)->linkAuthenticatedUser($request);

            return redirect()->intended(route('account.index'));
        }

        return back()->withErrors(['email' => 'E-posta veya şifre hatalı.'])->onlyInput('email');
    }

    public function showRegister(): View
    {
        return view('shop.auth.register', [
            'metaTitle' => 'Kayıt ol',
            ...Seo::noIndexMeta(),
        ]);
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'is_admin' => false,
        ]);

        event(new \Illuminate\Auth\Events\Registered($user));

        Auth::login($user);
        $request->session()->regenerate();

        app(AnalyticsTracker::class)->linkAuthenticatedUser($request);

        return redirect()->route('account.index')->with('success', 'Hesabınız oluşturuldu.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    public function showForgotPassword(): View
    {
        return view('shop.auth.forgot-password', [
            'metaTitle' => 'Şifremi unuttum',
            ...Seo::noIndexMeta(),
        ]);
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $user = User::query()->where('email', $request->input('email'))->first();
        if (! $user || $user->isAdmin()) {
            return back()->withErrors(['email' => 'Bu e-posta ile kayıtlı müşteri hesabı bulunamadı.'])->onlyInput('email');
        }

        $status = Password::broker()->sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT
            ? back()->with('success', 'Şifre sıfırlama bağlantısı e-posta adresinize gönderildi.')
            : back()->withErrors(['email' => 'Şifre sıfırlama bağlantısı gönderilemedi. SMTP ayarlarını kontrol edin.'])->onlyInput('email');
    }

    public function showResetPassword(Request $request, string $token): View
    {
        return view('shop.auth.reset-password', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
            'metaTitle' => 'Yeni şifre belirle',
            ...Seo::noIndexMeta(),
        ]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $status = Password::broker()->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password): void {
                if ($user->isAdmin()) {
                    return;
                }

                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('success', 'Şifreniz güncellendi. Giriş yapabilirsiniz.');
        }

        return back()->withErrors(['email' => 'Şifre sıfırlama bağlantısı geçersiz veya süresi dolmuş.'])->onlyInput('email');
    }
}
