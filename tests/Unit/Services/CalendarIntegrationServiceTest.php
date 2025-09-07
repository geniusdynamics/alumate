<?php

namespace Tests\Unit\Services;

use App\Models\CalendarConnection;
use App\Models\Event;
use App\Models\MentorshipSession;
use App\Models\User;
use App\Services\CalendarIntegrationService;
use Carbon\Carbon;
use Google\Client as GoogleClient;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Microsoft\Graph\Graph;
use Tests\TestCase;
use Mockery;
use PHPUnit\Framework\Assert;

/**
 * Unit tests for CalendarIntegrationService
 */
class CalendarIntegrationServiceTest extends TestCase
{
    private CalendarIntegrationService $calendarService;
    private GoogleClient $mockGoogleClient;
    private Graph $mockMicrosoftGraph;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock external dependencies
        $this->mockGoogleClient = Mockery::mock(GoogleClient::class);
        $this->mockMicrosoftGraph = Mockery::mock(Graph::class);

        // Create service instance with mocked dependencies
        $this->calendarService = new CalendarIntegrationService(
            $this->mockGoogleClient,
            $this->mockMicrosoftGraph
        );

        // Mock Laravel facades
        Cache::shouldReceive('get')->andReturn(null);
        Cache::shouldReceive('put')->andReturn(true);
        Log::shouldReceive('info')->andReturn(null);
        Log::shouldReceive('error')->andReturn(null);
        Log::shouldReceive('warning')->andReturn(null);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ===== APPLE CALENDAR TESTS =====

    /**
     * Test fetching Apple Calendar events successfully
     */
    public function test_fetch_apple_events_success()
    {
        $connection = $this->createMockCalendarConnection('apple');
        $credentials = ['username' => 'test@example.com', 'password' => 'password'];

        // Mock decrypt function
        $this->mockDecrypt($credentials);

        // Mock HTTP client
        Http::shouldReceive('withBasicAuth')
            ->once()
            ->andReturnSelf();
        Http::shouldReceive('withHeaders')
            ->once()
            ->andReturnSelf();
        Http::shouldReceive('send')
            ->once()
            ->andReturnSelf();
        Http::shouldReceive('successful')
            ->once()
            ->andReturn(true);
        Http::shouldReceive('body')
            ->once()
            ->andReturn($this->getMockCalDAVResponse());

        $events = $this->calendarService->fetchAppleEvents($connection);

        $this->assertInstanceOf(Collection::class, $events);
        $this->assertGreaterThan(0, $events->count());
    }

    /**
     * Test Apple Calendar connection test success
     */
    public function test_test_apple_connection_success()
    {
        $connection = $this->createMockCalendarConnection('apple');
        $credentials = ['username' => 'test@example.com', 'password' => 'password'];

        $this->mockDecrypt($credentials);

        Http::shouldReceive('withBasicAuth')
            ->once()
            ->andReturnSelf();
        Http::shouldReceive('withHeaders')
            ->once()
            ->andReturnSelf();
        Http::shouldReceive('send')
            ->once()
            ->andReturnSelf();
        Http::shouldReceive('successful')
            ->once()
            ->andReturn(true);
        Http::shouldReceive('status')
            ->once()
            ->andReturn(200);

        $result = $this->calendarService->testAppleConnection($connection);

        $this->assertTrue($result);
    }

    /**
     * Test Apple Calendar connection test failure
     */
    public function test_test_apple_connection_failure()
    {
        $connection = $this->createMockCalendarConnection('apple');
        $credentials = ['username' => 'test@example.com', 'password' => 'password'];

        $this->mockDecrypt($credentials);

        Http::shouldReceive('withBasicAuth')
            ->once()
            ->andReturnSelf();
        Http::shouldReceive('withHeaders')
            ->once()
            ->andReturnSelf();
        Http::shouldReceive('send')
            ->once()
            ->andReturnSelf();
        Http::shouldReceive('successful')
            ->once()
            ->andReturn(false);
        Http::shouldReceive('status')
            ->once()
            ->andReturn(401);

        $result = $this->calendarService->testAppleConnection($connection);

        $this->assertFalse($result);
    }

    // ===== CALDAV TESTS =====

    /**
     * Test fetching CalDAV events successfully
     */
    public function test_fetch_caldav_events_success()
    {
        $connection = $this->createMockCalendarConnection('caldav');
        $credentials = [
            'server_url' => 'https://caldav.example.com',
            'username' => 'test@example.com',
            'password' => 'password'
        ];

        $this->mockDecrypt($credentials);

        Http::shouldReceive('withHeaders')
            ->once()
            ->andReturnSelf();
        Http::shouldReceive('withBasicAuth')
            ->once()
            ->andReturnSelf();
        Http::shouldReceive('send')
            ->once()
            ->andReturnSelf();
        Http::shouldReceive('successful')
            ->once()
            ->andReturn(true);
        Http::shouldReceive('body')
            ->once()
            ->andReturn($this->getMockCalDAVResponse());

        $events = $this->calendarService->fetchCalDAVEvents($connection);

        $this->assertInstanceOf(Collection::class, $events);
        $this->assertGreaterThan(0, $events->count());
    }

