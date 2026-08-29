<?php

namespace Tests\Unit;

use App\Support\BingSiteAuthXml;
use PHPUnit\Framework\TestCase;

class BingSiteAuthXmlTest extends TestCase
{
    public function test_builds_full_xml_from_user_tag_only(): void
    {
        $xml = BingSiteAuthXml::normalize('<user>1574AB4B2731BD4765E799EE759774C9</user>');

        $this->assertStringContainsString('<?xml version="1.0"?>', $xml);
        $this->assertStringContainsString('<users>', $xml);
        $this->assertStringContainsString('<user>1574AB4B2731BD4765E799EE759774C9</user>', $xml);
        $this->assertStringContainsString('</users>', $xml);
    }

    public function test_builds_full_xml_from_plain_code(): void
    {
        $xml = BingSiteAuthXml::normalize('1574AB4B2731BD4765E799EE759774C9');

        $this->assertStringContainsString('<user>1574AB4B2731BD4765E799EE759774C9</user>', $xml);
    }
}
