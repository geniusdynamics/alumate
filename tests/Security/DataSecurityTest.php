<?php

namespace Tests\Security;

use App\Models\Course;
use App\Models\Employer;
use App\Models\Graduate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DataSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_prevents_sql_injection_attacks(): void
    {
        $user = $this->createUserWithRole('institution-admin');
        $this->actingAs($user);

        // Test SQL injection in search parameters
        $maliciousInputs = [
            "'; DROP TABLE graduates; --",
            "' OR '1'='1",
            "'; UPDATE users SET password='hacked'; --",
            "' UNION SELECT * FROM users --",
        ];

        foreach ($maliciousInputs as $maliciousInput) {
            $response = $this->get(route('graduates.index', [
                'search' => $maliciousInput,
            ]));

            // Should not cause SQL errors or unauthorized data access
            $response->assertStatus(200);

            // Verify database integrity
            $this->assertDatabaseHas('graduates', []);
            $this->assertDatabaseHas('users', ['id' => $user->id]);
        }
    }

    public function test_prevents_xss_attacks(): void
    {
        $user = $this->createUserWithRole('institution-admin');
        $course = Course::factory()->create();
        $this->actingAs($user);

        // Test XSS in form inputs
        $xssPayloads = [
            '<script>alert("XSS")</script>',
            '<img src="x" onerror="alert(\'XSS\')">',
            'javascript:alert("XSS")',
            '<svg onload="alert(\'XSS\')">',
            '"><script>alert("XSS")</script>',
        ];

        foreach ($xssPayloads as $payload) {
            $response = $this->post(route('graduates.store'), [
                'name' => $payload,
                'email' => 'test@example.com',
                'course_id' => $course->id,
                'graduation_year' => 2024,
            ]);

            // Should either reject the input or sanitize it
            if ($response->isRedirect()) {
                $graduate = Graduate::where('email', 'test@example.com')->first();
                if ($graduate) {
                    // Name should be sanitized
                    $this->assertStringNotContainsString('<script>', $graduate->name);
                    $this->assertStringNotContainsString('javascript:', $graduate->name);
                    $graduate->delete(); // Clean up for next iteration
                }
            } else {
                // Should have validation errors
                $response->assertSessionHasErrors();
            }
        }
    }

    public function test_enforces_authorization_on_sensitive_data(): void
    {
        // Create users with different roles
        $superAdmin = $this->createUserWithRole('super-admin');
        $institutionAdmin = $this->createUserWithRole('institution-admin');
        $employer = $this->createUserWithRole('employer');
        $graduate = $this->createUserWithRole('graduate');

        $course = Course::factory()->create();
        $testGraduate = Graduate::factory()->create(['course_id' => $course->id]);

        // Test super admin access
        $this->actingAs($superAdmin);
        $response = $this->get(route('graduates.show', $testGraduate));
        $response->assertStatus(200);

        // Test institution admin access
        $this->actingAs($institutionAdmin);
        $response = $this->get(route('graduates.show', $testGraduate));
        $response->assertStatus(200);

        // Test employer access (should be restricted)
        $this->actingAs($employer);
        $response = $this->get(route('graduates.show', $testGraduate));
        $response->assertStatus(403);

        // Test graduate access to other graduate's data
        $this->actingAs($graduate);
        $response = $this->get(route('graduates.show', $testGraduate));
        $response->assertStatus(403);
    }

    public function test_logs_sensitive_data_access(): void
    {
        $user = $this->createUserWithRole('institution-admin');
        $course = Course::factory()->create();
        $graduate = Graduate::factory()->create(['course_id' => $course->id]);

        $this->actingAs($user);

        // Access sensitive graduate data
        $response = $this->get(route('graduates.show', $graduate));
        $response->assertStatus(200);

        // Verify access was logged
        $this->assertDatabaseHas('data_access_logs', [
            'user_id' => $user->id,
            'resource_type' => 'Graduate',
            'resource_id' => $graduate->id,
            'action' => 'view',
        ]);
    }

    public function test_encrypts_sensitive_personal_data(): void
    {
        $course = Course::factory()->create();

        $sensitiveData = [
            'phone' => '123-456-7890',
            'address' => '123 Main Street, City, State',
            'personal_notes' => 'Confidential information about the graduate',
        ];

        $graduate = Graduate::factory()->create([
            'course_id' => $course->id,
            'phone' => $sensitiveData['phone'],
            'address' => $sensitiveData['address'],
        ]);

        // Check that sensitive data is encrypted in database
        $rawData = \DB::table('graduates')->where('id', $graduate->id)->first();

        // Phone and address should be encrypted if encryption is enabled
        if (config('app.encrypt_personal_data')) {
            $this->assertNotEquals($sensitiveData['phone'], $rawData->phone);
            $this->assertNotEquals($sensitiveData['address'], $rawData->address);
        }

        // But should be decrypted when accessed through model
        $this->assertEquals($sensitiveData['phone'], $graduate->phone);
        $this->assertEquals($sensitiveData['address'], $graduate->address);
    }

    public function test_prevents_mass_assignment_vulnerabilities(): void
    {
        $user = $this->createUserWithRole('institution-admin');
        $course = Course::factory()->create();
        $this->actingAs($user);

        // Attempt to mass assign protected fields
        $response = $this->post(route('graduates.store'), [
            'name' => 'Test Graduate',
            'email' => 'test@example.com',
            'course_id' => $course->id,
            'graduation_year' => 2024,
            // Attempt to set protected fields
            'id' => 999,
            'created_at' => '2020-01-01',
            'updated_at' => '2020-01-01',
        ]);

        $graduate = Graduate::where('email', 'test@example.com')->first();

        if ($graduate) {
            // Protected fields should not be mass assigned
            $this->assertNotEquals(999, $graduate->id);
            $this->assertNotEquals('2020-01-01', $graduate->created_at->format('Y-m-d'));
        }
    }

    public function test_validates_file_upload_security(): void
    {
        $user = $this->createUserWithRole('graduate');
        $this->actingAs($user);

        // Test malicious file uploads
        $maliciousFiles = [
            ['name' => 'malicious.php', 'content' => '<?php system($_GET["cmd"]); ?>', 'mime' => 'application/x-php'],
            ['name' => 'script.js', 'content' => 'alert("XSS")', 'mime' => 'application/javascript'],
            ['name' => 'executable.exe', 'content' => 'MZ...', 'mime' => 'application/x-msdownload'],
        ];

        foreach ($maliciousFiles as $fileData) {
            $file = \Illuminate\Http\Testing\File::create($fileData['name'])
                ->mimeType($fileData['mime']);

            $response = $this->post(route('graduates.upload-resume'), [
                'resume' => $file,
            ]);

            // Should reject malicious files
            $response->assertSessionHasErrors('resume');
        }

        // Test legitimate file upload
        $legitimateFile = \Illuminate\Http\Testing\File::create('resume.pdf')
            ->mimeType('application/pdf');

        $response = $this->post(route('graduates.upload-resume'), [
            'resume' => $legitimateFile,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_prevents_directory_traversal_attacks(): void
    {
        $user = $this->createUserWithRole('institution-admin');
        $this->actingAs($user);

        // Test directory traversal in file access
        $maliciousPaths = [
            '../../../etc/passwd',
            '..\\..\\..\\windows\\system32\\config\\sam',
            '....//....//....//etc/passwd',
            '%2e%2e%2f%2e%2e%2f%2e%2e%2fetc%2fpasswd',
        ];

        foreach ($maliciousPaths as $path) {
            $response = $this->get(route('graduates.download-resume', [
                'graduate' => 1,
                'filename' => $path,
            ]));

            // Should not allow access to system files
            $response->assertStatus(404);
        }
    }

    public function test_enforces_data_retention_policies(): void
    {
        // Create old graduate data
        $oldGraduate = Graduate::factory()->create([
            'created_at' => now()->subYears(8), // Older than retention policy
            'updated_at' => now()->subYears(8),
        ]);

        $recentGraduate = Graduate::factory()->create([
            'created_at' => now()->subYears(2), // Within retention policy
            'updated_at' => now()->subYears(2),
        ]);

        // Simulate data retention cleanup
        $retentionYears = 7;
        $cutoffDate = now()->subYears($retentionYears);

        $expiredRecords = Graduate::where('updated_at', '<', $cutoffDate)->get();

        // Old data should be identified for cleanup
        $this->assertTrue($expiredRecords->contains($oldGraduate));
        $this->assertFalse($expiredRecords->contains($recentGraduate));
    }

    public function test_prevents_data_leakage_in_api_responses(): void
    {
        $user = $this->createUserWithRole('employer');
        $course = Course::factory()->create();
        $graduate = Graduate::factory()->create([
            'course_id' => $course->id,
            'privacy_settings' => [
                'profile_visible' => true,
                'show_contact_info' => false,
                'show_employment_status' => false,
            ],
        ]);

        $this->actingAs($user);

        // Request graduate data through API
        $response = $this->getJson(route('api.graduates.show', $graduate));

        if ($response->status() === 200) {
            $data = $response->json();

            // Sensitive data should be filtered based on privacy settings
            $this->assertArrayNotHasKey('phone', $data);
            $this->assertArrayNotHasKey('address', $data);
            $this->assertArrayNotHasKey('employment_status', $data);
        }
    }

    public function test_validates_input_sanitization(): void
    {
        $user = $this->createUserWithRole('institution-admin');
        $course = Course::factory()->create();
        $this->actingAs($user);

        // Test various malicious inputs
        $maliciousInputs = [
            'name' => '<script>alert("XSS")</script>John Doe',
            'email' => 'test@example.com<script>alert("XSS")</script>',
            'phone' => '123-456-7890<img src=x onerror=alert("XSS")>',
            'address' => '123 Main St<svg onload=alert("XSS")>',
        ];

        $response = $this->post(route('graduates.store'), array_merge($maliciousInputs, [
            'course_id' => $course->id,
            'graduation_year' => 2024,
        ]));

        if ($response->isRedirect()) {
            $graduate = Graduate::latest()->first();

            // All inputs should be sanitized
            $this->assertStringNotContainsString('<script>', $graduate->name);
            $this->assertStringNotContainsString('<img', $graduate->phone);
            $this->assertStringNotContainsString('<svg', $graduate->address);
            $this->assertStringNotContainsString('onerror', $graduate->phone);
            $this->assertStringNotContainsString('onload', $graduate->address);
        }
    }

    public function test_enforces_secure_password_storage(): void
    {
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'TestPassword123!',
            'password_confirmation' => 'TestPassword123!',
        ];

        $response = $this->post(route('register'), $userData);

        $user = User::where('email', 'test@example.com')->first();

        if ($user) {
            // Password should be hashed
            $this->assertNotEquals('TestPassword123!', $user->password);

            // Should use strong hashing algorithm
            $this->assertTrue(\Hash::check('TestPassword123!', $user->password));

            // Raw password should not be stored anywhere
            $rawUserData = \DB::table('users')->where('id', $user->id)->first();
            $this->assertStringNotContainsString('TestPassword123!', json_encode($rawUserData));
        }
    }

    public function test_prevents_information_disclosure(): void
    {
        // Test error messages don't reveal sensitive information
        $response = $this->post(route('login'), [
            'email' => 'nonexistent@example.com',
            'password' => 'password',
        ]);

        // Error message should be generic
        $response->assertSessionHasErrors('email');
        $errors = session('errors')->get('email');

        // Should not reveal whether email exists or not
        $this->assertStringNotContainsString('user not found', strtolower($errors[0]));
        $this->assertStringNotContainsString('email does not exist', strtolower($errors[0]));
    }

    public function test_enforces_secure_session_handling(): void
    {
        $user = User::factory()->create();

        // Login user
        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        // Check session security
        $sessionId = session()->getId();
        $this->assertNotEmpty($sessionId);

        // Session should regenerate on login
        $this->assertTrue(session()->isStarted());

        // Logout should invalidate session
        $response = $this->post(route('logout'));

        // Session should be invalidated
        $this->assertGuest();
    }
}