    /**
     * Test CalDAV connection test success
     */
    public function test_test_caldav_connection_success()
    {
        $connection = $this->createMockCalendarConnection('caldav');
        $credentials = [
            'server_url' => 'https://caldav.example.com',
            'username' => 'test@example.com',
            'password' => 'password'
        ];

        $this->mockDecrypt($credentials);

        Http::shouldReceive('withHeaders')
            ->once()
            ->andReturnSelf();
        Http::shouldReceive('withBasicAuth')
            ->once()
            ->andReturnSelf();
        Http::shouldReceive('send')
            ->once()
            ->andReturnSelf();
        Http::shouldReceive('successful')
            ->once()
            ->andReturn(true);
        Http::shouldReceive('status')
            ->once()
            ->andReturn(200);

        $result = $this->calendarService->testCalDAVConnection($connection);

        $this->assertTrue($result);
    }

    // ===== GOOGLE CALENDAR TESTS =====

    /**
     * Test Google Calendar connection test success
     */
    public function test_test_google_connection_success()
    {
        $connection = $this->createMockCalendarConnection('google');
        $credentials = ['access_token' => 'valid_token'];

        $this->mockDecrypt($credentials);

        $this->mockGoogleClient->shouldReceive('setAccessToken')
            ->once()
            ->with($credentials);
        $this->mockGoogleClient->shouldReceive('isAccessTokenExpired')
            ->once()
            ->andReturn(false);

        $mockService = Mockery::mock();
        $mockCalendarList = Mockery::mock();
        $mockCalendarList->shouldReceive('listCalendarList')
            ->once()
            ->andReturnSelf();
        $mockCalendarList->shouldReceive('getItems')
            ->once()
            ->andReturn([Mockery::mock()]);

        $this->mockGoogleClient->shouldReceive('calendarList')
            ->once()
            ->andReturn($mockCalendarList);

        $result = $this->calendarService->testGoogleConnection($connection);

        $this->assertTrue($result);
    }

    /**
     * Test Google token refresh success
     */
    public function test_refresh_google_token_success()
    {
        $connection = $this->createMockCalendarConnection('google');
        $credentials = [
            'access_token' => 'old_token',
            'refresh_token' => 'refresh_token'
        ];

        $this->mockDecrypt($credentials);

        $this->mockGoogleClient->shouldReceive('setAccessToken')
            ->once()
            ->with($credentials);
        $this->mockGoogleClient->shouldReceive('fetchAccessTokenWithRefreshToken')
            ->once()
            ->with('refresh_token')
            ->andReturn(true);
        $this->mockGoogleClient->shouldReceive('getAccessToken')
            ->once()
            ->andReturn([
                'access_token' => 'new_token',
                'expires_in' => 3600,
                'created' => time()
            ]);

        $connection->shouldReceive('update')
            ->once()
            ->with(Mockery::on(function ($data) {
                return isset($data['credentials']);
            }));

        $this->calendarService->refreshGoogleToken($connection);
    }

    /**
     * Test Google token refresh failure
     */
    public function test_refresh_google_token_failure()
    {
        $connection = $this->createMockCalendarConnection('google');
        $credentials = [
            'access_token' => 'old_token',
            'refresh_token' => 'refresh_token'
        ];

        $this->mockDecrypt($credentials);

        $this->mockGoogleClient->shouldReceive('setAccessToken')
            ->once()
            ->with($credentials);
        $this->mockGoogleClient->shouldReceive('fetchAccessTokenWithRefreshToken')
            ->once()
            ->with('refresh_token')
            ->andReturn(false);

        $connection->shouldReceive('update')
            ->once()
            ->with([
                'is_active' => false,
                'sync_error' => 'Token refresh failed: Token refresh failed'
            ]);

        $this->expectException(\Exception::class);
        $this->calendarService->refreshGoogleToken($connection);
    }

    // ===== OUTLOOK TESTS =====

    /**
     * Test Outlook connection test success
     */
    public function test_test_outlook_connection_success()
    {
        $connection = $this->createMockCalendarConnection('outlook');
        $credentials = ['access_token' => 'valid_token'];

        $this->mockDecrypt($credentials);

        $mockUserRequest = Mockery::mock();
        $mockUserRequest->shouldReceive('execute')
            ->once()
            ->andReturn(Mockery::mock([
                'getBody' => ['id' => 'user123', 'displayName' => 'Test User']
            ]));

        $this->mockMicrosoftGraph->shouldReceive('createRequest')
            ->once()
            ->with('GET', '/me?$select=id,displayName,userPrincipalName')
            ->andReturn($mockUserRequest);

        $result = $this->calendarService->testOutlookConnection($connection);

        $this->assertTrue($result);
    }

