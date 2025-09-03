<?php

namespace Tests\Browser;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\Hash;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class GraduateRegistrationBrowserTest extends DuskTestCase
{
    use DatabaseMigrations, DatabaseTruncation;

    protected User $testGraduate;
    protected Institution $testInstitution;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testInstitution = Institution::factory()->create([
            'name' => 'Test University',
            'domain' => 'test-university.com',
        ]);

        // Create a test graduate user
        $this->testGraduate = User::factory()->create([
            'name' => 'John Graduate',
            'email' => 'john.graduate@test-university.com',
            'password' => Hash::make('password123'),
            'institution_id' => $this->testInstitution->id,
            'email_verified_at' => now(),
        ]);
    }

    public function test_complete_gradate_registration_and_profile_setup_journey()
    {
        $this->browse(function (Browser $browser) {
            // Step 1: Visit the registration page
            $browser->visit('/')
                   ->assertSee('Alumni Management System')
                   ->clickLink('Register');

            // Step 2: Fill out registration form
            $browser->waitFor('.register-form')
                   ->type('name', 'Alice Johnson')
                   ->type('email', 'alice.johnson@test-university.com')
                   ->type('password', 'password123')
                   ->type('password_confirmation', 'password123')
                   ->select('institution_id', $this->testInstitution->id)
                   ->click('.register-submit-button');

            // Step 3: Verify redirect to email verification or dashboard
            $browser->assertPathIs('/email/verify')
                   ->or($browser->assertPathIs('/dashboard'));

            // Step 4: Complete profile setup (if redirected to dashboard)
            if ($browser->driver->getCurrentURL() == config('app.url') . '/dashboard') {
                $browser->visit('/profile/setup')
                       ->waitFor('.profile-setup-form');

                // Fill basic information
                $browser->type('first_name', 'Alice')
                       ->type('last_name', 'Johnson')
                       ->type('phone', '+1234567890')
                       ->type('bio', 'Recent graduate passionate about software development and technology innovation.');

                // Education details
                $browser->select('degree', 'Bachelor of Science')
                       ->type('major', 'Computer Science')
                       ->select('graduation_year', '2023')
                       ->type('graduation_date', '2023-05-15');

                // Location and preferences
                $browser->type('location_city', 'San Francisco')
                       ->type('location_country', 'USA')
                       ->type('preferred_location', 'Remote')
                       ->check('open_to_relocation')
                       ->select('preferred_work_type', 'full-time');

                // Skills section
                $browser->waitFor('.skills-section')
                       ->type('skills[]', 'JavaScript, React, Node.js, Python, SQL, Git')
                       ->select('experience_level', 'entry');

                // Career goals
                $browser->type('career_goals', 'Become a full-stack developer and contribute to innovative tech projects.')
                       ->select('job_search_status', 'active');

                // Interests and networking
                $browser->type('industry_interests', 'Technology, FinTech, Healthcare Technology')
                       ->check('interested_events')
                       ->check('interested_mentorship')
                       ->check('interested_volunteer');

                // Submit profile setup
                $browser->click('.submit-profile-setup')
                       ->assertSee('Profile updated successfully');

                // Step 5: Verify profile data was saved
                $browser->visit('/profile')
                       ->assertSee('Alice Johnson')
                       ->assertSee('Computer Science')
                       ->assertSee('Bachelor of Science')
                       ->assertSee('San Francisco')
                       ->assertSee('JavaScript')
                       ->assertSee('entry level');
            }

            // Step 6: Test account verification
            $browser->visit('/profile/verification')
                   ->waitFor('.verification-section');

            // Upload profile picture
            $browser->attach('.profile-picture-upload', storage_path('testing/test-avatar.jpg'))
                   ->click('.upload-picture-button')
                   ->assertSee('Profile picture uploaded successfully');

            // Resume upload
            $browser->attach('.resume-upload', storage_path('testing/test-resume.pdf'))
                   ->click('.upload-resume-button')
                   ->assertSee('Resume uploaded successfully');

            // LinkedIn verification
            $browser->type('linkedin_url', 'https://linkedin.com/in/alice-johnson')
                   ->click('.verify-social-button')
                   ->assertSee('LinkedIn verified');

            // Step 7: Complete onboarding questionnaire
            $browser->visit('/onboarding')
                   ->waitFor('.onboarding-form');

            // Career preferences
            $browser->click('.career-preference-option[data-type="development"]')
                   ->click('.experience-level-option[data-level="junior"]')
                   ->click('.salary-range-option[data-range="50-80k"]');

            // Work preferences
            $browser->check('work_type_remote')
                   ->check('work_type_hybrid')
                   ->click('.company-size-option[data-size="startup"]');

            // Learning goals
            $browser->click('.learning-goal-option[data-goal="technical-skills"]')
                   ->click('.learning-goal-option[data-goal="leadership"]');

            // Industry interests
            $browser->click('.industry-interest-option[data-industry="technology"]')
                   ->click('.industry-interest-option[data-industry="finance"]');

            $browser->click('.complete-onboarding-button')
                   ->assertSee('Onboarding completed successfully');

            // Step 8: Verify dashboard shows personalized recommendations
            $browser->visit('/dashboard')
                   ->waitFor('.graduate-dashboard')
                   ->assertSee('Welcome to your dashboard, Alice')
                   ->assertSee('Job Recommendations')
                   ->assertSee('Suggested Connections')
                   ->assertSee('Upcoming Events')
                   ->assertSee('Mentorship Opportunities');

            // Step 9: Test search and filtering functionality
            $browser->visit('/jobs')
                   ->waitFor('.jobs-list');

            // Search for jobs
            $browser->type('.job-search-input', 'Software Developer')
                   ->select('.job-filter-location', 'Remote')
                   ->select('.job-filter-experience', 'entry')
                   ->click('.search-jobs-button');

            // Verify search results
            $browser->assertSee('Software Developer')
                   ->assertSee('Remote')
                   ->assertVisible('.job-results-count');

            // Step 10: Test job application flow
            $browser->click('.apply-job-button:first-child')
                   ->waitFor('.job-application-form');

            // Fill application form
            $browser->type('.cover-letter', 'Dear Hiring Manager,\n\nI am excited about the Software Developer position...')
                   ->attach('.application-resume', storage_path('testing/test-resume.pdf'))
                   ->type('.availability_date', '2024-01-15')
                   ->click('.submit-application-button')
                   ->assertSee('Application submitted successfully');

            // Step 11: Verify application tracking
            $browser->click('.my-applications-link')
                   ->waitFor('.applications-list')
                   ->assertSee('Application submitted successfully')
                   ->click('.view-application-details:first-child')
                   ->assertSee('Under Review')
                   ->assertSee('Software Developer');

            // Step 12: Test alumni network discovery
            $browser->visit('/alumni')
                   ->waitFor('.alumni-directory');

            // Search alumni
            $browser->type('.alumni-search-input', 'Software Engineer')
                   ->select('.alumni-filter-location', 'San Francisco')
                   ->click('.search-alumni-button');

            // Connect with alumni
            $browser->click('.connect-alumni-button:first-child')
                   ->waitFor('.connection-request-modal')
                   ->type('.connection-message', 'Hi! I\'m interested in connecting and learning about your experience in software development.')
                   ->click('.send-connection-request-button')
                   ->assertSee('Connection request sent');

            // Step 13: Test mentorship discovery
            $browser->visit('/mentorship')
                   ->waitFor('.mentorship-section')
                   ->type('.mentor-search-input', 'Product Management')
                   ->click('.find-mentors-button')
                   ->assertSee('Available Mentors');

            // Step 14: Test event discovery and registration
            $browser->visit('/events')
                   ->waitFor('.events-list');

            // Filter events
            $browser->select('.event-filter-type', 'networking')
                   ->select('.event-filter-location', 'online')
                   ->click('.filter-events-button');

            // Register for event
            $browser->click('.register-event-button:first-child')
                   ->waitFor('.event-registration-form')
                   ->click('.confirm-registration-button')
                   ->assertSee('Successfully registered for event');

            // Step 15: Test profile analytics and progress tracking
            $browser->visit('/profile/progress')
                   ->waitFor('.progress-dashboard')
                   ->assertSee('Profile Completion: 95%')
                   ->assertSee('Recent Activity')
                   ->assertSee('Skills Assessment')
                   ->assertSee('Career Readiness Score');

            // Step 16: Test notifications and messaging
            $browser->visit('/notifications')
                   ->waitFor('.notifications-list')
                   ->assertSee('You have new notifications')
                   ->click('.notification-item:first-child')
                   ->assertSee('Mark as read');

            // Step 17: Test settings and preferences
            $browser->visit('/settings')
                   ->waitFor('.settings-panel')
                   ->click('.account-settings-tab')
                   ->type('.current-password', 'password123')
                   ->type('.new-password', 'newPassword123')
                   ->type('.new-password-confirmation', 'newPassword123')
                   ->click('.update-password-button')
                   ->assertSee('Password updated successfully');

            // Step 18: Test privacy and data settings
            $browser->click('.privacy-settings-tab')
                   ->check('.profile-visibility-public')
                   ->check('.job-search-notifications')
                   ->uncheck('.profile-share-allowed')
                   ->click('.save-privacy-settings-button')
                   ->assertSee('Privacy settings saved');

            // Step 19: Complete final onboarding tasks
            $browser->visit('/dashboard')
                   ->assertDontSee('.onboarding-banner') // Should be hidden now
                   ->assertSee('.dashboard-navigation')
                   ->assertSee('.profile-progress-indicator[progress="95"]')
                   ->assertSee('.recent-activity-feed')
                   ->assertSee('.recommended-content');

            // Step 20: Success verification - Graduate is now fully onboarded
            $browser->assertUrlContains('/dashboard')
                   ->driver->executeScript('return window.isFullyOnboarded() === true')
                   ->assertTrue(true, 'User is successfully onboarded');
        });
    }
}