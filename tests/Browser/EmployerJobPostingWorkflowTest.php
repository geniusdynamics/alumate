<?php

namespace Tests\Browser;

use App\Models\Company;
use App\Models\Employer;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Hash;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class EmployerJobPostingWorkflowTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected User $testEmployer;
    protected Employer $employer;
    protected Company $company;
    protected Institution $institution;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institution = Institution::factory()->create([
            'name' => 'Test University',
            'domain' => 'test-university.com',
        ]);

        $this->company = Company::factory()->create([
            'name' => 'Tech Innovations Inc.',
            'website' => 'https://techinnovations.com',
            'industry' => 'Technology',
            'size' => '50-200 employees',
            'location' => 'San Francisco, CA',
            'description' => 'Leading technology company focused on innovative software solutions.',
        ]);

        $this->testEmployer = User::factory()->create([
            'name' => 'Sarah Johnson',
            'email' => 'sarah.johnson@techinnovations.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        $this->employer = Employer::factory()->create([
            'user_id' => $this->testEmployer->id,
            'company_id' => $this->company->id,
            'title' => 'HR Manager',
            'department' => 'Human Resources',
            'is_primary_contact' => true,
            'can_post_jobs' => true,
        ]);
    }

    public function test_complete_employer_job_posting_workflow()
    {
        $this->browse(function (Browser $browser) {
            // Step 1: Employer login
            $browser->visit(config('app.url') . '/login')
                   ->waitFor('.login-form')
                   ->type('email', 'sarah.johnson@techinnovations.com')
                   ->type('password', 'password123')
                   ->click('.login-submit-button')
                   ->assertPathIs('/dashboard');

            // Verify employer dashboard is loaded
            $browser->assertSee('Welcome to your dashboard, Sarah')
                   ->assertSee('Employer Dashboard')
                   ->assertSee('Job Postings')
                   ->assertSee('Application Management');

            // Step 2: Navigate to job posting section
            $browser->clickLink('Post New Job')
                   ->waitFor('.job-posting-form')
                   ->assertPathIs('/employer/jobs/create');

            // Step 3: Fill out basic job information
            $browser->type('job_title', 'Senior Frontend Developer')
                   ->select('employment_type', 'full-time')
                   ->select('experience_level', 'senior')
                   ->select('department', 'Engineering')
                   ->type('job_description', 'We are looking for an experienced Frontend Developer to join our growing engineering team. This role involves building cutting-edge web applications using modern JavaScript frameworks and technologies.')
                   ->type('responsibilities', '- Develop and maintain responsive web applications
- Collaborate with design team on UI/UX improvements
- Implement frontend architecture and best practices
- Mentor junior developers
- Participate in code reviews and technical discussions');

            // Step 4: Technical requirements
            $browser->waitFor('.technical-requirements-section')
                   ->type('required_skills[]', 'JavaScript, TypeScript, React, Vue.js, CSS3, HTML5')
                   ->type('preferred_skills[]', 'Webpack, Node.js, GraphQL, Testing Frameworks')
                   ->select('programming_languages[]', 'JavaScript,TypeScript')
                   ->select('frameworks[]', 'React,Vue.js')
                   ->select('tools[]', 'Git,Docker,Jira');

            // Step 5: Compensation and benefits
            $browser->click('.compensation-tab')
                   ->type('salary_min', '120000')
                   ->type('salary_max', '160000')
                   ->select('salary_period', 'annual')
                   ->type('bonus_structure', '20% performance bonus based on company and individual metrics')
                   ->type('benefits', '• Full health, dental, and vision coverage
• 401k with 4% company match
• Annual learning budget ($5000)
• Flexible PTO (20 days)
• Remote work arrangement
• Professional development opportunities');

            // Step 6: Location and work arrangement
            $browser->click('.location-tab')
                   ->select('work_location_type', 'hybrid')
                   ->type('work_location_city', 'San Francisco')
                   ->type('work_location_state', 'California')
                   ->check('remote_allowed')
                   ->type('office_requirement', '3 days per week in office, flexible schedule')
                   ->check('relocation_assistance')
                   ->type('visa_sponsorship', 'H1B visa sponsorship available for qualified candidates');

            // Step 7: Application process
            $browser->click('.application-process-tab')
                   ->type('application_deadline', now()->addDays(30)->format('Y-m-d'))
                   ->select('application_method', 'online-portal')
                   ->type('hiring_manager', 'John Smith (Engineering Manager)')
                   ->type('selection_process', '1. Resume screening (within 3 business days)
2. Technical phone screening (within 1 week)
3. Technical coding interview (1-2 weeks after screening)
4. System design interview (2 weeks after coding interview)
5. Final culture fit interview (3 weeks after design interview)
6. Reference checks and background verification
7. Offer extension')
                   ->type('expected_timeline', '45-60 days from application to offer');

            // Step 8: Customize application form
            $browser->click('.custom-fields-tab')
                   ->check('include_portfolio')
                   ->check('include_cover_letter')
                   ->type('custom_question_1', 'What is your favorite JavaScript framework and why?')
                   ->type('custom_question_2', 'Describe a challenging technical problem you solved recently.')
                   ->type('custom_question_3', 'How do you stay updated with the latest frontend technologies?');

            // Step 9: Set application notifications
            $browser->click('.notifications-tab')
                   ->check('notify_on_application')
                   ->check('notify_on_shortlist')
                   ->check('notify_on_interview_scheduled')
                   ->check('email_digest_daily')
                   ->type('notification_emails[]', 'recruiting@techinnovations.com,john.smith@techinnovations.com');

            // Step 10: Review and preview
            $browser->click('.preview-button')
                   ->waitFor('.job-preview-modal')
                   ->assertSee('Senior Frontend Developer')
                   ->assertSee('Tech Innovations Inc.')
                   ->assertSee('$120,000 - $160,000 per year')
                   ->assertSee('San Francisco, California')
                   ->assertSee('Hybrid')
                   ->click('.job-preview-modal .close-button');

            // Step 11: Publish the job
            $browser->click('.publish-job-button')
                   ->waitFor('.confirmation-modal')
                   ->check('agree_to_terms')
                   ->click('.confirm-publish-button')
                   ->assertSee('Job posted successfully!')
                   ->assertPathIs('/employer/jobs');

            // Step 12: Verify job listing in employer dashboard
            $browser->visit('/employer/jobs')
                   ->waitFor('.jobs-list')
                   ->assertSee('Senior Frontend Developer')
                   ->assertSee('Published')
                   ->assertSee('0 applications')
                   ->assertSee('Active')
                   ->click('.job-actions-dropdown:first-child')
                   ->click('.edit-job-link');

            // Step 13: Edit job posting
            $browser->waitFor('.job-edit-form')
                   ->type('job_description', 'UPDATE: Added team details - Join our growing frontend team of 12 developers working on exciting B2B SaaS products.')
                   ->click('.save-changes-button')
                   ->assertSee('Job updated successfully');

            // Step 14: Manage job visibility
            $browser->visit('/employer/jobs')
                   ->click('.job-status-toggle:first-child')
                   ->waitFor('.status-change-confirmation')
                   ->click('.confirm-status-change')
                   ->assertSee('Job status updated successfully');

            // Step 15: Set up job alerts and analytics
            $browser->click('.job-analytics-link:first-child')
                   ->waitFor('.job-analytics-dashboard')
                   ->assertSee('Job Performance')
                   ->assertSee('Application Trends')
                   ->assertSee('Source Analysis')
                   ->assertSee('Demographic Breakdown');

            // Step 16: Create job template for future use
            $browser->visit('/employer/job-templates')
                   ->click('.create-template-button')
                   ->waitFor('.template-form')
                   ->type('template_name', 'Senior Frontend Developer Template')
                   ->type('template_description', 'Standard template for senior frontend developer positions')
                   ->select('template_category', 'Engineering')
                   ->click('.save-template-button')
                   ->assertSee('Template saved successfully');

            // Step 17: Manage candidate pipeline
            $browser->visit('/employer/jobs')
                   ->click('.manage-applications-link:first-child')
                   ->waitFor('.applications-pipeline')
                   ->assertSee('Application Stages')
                   ->assertSee('No applications yet')
                   ->assertSee('0 - Applied')
                   ->assertSee('0 - Reviewed')
                   ->assertSee('0 - Shortlisted');

            // Step 18: Access recruiter resources
            $browser->visit('/employer/resources')
                   ->waitFor('.resources-dashboard')
                   ->assertSee('Hiring Best Practices')
                   ->assertSee('Interview Templates')
                   ->assertSee('Candidate Assessment Tools')
                   ->assertSee('Legal Compliance Guide')
                   ->assertSee('Diversity & Inclusion Resources');

            // Step 19: View company branding and setup
            $browser->visit('/employer/company/profile')
                   ->waitFor('.company-profile-form')
                   ->assertSee('Company Profile')
                   ->assertSee('Tech Innovations Inc.')
                   ->type('company_values', 'Innovation, Collaboration, Excellence, Learning')
                   ->type('company_culture', 'We foster a culture of continuous learning and innovation where everyone has a voice...')
                   ->click('.save-company-profile')
                   ->assertSee('Company profile updated successfully');

            // Step 20: Set up integration with external platforms
            $browser->visit('/employer/integrations')
                   ->waitFor('.integrations-panel')
                   ->assertSee('LinkedIn')
                   ->assertSee('Indeed')
                   ->assertSee('Glassdoor')
                   ->assertSee('Company Careers Page')
                   ->click('.linkedin-integration-toggle')
                   ->assertSee('LinkedIn integration activated');

            // Step 21: Review hiring analytics
            $browser->visit('/employer/analytics')
                   ->waitFor('.hiring-analytics-dashboard')
                   ->assertSee('Hiring Funnel')
                   ->assertSee('Time to Hire')
                   ->assertSee('Quality of Hire')
                   ->assertSee('Offer Acceptance Rate')
                   ->assertSee('Candidate Experience Scores');

            // Step 22: Manage team collaboration
            $browser->visit('/employer/team')
                   ->waitFor('.team-management-panel')
                   ->click('.add-team-member-button')
                   ->waitFor('.add-member-modal')
                   ->type('member_email', 'jane.doe@techinnovations.com')
                   ->select('member_role', 'Senior Recruiter')
                   ->select('access_level', 'can_post_jobs,can_review_applications,can_schedule_interviews')
                   ->click('.send-invitation-button')
                   ->assertSee('Team member invitation sent successfully');

            // Step 23: Compliance and legal checklist
            $browser->visit('/employer/compliance')
                   ->waitFor('.compliance-dashboard')
                   ->assertSee('Legal Requirements')
                   ->assertSee('EEO Compliance')
                   ->assertSee('Data Privacy')
                   ->assertSee('ADA Compliance')
                   ->check('.confirm_equal_employment_opportunity')
                   ->check('.confirm_data_privacy_compliance')
                   ->check('.confirm_ada_compliance')
                   ->click('.generate-compliance-report')
                   ->assertSee('Compliance report generated successfully');

            // Step 24: Set up automated workflows
            $browser->visit('/employer/workflows')
                   ->waitFor('.workflows-panel')
                   ->click('.create-workflow-button')
                   ->waitFor('.workflow-builder')
                   ->select('workflow_trigger', 'new_application')
                   ->click('.add-step-button')
                   ->select('step_type', 'auto_response')
                   ->type('step_message', 'Thank you for applying! We\'ll review your application and get back to you within 3 business days.')
                   ->click('.add-step-button')
                   ->select('step_type', 'qualification_check')
                   ->select('criteria', 'minimum_experience:3_years')
                   ->click('.save-workflow-button')
                   ->assertSee('Workflow created successfully');

            // Step 25: Access support resources
            $browser->visit('/employer/support')
                   ->waitFor('.support-panel')
                   ->assertSee('FAQs')
                   ->assertSee('Video Tutorials')
                   ->assertSee('Live Chat Support')
                   ->assertSee('Documentation')
                   ->assertSee('Community Forum');

            // Step 26: Final dashboard verification
            $browser->visit('/dashboard')
                   ->assertSee('Employer Dashboard')
                   ->assertSee('Active Job Postings: 1')
                   ->assertSee('Applications Received: 0')
                   ->assertSee('Compliance Status: Passed')
                   ->assertSee('Workflows Active: 1')
                   ->assertSee('Team Members: 2');

            // Step 27: Export data and generate reports
            $browser->visit('/employer/reports')
                   ->waitFor('.reports-panel')
                   ->click('.generate-hiring-report')
                   ->waitFor('.report-generation-modal')
                   ->select('report_period', 'last_30_days')
                   ->check('include_applicant_demographics')
                   ->check('include_time_metrics')
                   ->check('include_offer_analytics')
                   ->click('.generate-report-button')
                   ->assertPathIs('/employer/reports')
                   ->assertSee('Report generated successfully');
        });
    }

    public function test_employer_bulk_job_posting_workflow()
    {
        $this->browse(function (Browser $browser) {
            // Login as employer
            $browser->visit(config('app.url') . '/login')
                   ->type('email', 'sarah.johnson@techinnovations.com')
                   ->type('password', 'password123')
                   ->click('.login-submit-button')
                   ->assertPathIs('/dashboard');

            // Navigate to bulk posting
            $browser->visit('/employer/bulk-posting')
                   ->waitFor('.bulk-posting-interface')
                   ->assertSee('Bulk Job Posting')
                   ->assertSee('Create Multiple Job Postings')
                   ->assertSee('Import from Excel/CSV')
                   ->assertSee('Use Templates');

            // Create jobs from spreadsheet
            $browser->attach('.bulk-upload-input', storage_path('testing/bulk-jobs-template.xlsx'))
                   ->click('.validate-uploaded-data-button')
                   ->waitFor('.validation-results')
                   ->assertSee('Validation completed successfully')
                   ->assertDontSee('.validation-errors');

            // Configure bulk settings
            $browser->type('bulk_job_prefix', 'Tech Innovations - Open Positions')
                   ->check('apply_company_defaults')
                   ->select('bulk_publish_schedule', 'publish-immediately')
                   ->check('send_notification_after_bulk')
                   ->type('notification_emails[]', 'recruiting@techinnovations.com');

            // Preview jobs
            $browser->click('.bulk-preview-button')
                   ->waitFor('.bulk-jobs-preview')
                   ->assertSee('Previewing 5 job postings')
                   ->assertSee('Junior Software Developer')
                   ->assertSee('Senior Frontend Developer')
                   ->assertSee('DevOps Engineer')
                   ->assertSee('Product Manager')
                   ->assertSee('UI/UX Designer')
                   ->click('.confirm-bulk-publication')
                   ->assertSee('5 job postings published successfully!');

            // Verify published jobs
            $browser->visit('/employer/jobs')
                   ->assertSee('Junior Software Developer')
                   ->assertSee('Senior Frontend Developer')
                   ->assertSee('DevOps Engineer')
                   ->assertSee('Product Manager')
                   ->assertSee('UI/UX Designer');
        });
    }
}