    /**
     * Test Outlook connection test failure
     */
    public function test_test_outlook_connection_failure()
    {
        $connection = $this->createMockCalendarConnection('outlook');
        $credentials = ['access_token' => 'invalid_token'];

        $this->mockDecrypt($credentials);

        $this->mockMicrosoftGraph->shouldReceive('createRequest')
            ->once()
            ->andThrow(new \Exception('Invalid token'));

        $result = $this->calendarService->testOutlookConnection($connection);

        $this->assertFalse($result);
    }

    // ===== EVENT MANAGEMENT TESTS =====

    /**
     * Test creating or updating event
     */
    public function test_create_or_update_event_new()
    {
        $connection = $this->createMockCalendarConnection('google');
        $externalEvent = [
            'external_id' => 'ext123',
            'title' => 'Test Event',
            'description' => 'Test Description',
            'start_time' => '2024-01-01 10:00:00',
            'end_time' => '2024-01-01 11:00:00',
            'location' => 'Test Location'
        ];

        // Mock Event model
        $mockEvent = Mockery::mock(Event::class);
        $mockEvent->shouldReceive('getAttribute')
            ->with('id')
            ->andReturn(1);

        Event::shouldReceive('where')
            ->andReturnSelf();
        Event::shouldReceive('whereJsonContains')
            ->andReturnSelf();
        Event::shouldReceive('first')
            ->andReturn(null); // No existing event
        Event::shouldReceive('create')
            ->once()
            ->andReturn($mockEvent);
        Event::shouldReceive('update')
            ->andReturn(true);

        $result = $this->calendarService->createOrUpdateEvent($connection, $externalEvent);

        $this->assertInstanceOf(Event::class, $result);
    }

    /**
     * Test syncing event to external calendars
     */
    public function test_sync_event_to_external_calendars()
    {
        $event = $this->createMockEvent();
        $user = $this->createMockUser();
        $connection = $this->createMockCalendarConnection('google');

        $event->shouldReceive('getAttribute')
            ->with('user')
            ->andReturn($user);
        $user->shouldReceive('calendarConnections')
            ->andReturnSelf();
        $user->shouldReceive('active')
            ->andReturnSelf();
        $user->shouldReceive('get')
            ->andReturn(collect([$connection]));

        $connection->shouldReceive('getAttribute')
            ->with('provider')
            ->andReturn('google');

        // Mock the createEventInExternalCalendar method
        $reflection = new \ReflectionClass($this->calendarService);
        $method = $reflection->getMethod('createEventInExternalCalendar');
        $method->setAccessible(true);

        // We'll test the general flow without mocking private methods
        $this->calendarService->syncEventToExternalCalendars($event);
    }

    /**
     * Test creating event in user calendar
     */
    public function test_create_event_in_user_calendar()
    {
        $user = $this->createMockUser();
        $event = $this->createMockEvent();
        $connection = $this->createMockCalendarConnection('google');

        $user->shouldReceive('calendarConnections')
            ->andReturnSelf();
        $user->shouldReceive('active')
            ->andReturnSelf();
        $user->shouldReceive('get')
            ->andReturn(collect([$connection]));

        $connection->shouldReceive('getAttribute')
            ->with('provider')
            ->andReturn('google');

        $this->calendarService->createEventInUserCalendar($user, $event);
    }

    // ===== COMMUNICATION TESTS =====

    /**
     * Test sending email invite success
     */
    public function test_send_email_invite_success()
    {
        $event = $this->createMockEvent();
        $email = 'test@example.com';

        $event->shouldReceive('getAttribute')
            ->with('id')
            ->andReturn(1);
        $event->shouldReceive('getAttribute')
            ->with('title')
            ->andReturn('Test Event');

        Mail::shouldReceive('to')
            ->once()
            ->with($email)
            ->andReturnSelf();
        Mail::shouldReceive('send')
            ->once()
            ->andReturn(true);

        $this->calendarService->sendEmailInvite($event, $email);
    }

    /**
     * Test sending email invite with invalid email
     */
    public function test_send_email_invite_invalid_email()
    {
        $event = $this->createMockEvent();
        $email = 'invalid-email';

        $event->shouldReceive('getAttribute')
            ->with('id')
            ->andReturn(1);

        $this->calendarService->sendEmailInvite($event, $email);
        // Should not throw exception, just log warning
    }

    /**
     * Test sending email invite failure
     */
    public function test_send_email_invite_failure()
    {
        $event = $this->createMockEvent();
        $email = 'test@example.com';

        $event->shouldReceive('getAttribute')
            ->with('id')
            ->andReturn(1);
        $event->shouldReceive('getAttribute')
            ->with('title')
            ->andReturn('Test Event');

        Mail::shouldReceive('to')
            ->once()
            ->with($email)
            ->andReturnSelf();
        Mail::shouldReceive('send')
            ->once()
            ->andThrow(new \Illuminate\Mail\MailException('Mail failed'));

        $this->expectException(\Exception::class);
        $this->calendarService->sendEmailInvite($event, $email);
    }

    // ===== AVAILABILITY TESTS =====

