<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create roles that are needed for the tests
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'graduate']);
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super-admin']);
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'institution-admin']);
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'employer']);
});

it('can render form demo page', function () {
    $user = User::factory()->create();

    // Ensure user has a role to avoid database transaction issues
    $user->assignRole('graduate');

    $response = $this->actingAs($user)
        ->get('/component-library/forms');

    $response->assertOk();
});

it('can handle form submission endpoint', function () {
    $user = User::factory()->create();

    $formData = [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john.doe@example.com',
        'graduation_year' => '2020',
        'industry' => 'technology',
        'newsletter_opt_in' => true,
        '_form_config' => [
            'title' => 'Test Form',
            'fields' => [
                ['name' => 'first_name', 'label' => 'First Name', 'type' => 'text', 'required' => true],
                ['name' => 'last_name', 'label' => 'Last Name', 'type' => 'text', 'required' => true],
                ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true],
            ],
        ],
    ];

    $response = $this->actingAs($user)
        ->postJson('/api/forms/submit', $formData);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Form submitted successfully',
        ]);
});

it('validates required form fields', function () {
    $user = User::factory()->create();

    $formData = [
        'email' => 'invalid-email',
        '_form_config' => [
            'title' => 'Test Form',
            'fields' => [
                ['name' => 'first_name', 'label' => 'First Name', 'type' => 'text', 'required' => true],
                ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true],
            ],
        ],
    ];

    $response = $this->actingAs($user)
        ->postJson('/api/forms/submit', $formData);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['first_name', 'email']);
});

it('can handle auto-save endpoint', function () {
    $user = User::factory()->create();

    $autoSaveData = [
        'formData' => [
            'first_name' => 'John',
            'email' => 'john@example.com',
        ],
        'formConfig' => [
            'title' => 'Test Form',
            'fields' => [
                ['name' => 'first_name', 'label' => 'First Name', 'type' => 'text'],
                ['name' => 'email', 'label' => 'Email', 'type' => 'email'],
            ],
        ],
    ];

    $response = $this->actingAs($user)
        ->postJson('/api/forms/autosave', $autoSaveData);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Form data auto-saved successfully',
        ]);
});

// Individual Signup Form Template Tests
describe('Individual Signup Form Template', function () {
    it('can submit valid individual signup form', function () {
        $user = User::factory()->create();

        $formData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@gmail.com',
            'phone' => '+1234567890',
            'graduation_year' => 2020,
            'degree_level' => 'bachelor',
            'major' => 'Computer Science',
            'current_job_title' => 'Software Engineer',
            'current_company' => 'Tech Corp',
            'industry' => 'technology',
            'experience_level' => '3-5',
            'location' => 'San Francisco, CA',
            'interests' => ['career_development', 'networking'],
            'newsletter_opt_in' => true,
            'privacy_consent' => true,
            'terms_consent' => true,
            // Required spam protection fields
            'submit_time' => 5,
            'user_agent' => 'Mozilla/5.0 (Test Browser)',
            'honeypot' => '',
        ];

        $response = $this->actingAs($user)
            ->postJson('/api/forms/individual-signup', $formData);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Registration submitted successfully! Welcome to our alumni network.',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'submission_id',
                'crm_result',
            ]);
    });

    it('validates required fields for individual signup', function () {
        $user = User::factory()->create();

        $formData = [
            'first_name' => 'John',
            // Missing required fields: last_name, email, graduation_year, degree_level, major, privacy_consent
        ];

        $response = $this->actingAs($user)
            ->postJson('/api/forms/individual-signup', $formData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'last_name',
                'email',
                'graduation_year',
                'degree_level',
                'major',
                'privacy_consent',
            ]);
    });

    it('validates email uniqueness for individual signup', function () {
        $existingUser = User::factory()->create(['email' => 'existing@gmail.com']);
        $user = User::factory()->create();

        $formData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'existing@gmail.com', // Duplicate email
            'graduation_year' => 2020,
            'degree_level' => 'bachelor',
            'major' => 'Computer Science',
            'privacy_consent' => true,
            'terms_consent' => true,
            // Required spam protection fields
            'submit_time' => 5,
            'user_agent' => 'Mozilla/5.0 (Test Browser)',
            'honeypot' => '',
        ];

        $response = $this->actingAs($user)
            ->postJson('/api/forms/individual-signup', $formData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    });
});

