<?php

namespace Tests\Feature\Security;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class SQLInjectionTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_sanitizes_sql_union_injection()
    {
        $maliciousInput = "1' UNION SELECT username, password FROM users --";
        $expectedOutput = "1' UNION SELECT username, password FROM users --";

        $request = Request::create('/test', 'POST', [
            'id' => $maliciousInput
        ]);

        $middleware = new \App\Http\Middleware\SanitizeInput();
        $middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        });

        // The middleware should sanitize dangerous keywords
        $this->assertStringNotContainsString('UNION', $request->input('id'));
        $this->assertStringNotContainsString('SELECT', $request->input('id'));
    }

    /** @test */
    public function it_sanitizes_sql_drop_table_injection()
    {
        $maliciousInput = "'; DROP TABLE users; --";
        $expectedOutput = "'; DROP TABLE users; --";

        $request = Request::create('/test', 'POST', [
            'query' => $maliciousInput
        ]);

        $middleware = new \App\Http\Middleware\SanitizeInput();
        $middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        });

        $this->assertStringNotContainsString('DROP', $request->input('query'));
        $this->assertStringNotContainsString('TABLE', $request->input('query'));
    }

    /** @test */
    public function it_sanitizes_sql_insert_injection()
    {
        $maliciousInput = "'; INSERT INTO users VALUES ('admin', 'password'); --";
        $expectedOutput = "'; INSERT INTO users VALUES ('admin', 'password'); --";

        $request = Request::create('/test', 'POST', [
            'data' => $maliciousInput
        ]);

        $middleware = new \App\Http\Middleware\SanitizeInput();
        $middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        });

        $this->assertStringNotContainsString('INSERT', $request->input('data'));
        $this->assertStringNotContainsString('INTO', $request->input('data'));
    }

    /** @test */
    public function it_sanitizes_sql_update_injection()
    {
        $maliciousInput = "'; UPDATE users SET password='hacked' WHERE id=1; --";
        $expectedOutput = "'; UPDATE users SET password='hacked' WHERE id=1; --";

        $request = Request::create('/test', 'POST', [
            'update' => $maliciousInput
        ]);

        $middleware = new \App\Http\Middleware\SanitizeInput();
        $middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        });

        $this->assertStringNotContainsString('UPDATE', $request->input('update'));
        $this->assertStringNotContainsString('SET', $request->input('update'));
    }

    /** @test */
    public function it_sanitizes_sql_delete_injection()
    {
        $maliciousInput = "'; DELETE FROM users WHERE 1=1; --";
        $expectedOutput = "'; DELETE FROM users WHERE 1=1; --";

        $request = Request::create('/test', 'POST', [
            'delete' => $maliciousInput
        ]);

        $middleware = new \App\Http\Middleware\SanitizeInput();
        $middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        });

        $this->assertStringNotContainsString('DELETE', $request->input('delete'));
        $this->assertStringNotContainsString('FROM', $request->input('delete'));
    }

    /** @test */
    public function it_sanitizes_sql_comment_injection()
    {
        $maliciousInput = "admin' /*";
        $expectedOutput = "admin' /*";

        $request = Request::create('/test', 'POST', [
            'username' => $maliciousInput
        ]);

        $middleware = new \App\Http\Middleware\SanitizeInput();
        $middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        });

        // Comments should be preserved but dangerous keywords removed
        $this->assertEquals($expectedOutput, $request->input('username'));
    }

    /** @test */
    public function it_sanitizes_sql_exec_injection()
    {
        $maliciousInput = "'; EXEC xp_cmdshell 'net user'; --";
        $expectedOutput = "'; EXEC xp_cmdshell 'net user'; --";

        $request = Request::create('/test', 'POST', [
            'command' => $maliciousInput
        ]);

        $middleware = new \App\Http\Middleware\SanitizeInput();
        $middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        });

        $this->assertStringNotContainsString('EXEC', $request->input('command'));
    }

    /** @test */
    public function it_preserves_normal_sql_keywords_in_safe_context()
    {
        $safeInput = "SELECT * FROM products WHERE category = 'electronics'";
        $expectedOutput = "SELECT * FROM products WHERE category = 'electronics'";

        $request = Request::create('/test', 'POST', [
            'query' => $safeInput
        ]);

        $middleware = new \App\Http\Middleware\SanitizeInput();
        $middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        });

        // In a real application, this would be handled by prepared statements
        // The middleware focuses on input sanitization for display/XSS protection
        $this->assertEquals($expectedOutput, $request->input('query'));
    }

    /** @test */
    public function it_handles_null_bytes()
    {
        $maliciousInput = "admin\x00' OR 1=1 --";
        $expectedOutput = "admin' OR 1=1 --";

        $request = Request::create('/test', 'POST', [
            'username' => $maliciousInput
        ]);

        $middleware = new \App\Http\Middleware\SanitizeInput();
        $middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        });

        $this->assertEquals($expectedOutput, $request->input('username'));
    }
}