    /**
     * Test fetching busy times success
     */
    public function test_fetch_busy_times_success()
    {
        $connection = $this->createMockCalendarConnection('google');
        $startDate = Carbon::now();
        $endDate = Carbon::now()->addDays(1);

        Cache::shouldReceive('get')
            ->once()
            ->andReturn(null); // Cache miss
        Cache::shouldReceive('put')
            ->once();

        // Mock the provider-specific method
        $reflection = new \ReflectionClass($this->calendarService);
        $method = $reflection->getMethod('fetchGoogleBusyTimes');
        $method->setAccessible(true);

        $mockBusyTimes = collect([
            ['start' => $startDate, 'end' => $startDate->addHour(), 'source' => 'google']
        ]);

        // We'll test the general flow without mocking private methods
        $result = $this->calendarService->fetchBusyTimes($connection, $startDate, $endDate);

        $this->assertInstanceOf(Collection::class, $result);
    }

    /**
     * Test finding available slots in day
     */
    public function test_find_slots_in_day_success()
    {
        $dayStart = Carbon::now()->setTime(9, 0);
        $dayEnd = Carbon::now()->setTime(17, 0);
        $busyTimes = collect([
            [
                'start' => Carbon::now()->setTime(10, 0),
                'end' => Carbon::now()->setTime(11, 0)
            ]
        ]);
        $durationMinutes = 60;

        $slots = $this->calendarService->findSlotsInDay($dayStart, $dayEnd, $busyTimes, $durationMinutes);

        $this->assertInstanceOf(Collection::class, $slots);
        $this->assertGreaterThan(0, $slots->count());
    }

    /**
     * Test finding slots with no available time
     */
    public function test_find_slots_in_day_no_slots()
    {
        $dayStart = Carbon::now()->setTime(9, 0);
        $dayEnd = Carbon::now()->setTime(10, 0);
        $busyTimes = collect([
            [
                'start' => Carbon::now()->setTime(9, 0),
                'end' => Carbon::now()->setTime(10, 0)
            ]
        ]);
        $durationMinutes = 60;

        $slots = $this->calendarService->findSlotsInDay($dayStart, $dayEnd, $busyTimes, $durationMinutes);

        $this->assertInstanceOf(Collection::class, $slots);
        $this->assertCount(0, $slots);
    }

    // ===== ERROR HANDLING TESTS =====

    /**
     * Test invalid provider validation
     */
    public function test_validate_provider_invalid()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported calendar provider: invalid');

        $reflection = new \ReflectionClass($this->calendarService);
        $method = $reflection->getMethod('validateProvider');
        $method->setAccessible(true);

