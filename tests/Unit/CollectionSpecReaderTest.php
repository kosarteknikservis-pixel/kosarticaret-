<?php

namespace Tests\Unit;

use App\Support\CollectionSpecReader;
use PHPUnit\Framework\TestCase;

class CollectionSpecReaderTest extends TestCase
{
    private CollectionSpecReader $reader;

    protected function setUp(): void
    {
        parent::setUp();
        $this->reader = new CollectionSpecReader;
    }

    public function test_explicit_hp_is_used(): void
    {
        $reading = $this->reader->motorHp(['Motor Gücü' => '1 HP (750 Watt)']);

        $this->assertSame('clear', $reading['status']);
        $this->assertEquals(1.0, $reading['hp']);
    }

    public function test_standard_kw_maps_to_hp(): void
    {
        $reading = $this->reader->motorHp(['Güç' => '0,75 kW']);

        $this->assertSame('clear', $reading['status']);
        $this->assertEquals(1.0, $reading['hp']);
    }

    public function test_off_table_kw_stays_out(): void
    {
        $reading = $this->reader->motorHp(['Güç (kW)' => '1,6 kW']);

        $this->assertSame('uncertain', $reading['status']);
        $this->assertNull($reading['hp']);
    }

    public function test_explicit_hp_is_not_overridden_by_nearby_kw(): void
    {
        $reading = $this->reader->motorHp(['Motor Gücü' => '2.2 HP (1.5 kW)']);

        $this->assertSame('clear', $reading['status']);
        $this->assertEquals(2.2, $reading['hp']);
    }

    public function test_multiplied_kw_without_hp_stays_out(): void
    {
        $reading = $this->reader->motorHp(['Güç (kW)' => '3x2.2 kW']);

        $this->assertSame('uncertain', $reading['status']);
        $this->assertNull($reading['hp']);
    }

    public function test_repeated_same_hp_with_multiplier_stays(): void
    {
        $reading = $this->reader->motorHp(['Motor Gücü' => '3 × 3 HP (3 × 2.2 kW)']);

        $this->assertSame('clear', $reading['status']);
        $this->assertEquals(3.0, $reading['hp']);
    }

    public function test_explicit_hp_that_disagrees_with_mapped_kw_stays_out(): void
    {
        $conflict = $this->reader->motorHp(['Güç' => '3 HP (3 kW)']);
        $this->assertSame('uncertain', $conflict['status']);

        $paired = $this->reader->motorHp(['Güç' => '3 HP (2.2 kW)']);
        $this->assertSame('clear', $paired['status']);
        $this->assertEquals(3.0, $paired['hp']);
    }

    public function test_multi_pump_signals_and_unverified_sumak_letter(): void
    {
        $this->assertSame('multi', $this->reader->multiPump('Sumak SHM6 B Çift Pompalı Hidrofor', ['Güç' => '1 HP'], null)['status']);
        $this->assertSame('multi', $this->reader->multiPump('Winpo WNP2 VM İki Pompalı Hidrofor', ['Güç' => '3 HP'], null)['status']);
        $this->assertSame('multi', $this->reader->multiPump('Sumak SMINOX12B Hidrofor', ['Motor Gücü' => '2 × 3 HP (2 × 2.2 kW)'], null)['status']);
        $this->assertSame('single', $this->reader->multiPump('Sumak SMINOX12A Hidrofor', ['Motor Gücü' => '3 HP (2.2 kW)'], null)['status']);
        $this->assertSame('multi', $this->reader->multiPump('Sumak SHM6 B Hidrofor', ['Güç' => '1 HP'], 'Bu model çift pompalı sistemdir.')['status']);
        $this->assertSame('unverified', $this->reader->multiPump('Sumak SHM6 B 100/6 Hidrofor', ['Güç' => '1 HP'], 'Su basıncı sağlar.')['status']);
    }

    public function test_bare_motor_name_is_not_a_pump_set(): void
    {
        $this->assertTrue($this->reader->bareMotor('Sumak 4SM10 4 inch Dalgıç Pompa Motoru'));
        $this->assertTrue($this->reader->bareMotor('Pedrollo 4 PDm Derin Kuyu Dalgıç Motoru'));
        $this->assertFalse($this->reader->bareMotor('Pedrollo 4 SR Dalgıç Pompa Motorlu 1 HP'));
        $this->assertFalse($this->reader->bareMotor('Winpo 4SKM 100 Keson Kuyu Dalgıç Pompa'));
        $this->assertFalse($this->reader->bareMotor('Pedrollo TRm Parçalayıcı Bıçaklı Foseptik Dalgıç Pompa'));
    }

    public function test_input_power_is_not_motor_power(): void
    {
        $reading = $this->reader->motorHp(['Çekilen Güç' => '0,75 kW']);

        $this->assertSame('uncertain', $reading['status']);
        $this->assertNull($reading['hp']);
    }

    public function test_single_220_or_230_is_monofaze(): void
    {
        $this->assertSame('mono', $this->reader->phase(['Voltaj' => '220 V'])['status']);
        $this->assertSame('mono', $this->reader->phase(['Elektrik' => '230 V - 50 Hz'])['status']);
    }

    public function test_split_and_three_phase_voltages_are_trifaze(): void
    {
        $this->assertSame('tri', $this->reader->phase(['Voltaj' => '220/380'])['status']);
        $this->assertSame('tri', $this->reader->phase(['Voltaj' => '230/400 V'])['status']);
        $this->assertSame('tri', $this->reader->phase(['Voltaj' => '380 V'])['status']);
        $this->assertSame('tri', $this->reader->phase(['Voltaj' => '400 V'])['status']);
    }
}
