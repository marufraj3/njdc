<?php

namespace Tests\Unit;

use App\Rules\SafeUrl;
use App\Support\HtmlSanitizer;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ContentSafetyTest extends TestCase
{
    public function test_safe_url_rejects_executable_protocol_relative_and_control_character_urls(): void
    {
        foreach (['javascript:alert(1)', '//evil.example/path', "https://example.test/\nattack", 'data:text/html,bad'] as $url) {
            $this->assertTrue(Validator::make(['url' => $url], ['url' => [new SafeUrl]])->fails(), $url.' should be rejected.');
        }

        foreach (['/pages/about', '#section', '?page=2', 'https://example.test', 'mailto:info@example.test', 'tel:+880100000'] as $url) {
            $this->assertFalse(Validator::make(['url' => $url], ['url' => [new SafeUrl]])->fails(), $url.' should be accepted.');
        }
    }

    public function test_rich_html_is_sanitized_before_it_can_be_rendered(): void
    {
        $clean = app(HtmlSanitizer::class)->clean(<<<'HTML'
<script>alert(1)</script><p onclick="bad()" style="color:red;behavior:url(x)">Safe</p>
<a href="javascript:alert(2)">Bad link</a><a href="https://example.test" target="_blank">Good link</a>
<img src="//evil.example/pixel.png" onerror="bad()"><iframe src="https://evil.example/embed"></iframe>
<iframe src="https://www.youtube.com/embed/abc"></iframe>
HTML);

        $this->assertStringNotContainsString('alert(1)', $clean);
        $this->assertStringNotContainsString('onclick', $clean);
        $this->assertStringNotContainsString('javascript:', $clean);
        $this->assertStringNotContainsString('//evil.example', $clean);
        $this->assertStringNotContainsString('behavior', $clean);
        $this->assertStringContainsString('Safe', $clean);
        $this->assertStringContainsString('rel="noopener noreferrer"', $clean);
        $this->assertStringContainsString('https://www.youtube.com/embed/abc', $clean);
    }
}
