<?php

use App\Models\User;
use App\Models\Tenant;
use App\Models\FormBuilder;
use App\Models\FormField;
use App\Models\FormSubmission;
use App\Services\FormBuilderService;
use App\Services\CrmIntegrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->user = User::factory()->create();
    
    // Set up tenant context
    tenancy()->initialize($this->tenant);
    
    $this->actingAs($this->user);
});

describe('Form Builder API', function () {
    it('can create a form with fields', function () {
        $formData = [
            'name' => 'Contact Form',
            'description' => 'A simple contact form',
            'success_message' => 'Thank you for contacting us!',
            'crm_integration_config' => [
                'enabled' => true,
                'provider' => 'salesforce',
                'field_mappings' => [
                    'first_name' => 'FirstName',
                    'last_name' => 'LastName',
                    'email' => 'Email'
                ]
            ],
            'fields' => [
                [
                    'field_type' => 'text',
                    'field_name' => 'first_name',
                    'field_label' => 'First Name',
                    'is_required' => true,
                    'order_index' => 0
                ],
                [
                    'field_type' => 'text',
                    'field_name' => 'last_name',
                    'field_label' => 'Last Name',
                    'is_required' => true,
                    'order_index' => 1
                ],
                [
                    'field_type' => 'email',
                    'field_name' => 'email',
                    'field_label' => 'Email Address',
                    'is_required' => true,
                    'order_index' => 2
                ]
            ]
        ];

        $response = $this->postJson('/api/form-builders', $formData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'form' => [
                    'id',
                    'name',
                    'description',
                    'crm_integration_config',
                    'fields' => [
                        '*' => [
                            'id',
                            'field_type',
                            'field_name',
                            'field_label',
                            'is_required',
                            'order_index'
                        ]
                    ]
                ]
            ]);

        $this->assertDatabaseHas('form_builders', [
            'name' => 'Contact Form',
            'tenant_id' => $this->tenant->id
        ]);

        $this->assertDatabaseCount('form_fields', 3);
    });

    it('can list forms for a tenant', function () {
        FormBuilder::factory()
            ->has(FormField::factory()->count(3))
            ->count(2)
            ->create(['tenant_id' => $this->tenant->id]);

        $response = $this->getJson('/api/form-builders');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'description',
                        'is_active',
                        'fields'
                    ]
                ]
            ]);
    });

    it('can update a form', function () {
        $form = FormBuilder::factory()
            ->has(FormField::factory()->count(2))
            ->create(['tenant_id' => $this->tenant->id]);

        $updateData = [
            'name' => 'Updated Form Name',
            'description' => 'Updated description',
            'fields' => [
                [
                    'field_type' => 'text',
                    'field_name' => 'name',
                    'field_label' => 'Full Name',
                    'is_required' => true,
                    'order_index' => 0
                ]
            ]
        ];

        $response = $this->putJson("/api/form-builders/{$form->id}", $updateData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('form_builders', [
            'id' => $form->id,
            'name' => 'Updated Form Name'
        ]);

        // Should have only 1 field after update
        $this->assertDatabaseCount('form_fields', 1);
    });

    it('can delete a form', function () {
        $form = FormBuilder::factory()
            ->has(FormField::factory()->count(2))
            ->create(['tenant_id' => $this->tenant->id]);

        $response = $this->deleteJson("/api/form-builders/{$form->id}");

        $response->assertStatus(200);

        $this->assertSoftDeleted('form_builders', ['id' => $form->id]);
    });

    it('validates required fields when creating form', function () {
        $response = $this->postJson('/api/form-builders', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    });

    it('validates CRM provider when CRM integration is enabled', function () {
        $formData = [
            'name' => 'Test Form',
            'crm_integration_config' => [
                'enabled' => true
                // Missing provider
            ]
        ];

        $response = $this->postJson('/api/form-builders', $formData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['crm_integration_config.provider']);
    });
});

