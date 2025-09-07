<?php

namespace AlumniPlatform\ApiClient;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;

class Client
{
    private HttpClient $httpClient;

    private string $baseUri;

    private string $token;

    public function __construct(array $config)
    {
        $this->baseUri = rtrim($config['base_uri'], '/');
        $this->token = $config['token'];

        $this->httpClient = new HttpClient([
            'base_uri' => $this->baseUri,
            'timeout' => $config['timeout'] ?? 30,
            'headers' => [
                'Authorization' => 'Bearer '.$this->token,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'User-Agent' => 'AlumniPlatform-PHP-SDK/1.0',
            ],
        ]);
    }

    /**
     * Get current user profile
     */
    public function getUser(): array
    {
        return $this->get('/user')['data'];
    }

    /**
     * Get timeline
     */
    public function getTimeline(int $page = 1, int $perPage = 20): array
    {
        return $this->get('/timeline', [
            'page' => $page,
            'per_page' => $perPage,
        ]);
    }

    /**
     * Create a post
     */
    public function createPost(array $data): array
    {
        return $this->post('/posts', $data)['data'];
    }

    /**
     * Like a post
     */
    public function likePost(int $postId): void
    {
        $this->post("/posts/{$postId}/like");
    }

    /**
     * Comment on a post
     */
    public function commentOnPost(int $postId, string $content): void
    {
        $this->post("/posts/{$postId}/comment", ['content' => $content]);
    }

    /**
     * Search alumni
     */
    public function searchAlumni(array $filters = []): array
    {
        return $this->get('/alumni/search', $filters);
    }

    /**
     * Connect with alumni
     */
    public function connectWithAlumni(int $userId, ?string $message = null): void
    {
        $data = [];
        if ($message) {
            $data['message'] = $message;
        }

        $this->post("/alumni/{$userId}/connect", $data);
    }

    /**
     * Get job recommendations
     */
    public function getJobRecommendations(array $filters = []): array
    {
        return $this->get('/jobs/recommendations', $filters)['data'];
    }

    /**
     * Apply for job
     */
    public function applyForJob(int $jobId, array $applicationData): void
    {
        $this->post("/jobs/{$jobId}/apply", $applicationData);
    }

    /**
     * Get events
     */
    public function getEvents(array $filters = []): array
    {
        return $this->get('/events', $filters);
    }

    /**
     * Register for event
     */
    public function registerForEvent(int $eventId): void
    {
        $this->post("/events/{$eventId}/register");
    }

    /**
     * Request mentorship
     */
    public function requestMentorship(array $data): void
    {
        $this->post('/mentorship/request', $data);
    }

    /**
     * Get mentorships
     */
    public function getMentorships(): array
    {
        return $this->get('/mentorships')['data'];
    }

    /**
     * Create webhook
     */
    public function createWebhook(array $data): array
    {
        return $this->post('/developer/webhooks', $data)['data'];
    }

    /**
     * Get webhooks
     */
    public function getWebhooks(): array
    {
        return $this->get('/developer/webhooks')['data'];
    }

    /**
     * Delete webhook
     */
    public function deleteWebhook(int $webhookId): void
    {
        $this->delete("/developer/webhooks/{$webhookId}");
    }

    /**
     * Get notifications
     */
    public function getNotifications(int $page = 1): array
    {
        return $this->get('/notifications', ['page' => $page]);
    }

    /**
     * Mark notification as read
     */
    public function markNotificationAsRead(int $notificationId): void
    {
        $this->post("/notifications/{$notificationId}/read");
    }

    /**
     * Mark all notifications as read
     */
    public function markAllNotificationsAsRead(): void
    {
        $this->post('/notifications/mark-all-read');
    }

    /**
     * Ping API
     */
    public function ping(): array
    {
        return $this->get('/ping');
    }

    /**
     * Verify webhook signature
     */
    public static function verifyWebhookSignature(string $payload, string $signature, string $secret): bool
    {
        $expectedSignature = 'sha256='.hash_hmac('sha256', $payload, $secret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Make GET request
     */
    private function get(string $endpoint, array $params = []): array
    {
        try {
            $response = $this->httpClient->get($endpoint, [
                'query' => $params,
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (ClientException $e) {
            $this->handleClientException($e);
        } catch (RequestException $e) {
            throw new AlumniPlatformException('Request failed: '.$e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Make POST request
     */
    private function post(string $endpoint, array $data = []): array
    {
        try {
            $response = $this->httpClient->post($endpoint, [
                'json' => $data,
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (ClientException $e) {
            $this->handleClientException($e);
        } catch (RequestException $e) {
            throw new AlumniPlatformException('Request failed: '.$e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Make DELETE request
     */
    private function delete(string $endpoint): array
    {
        try {
            $response = $this->httpClient->delete($endpoint);

            return json_decode($response->getBody()->getContents(), true);
        } catch (ClientException $e) {
            $this->handleClientException($e);
        } catch (RequestException $e) {
            throw new AlumniPlatformException('Request failed: '.$e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Handle client exceptions
     */
    private function handleClientException(ClientException $e): void
    {
        $statusCode = $e->getResponse()->getStatusCode();
        $body = $e->getResponse()->getBody()->getContents();

        switch ($statusCode) {
            case 401:
                throw new AlumniPlatformException('Authentication failed. Please check your API token.', 401, $e);
            case 403:
                throw new AlumniPlatformException('Access forbidden. You do not have permission to access this resource.', 403, $e);
            case 404:
                throw new AlumniPlatformException('Resource not found.', 404, $e);
            case 422:
                $errors = json_decode($body, true);
                throw new AlumniPlatformException('Validation failed: '.json_encode($errors['errors'] ?? $errors), 422, $e);
            case 429:
                throw new AlumniPlatformException('Rate limit exceeded. Please try again later.', 429, $e);
            default:
                throw new AlumniPlatformException('API error: '.$body, $statusCode, $e);
        }
    }
}

class AlumniPlatformException extends \Exception
{
    //
}
