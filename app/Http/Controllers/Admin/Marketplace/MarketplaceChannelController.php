<?php

namespace App\Http\Controllers\Admin\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceChannel;
use App\Services\Marketplace\MarketplaceManager;
use App\Services\Marketplace\MarketplaceSyncLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MarketplaceChannelController extends Controller
{
    public function index(MarketplaceManager $manager): View
    {
        return view('admin.marketplace.channels.index', [
            'channels' => $manager->channels(),
        ]);
    }

    public function edit(MarketplaceChannel $channel, MarketplaceManager $manager): View
    {
        $provider = $manager->provider($channel->key);

        return view('admin.marketplace.channels.edit', [
            'channel' => $channel,
            'provider' => $provider,
            'credentialFields' => $provider->credentialFields(),
            'settings' => $channel->settings ?? [],
        ]);
    }

    public function update(Request $request, MarketplaceChannel $channel, MarketplaceManager $manager): RedirectResponse
    {
        $provider = $manager->provider($channel->key);
        $credentialKeys = array_keys($provider->credentialFields());

        $validated = $request->validate([
            'is_active' => ['sometimes', 'boolean'],
            'environment' => ['required', 'in:sandbox,production'],
            'credentials' => ['nullable', 'array'],
            'credentials.*' => ['nullable', 'string', 'max:500'],
            'settings' => ['nullable', 'array'],
            'settings.price_mode' => ['nullable', 'in:site,markup,fixed'],
            'settings.price_markup_percent' => ['nullable', 'numeric', 'min:0', 'max:500'],
            'settings.stock_buffer_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'settings.auto_sync_stock' => ['sometimes', 'boolean'],
            'settings.auto_sync_price' => ['sometimes', 'boolean'],
        ]);

        $existing = $channel->credentials ?? [];
        $incoming = $validated['credentials'] ?? [];

        foreach ($credentialKeys as $key) {
            if (array_key_exists($key, $incoming) && filled($incoming[$key])) {
                $existing[$key] = $incoming[$key];
            }
        }

        $settings = array_merge($channel->settings ?? [], $validated['settings'] ?? []);
        $settings['auto_sync_stock'] = $request->boolean('settings.auto_sync_stock');
        $settings['auto_sync_price'] = $request->boolean('settings.auto_sync_price');

        $channel->update([
            'is_active' => $request->boolean('is_active'),
            'environment' => $validated['environment'],
            'credentials' => $existing,
            'settings' => $settings,
        ]);

        return redirect()
            ->route('admin.integrations.marketplace.channels.edit', $channel)
            ->with('success', $channel->name.' ayarları kaydedildi.');
    }

    public function testConnection(
        MarketplaceChannel $channel,
        MarketplaceManager $manager,
        MarketplaceSyncLogger $logger,
    ): RedirectResponse {
        $started = microtime(true);
        $result = $manager->provider($channel->key)->testConnection($channel);
        $duration = (int) round((microtime(true) - $started) * 1000);

        $logger->log(
            action: 'connection_test',
            status: $result->success ? 'success' : 'failed',
            channelKey: $channel->key,
            message: $result->message,
            durationMs: $duration,
        );

        $channel->update([
            'last_error' => $result->success ? null : $result->message,
            'last_sync_at' => $result->success ? now() : $channel->last_sync_at,
        ]);

        return back()->with(
            $result->success ? 'success' : 'error',
            $result->message,
        );
    }
}