        $method->invokeArgs($this->calendarService, ['invalid']);
    }

    /**
     * Test connection test with invalid provider
     */
    public function test_test_connection_invalid_provider()
    {
        $connection = $this->createMockCalendarConnection('invalid');

        $result = $this->calendarService->testConnection($connection);

        $this->assertFalse($result);
    }

    // ===== APPLE EVENT CREATION TESTS =====

    /**
     * Test creating Apple Calendar event successfully
     */
    public function test_create_apple_event_success()
    {
        $connection = $this->createMockCalendarConnection('apple');
        $eventData = [
            'title' => 'Test Apple Event',
            'description' => 'Test Description',
            'start_time' => '2024-01-01T10:00:00Z',
            'end_time' => '2024-01-01T11:00:00Z',
            'location' => 'Test Location'
        ];
        $credentials = [
            'username' => 'test@example.com',
            'password' => 'password',
            'server_url' => 'https://caldav.icloud.com'
        ];

        $this->mockDecrypt($credentials);

        Http::shouldReceive('withBasicAuth')
            ->once()
            ->andReturnSelf();
        Http::shouldReceive('withHeaders')
            ->once()
            ->andReturnSelf();
        Http::shouldReceive('put')
            ->once()
            ->andReturnSelf();
        Http::shouldReceive('successful')
            ->once()
            ->andReturn(true);

        $result = $this->calendarService->createAppleEvent($connection, $eventData);

        $this->assertIsString($result);
        $this->assertTrue(strpos($result, 'alumate-') !== false);
        $this->assertTrue(strpos($result, '@caldav.icloud.com') !== false);
    }

    /**
     * Test creating Apple Calendar event with network failure
     */
    public function test_create_apple_event_network_failure()
    {
        $connection = $this->createMockCalendarConnection('apple');
        $eventData = [
            'title' => 'Test Apple Event',
            'description' => 'Test Description',
            'start_time' => '2024-01-01T10:00:00Z',
            'end_time' => '2024-01-01T11:00:00Z',
            'location' => 'Test Location'
        ];
        $credentials = [
            'username' => 'test@example.com',
            'password' => 'password',
            'server_url' => 'https://caldav.icloud.com'
        ];

        $this->mockDecrypt($credentials);

        Http::shouldReceive('withBasicAuth')
            ->once()
            ->andReturnSelf();
        Http::shouldReceive('withHeaders')
            ->once()
            ->andReturnSelf();
        Http::shouldReceive('put')
            ->once()
            ->andReturnSelf();
        Http::shouldReceive('successful')
            ->once()
            ->andReturn(false);
        Http::shouldReceive('status')
            ->once()
            ->andReturn(500);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to create Apple Calendar event: 500');

        $this->calendarService->createAppleEvent($connection, $eventData);
    }

    /**
     * Test creating Apple Calendar event with invalid credentials
     */
    public function test_create_apple_event_invalid_credentials()
    {
        $connection = $this->createMockCalendarConnection('apple');
        $eventData = [
            'title' => 'Test Apple Event',
            'description' => 'Test Description',
            'start_time' => '2024-01-01T10:00:00Z',
            'end_time' => '2024-01-01T11:00:00Z',
            'location' => 'Test Location'
        ];
        $credentials = [
            'username' => 'test@example.com',
            'password' => 'invalid_password',
            'server_url' => 'https://caldav.icloud.com'
        ];

        $this->mockDecrypt($credentials);

        Http::shouldReceive('withBasicAuth')
            ->once()
            ->andReturnSelf();
        Http::shouldReceive('withHeaders')
            ->once()
            ->andReturnSelf();
        Http::shouldReceive('put')
            ->once()
            ->andReturnSelf();
        Http::shouldReceive('successful')
            ->once()
            ->andReturn(false);
        Http::shouldReceive('status')
            ->once()
            ->andReturn(401);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to create Apple Calendar event: 401');

        $this->calendarService->createAppleEvent($connection, $eventData);
    }

    /**
     * Test creating Apple Calendar event with missing server URL
     */
    public function test_create_apple_event_missing_server_url()
    {
        $connection = $this->createMockCalendarConnection('apple');
        $eventData = [
            'title' => 'Test Apple Event',
            'description' => 'Test Description',
            'start_time' => '2024-01-01T10:00:00Z',
            'end_time' => '2024-01-01T11:00:00Z',
            'location' => 'Test Location'
        ];
        $credentials = [
            'username' => 'test@example.com',
            'password' => 'password'
            // Missing server_url
        ];

        $this->mockDecrypt($credentials);

        Http::shouldReceive('withBasicAuth')
            ->once()
            ->andReturnSelf();
        Http::shouldReceive('withHeaders')
            ->once()
            ->andReturnSelf();
        Http::shouldReceive('put')
            ->once()
            ->andReturnSelf();
        Http::shouldReceive('successful')
            ->once()
            ->andReturn(true);

        $result = $this->calendarService->createAppleEvent($connection, $eventData);

        $this->assertIsString($result);
        // Should use default Apple Calendar URL
        $this->assertTrue(strpos($result, '@caldav.icloud.com') !== false);
    }

    // ===== CALDAV EVENT CREATION TESTS =====

    /**
     * Test creating CalDAV event successfully with basic auth
     */
    public function test_create_caldav_event_success_basic_auth()
    {
        $connection = $this->createMockCalendarConnection('caldav');
        $eventData = [
            'title' => 'Test CalDAV Event',
            'description' => 'Test Description',
            'start_time' => '2024-01-01T10:00:00Z',
            'end_time' => '2024-01-01T11:00:00Z',
            'location' => 'Test Location'
        ];
        $credentials = [
            'server_url' => 'https://caldav.example.com',
            'username' => 'test@example.com',
            'password' => 'password',
            'auth_type' => 'basic'
        ];

        $this->mockDecrypt($credentials);

        Http::shouldReceive('withHeaders')
            ->once()
            ->andReturnSelf();
        Http::shouldReceive('withBasicAuth')
            ->once()
            ->andReturnSelf();
        Http::shouldReceive('put')
            ->once()
            ->andReturnSelf();
        Http::shouldReceive('successful')
            ->once()
            ->andReturn(true);

        $result = $this->calendarService->createCalDAVEvent($connection, $eventData);

        $this->assertIsString($result);
        $this->assertStringContains('alumate-', $result);
        $this->assertStringContains('@caldav.example.com', $result);
    }

    /**
     * Test creating CalDAV event successfully with bearer auth
     */
    public function test_create_caldav_event_success_bearer_auth()
    {
        $connection = $this->createMockCalendarConnection('caldav');
        $eventData = [
            'title' => 'Test CalDAV Event',
            'description' => 'Test Description',
            'start_time' => '2024-01-01T10:00:00Z',
            'end_time' => '2024-01-01T11:00:00Z',
            'location' => 'Test Location'
        ];
        $credentials = [
            'server_url' => 'https://caldav.example.com',
            'token' => 'bearer_token',
            'auth_type' => 'bearer'
        ];

        $this->mockDecrypt($credentials);

        Http::shouldReceive('withHeaders')
            ->once()
            ->andReturnSelf();
        Http::shouldReceive('withToken')
            ->once()
            ->with('bearer_token')
            ->andReturnSelf();
        Http::shouldReceive('put')
            ->once()
            ->andReturnSelf();
        Http::shouldReceive('successful')
            ->once()
            ->andReturn(true);

        $result = $this->calendarService->createCalDAVEvent($connection, $eventData);

        $this->assertIsString($result);
        $this->assertStringContains('alumate-', $result);
        $this->assertStringContains('@caldav.example.com', $result);
    }

    /**
     * Test creating CalDAV event with network failure
     */
    public function test_create_caldav_event_network_failure()
    {
        $connection = $this->createMockCalendarConnection('caldav');
        $eventData = [
            'title' => 'Test CalDAV Event',
            'description' => 'Test Description',
            'start_time' => '2024-01-01T10:00:00Z',
            'end_time' => '2024-01-01T11:00:00Z',
            'location' => 'Test Location'
        ];
        $credentials = [
            'server_url' => 'https://caldav.example.com',
            'username' => 'test@example.com',
            'password' => 'password'
        ];

        $this->mockDecrypt($credentials);

        Http::shouldReceive('withHeaders')
            ->once()
            ->andReturnSelf();
        Http::shouldReceive('withBasicAuth')
            ->once()
            ->andReturnSelf();
        Http::shouldReceive('put')
            ->once()
            ->andReturnSelf();
        Http::shouldReceive('successful')
            ->once()
            ->andReturn(false);
        Http::shouldReceive('status')
            ->once()
            ->andReturn(503);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to create CalDAV event: 503');

        $this->calendarService->createCalDAVEvent($connection, $eventData);
    }

    /**
     * Test creating CalDAV event with invalid server URL
     */
    public function test_create_caldav_event_invalid_server_url()
    {
        $connection = $this->createMockCalendarConnection('caldav');
        $eventData = [
            'title' => 'Test CalDAV Event',
            'description' => 'Test Description',
            'start_time' => '2024-01-01T10:00:00Z',
            'end_time' => '2024-01-01T11:00:00Z',
            'location' => 'Test Location'
        ];
        $credentials = [
            'server_url' => 'invalid-url',
            'username' => 'test@example.com',
            'password' => 'password'
        ];

        $this->mockDecrypt($credentials);

        Http::shouldReceive('withHeaders')
            ->once()
            ->andReturnSelf();
        Http::shouldReceive('withBasicAuth')
            ->once()
            ->andReturnSelf();
        Http::shouldReceive('put')
            ->once()
            ->andReturnSelf();
        Http::shouldReceive('successful')
            ->once()
            ->andReturn(true);

        $result = $this->calendarService->createCalDAVEvent($connection, $eventData);

        $this->assertIsString($result);
        // Should handle invalid URL gracefully
        $this->assertStringContains('alumate-', $result);
    }

    /**
     * Test creating CalDAV event with specific calendar URL
     */
    public function test_create_caldav_event_with_calendar_url()
    {
        $connection = $this->createMockCalendarConnection('caldav');
        $eventData = [
            'title' => 'Test CalDAV Event',
            'description' => 'Test Description',
            'start_time' => '2024-01-01T10:00:00Z',
            'end_time' => '2024-01-01T11:00:00Z',
            'location' => 'Test Location'
        ];
        $credentials = [
            'server_url' => 'https://caldav.example.com',
            'calendar_url' => 'https://caldav.example.com/calendars/user/calendar1/',
            'username' => 'test@example.com',
            'password' => 'password'
        ];

        $this->mockDecrypt($credentials);

        Http::shouldReceive('withHeaders')
            ->once()
            ->andReturnSelf();
        Http::shouldReceive('withBasicAuth')
            ->once()
            ->andReturnSelf();
        Http::shouldReceive('put')
            ->once()
            ->with('https://caldav.example.com/calendars/user/calendar1/alumate-.ics')
            ->andReturnSelf();
        Http::shouldReceive('successful')
            ->once()
            ->andReturn(true);

        $result = $this->calendarService->createCalDAVEvent($connection, $eventData);

        $this->assertIsString($result);
        $this->assertStringContains('alumate-', $result);
    }

    // ===== EXTERNAL EVENT ID EXTRACTION TESTS =====

    /**
     * Test extracting external event ID from Google response
     */
    public function test_get_external_event_id_google_success()
    {
        $reflection = new \ReflectionClass($this->calendarService);
        $method = $reflection->getMethod('getExternalEventId');
        $method->setAccessible(true);

        // Test array response
        $googleResponse = ['id' => 'google_event_123'];
        $result = $method->invokeArgs($this->calendarService, [$googleResponse, 'google']);
        $this->assertEquals('google_event_123', $result);

        // Test object response
        $googleEvent = new \stdClass();
        $googleEvent->id = 'google_event_456';
        $result = $method->invokeArgs($this->calendarService, [$googleEvent, 'google']);
        $this->assertEquals('google_event_456', $result);
    }

    /**
     * Test extracting external event ID from Outlook response
     */
    public function test_get_external_event_id_outlook_success()
    {
        $reflection = new \ReflectionClass($this->calendarService);
        $method = $reflection->getMethod('getExternalEventId');
        $method->setAccessible(true);

        // Test array response
        $outlookResponse = ['id' => 'outlook_event_123'];
        $result = $method->invokeArgs($this->calendarService, [$outlookResponse, 'outlook']);
        $this->assertEquals('outlook_event_123', $result);

        // Test object response
        $outlookEvent = new \stdClass();
        $outlookEvent->id = 'outlook_event_456';
        $result = $method->invokeArgs($this->calendarService, [$outlookEvent, 'outlook']);
        $this->assertEquals('outlook_event_456', $result);
    }

    /**
     * Test extracting external event ID from CalDAV response
     */
    public function test_get_external_event_id_caldav_success()
    {
        $reflection = new \ReflectionClass($this->calendarService);
        $method = $reflection->getMethod('getExternalEventId');
        $method->setAccessible(true);

        // Test string response (UID we created)
        $caldavResponse = 'alumate-test-uid@caldav.example.com';
        $result = $method->invokeArgs($this->calendarService, [$caldavResponse, 'caldav']);
        $this->assertEquals('alumate-test-uid@caldav.example.com', $result);

        $appleResponse = 'alumate-apple-uid@icloud.com';
        $result = $method->invokeArgs($this->calendarService, [$appleResponse, 'apple']);
        $this->assertEquals('alumate-apple-uid@icloud.com', $result);
    }

    /**
     * Test extracting external event ID with invalid response
     */
    public function test_get_external_event_id_invalid_response()
    {
        $reflection = new \ReflectionClass($this->calendarService);
        $method = $reflection->getMethod('getExternalEventId');
        $method->setAccessible(true);

        // Test null response
        $result = $method->invokeArgs($this->calendarService, [null, 'google']);
        $this->assertEquals('', $result);

        // Test empty array
        $result = $method->invokeArgs($this->calendarService, [[], 'google']);
        $this->assertEquals('', $result);

        // Test unknown provider
        $result = $method->invokeArgs($this->calendarService, [['id' => 'test'], 'unknown']);
        $this->assertEquals('', $result);
    }

    /**
     * Test extracting external event ID with malformed Google response
     */
    public function test_get_external_event_id_google_malformed()
    {
        $reflection = new \ReflectionClass($this->calendarService);
        $method = $reflection->getMethod('getExternalEventId');
        $method->setAccessible(true);

        // Test object without id property
        $malformedObject = new \stdClass();
        $malformedObject->name = 'test';
        $result = $method->invokeArgs($this->calendarService, [$malformedObject, 'google']);
        $this->assertEquals('', $result);

        // Test array without id key
        $malformedArray = ['name' => 'test', 'type' => 'event'];
        $result = $method->invokeArgs($this->calendarService, [$malformedArray, 'google']);
        $this->assertEquals('', $result);
    }

    // ===== EDGE CASE AND ERROR HANDLING TESTS =====

    /**
     * Test creating event with invalid data
     */
    public function test_create_event_invalid_data()
    {
        $user = $this->createMockUser();
        $eventData = [
            'title' => '', // Empty title
            'start_time' => 'invalid-date',
            'end_time' => '2024-01-01T11:00:00Z'
        ];

        $this->expectException(\Exception::class);
        $this->calendarService->createEvent($user, $eventData);
    }

    /**
     * Test syncing calendar with network timeout
     */
    public function test_sync_calendar_network_timeout()
    {
        $connection = $this->createMockCalendarConnection('google');
        $credentials = ['access_token' => 'valid_token'];

        $this->mockDecrypt($credentials);

        // Mock network timeout
        $this->googleClient->shouldReceive('setAccessToken')
            ->once()
            ->andThrow(new \Exception('Network timeout'));

        $result = $this->calendarService->syncCalendar($connection);

        $this->assertFalse($result);
    }

    /**
     * Test fetching busy times with invalid date range
     */
    public function test_fetch_busy_times_invalid_date_range()
    {
        $connection = $this->createMockCalendarConnection('google');
        $startDate = Carbon::now();
        $endDate = Carbon::now()->subDay(); // End before start

        $result = $this->calendarService->fetchBusyTimes($connection, $startDate, $endDate);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(0, $result);
    }

    /**
     * Test finding slots with overlapping busy times
     */
    public function test_find_slots_with_overlapping_busy_times()
    {
        $dayStart = Carbon::now()->setTime(9, 0);
        $dayEnd = Carbon::now()->setTime(17, 0);
        $busyTimes = collect([
            [
                'start' => Carbon::now()->setTime(10, 0),
                'end' => Carbon::now()->setTime(11, 0)
            ],
            [
                'start' => Carbon::now()->setTime(10, 30), // Overlaps with previous
                'end' => Carbon::now()->setTime(12, 0)
            ]
        ]);
        $durationMinutes = 60;

        $slots = $this->calendarService->findSlotsInDay($dayStart, $dayEnd, $busyTimes, $durationMinutes);

        $this->assertInstanceOf(Collection::class, $slots);
        // Should merge overlapping busy times and find appropriate slots
    }

    /**
     * Test email invite with special characters in event title
     */
    public function test_send_email_invite_special_characters()
    {
        $event = $this->createMockEvent();
        $email = 'test@example.com';

        $event->shouldReceive('getAttribute')
            ->with('id')
            ->andReturn(1);
        $event->shouldReceive('getAttribute')
            ->with('title')
            ->andReturn('Event with special chars: café, naïve, résumé');

        Mail::shouldReceive('to')
            ->once()
            ->with($email)
            ->andReturnSelf();
        Mail::shouldReceive('send')
            ->once()
            ->andReturn(true);

        $this->calendarService->sendEmailInvite($event, $email);
    }

    /**
     * Test connection test with expired credentials
     */
    public function test_test_connection_expired_credentials()
    {
        $connection = $this->createMockCalendarConnection('google');
        $credentials = ['access_token' => 'expired_token'];

        $this->mockDecrypt($credentials);

        $this->googleClient->shouldReceive('setAccessToken')
            ->once()
            ->andThrow(new \Exception('Token expired'));

        $result = $this->calendarService->testConnection($connection);

        $this->assertFalse($result);
    }

    /**
     * Test creating event with very long title
     */
    public function test_create_event_very_long_title()
    {
        $user = $this->createMockUser();
        $longTitle = str_repeat('A', 300); // Very long title
        $eventData = [
            'title' => $longTitle,
            'description' => 'Test Description',
            'start_time' => '2024-01-01T10:00:00Z',
            'end_time' => '2024-01-01T11:00:00Z',
            'location' => 'Test Location'
        ];

        // Mock Event creation
        $mockEvent = Mockery::mock(Event::class);
        $mockEvent->shouldReceive('getAttribute')
            ->with('id')
            ->andReturn(1);

        Event::shouldReceive('create')
            ->once()
            ->andReturn($mockEvent);

        $result = $this->calendarService->createEvent($user, $eventData);

        $this->assertInstanceOf(Event::class, $result);
    }

    /**
     * Test fetching events with empty response
     */
    public function test_fetch_external_events_empty_response()
    {
        $connection = $this->createMockCalendarConnection('google');
        $credentials = ['access_token' => 'valid_token'];

        $this->mockDecrypt($credentials);

        $this->googleClient->shouldReceive('setAccessToken')
            ->once();
        $this->googleClient->shouldReceive('isAccessTokenExpired')
            ->once()
            ->andReturn(false);

        $service = Mockery::mock();
        $events = Mockery::mock();

        $this->googleClient->shouldReceive('calendarList')
            ->once()
            ->andReturn($service);
        $service->shouldReceive('events')
            ->once()
            ->andReturn($events);
        $events->shouldReceive('listEvents')
            ->once()
            ->andReturn($events);
        $events->shouldReceive('getItems')
            ->once()
            ->andReturn([]); // Empty response

        $reflection = new \ReflectionClass($this->calendarService);
        $method = $reflection->getMethod('fetchExternalEvents');
        $method->setAccessible(true);

        $result = $method->invokeArgs($this->calendarService, [$connection]);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(0, $result);
    }

    // ===== HELPER METHODS =====

    private function createMockCalendarConnection(string $provider): CalendarConnection
    {
        $connection = Mockery::mock(CalendarConnection::class);
        $connection->shouldReceive('getAttribute')
            ->with('provider')
            ->andReturn($provider);
        $connection->shouldReceive('getAttribute')
            ->with('id')
            ->andReturn(1);
        $connection->shouldReceive('getAttribute')
            ->with('user_id')
            ->andReturn(1);

        return $connection;
    }

    private function createMockUser(): User
    {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('getAttribute')
            ->with('id')
            ->andReturn(1);
        $user->shouldReceive('getAttribute')
            ->with('email')
            ->andReturn('test@example.com');

        return $user;
    }

    private function createMockEvent(): Event
    {
        $event = Mockery::mock(Event::class);
        $event->shouldReceive('getAttribute')
            ->with('id')
            ->andReturn(1);
        $event->shouldReceive('getAttribute')
            ->with('title')
            ->andReturn('Test Event');
        $event->shouldReceive('getAttribute')
            ->with('start_date')
            ->andReturn(Carbon::now());
        $event->shouldReceive('getAttribute')
            ->with('end_date')
            ->andReturn(Carbon::now()->addHour());
        $event->shouldReceive('getAttribute')
            ->with('location')
            ->andReturn('Test Location');

        return $event;
    }

    private function mockDecrypt(array $credentials): void
    {
        // Mock the decrypt function globally
        if (!function_exists('decrypt')) {
            eval('function decrypt($value) { return $value; }');
        }
    }

    private function getMockCalDAVResponse(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>
        <multistatus xmlns="DAV:">
            <response>
                <href>/calendars/test/event1.ics</href>
                <propstat>
                    <prop>
                        <calendar-data xmlns="urn:ietf:params:xml:ns:caldav">
BEGIN:VCALENDAR
BEGIN:VEVENT
UID:event1
SUMMARY:Test Event
DTSTART:20240101T100000Z
DTEND:20240101T110000Z
END:VEVENT
END:VCALENDAR
                        </calendar-data>
                    </prop>
                </propstat>
            </response>
        </multistatus>';
    }
}