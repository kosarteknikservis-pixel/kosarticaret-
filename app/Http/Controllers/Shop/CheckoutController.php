<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Order;
use App\Services\AnalyticsTracker;
use App\Services\CartService;
use App\Services\CartPricingService;
use App\Services\Payment\PaymentManager;
use App\Services\StoreConfig;
use App\Support\PaymentGatewayConfig;
use App\Services\CheckoutCalculator;
use App\Services\CouponService;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function __construct(
        private CartService $cart,
        private CouponService $coupons,
        private CartPricingService $pricing,
        private CheckoutCalculator $calculator,
        private OrderService $orders,
        private StoreConfig $store,
    ) {}

    public function show(Request $request): View|RedirectResponse
    {
        if ($this->cart->isEmpty()) {
            return redirect()->route('cart.index')->withErrors(['cart' => 'Sepetiniz boş.']);
        }

        app(AnalyticsTracker::class)->trackCheckoutStarted($request, $this->cart);

        return $this->checkoutView();
    }

    public function applyCoupon(Request $request): RedirectResponse
    {
        $code = $request->string('coupon_code');
        $result = $this->coupons->apply($code);

        return back()->with(
            $result['ok'] ? 'success' : 'error',
            $result['message'],
        );
    }

    public function removeCoupon(): RedirectResponse
    {
        $this->coupons->remove();

        return back()->with('success', 'Kupon kaldırıldı.');
    }

    public function store(Request $request): RedirectResponse
    {
        if ($this->cart->isEmpty()) {
            return redirect()->route('cart.index');
        }

        $districtsByCity = config('turkiye.cities', []);

        $data = $request->validate([
            'ad' => ['required', 'string', 'max:100'],
            'soyad' => ['required', 'string', 'max:100'],
            'eposta' => ['required', 'email'],
            'telefon' => ['required', 'string', 'max:30'],
            'il' => ['required', 'string', Rule::in(array_keys($districtsByCity))],
            'ilce' => ['required', 'string', 'max:100', Rule::in($districtsByCity[$request->input('il')] ?? [])],
            'adres' => ['required', 'string', 'max:500'],
            'posta_kodu' => ['nullable', 'string', 'max:10'],
            'kurumsal_fatura' => ['sometimes', 'boolean'],
            'firma_adi' => ['nullable', 'required_if:kurumsal_fatura,1', 'string', 'max:200'],
            'vergi_numarasi' => ['nullable', 'required_if:kurumsal_fatura,1', 'string', 'max:30'],
            'vergi_dairesi' => ['nullable', 'required_if:kurumsal_fatura,1', 'string', 'max:120'],
            'fatura_adresi' => ['nullable', 'required_if:kurumsal_fatura,1', 'string', 'max:500'],
            'kargo_yontemi' => ['required', Rule::in(array_column($this->shippingMethodsForCheckout(), 'id'))],
            'odeme_yontemi' => ['required', Rule::in($this->store->enabledPaymentIds())],
            'sozlesme' => ['accepted'],
        ]);

        $teslimat = [
            'ad' => $data['ad'],
            'soyad' => $data['soyad'],
            'eposta' => $data['eposta'],
            'telefon' => $data['telefon'],
            'il' => $data['il'],
            'ilce' => $data['ilce'],
            'adres' => $data['adres'],
            'postaKodu' => $data['posta_kodu'] ?? null,
        ];

        if ($request->boolean('kurumsal_fatura')) {
            $teslimat['kurumsalFatura'] = [
                'firmaAdi' => $data['firma_adi'],
                'vergiNumarasi' => $data['vergi_numarasi'],
                'vergiDairesi' => $data['vergi_dairesi'],
                'faturaAdresi' => $data['fatura_adresi'],
            ];
        }

        app(AnalyticsTracker::class)->updateCheckoutContact($request, $this->cart, $data);

        try {
            $result = $this->orders->create(
                $teslimat,
                $data['kargo_yontemi'],
                $data['odeme_yontemi'],
            );
        } catch (\RuntimeException $e) {
            return back()->withInput()->withErrors(['cart' => $e->getMessage()]);
        }

        $order = $result['order'];
        app(AnalyticsTracker::class)->attachOrder($request, $order);
        session(['last_order_email' => $order->email]);

        if ($result['payment_url']) {
            return redirect($result['payment_url']);
        }

        return redirect()->route('checkout.success', ['order' => $order->order_number]);
    }

    public function success(string $order): View
    {
        $model = Order::query()->where('order_number', $order)->with('items')->firstOrFail();

        return view('shop.checkout.success', [
            'menuCategories' => Category::menu()->get(),
            'order' => $model,
        ]);
    }

    public function payment(string $order, PaymentManager $payments): View|RedirectResponse
    {
        $model = Order::query()->where('order_number', $order)->firstOrFail();

        if ($model->payment_status === 'basarili') {
            return redirect()->route('checkout.success', ['order' => $model->order_number]);
        }

        $isDemo = ! PaymentGatewayConfig::isLive();
        $retryUrl = null;
        if ($model->payment_method === 'kredi_karti' && ! $isDemo) {
            $odeme = $payments->baslat($model);
            if ($odeme['basarili'] && ! empty($odeme['odeme_url'])) {
                $retryUrl = $odeme['odeme_url'];
            }
        }

        return view('shop.checkout.payment', [
            'menuCategories' => Category::menu()->get(),
            'order' => $model,
            'isDemo' => $isDemo,
            'gatewayLabel' => PaymentGatewayConfig::label(),
            'retryUrl' => $retryUrl,
        ]);
    }

    public function completePayment(string $order): RedirectResponse
    {
        abort_unless(! PaymentGatewayConfig::isLive() && request()->boolean('demo'), 404);

        $model = Order::query()->where('order_number', $order)->firstOrFail();
        if ($model->payment_status !== 'basarili') {
            $this->orders->confirmPayment($model);
        }

        return redirect()->route('checkout.success', ['order' => $model->order_number])
            ->with('success', request()->has('demo') ? 'Ödeme alındı (demo).' : 'Ödeme alındı.');
    }

    /** @return array<int, array<string, string>> */
    private function shippingMethodsForCheckout(): array
    {
        return $this->store->shippingMethods();
    }

    private function checkoutView(): View|RedirectResponse
    {
        $paymentMethods = $this->store->paymentMethods();
        if ($paymentMethods === []) {
            return redirect()->route('cart.index')->withErrors([
                'cart' => 'Şu an aktif ödeme yöntemi yok. Lütfen mağaza yönetimi ile iletişime geçin.',
            ]);
        }

        $breakdown = $this->pricing->breakdown();
        $coupon = $this->coupons->findValid($this->coupons->appliedCode() ?? '');
        $shippingMethods = $this->shippingMethodsForCheckout();
        $districtsByCity = config('turkiye.cities', []);
        $defaultShipping = old('kargo_yontemi', $shippingMethods[0]['id'] ?? 'standart');
        $defaultPayment = old('odeme_yontemi', $paymentMethods[0]['id']);
        if (! in_array($defaultPayment, array_column($paymentMethods, 'id'), true)) {
            $defaultPayment = $paymentMethods[0]['id'];
        }
        $totals = $this->calculator->totals(
            $breakdown['subtotal'],
            $breakdown['total_discount'],
            $defaultShipping,
            $defaultPayment,
            $breakdown['free_shipping'],
        );

        return view('shop.checkout.index', [
            'lines' => $this->cart->lines(),
            'pricing' => $breakdown,
            'totals' => $totals,
            'coupon' => $coupon,
            'cities' => array_keys($districtsByCity),
            'districtsByCity' => $districtsByCity,
            'shippingMethods' => $shippingMethods,
            'paymentMethods' => $paymentMethods,
            'defaultPayment' => $defaultPayment,
            'defaultShipping' => $defaultShipping,
        ]);
    }
}
