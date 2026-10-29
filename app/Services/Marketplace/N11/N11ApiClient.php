<?php

namespace App\Services\Marketplace\N11;

use App\Models\MarketplaceChannel;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

class N11ApiClient
{
    private ?MarketplaceChannel $channel = null;

    private string $appKey = '';

    private string $appSecret = '';

    public function forChannel(MarketplaceChannel $channel): self
    {
        $clone = clone $this;
        $clone->channel = $channel;
        $clone->appKey = trim((string) data_get($channel->credentials, 'app_key', ''));
        $clone->appSecret = trim((string) data_get($channel->credentials, 'app_secret', ''));

        if ($clone->appKey === '' || $clone->appSecret === '') {
            throw new InvalidArgumentException('N11 API bilgileri eksik (app_key, app_secret).');
        }

        return $clone;
    }

    /**
     * @return array<string, mixed>
     */
    public function ping(): array
    {
        return $this->getCategories();
    }

    /**
     * @return array<string, mixed>
     */
    public function getCategories(): array
    {
        return $this->get('/cdn/categories', [], authSecret: false);
    }

    /**
     * @return list<array{id: int, name: string, path: string}>
     */
    public function searchLeafCategories(string $query, int $limit = 40): array
    {
        $needle = mb_strtolower(trim($query));
        if ($needle === '') {
            return [];
        }

        $tree = $this->getCategories();
        $categories = $tree['categories'] ?? $tree;
        if (! is_array($categories)) {
            return [];
        }

        $matches = [];
        $this->walkCategories($categories, [], function (array $node, array $path) use ($needle, $limit, &$matches): void {
            if (count($matches) >= $limit) {
                return;
            }

            $subs = $node['subCategories'] ?? null;
            if ($subs !== null && is_array($subs) && $subs !== []) {
                return;
            }

            $id = (int) ($node['id'] ?? 0);
            $name = (string) ($node['name'] ?? '');
            if ($id <= 0 || $name === '') {
                return;
            }

            $pathLabel = implode(' > ', array_merge($path, [$name]));
            $hay = mb_strtolower($name.' '.$pathLabel);
            if (! str_contains($hay, $needle)) {
                return;
            }

            $matches[] = [
                'id' => $id,
                'name' => $name,
                'path' => $pathLabel,
            ];
        });

        return $matches;
    }

    /**
     * @return array<string, mixed>
     */
    public function getCategoryAttributes(int $categoryId): array
    {
        if ($categoryId <= 0) {
            throw new InvalidArgumentException('categoryId zorunlu.');
        }

        return $this->get('/cdn/category/'.$categoryId.'/attribute', [], authSecret: false);
    }

    /**
     * @param  array{payload: array<string, mixed>}  $body
     * @return array<string, mixed>
     */
    public function createProducts(array $body): array
    {
        return $this->post('/ms/product/tasks/product-create', $body);
    }

    /**
     * @return array<string, mixed>
     */
    public function taskDetails(int $taskId, int $page = 0, int $size = 100): array
    {
        return $this->post('/ms/product/task-details/page-query', [
            'taskId' => $taskId,
            'pageable' => [
                'page' => $page,
                'size' => $size,
            ],
        ]);
    }

