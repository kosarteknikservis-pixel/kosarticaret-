<?php

namespace Tests\Unit;

use App\Support\ProductSpecs;
use Tests\TestCase;

class ProductSpecsTest extends TestCase
{
    public function test_synonym_labels_are_unified(): void
    {
        $rows = ProductSpecs::rows([
            'Güç' => '1,1 kW (1,5 HP)',
            'Maksimum Debi' => '11–15 m³/sa',
            'Max. Yükseklik (mSS)' => '42',
            'Elektrik' => '220 V Monofaze',
        ])->all();

        $this->assertSame([
            ['Motor Gücü', '1,1 kW (1,5 HP)'],
            ['Maks. Debi', '11–15 m³/sa'],
            ['Maks. Basma Yüksekliği (mSS)', '42'],
            ['Voltaj', '220 V Monofaze'],
        ], $rows);
    }

    public function test_units_are_not_merged_into_one_label(): void
    {
        $rows = ProductSpecs::rows([
            'Güç (kW)' => '0.75',
            'Güç (HP)' => '1',
        ])->all();

        $this->assertSame([
            ['Motor Gücü (kW)', '0.75'],
            ['Motor Gücü (HP)', '1'],
        ], $rows);
    }

    public function test_imported_table_header_rows_are_dropped(): void
    {
        $rows = ProductSpecs::rows([
            'Teknik Özellikler' => 'Değerler',
            'Teknik Veriler' => 'Değerler',
            'Tip' => 'kW',
            'Voltaj' => '380 V Trifaze',
        ])->all();

        $this->assertSame([['Voltaj', '380 V Trifaze']], $rows);
    }

    public function test_colliding_labels_keep_both_values(): void
    {
        $rows = ProductSpecs::rows([
            'Motor Gücü' => '1 HP',
            'Güç' => '1 HP',
            'Pompa Gücü' => '0,75 kW',
        ])->all();

        $this->assertSame([
            ['Motor Gücü', '1 HP'],
            ['Pompa Gücü', '0,75 kW'],
        ], $rows);
    }

    public function test_list_format_and_dotted_capital_i_are_supported(): void
    {
        $rows = ProductSpecs::rows([
            ['label' => 'GİRİŞ ÖLÇÜSÜ', 'value' => '1"'],
            ['label' => '', 'value' => 'boş'],
        ])->all();

        $this->assertSame([['Giriş Ölçüsü', '1"']], $rows);
    }
}