describe('Form Submission', function () {
    it('can submit form data', function () {
        $form = FormBuilder::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true
        ]);

        FormField::factory()->create([
            'form_id' => $form->id,
            'field_type' => 'text',
            'field_name' => 'name',
            'field_label' => 'Name',
            'is_required' => true
        ]);

        FormField::factory()->create([
            'form_id' => $form->id,
            'field_type' => 'email',
            'field_name' => 'email',
            'field_label' => 'Email',
            'is_required' => true
        ]);

        $submissionData = [
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ];

        $response = $this->withoutMiddleware(['auth:sanctum'])
            ->postJson("/api/form-builders/{$form->id}/submit", $submissionData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'submission_id'
            ]);

        $this->assertDatabaseHas('form_submissions', [
            'form_id' => $form->id,
            'tenant_id' => $this->tenant->id
        ]);
    });

    it('validates required fields on submission', function () {
        $form = FormBuilder::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true
        ]);

        FormField::factory()->create([
            'form_id' => $form->id,
            'field_type' => 'text',
            'field_name' => 'name',
            'field_label' => 'Name',
            'is_required' => true
        ]);

        $response = $this->withoutMiddleware(['auth:sanctum'])
            ->postJson("/api/form-builders/{$form->id}/submit", []);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors'
            ]);
    });

    it('rejects submissions to inactive forms', function () {
        $form = FormBuilder::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => false
        ]);

        $response = $this->withoutMiddleware(['auth:sanctum'])
            ->postJson("/api/form-builders/{$form->id}/submit", [
                'name' => 'John Doe'
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Form is not active'
            ]);
    });
});

describe('Conditional Logic', function () {
    it('can evaluate conditional logic for form fields', function () {
        $form = FormBuilder::factory()->create(['tenant_id' => $this->tenant->id]);

        FormField::factory()->create([
            'form_id' => $form->id,
            'field_name' => 'contact_method',
            'field_type' => 'select',
            'field_options' => [
                ['value' => 'email', 'label' => 'Email'],
                ['value' => 'phone', 'label' => 'Phone']
            ]
        ]);

        FormField::factory()->create([
            'form_id' => $form->id,
            'field_name' => 'phone_number',
            'field_type' => 'phone',
            'conditional_logic' => [
                'logic' => 'and',
                'rules' => [
                    [
                        'field' => 'contact_method',
                        'operator' => 'equals',
                        'value' => 'phone'
                    ]
                ]
            ]
        ]);

        $submissionData = [
            'contact_method' => 'phone'
        ];

        $response = $this->postJson("/api/form-builders/{$form->id}/conditional-logic", $submissionData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'visible_fields'
            ]);

        $visibleFields = $response->json('visible_fields');
        expect($visibleFields['phone_number'])->toBeTrue();
    });
});

describe('Field Types', function () {
    it('can get available field types', function () {
        $response = $this->getJson('/api/form-builders/field-types');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'text' => [
                    'label',
                    'icon',
                    'validation_options'
                ],
                'email' => [
                    'label',
                    'icon',
                    'validation_options'
                ]
            ]);
    });
});

describe('CRM Integration', function () {
    it('queues CRM sync job when form has CRM integration enabled', function () {
        Queue::fake();

        $form = FormBuilder::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
            'crm_integration_config' => [
                'enabled' => true,
                'provider' => 'salesforce',
                'field_mappings' => [
                    'email' => 'Email'
                ]
            ]
        ]);

        FormField::factory()->create([
            'form_id' => $form->id,
            'field_type' => 'email',
            'field_name' => 'email',
            'is_required' => true
        ]);

        $this->withoutMiddleware(['auth:sanctum'])
            ->postJson("/api/form-builders/{$form->id}/submit", [
                'email' => 'test@example.com'
            ]);

        Queue::assertPushed(\App\Jobs\ProcessFormSubmissionToCrm::class);
    });
});

describe('Form Analytics', function () {
    it('can get form submission analytics', function () {
        $form = FormBuilder::factory()->create(['tenant_id' => $this->tenant->id]);

        // Create some test submissions
        FormSubmission::factory()->count(5)->create([
            'form_id' => $form->id,
            'tenant_id' => $this->tenant->id,
            'status' => 'processed',
            'crm_sync_status' => 'synced'
        ]);

        FormSubmission::factory()->count(2)->create([
            'form_id' => $form->id,
            'tenant_id' => $this->tenant->id,
            'status' => 'failed',
            'crm_sync_status' => 'failed'
        ]);

        $response = $this->getJson("/api/form-builders/{$form->id}/analytics");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'summary' => [
                    'total_submissions',
                    'successful_submissions',
                    'success_rate',
                    'crm_synced',
                    'crm_sync_rate',
                    'failed_crm_sync'
                ],
                'daily_submissions',
                'top_referrers',
                'utm_sources'
            ]);

        $summary = $response->json('summary');
        expect($summary['total_submissions'])->toBe(7);
        expect($summary['successful_submissions'])->toBe(5);
        expect($summary['crm_synced'])->toBe(5);
    });
});