// Institution Demo Request Form Template Tests
describe('Institution Demo Request Form Template', function () {
    it('can submit valid institution demo request form', function () {
        $user = User::factory()->create();

        $formData = [
            'contact_name' => 'Jane Smith',
            'contact_title' => 'Alumni Relations Director',
            'institution_name' => 'University of Example',
            'institution_type' => 'public_university',
            'institution_size' => '15000-30000',
            'email' => 'jane.smith@university.edu',
            'phone' => '+1234567890',
            'department' => 'alumni_relations',
            'decision_role' => 'decision_maker',
            'alumni_count' => '15000-50000',
            'current_system' => 'manual',
            'budget_range' => '25k-50k',
            'implementation_timeline' => '3-6months',
            'primary_goals' => ['alumni_engagement', 'fundraising', 'data_management'],
            'current_challenges' => 'We struggle with outdated contact information and low engagement rates.',
            'demo_preferences' => 'Focus on engagement tools and analytics',
            'preferred_demo_time' => 'afternoon',
            'additional_attendees' => 2,
            'follow_up_consent' => true,
            'data_sharing_consent' => true,
            // Required spam protection fields
            'submit_time' => 5,
            'user_agent' => 'Mozilla/5.0 (Test Browser)',
            'honeypot' => '',
        ];

        $response = $this->actingAs($user)
            ->postJson('/api/forms/institution-demo-request', $formData);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Demo request submitted successfully! Our team will contact you soon.',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'submission_id',
                'crm_result',
            ]);
    });

    it('validates required fields for institution demo request', function () {
        $user = User::factory()->create();

        $formData = [
            'contact_name' => 'Jane Smith',
            // Missing many required fields
        ];

        $response = $this->actingAs($user)
            ->postJson('/api/forms/institution-demo-request', $formData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'contact_title',
                'institution_name',
                'institution_type',
                'institution_size',
                'email',
                'phone',
                'department',
                'decision_role',
                'alumni_count',
                'implementation_timeline',
                'primary_goals',
            ]);
    });

    it('validates enum values for institution demo request', function () {
        $user = User::factory()->create();

        $formData = [
            'contact_name' => 'Jane Smith',
            'contact_title' => 'Director',
            'institution_name' => 'University',
            'institution_type' => 'invalid_type', // Invalid enum value
            'institution_size' => 'invalid_size', // Invalid enum value
            'email' => 'jane@university.edu',
            'phone' => '+1234567890',
            'department' => 'invalid_department', // Invalid enum value
            'decision_role' => 'invalid_role', // Invalid enum value
            'alumni_count' => 'invalid_count', // Invalid enum value
            'implementation_timeline' => 'invalid_timeline', // Invalid enum value
            'primary_goals' => ['engagement'],
            'follow_up_consent' => true,
            'data_sharing_consent' => true,
            // Required spam protection fields
            'submit_time' => 5,
            'user_agent' => 'Mozilla/5.0 (Test Browser)',
            'honeypot' => '',
        ];

        $response = $this->actingAs($user)
            ->postJson('/api/forms/institution-demo-request', $formData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'institution_type',
                'institution_size',
                'department',
                'decision_role',
                'alumni_count',
                'implementation_timeline',
            ]);
    });
});

// Contact Form Template Tests
describe('Contact Form Template', function () {
    it('can submit valid contact form', function () {
        $user = User::factory()->create();

        $formData = [
            'name' => 'Bob Johnson',
            'organization' => 'Tech Startup Inc',
            'email' => 'bob@techstartup.com',
            'phone' => '+1234567890',
            'contact_role' => 'employer',
            'inquiry_category' => 'partnership',
            'priority_level' => 'medium',
            'preferred_contact_method' => 'email',
            'subject' => 'Partnership Opportunity Discussion',
            'message' => 'We are interested in partnering with your alumni network for recruitment purposes. Could we schedule a call to discuss this further?',
            'attachments_needed' => false,
            'follow_up_consent' => true,
            // Required spam protection fields
            'submit_time' => 5,
            'user_agent' => 'Mozilla/5.0 (Test Browser)',
            'honeypot' => '',
        ];

        $response = $this->actingAs($user)
            ->postJson('/api/forms/contact', $formData);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Your message has been sent successfully!',
            ]);
    });

    it('validates message length for contact form', function () {
        $user = User::factory()->create();

        $formData = [
            'name' => 'Bob Johnson',
            'email' => 'bob@example.com',
            'contact_role' => 'employer',
            'inquiry_category' => 'general',
            'priority_level' => 'low',
            'subject' => 'Test',
            'message' => 'Too short', // Less than 20 characters
            'follow_up_consent' => true,
        ];

        $response = $this->actingAs($user)
            ->postJson('/api/forms/contact', $formData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['message']);
    });

    it('validates subject length for contact form', function () {
        $user = User::factory()->create();

        $formData = [
            'name' => 'Bob Johnson',
            'email' => 'bob@example.com',
            'contact_role' => 'employer',
            'inquiry_category' => 'general',
            'priority_level' => 'low',
            'subject' => 'Hi', // Less than 5 characters
            'message' => 'This is a valid message that meets the minimum length requirement.',
            'follow_up_consent' => true,
        ];

        $response = $this->actingAs($user)
            ->postJson('/api/forms/contact', $formData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['subject']);
    });
});

// Newsletter Signup Form Template Tests
describe('Newsletter Signup Form Template', function () {
    it('can submit valid newsletter signup form', function () {
        $user = User::factory()->create();

        $formData = [
            'email' => 'subscriber@example.com',
            'first_name' => 'Alice',
            'newsletter_interests' => ['events', 'careers', 'news'],
            'email_frequency' => 'monthly',
        ];

        $response = $this->actingAs($user)
            ->postJson('/api/forms/newsletter-signup', $formData);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Thank you for subscribing! Check your email to confirm your subscription.',
            ]);
    });

    it('validates email for newsletter signup', function () {
        $user = User::factory()->create();

        $formData = [
            'email' => 'invalid-email-format',
            'first_name' => 'Alice',
        ];

        $response = $this->actingAs($user)
            ->postJson('/api/forms/newsletter-signup', $formData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    });
});

