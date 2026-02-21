<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Tests for helper functions in src/Helper/functions.php
 */
class HelperFunctionsTest extends TestCase
{
    public function testHEscapesHtml(): void
    {
        $this->assertEquals('&lt;script&gt;', h('<script>'));
        $this->assertEquals('&amp;', h('&'));
        $this->assertEquals('&quot;test&quot;', h('"test"'));
        // ENT_HTML5 uses &apos; for single quotes
        $this->assertEquals("&apos;single&apos;", h("'single'"));
    }

    public function testHHandlesNull(): void
    {
        $this->assertEquals('', h(null));
    }

    public function testHHandlesEmptyString(): void
    {
        $this->assertEquals('', h(''));
    }

    public function testHPreservesPlainText(): void
    {
        $this->assertEquals('Hello World', h('Hello World'));
        $this->assertEquals('Test 123', h('Test 123'));
    }

    public function testCsrfTokenGeneratesToken(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $token = csrf_token();

        $this->assertNotEmpty($token);
        $this->assertEquals(64, strlen($token)); // 32 bytes = 64 hex chars
    }

    public function testCsrfTokenReturnsSameTokenWithinSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $token1 = csrf_token();
        $token2 = csrf_token();

        $this->assertEquals($token1, $token2);
    }

    public function testCsrfFieldGeneratesHiddenInput(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $field = csrf_field();

        $this->assertStringContainsString('<input type="hidden"', $field);
        $this->assertStringContainsString('name="csrf_token"', $field);
        $this->assertStringContainsString('value="', $field);
    }

    public function testVerifyCsrfReturnsTrueForValidToken(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $token = csrf_token();

        $this->assertTrue(verify_csrf($token));
    }

    public function testVerifyCsrfReturnsFalseForInvalidToken(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Generate a valid token first
        csrf_token();

        $this->assertFalse(verify_csrf('invalid_token'));
        $this->assertFalse(verify_csrf(''));
    }

    public function testFormatDateReturnsFormattedDate(): void
    {
        // format_date uses 'M j, Y' format by default
        $this->assertEquals('Jan 15, 2024', format_date('2024-01-15'));
        $this->assertEquals('Dec 25, 2023', format_date('2023-12-25'));
    }

    public function testFormatDateHandlesNull(): void
    {
        $this->assertEquals('', format_date(null));
        $this->assertEquals('', format_date(''));
    }

    public function testFormatDateTimeReturnsFormattedDateTime(): void
    {
        $result = format_datetime('2024-01-15 14:30:00');
        $this->assertStringContainsString('Jan 15, 2024', $result);
        $this->assertStringContainsString('2:30', $result);
    }

    protected function tearDown(): void
    {
        // Clean up session
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        $_SESSION = [];
    }
}
