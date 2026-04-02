<?php

namespace Tests\Feature\Security;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class XSSTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_sanitizes_xss_script_tags()
    {
        $maliciousInput = '<script>alert("XSS Attack")</script>';
        $expectedOutput = '<script>alert("XSS Attack")</script>';

        $request = Request::create('/test', 'POST', [
            'comment' => $maliciousInput
        ]);

        // Simulate middleware processing
        $middleware = new \App\Http\Middleware\SanitizeInput();
        $middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        });

        $this->assertEquals($expectedOutput, $request->input('comment'));
    }

    /** @test */
    public function it_sanitizes_xss_event_handlers()
    {
        $maliciousInput = '<img src="x" onerror="alert(\'XSS\')" />';
        $expectedOutput = '<img src="x" onerror="alert(&#039;XSS&#039;)" />';

        $request = Request::create('/test', 'POST', [
            'content' => $maliciousInput
        ]);

        $middleware = new \App\Http\Middleware\SanitizeInput();
        $middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        });

        $this->assertEquals($expectedOutput, $request->input('content'));
    }

    /** @test */
    public function it_sanitizes_xss_javascript_urls()
    {
        $maliciousInput = 'javascript:alert("XSS")';
        $expectedOutput = 'javascript:alert("XSS")';

        $request = Request::create('/test', 'POST', [
            'url' => $maliciousInput
        ]);

        $middleware = new \App\Http\Middleware\SanitizeInput();
        $middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        });

        $this->assertEquals($expectedOutput, $request->input('url'));
    }

    /** @test */
    public function it_sanitizes_xss_vbscript()
    {
        $maliciousInput = '<vbscript>msgbox("XSS")</vbscript>';
        $expectedOutput = '<vbscript>msgbox("XSS")</vbscript>';

        $request = Request::create('/test', 'POST', [
            'script' => $maliciousInput
        ]);

        $middleware = new \App\Http\Middleware\SanitizeInput();
        $middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        });

        $this->assertEquals($expectedOutput, $request->input('script'));
    }

    /** @test */
    public function it_preserves_safe_html()
    {
        $safeInput = '<p>This is <strong>safe</strong> HTML</p>';
        $expectedOutput = '<p>This is <strong>safe</strong> HTML</p>';

        $request = Request::create('/test', 'POST', [
            'description' => $safeInput
        ]);

        $middleware = new \App\Http\Middleware\SanitizeInput();
        $middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        });

        $this->assertEquals($expectedOutput, $request->input('description'));
    }

    /** @test */
    public function it_sanitizes_nested_arrays()
    {
        $maliciousInput = [
            'user' => [
                'name' => '<script>alert("XSS")</script>',
                'profile' => [
                    'bio' => 'javascript:evil()'
                ]
            ]
        ];

        $request = Request::create('/test', 'POST', $maliciousInput);

        $middleware = new \App\Http\Middleware\SanitizeInput();
        $middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        });

        $this->assertEquals('<script>alert("XSS")</script>', $request->input('user.name'));
        $this->assertEquals('javascript:evil()', $request->input('user.profile.bio'));
    }
}