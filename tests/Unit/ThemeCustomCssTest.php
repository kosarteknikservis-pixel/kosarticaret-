<?php

namespace Tests\Unit;

use App\Support\ThemeSettings;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ThemeCustomCssTest extends TestCase
{
    #[Test]
    public function allows_plain_css(): void
    {
        $css = 'body { color: red; }';

        $this->assertSame($css, ThemeSettings::sanitizeCustomCss($css));
    }

    #[Test]
    public function rejects_any_input_containing_angle_bracket(): void
    {
        $this->assertSame('', ThemeSettings::sanitizeCustomCss('</sty</style>le>'));
        $this->assertSame('', ThemeSettings::sanitizeCustomCss('body { color: red; } /* <hack> */'));
    }

    #[Test]
    public function rejects_import(): void
    {
        $this->assertSame('', ThemeSettings::sanitizeCustomCss('@import url(x)'));
    }

    #[Test]
    public function rejects_javascript_url(): void
    {
        $this->assertSame('', ThemeSettings::sanitizeCustomCss('a { background: url(javascript:x) }'));
    }

    #[Test]
    public function allows_data_image_svg_url(): void
    {
        $css = "a { background: url('data:image/svg+xml;utf8,%3Csvg%3E%3C/svg%3E') }";

        $this->assertSame($css, ThemeSettings::sanitizeCustomCss($css));
    }
}
