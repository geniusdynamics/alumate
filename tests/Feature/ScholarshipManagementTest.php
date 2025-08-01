<?php

use App\Models\Scholarship;
use App\Models\ScholarshipApplication;
use App\Models\ScholarshipRecipient;
use App\Models\User;

test('can create scholarship', function () {
    $user = User::factory()->create();
    
    $scholarshipData = [
        'name' => 'Test Scholarship',
        'description' => 'A test scholarship for students',
        'amount' => 5000.00,
        'type' => 'one_time',
        'eligibility_criteria' => [
            'min_gpa' => 3.0,
            'academic_year' => 'junior'
        ],
        'application_requirements' => [
            'personal_statement' => true,
            'transcripts' => true
        ],
        'application_deadline' => now()->addMonths(3)->format('Y-m-d'),
        'max_recipients' => 2,
        'total_fund_amount' => 10000.00
    ];

    $response = $this->actingAs($user)
        ->postJson('/api/scholarships', $scholarshipData);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'data' => [
                'name' => 'Test Scholarship',
                'creator_id' => $user->id
            ]
        ]);

    $this->assertDatabaseHas('scholarships', [
        'name' => 'Test Scholarship',
        'creator_id' => $user->id
    ]);
});

test('can submit scholarship application', function () {
    $scholarship = Scholarship::factory()->create([
        'status' => 'active',
        'application_deadline' => now()->addMonth()
    ]);
    $applicant = User::factory()->create();

    $applicationData = [
        'application_data' => [
            'academic_year' => 'junior',
            'field_of_study' => 'Engineering'
        ],
        'personal_statement' => 'I am passionate about engineering and need financial support.',
        'gpa' => 3.5
    ];

    $response = $this->actingAs($applicant)
        ->postJson("/api/scholarships/{$scholarship->id}/applications", $applicationData);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'data' => [
                'scholarship_id' => $scholarship->id,
                'applicant_id' => $applicant->id,
                'status' => 'submitted'
            ]
        ]);

    $this->assertDatabaseHas('scholarship_applications', [
        'scholarship_id' => $scholarship->id,
        'applicant_id' => $applicant->id,
        'status' => 'submitted'
    ]);
});

test('can review scholarship application', function () {
    $application = ScholarshipApplication::factory()->create([
        'status' => 'submitted'
    ]);
    $reviewer = User::factory()->create();

    $reviewData = [
        'score' => 85.5,
        'comments' => 'Strong application with good academic performance.',
        'recommendation' => 'approve'
    ];

    $response = $this->actingAs($reviewer)
        ->postJson("/api/scholarships/{$application->scholarship_id}/applications/{$application->id}/review", $reviewData);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'application_id' => $application->id,
                'reviewer_id' => $reviewer->id,
                'score' => 85.5,
                'recommendation' => 'approve'
            ]
        ]);

    $this->assertDatabaseHas('scholarship_reviews', [
        'application_id' => $application->id,
        'reviewer_id' => $reviewer->id,
        'score' => 85.5
    ]);
});

test('can award scholarship to recipient', function () {
    $application = ScholarshipApplication::factory()->create([
        'status' => 'approved'
    ]);
    $admin = User::factory()->create();

    $awardData = [
        'awarded_amount' => 5000.00,
        'award_date' => now()->format('Y-m-d')
    ];

    $response = $this->actingAs($admin)
        ->postJson("/api/scholarships/{$application->scholarship_id}/applications/{$application->id}/award", $awardData);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'scholarship_id' => $application->scholarship_id,
                'application_id' => $application->id,
                'recipient_id' => $application->applicant_id,
                'awarded_amount' => '5000.00',
                'status' => 'awarded'
            ]
        ]);

    $this->assertDatabaseHas('scholarship_recipients', [
        'scholarship_id' => $application->scholarship_id,
        'application_id' => $application->id,
        'awarded_amount' => 5000.00
    ]);
});

test('can get scholarship impact report', function () {
    $scholarship = Scholarship::factory()->create();
    $recipients = ScholarshipRecipient::factory()->count(3)->create([
        'scholarship_id' => $scholarship->id
    ]);
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->getJson("/api/scholarships/{$scholarship->id}/impact-report");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'recipients_count' => 3
            ]
        ]);
});
