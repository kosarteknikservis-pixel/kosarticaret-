<?php

namespace Tests\Unit;

use App\Services\PumpSelection\PumpRequirementCalculator;
use App\Services\PumpSelection\PumpSpecExtractor;
use PHPUnit\Framework\TestCase;

class PumpSelectionTest extends TestCase
{
    public function test_hydrofor_apartment_calculation(): void
    {
        $calc = new PumpRequirementCalculator();
        $result = $calc->calculate('hydrofor_apartment', ['apartments' => 20, 'floors' => 5]);

        $this->assertSame('hydrofor_apartment', $result['application']);
        $this->assertGreaterThanOrEqual(2.0, $result['flow_m3h']);
        $this->assertGreaterThanOrEqual(40.0, $result['head_m']);
    }

    public function test_parses_flow_from_specs_string(): void
    {
        $extractor = new PumpSpecExtractor();

        $this->assertSame(3.0, $extractor->parseFlow('50 Lt/dk'));
        $this->assertSame(12.0, $extractor->parseFlow('12 m³/h'));
    }

    public function test_parses_head_from_bar(): void
    {
        $extractor = new PumpSpecExtractor();

        $this->assertSame(30.6, $extractor->parseHead('3 bar'));
    }
}