    /**
     * SOAP GetShipmentTemplateList — paneldeki kargo şablon adları.
     *
     * @return list<string>
     */
    public function listShipmentTemplateNames(): array
    {
        $envelope = '<?xml version="1.0" encoding="UTF-8"?>'
            .'<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:sch="http://www.n11.com/ws/schemas">'
            .'<soapenv:Header/>'
            .'<soapenv:Body>'
            .'<sch:GetShipmentTemplateListRequest>'
            .'<auth>'
            .'<appKey>'.htmlspecialchars($this->appKey, ENT_XML1).'</appKey>'
            .'<appSecret>'.htmlspecialchars($this->appSecret, ENT_XML1).'</appSecret>'
            .'</auth>'
            .'</sch:GetShipmentTemplateListRequest>'
            .'</soapenv:Body>'
            .'</soapenv:Envelope>';

        $response = Http::timeout(60)
            ->withHeaders([
                'Content-Type' => 'text/xml; charset=utf-8',
                'SOAPAction' => '',
            ])
            ->withBody($envelope, 'text/xml')
            ->post('https://api.n11.com/ws/ShipmentService.wsdl');

        // Bazı ortamlarda .wsdl POST olmaz; endpoint'e dene.
        if (! $response->successful()) {
            $response = Http::timeout(60)
                ->withHeaders([
                    'Content-Type' => 'text/xml; charset=utf-8',
                    'SOAPAction' => '',
                ])
                ->withBody($envelope, 'text/xml')
                ->post('https://api.n11.com/ws/ShipmentService');
        }

        $body = (string) $response->body();
        if (! $response->successful() && $body === '') {
            throw new N11ApiException('N11 kargo şablon listesi alınamadı (HTTP '.$response->status().').', $response->status());
        }

        $names = [];
        if (preg_match_all('/<(?:\w+:)?templateName>([^<]+)<\/(?:\w+:)?templateName>/u', $body, $m)) {
            foreach ($m[1] as $name) {
                $name = html_entity_decode(trim((string) $name), ENT_QUOTES | ENT_XML1, 'UTF-8');
                if ($name !== '') {
                    $names[] = $name;
                }
            }
        }
        if ($names === [] && preg_match_all('/<(?:\w+:)?name>([^<]+)<\/(?:\w+:)?name>/u', $body, $m2)) {
            foreach ($m2[1] as $name) {
                $name = html_entity_decode(trim((string) $name), ENT_QUOTES | ENT_XML1, 'UTF-8');
                if ($name !== '' && ! in_array($name, ['GetShipmentTemplateListRequest', 'auth'], true)) {
                    $names[] = $name;
                }
            }
        }

        $names = array_values(array_unique($names));
        if ($names === []) {
            throw new N11ApiException(
                'N11 kargo şablonu bulunamadı. SOAP yanıtı: '.mb_substr(trim(strip_tags($body)), 0, 240),
                $response->status(),
                ['raw' => mb_substr($body, 0, 2000)]
            );
        }

        return $names;
    }

    /**
     * @param  array<int, array<string, mixed>>  $nodes
     * @param  list<string>  $path
     * @param  callable(array<string, mixed>, list<string>): void  $visitor
     */
    private function walkCategories(array $nodes, array $path, callable $visitor): void
    {
        foreach ($nodes as $node) {
            if (! is_array($node)) {
                continue;
            }

            $visitor($node, $path);
            $subs = $node['subCategories'] ?? null;
            if (is_array($subs) && $subs !== []) {
                $name = (string) ($node['name'] ?? '');
                $this->walkCategories($subs, array_merge($path, $name !== '' ? [$name] : []), $visitor);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    private function get(string $path, array $query = [], bool $authSecret = true): array
    {
        $response = Http::timeout(60)
            ->withHeaders($this->headers($authSecret))
            ->acceptJson()
            ->get($this->url($path), $query);

        return $this->decode($response, $path);
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    private function post(string $path, array $body): array
    {
        $response = Http::timeout(90)
            ->withHeaders($this->headers(true))
            ->acceptJson()
            ->asJson()
            ->post($this->url($path), $body);

        return $this->decode($response, $path);
    }

    /**
     * @return array<string, string>
     */
    private function headers(bool $withSecret): array
    {
        $headers = [
            'appKey' => $this->appKey,
            'Content-Type' => 'application/json',
        ];

        if ($withSecret) {
            $headers['appSecret'] = $this->appSecret;
        }

        return $headers;
    }

    private function url(string $path): string
    {
        $base = rtrim((string) config('marketplace.n11.base_url', 'https://api.n11.com'), '/');

        return $base.'/'.ltrim($path, '/');
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(Response $response, string $path): array
    {
        $json = $response->json();
        $data = is_array($json) ? $json : ['raw' => $response->body()];

        if ($response->successful()) {
            return $data;
        }

        $message = (string) (
            data_get($data, 'message')
            ?? data_get($data, 'errorMessage')
            ?? data_get($data, 'reasons.0')
            ?? data_get($data, 'error')
            ?? ('N11 API hatası HTTP '.$response->status().' @ '.$path)
        );

        if (is_array(data_get($data, 'reasons'))) {
            $reasons = array_filter(array_map('strval', (array) data_get($data, 'reasons')));
            if ($reasons !== []) {
                $message = implode(' | ', $reasons);
            }
        }

        throw new N11ApiException($message, $response->status(), $data);
    }
}
