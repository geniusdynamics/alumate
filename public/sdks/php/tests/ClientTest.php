<?php

namespace AlumniPlatform\ApiClient\Tests;

use AlumniPlatform\ApiClient\AlumniPlatformException;
use AlumniPlatform\ApiClient\Client;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    private Client $client;

    private MockHandler $mockHandler;

    protected function setUp(): void
    {
        $this->mockHandler = new MockHandler;
        $handlerStack = HandlerStack::create($this->mockHandler);

        $this->client = new Client([
            'base_uri' => 'https://api.example.com',
            'token' => 'test-token',
        ]);

        // Use reflection to inject the mock handler
        $reflection = new \ReflectionClass($this->client);
        $httpClientProperty = $reflection->getProperty('httpClient');
        $httpClientProperty->setAccessible(true);
        $httpClientProperty->setValue($this->client, new HttpClient(['handler' => $handlerStack]));
    }

    public function test_get_user_returns_user_data(): void
    {
        $userData = ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com'];
        $this->mockHandler->append(new Response(200, [], json_encode(['data' => $userData])));

        $result = $this->client->getUser();

        $this->assertEquals($userData, $result);
    }

    public function test_get_timeline_with_pagination(): void
    {
        $timelineData = [
            'data' => [['id' => 1, 'content' => 'Test post']],
            'meta' => ['current_page' => 1, 'per_page' => 20],
        ];
        $this->mockHandler->append(new Response(200, [], json_encode($timelineData)));

        $result = $this->client->getTimeline(1, 20);

        $this->assertEquals($timelineData, $result);
    }

    public function test_create_post_returns_post_data(): void
    {
        $postData = ['id' => 1, 'content' => 'Test post', 'visibility' => 'public'];
        $this->mockHandler->append(new Response(201, [], json_encode(['data' => $postData])));

        $result = $this->client->createPost(['content' => 'Test post', 'visibility' => 'public']);

        $this->assertEquals($postData, $result);
    }

    public function test_authentication_error_throws_exception(): void
    {
        $this->mockHandler->append(new ClientException(
            'Unauthorized',
            new \GuzzleHttp\Psr7\Request('GET', '/user'),
            new Response(401, [], json_encode(['message' => 'Unauthorized']))
        ));

        $this->expectException(AlumniPlatformException::class);
        $this->expectExceptionMessage('Authentication failed. Please check your API token.');
        $this->expectExceptionCode(401);

        $this->client->getUser();
    }

    public function test_validation_error_throws_exception(): void
    {
        $errors = ['content' => ['The content field is required.']];
        $this->mockHandler->append(new ClientException(
            'Validation Error',
            new \GuzzleHttp\Psr7\Request('POST', '/posts'),
            new Response(422, [], json_encode(['errors' => $errors]))
        ));

        $this->expectException(AlumniPlatformException::class);
        $this->expectExceptionMessage('Validation failed: {"content":["The content field is required."]}');
        $this->expectExceptionCode(422);

        $this->client->createPost([]);
    }

    public function test_rate_limit_error_throws_exception(): void
    {
        $this->mockHandler->append(new ClientException(
            'Too Many Requests',
            new \GuzzleHttp\Psr7\Request('GET', '/timeline'),
            new Response(429, [], json_encode(['message' => 'Too Many Requests']))
        ));

        $this->expectException(AlumniPlatformException::class);
        $this->expectExceptionMessage('Rate limit exceeded. Please try again later.');
        $this->expectExceptionCode(429);

        $this->client->getTimeline();
    }

    public function test_verify_webhook_signature_valid(): void
    {
        $payload = '{"event":"user.created","data":{"id":1}}';
        $secret = 'webhook-secret';
        $signature = 'sha256='.hash_hmac('sha256', $payload, $secret);

        $result = Client::verifyWebhookSignature($payload, $signature, $secret);

        $this->assertTrue($result);
    }

    public function test_verify_webhook_signature_invalid(): void
    {
        $payload = '{"event":"user.created","data":{"id":1}}';
        $secret = 'webhook-secret';
        $signature = 'sha256=invalid-signature';

        $result = Client::verifyWebhookSignature($payload, $signature, $secret);

        $this->assertFalse($result);
    }

    public function test_ping_returns_response(): void
    {
        $pingResponse = ['status' => 'ok', 'timestamp' => time()];
        $this->mockHandler->append(new Response(200, [], json_encode($pingResponse)));

        $result = $this->client->ping();

        $this->assertEquals($pingResponse, $result);
    }
}
