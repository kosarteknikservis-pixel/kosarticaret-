<?php

namespace App\Services\PumpSelection;

class PumpRequirementCalculator
{
    /**
     * @param  array<string, mixed>  $inputs
     * @return array{
     *     flow_m3h: float,
     *     head_m: float,
     *     summary: string,
     *     details: list<string>,
     *     application: string
     * }
     */
    public function calculate(string $application, array $inputs): array
    {
        return match ($application) {
            'hydrofor_apartment' => $this->hydroforApartment($inputs),
            'hydrofor_villa' => $this->hydroforVilla($inputs),
            'submersible_well' => $this->submersibleWell($inputs),
            'jet_shallow' => $this->jetShallow($inputs),
            'drainage' => $this->drainage($inputs),
            'septic' => $this->septic($inputs),
            'irrigation' => $this->irrigation($inputs),
            'circulation' => $this->circulation($inputs),
            'industrial_fan' => $this->industrialFan($inputs),
            default => throw new \InvalidArgumentException('Geçersiz uygulama tipi.'),
        };
    }

    /**
     * @param  array<string, mixed>  $inputs
     */
    private function hydroforApartment(array $inputs): array
    {
        $apartments = max(1, min(500, (int) ($inputs['apartments'] ?? 1)));
        $floors = max(1, min(40, (int) ($inputs['floors'] ?? 1)));

        // Eşzamanlı kullanım: daire başına ~0,12–0,16 m³/h (TS 914 ve pratik tesisat verisi)
        $flow = max(2.0, round($apartments * 0.14, 1));

        // Statik + artık basınç (~2,8 bar ≈ 28 m) + boru kaybı payı
        $head = round(($floors * 3.0) + 28 + min(12, $floors * 0.8), 0);

        return [
            'flow_m3h' => $flow,
            'head_m' => $head,
            'application' => 'hydrofor_apartment',
            'summary' => sprintf(
                '%d daire / %d kat için yaklaşık %.1f m³/saat debi ve %.0f m basma yüksekliği gerekir.',
                $apartments,
                $floors,
                $flow,
                $head
            ),
            'details' => [
                'Eşzamanlı musluk kullanımı ve basınç dalgalanması için hidrofor veya frekans konvertörlü paket sistem tercih edilir.',
                'Dikey kademeli veya monoblok hidrofor setleri apartman uygulamalarında yaygındır.',
                'Kesin seçim için tesisat çapı, mesafe ve sayaç basıncı teknik ekibimizle doğrulanmalıdır.',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $inputs
     */
    private function hydroforVilla(array $inputs): array
    {
        $bathrooms = max(1, min(20, (int) ($inputs['bathrooms'] ?? 2)));
        $floors = max(1, min(5, (int) ($inputs['floors'] ?? 2)));

        $flow = max(1.8, round(0.6 + ($bathrooms * 0.35), 1));
        $head = round(($floors * 3.0) + 25, 0);

        return [
            'flow_m3h' => $flow,
            'head_m' => $head,
            'application' => 'hydrofor_villa',
            'summary' => sprintf(
                '%d banyo / %d katlı konut için yaklaşık %.1f m³/saat debi ve %.0f m basma yüksekliği önerilir.',
                $bathrooms,
                $floors,
                $flow,
                $head
            ),
            'details' => [
                'Ev tipi tanklı hidrofor veya dikey kademeli pompa + tank kombinasyonu uygundur.',
                'Bahçe sulama hattı varsa debi payı %15–20 artırılabilir.',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $inputs
     */
    private function submersibleWell(array $inputs): array
    {
        $depth = max(1, min(200, (int) ($inputs['depth'] ?? 30)));
        $usage = (string) ($inputs['usage'] ?? 'household');

        $flow = match ($usage) {
            'garden' => 6.0,
            'agriculture' => 12.0,
            'livestock' => 8.0,
            default => 3.5,
        };

        // Kuyu derinliği + statik yükseltme + boru sürtünmesi payı
        $head = round($depth + 18 + min(25, $depth * 0.15), 0);

        $usageLabel = match ($usage) {
            'garden' => 'bahçe sulama',
            'agriculture' => 'tarımsal sulama',
            'livestock' => 'hayvancılık / ahır',
            default => 'konut içme suyu',
        };

        return [
            'flow_m3h' => $flow,
            'head_m' => $head,
            'application' => 'submersible_well',
            'summary' => sprintf(
                '%d m derinlikte kuyu, %s kullanımı için yaklaşık %.1f m³/saat debi ve %.0f m basma yüksekliği hedeflenir.',
                $depth,
                $usageLabel,
                $flow,
                $head
            ),
            'details' => [
                'Derin kuyu dalgıç pompa + uygun çap boru ve kablo kesiti seçilmelidir.',
                'Kuyu debisi (yield) pompa debisinden düşükse kuyu kuruma riski oluşur; hidrolik test önerilir.',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $inputs
     */
    private function jetShallow(array $inputs): array
    {
        $suction = max(1, min(8, (int) ($inputs['suction_depth'] ?? 4)));
        $usage = (string) ($inputs['usage'] ?? 'household');

        $flow = match ($usage) {
            'garden' => 5.0,
            'agriculture' => 10.0,
            default => 3.0,
        };

        // Emiş derinliği + basınç tankı / boru kaybı
        $head = round(($suction * 1.15) + 35, 0);

        return [
            'flow_m3h' => $flow,
            'head_m' => $head,
            'application' => 'jet_shallow',
            'summary' => sprintf(
                '%d m emiş derinliği için jet pompa: ~%.1f m³/saat debi, ~%.0f m basma yüksekliği.',
                $suction,
                $flow,
                $head
            ),
            'details' => [
                'Jet pompalar genelde 7–8 m emiş derinliğine kadar verimli çalışır.',
                'Emiş hattında foot valf ve uygun emiş borusu kullanımı kritiktir.',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $inputs
     */
    private function drainage(array $inputs): array
    {
        $volume = max(1, min(500, (int) ($inputs['volume_m3'] ?? 10)));
        $drainHours = max(1, min(24, (int) ($inputs['drain_hours'] ?? 2)));

        $flow = max(3.0, round($volume / $drainHours, 1));
        $head = match ((string) ($inputs['lift'] ?? 'medium')) {
            'high' => 18.0,
            'low' => 8.0,
            default => 12.0,
        };

        return [
            'flow_m3h' => $flow,
            'head_m' => $head,
            'application' => 'drainage',
            'summary' => sprintf(
                '%d m³ hacmin %d saatte tahliyesi için ~%.1f m³/saat debi, ~%.0f m basma yüksekliği.',
                $volume,
                $drainHours,
                $flow,
                $head
            ),
            'details' => [
                'Kirli su / drenaj pompalarında paslanmaz veya plastik gövde tercih edilir.',
                'Katı partikül boyutuna göre öğütücülü veya vortex tip seçilmelidir.',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $inputs
     */
    private function septic(array $inputs): array
    {
        $distance = max(5, min(100, (int) ($inputs['distance'] ?? 20)));
        $flow = max(4.0, min(15.0, round(5 + ($distance / 20), 1)));
        $head = round(8 + ($distance * 0.25), 0);

        return [
            'flow_m3h' => $flow,
            'head_m' => $head,
            'application' => 'septic',
            'summary' => sprintf(
                'Foseptik / atık su transferi için ~%.1f m³/saat debi, ~%.0f m basma yüksekliği.',
                $flow,
                $head
            ),
            'details' => [
                'Foseptik uygulamalarında öğütücülü (cutter) dalgıç pompalar tercih edilir.',
                'Pompa seçiminde boru çapı ve vana kayıpları hesaba katılmalıdır.',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $inputs
     */
    private function irrigation(array $inputs): array
    {
        $area = max(50, min(50000, (int) ($inputs['area_m2'] ?? 500)));
        $method = (string) ($inputs['method'] ?? 'sprinkler');

        // Sulama debisi: damla ~1,5 L/m²/h, yağmurlama ~3 L/m²/h
        $litersPerM2 = $method === 'drip' ? 1.5 : 3.0;
        $flow = max(4.0, round(($area * $litersPerM2) / 1000, 1));
        $head = match ($method) {
            'drip' => 35.0,
            'sprinkler' => 45.0,
            default => 40.0,
        };

        return [
            'flow_m3h' => $flow,
            'head_m' => $head,
            'application' => 'irrigation',
            'summary' => sprintf(
                '%d m² alan, %s sulama için ~%.1f m³/saat debi, ~%.0f m basma yüksekliği.',
                $area,
                $method === 'drip' ? 'damla' : 'yağmurlama',
                $flow,
                $head
            ),
            'details' => [
                'Sulama pompalarında emiş tarafında filtre ve check valf kullanımı önerilir.',
                'Uzun mesafe ve yüksek kot farkında debi payı artırılmalıdır.',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $inputs
     */
    private function circulation(array $inputs): array
    {
        $area = max(40, min(2000, (int) ($inputs['heated_area_m2'] ?? 120)));
        $floors = max(1, min(10, (int) ($inputs['floors'] ?? 2)));

        $flow = max(1.2, round(0.015 * $area, 1));
        $head = round(4 + ($floors * 1.5), 0);

        return [
            'flow_m3h' => $flow,
            'head_m' => $head,
            'application' => 'circulation',
            'summary' => sprintf(
                '%d m² / %d kat ısıtma tesisatı için ~%.1f m³/saat sirkülasyon debisi, ~%.0f m basma.',
                $area,
                $floors,
                $flow,
                $head
            ),
            'details' => [
                'Frekans konvertörlü sirkülasyon pompaları enerji tasarrufu sağlar.',
                'Kalorifer ve yerden ısıtma hatlarında farklı debi ihtiyacı olabilir.',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $inputs
     */
    private function industrialFan(array $inputs): array
    {
        $area = max(10, min(5000, (int) ($inputs['space_m2'] ?? 100)));
        $height = max(2, min(15, (int) ($inputs['height_m'] ?? 4)));

        $volume = $area * $height;
        $airChanges = match ((string) ($inputs['environment'] ?? 'workshop')) {
            'warehouse' => 6,
            'kitchen' => 15,
            default => 10,
        };

        $flowM3h = max(500, round($volume * $airChanges, 0));

        return [
            'flow_m3h' => $flowM3h,
            'head_m' => 0,
            'application' => 'industrial_fan',
            'summary' => sprintf(
                '%d m² × %d m hacim için saatte yaklaşık %d m³ hava debisi ( %d ach ) hedeflenir.',
                $area,
                $height,
                (int) $flowM3h,
                $airChanges
            ),
            'details' => [
                'Sanayi tipi vantilatör seçiminde kanal direnci ve filtre varlığı debiyi etkiler.',
                'Duvar, kanal veya çatı tipi montaj alanına göre model belirlenir.',
            ],
        ];
    }
}