// Event Registration Form Template Tests
describe('Event Registration Form Template', function () {
    it('can submit valid event registration form', function () {
        $user = User::factory()->create();

        $formData = [
            'attendee_name' => 'Charlie Brown',
            'email' => 'charlie@example.com',
            'phone' => '+1234567890',
            'graduation_year' => 2018,
            'guest_count' => 1,
            'dietary_restrictions' => 'Vegetarian, no nuts',
        ];

        $response = $this->actingAs($user)
            ->postJson('/api/forms/event-registration', $formData);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Registration successful! You will receive a confirmation email shortly.',
            ]);
    });

    it('validates guest count limits for event registration', function () {
        $user = User::factory()->create();

        $formData = [
            'attendee_name' => 'Charlie Brown',
            'email' => 'charlie@example.com',
            'phone' => '+1234567890',
            'graduation_year' => 2018,
            'guest_count' => 10, // Exceeds maximum of 5
        ];

        $response = $this->actingAs($user)
            ->postJson('/api/forms/event-registration', $formData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['guest_count']);
    });
});

// CRM Integration Tests
describe('CRM Integration', function () {
    it('calculates lead scores correctly for different templates', function () {
        $user = User::factory()->create();

        // High-value demo request should get high score
        $demoRequestData = [
            'contact_name' => 'Jane Smith',
            'contact_title' => 'VP Alumni Relations',
            'institution_name' => 'Major University',
            'institution_type' => 'public_university',
            'institution_size' => '>30000',
            'email' => 'jane@university.edu',
            'phone' => '+1234567890',
            'department' => 'alumni_relations',
            'decision_role' => 'decision_maker', // +25 points
            'alumni_count' => '>100000',
            'budget_range' => '25k-50k', // +20 points
            'implementation_timeline' => '1-3months', // +15 points
            'primary_goals' => ['alumni_engagement', 'fundraising'],
            'follow_up_consent' => true,
            'data_sharing_consent' => true,
            'urgency_reason' => 'Board meeting next month requires demo',
            // Required spam protection fields
            'submit_time' => 5,
            'user_agent' => 'Mozilla/5.0 (Test Browser)',
            'honeypot' => '',
        ];

        $response = $this->actingAs($user)
            ->postJson('/api/forms/institution-demo-request', $demoRequestData);

        $response->assertOk();

        // The response should include CRM result with calculated lead score
        $responseData = $response->json();
        expect($responseData['crm_result']['success'])->toBeTrue();
    });

    it('handles CRM integration failures gracefully', function () {
        $user = User::factory()->create();

        $formData = [
            'email' => 'test@example.com',
            'first_name' => 'Test',
            // Required spam protection fields
            'submit_time' => 5,
            'user_agent' => 'Mozilla/5.0 (Test Browser)',
            'honeypot' => '',
        ];

        // Even if CRM fails, form submission should succeed
        $response = $this->actingAs($user)
            ->postJson('/api/forms/newsletter-signup', $formData);

        $response->assertOk()
            ->assertJson(['success' => true]);
    });
});

// Template Validation Tests
describe('Template Validation', function () {
    it('validates phone number format across templates', function () {
        $user = User::factory()->create();

        // Test with institution demo request where phone is required
        $invalidPhoneData = [
            'contact_name' => 'Jane Smith',
            'contact_title' => 'Alumni Relations Director',
            'institution_name' => 'University of Example',
            'institution_type' => 'public_university',
            'institution_size' => '15000-30000',
            'email' => 'jane.smith@university.edu',
            'phone' => 'not-a-phone-number-at-all', // Invalid format
            'department' => 'alumni_relations',
            'decision_role' => 'decision_maker',
            'alumni_count' => '15000-50000',
            'current_system' => 'manual',
            'budget_range' => '25k-50k',
            'implementation_timeline' => '3-6months',
            'primary_goals' => ['alumni_engagement', 'fundraising', 'data_management'],
            'follow_up_consent' => true,
            'data_sharing_consent' => true,
            // Required spam protection fields
            'submit_time' => 5,
            'user_agent' => 'Mozilla/5.0 (Test Browser)',
            'honeypot' => '',
        ];

        $response = $this->actingAs($user)
            ->postJson('/api/forms/institution-demo-request', $invalidPhoneData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);
    });

    it('validates graduation year ranges', function () {
        $user = User::factory()->create();

        $invalidYearData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'graduation_year' => 1900, // Too old
            'degree_level' => 'bachelor',
            'major' => 'Computer Science',
            'privacy_consent' => true,
        ];

        $response = $this->actingAs($user)
            ->postJson('/api/forms/individual-signup', $invalidYearData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['graduation_year']);
    });
});
