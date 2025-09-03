<?php

namespace Tests\Browser;

use App\Models\Company;
use App\Models\Employer;
use App\Models\Institution;
use App\Models\Job;
use App\Models\User;
use App\Models\WorkExperience;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\Hash;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class JobApplicationJourneyBrowserTest extends DuskTestCase
{
    use DatabaseMigrations, DatabaseTruncation;

    protected User $testGraduate;
    protected User $testEmployer;
    protected Employer $employer;
    protected Company $company;
    protected Institution $institution;
    protected Job $job;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institution = Institution::factory()->create([
            'name' => 'Test University',
            'domain' => 'test-university.com',
        ]);

        $this->company = Company::factory()->create([
            'name' => 'Innovative Tech Solutions',
            'website' => 'https://innovativetech.com',
            'industry' => 'Software Development',
            'size' => '50-200 employees',
            'location' => 'San Francisco, CA',
        ]);

        // Create employer
        $this->testEmployer = User::factory()->create([
            'name' => 'John Hiring Manager',
            'email' => 'john.hiring@innovativetech.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        $this->employer = Employer::factory()->create([
            'user_id' => $this->testEmployer->id,
            'company_id' => $this->company->id,
            'title' => 'Senior Hiring Manager',
            'department' => 'Talent Acquisition',
            'is_primary_contact' => true,
            'can_post_jobs' => true,
        ]);

        // Create graduate with strong profile
        $this->testGraduate = User::factory()->create([
            'name' => 'Alex Developer',
            'email' => 'alex.dev@test-university.com',
            'password' => Hash::make('password123'),
            'institution_id' => $this->institution->id,
            'email_verified_at' => now(),
        ]);

        // Add work experience to graduate
        WorkExperience::factory()->create([
            'user_id' => $this->testGraduate->id,
            'company' => 'PreviousTech Corp',
            'title' => 'Junior Software Developer',
            'start_date' => now()->subYears(2),
            'end_date' => now()->subMonths(3),
            'description' => 'Developed web applications using React and Node.js',
            'is_current' => false,
        ]);

        WorkExperience::factory()->create([
            'user_id' => $this->testGraduate->id,
            'company' => 'Freelance',
            'title' => 'Full Stack Developer',
            'start_date' => now()->subMonths(3),
            'description' => 'Developing custom web solutions for various clients',
            'is_current' => true,
        ]);

        // Create job posting
        $this->job = Job::factory()->create([
            'employer_id' => $this->employer->id,
            'company_id' => $this->company->id,
            'title' => 'Full Stack Software Developer',
            'description' => 'We are looking for a talented full stack developer to join our growing engineering team. You will be working on cutting-edge web applications serving millions of users.',
            'location' => 'San Francisco, CA',
            'employment_type' => 'full-time',
            'experience_level' => 'senior',
            'salary_min' => 100000,
            'salary_max' => 140000,
            'is_active' => true,
            'required_skills' => ['React', 'Node.js', 'TypeScript', 'PostgreSQL', 'AWS'],
            'benefits' => ['Health Insurance', '401k Matching', 'Flexible PTO', 'Remote Work', 'Learning Budget']
        ]);
    }

    public function test_complete_job_application_lifecycle_journey()
    {
        $this->browse(function (Browser $browser) {
            // =======================================================================================
            // PHASE 1: GRADUATE DISCOVERS AND APPLIES FOR JOB
            // =======================================================================================

            // Step 1: Graduate logs in and navigates to job board
            $browser->visit(config('app.url') . '/login')
                   ->waitFor('.login-form')
                   ->type('email', 'alex.dev@test-university.com')
                   ->type('password', 'password123')
                   ->click('.login-submit-button')
                   ->assertPathIs('/dashboard')
                   ->assertSee('Welcome back, Alex Developer');

            // Step 2: Graduate browses job board
            $browser->visit('/jobs')
                   ->waitFor('.job-board')
                   ->type('.job-search-input', 'Full Stack Developer')
                   ->select('.job-filter-location', 'San Francisco, CA')
                   ->select('.job-filter-experience', 'senior')
                   ->click('.search-jobs-button');

            // Verify job appears in results
            $browser->assertSee('Full Stack Software Developer')
                   ->assertSee('Innovative Tech Solutions')
                   ->assertSee('San Francisco, CA')
                   ->assertSee('$100,000 - $140,000')
                   ->assertSee('Senior');

            // Step 3: Graduate views detailed job information
            $browser->click('.view-job-details:first-child')
                   ->waitFor('.job-details-modal')
                   ->assertSee('Full Stack Software Developer')
                   ->assertSee('We are looking for a talented full stack developer')
                   ->assertSee('React')
                   ->assertSee('Node.js')
                   ->assertSee('TypeScript')
                   ->assertSee('Health Insurance')
                   ->assertSee('401k Matching');

            // Step 4: Graduate bookmarks the job
            $browser->click('.bookmark-job-button')
                   ->assertSee('Job bookmarked successfully');

            // Step 5: Graduate submits application
            $browser->click('.apply-job-button')
                   ->waitFor('.job-application-form');

            // Fill out application form
            $browser->type('.cover-letter-textarea', 'Dear Hiring Team,

I am excited to apply for the Full Stack Software Developer position at Innovative Tech Solutions. With over 2 years of experience in web development, I have built multiple full-stack applications using React, Node.js, and TypeScript - the exact technologies mentioned in your job description.

In my previous role at PreviousTech Corp, I developed a customer portal that handled 10,000+ daily active users, improving user satisfaction by 35%. My freelance work has further honed my skills in modern web technologies and best practices.

I am particularly drawn to Innovative Tech Solutions because of your mission and the opportunity to work on applications that serve millions of users. Your commitment to learning and development aligns perfectly with my professional goals.

I would welcome the opportunity to discuss how my skills and experience can contribute to your engineering team.

Best regards,
Alex Developer');

            // Upload resume
            $browser->attach('.resume-upload', storage_path('testing/sample-resume.pdf'))
                   ->type('.phone-number', '+1-555-0123')
                   ->type('.linked-profile', 'https://linkedin.com/in/alex-developer')
                   ->type('.portfolio-url', 'https://alex-portfolio.dev');

            // Additional application questions
            $browser->type('.availability-date', now()->addWeeks(2)->format('Y-m-d'))
                   ->select('.notice-period', '2-weeks')
                   ->type('.expected-salary', '125000')
                   ->type('.why-companion', 'I am impressed by your innovative products and the company\'s focus on user experience. The opportunity to work with modern technologies while contributing to meaningful projects that impact millions of users is very appealing.')
                   ->type('.technical-challenge', 'I recently optimized a React application\'s performance by implementing code splitting and lazy loading, reducing initial bundle size by 40% and improving Time to Interactive (TTI) by 60%.');

            // Submit application
            $browser->click('.submit-application-button')
                   ->waitFor('.application-submitted-confirmation')
                   ->assertSee('Application submitted successfully!')
                   ->assertSee('Application ID: #APP-' . $this->job->id . '-001')
                   ->click('.view-application-status-link');

            // =======================================================================================
            // PHASE 2: EMPLOYER REVIEWS APPLICATION
            // =======================================================================================

            // Step 6: Employer logs in and views applications
            $browser->visit(config('app.url') . '/login') // Fresh browser instance for employer
                   ->waitFor('.login-form')
                   ->type('email', 'john.hiring@innovativetech.com')
                   ->type('password', 'password123')
                   ->click('.login-submit-button')
                   ->assertPathIs('/dashboard')
                   ->assertSee('Welcome back, John Hiring Manager');

            // Step 7: Employer views job applications
            $browser->visit('/employer/applications')
                   ->waitFor('.applications-dashboard')
                   ->assertSee('New Applications: 1')
                   ->click('.job-application-card:first-child')
                   ->waitFor('.application-details-modal');

            // Review candidate profile
            $browser->assertSee('Alex Developer')
                   ->assertSee('alex.dev@test-university.com')
                   ->assertSee('Test University')
                   ->assertSee('Junior Software Developer')
                   ->assertSee('Full Stack Developer')
                   ->assertSee('React, Node.js, TypeScript');

            // Review cover letter
            $browser->assertSee('Dear Hiring Team')
                   ->assertSee('PreviousTech Corp')
                   ->assertSee('customer portal')
                   ->assertSee('10,000+ daily active users');

            // Review application answers
            $browser->assertSee('Expected salary: $125,000')
                   ->assertSee('Notice period: 2 weeks')
                   ->assertSee('+1-555-0123')
                   ->assertSee('https://linkedin.com/in/alex-developer');

            // Step 8: Employer downloads and reviews resume
            $browser->click('.download-resume-button')
                   ->assertSee('Resume downloaded successfully');

            // Step 9: Employer schedules initial screening
            $browser->click('.schedule-screening-button')
                   ->waitFor('.screening-scheduler-modal')
                   ->select('.screening-type', 'phone-screening')
                   ->type('.screening-date', now()->addWeek()->format('Y-m-d'))
                   ->select('.screening-time', '14:00')
                   ->select('.screening-duration', '30')
                   ->type('.screening-notes', 'Technical screening focusing on React, Node.js, and system design skills')
                   ->select('.interviewer', 'John Hiring Manager')
                   ->type('.meeting-link', 'https://meet.google.com/abc-defg-hij')
                   ->click('.schedule-screening-confirm-button')
                   ->assertSee('Phone screening scheduled for ' . now()->addWeek()->format('M j, Y') . ' at 2:00 PM');

            // =======================================================================================
            // PHASE 3: GRADUATE RECEIVES AND PREPARES FOR INTERVIEW
            // =======================================================================================

            // Step 10: Graduate receives notification and checks interview details
            $browser->visit(config('app.url') . '/login') // Back to graduate session
                   ->waitFor('.login-form')
                   ->type('email', 'alex.dev@test-university.com')
                   ->type('password', 'password123')
                   ->click('.login-submit-button')
                   ->assertPathIs('/dashboard');

            // Check notifications
            $browser->click('.notifications-bell')
                   ->waitFor('.notifications-dropdown')
                   ->assertSee('Interview Scheduled')
                   ->assertSee('Phone Screening')
                   ->assertSee(now()->addWeek()->format('M j, Y'))
                   ->click('.view-interview-details-link')
                   ->waitFor('.interview-details-modal')
                   ->assertSee('Interview Scheduled')
                   ->assertSee('Phone Screening')
                   ->assertSee('John Hiring Manager')
                   ->assertSee('30 minutes')
                   ->assertSee('https://meet.google.com/abc-defg-hij')
                   ->assertSee('Technical screening focusing on React, Node.js, and system design skills');

            // Step 11: Graduate confirms interview attendance
            $browser->click('.confirm-interview-attendance-button')
                   ->waitFor('.confirmation-modal')
                   ->type('.interview-prep-notes', 'I will review React hooks, Node.js best practices, and prepare for system design questions.')
                   ->click('.confirm-attendance-button')
                   ->assertSee('Interview confirmed! Best of luck.');

            // =======================================================================================
            // PHASE 4: INTERVIEW PROCESS
            // =======================================================================================

            // Step 12: Post-interview - Employer updates status
            $browser->visit(config('app.url') . '/login') // Employer session
                   ->waitFor('.login-form')
                   ->type('email', 'john.hiring@innovativetech.com')
                   ->type('password', 'password123')
                   ->click('.login-submit-button')
                   ->assertPathIs('/dashboard');

            $browser->visit('/employer/applications')
                   ->click('.job-application-card:first-child')
                   ->waitFor('.application-details-modal')
                   ->click('.update-application-status-button')
                   ->waitFor('.status-update-modal')
                   ->select('.new-status', 'interviewed')
                   ->type('.interview-feedback', 'Excellent candidate! Strong technical skills in React and Node.js. Good understanding of system design. Positive attitude and great cultural fit. Presented portfolio work very professionally.')
                   ->select('.interview-rating', '4.5')
                   ->check('.recommend-for-next-round')
                   ->click('.save-status-update-button')
                   ->assertSee('Application status updated to Interviewed');

            // Step 13: Employer schedules technical interview
            $browser->click('.schedule-next-interview-button')
                   ->waitFor('.next-interview-scheduler-modal')
                   ->select('.interview-type', 'technical-coding')
                   ->type('.interview-date', now()->addWeeks(2)->format('Y-m-d'))
                   ->select('.interview-time', '10:00')
                   ->select('.interview-duration', '90')
                   ->type('.interview-focus', 'Hands-on coding challenges, system design, and architecture discussions')
                   ->select('.panel-interviewers', 'Sarah Technical Lead, John Hiring Manager')
                   ->type('.coding-challenge', 'Implement a React component for a user management dashboard with CRUD operations')
                   ->type('.system-design-question', 'Design a scalable notification system that handles millions of concurrent users')
                   ->click('.schedule-technical-interview-button')
                   ->assertSee('Technical interview scheduled successfully');

            // =======================================================================================
            // PHASE 5: TECHNICAL INTERVIEW AND FINAL DECISION
            // =======================================================================================

            // Step 14: Graduate receives technical interview notification
            $browser->visit(config('app.url') . '/login') // Graduate session
                   ->waitFor('.login-form')
                   ->type('email', 'alex.dev@test-university.com')
                   ->type('password', 'password123')
                   ->click('.login-submit-button')
                   ->assertPathIs('/dashboard');

            $browser->click('.notifications-bell')
                   ->click('.interview-notification-link')
                   ->waitFor('.interview-details-modal')
                   ->assertSee('Technical Interview Scheduled')
                   ->assertSee(now()->addWeeks(2)->format('M j, Y'))
                   ->assertSee('Sarah Technical Lead, John Hiring Manager')
                   ->assertSee('Implement a React component')
                   ->assertSee('Design a scalable notification system');

            // Step 15: Graduate prepares and confirms
            $browser->click('.confirm-interview-button')
                   ->type('.interview-preparation', 'I will prepare coding challenges in React and Node.js, review system design principles, and practice explaining technical decisions.')
                   ->click('.confirm-technical-interview-button')
                   ->assertSee('Technical interview confirmed');

            // Step 16: Post-technical interview - Employer makes offer decision
            $browser->visit(config('app.url') . '/login') // Employer session
                   ->waitFor('.login-form')
                   ->type('email', 'john.hiring@innovativetech.com')
                   ->type('password', 'password123')
                   ->click('.login-submit-button')
                   ->assertPathIs('/dashboard');

            $browser->visit('/employer/applications')
                   ->click('.job-application-card:first-child')
                   ->waitFor('.application-details-modal')
                   ->click('.extend-offer-button')
                   ->waitFor('.job-offer-modal')
                   ->type('.offered-salary', '115000')
                   ->type('.offered-bonus', '10000')
                   ->select('.equity-percentage', '0.5')
                   ->type('.start-date', now()->addWeeks(4)->format('Y-m-d'))
                   ->select('.offer-validity-period', '14-days')
                   ->type('.offer-message', 'Dear Alex,

We are thrilled to offer you the position of Full Stack Software Developer at Innovative Tech Solutions. Your technical skills, enthusiasm, and great cultural fit made you stand out during our interview process.

We believe you will be a wonderful addition to our engineering team and look forward to your contributions to our innovative projects that serve millions of users.

The offer includes the following:
- Base salary: $115,000/year
- Signing bonus: $10,000
- Equity: 0.5%
- Start date: ' . now()->addWeeks(4)->format('M j, Y') . '

Please review the complete offer details and let us know your decision by ' . now()->addWeeks(4)->addDays(14)->format('M j, Y') . '.

We are excited about the possibility of you joining our team!')
                   ->click('.send-offer-button')
                   ->assertSee('Job offer extended successfully!');

            // =======================================================================================
            // PHASE 6: GRADUATE RECEIVES AND ACCEPTS OFFER
            // =======================================================================================

            // Step 17: Graduate receives offer notification
            $browser->visit(config('app.url') . '/login') // Graduate session
                   ->waitFor('.login-form')
                   ->type('email', 'alex.dev@test-university.com')
                   ->type('password', 'password123')
                   ->click('.login-submit-button')
                   ->assertPathIs('/dashboard');

            $browser->click('.notifications-bell')
                   ->assertSee('Job Offer Received!')
                   ->click('.job-offer-notification-link')
                   ->waitFor('.job-offer-details-modal')
                   ->assertSee('Full Stack Software Developer')
                   ->assertSee('Innovative Tech Solutions')
                   ->assertSee('$115,000/year')
                   ->assertSee('$10,000')
                   ->assertSee('0.5%')
                   ->assertSee(now()->addWeeks(4)->format('M j, Y'));

            // Step 18: Graduate reviews and accepts offer
            $browser->click('.accept-offer-button')
                   ->waitFor('.accept-offer-modal')
                   ->type('.acceptance-message', 'Thank you for this amazing opportunity! I am excited to join the Innovative Tech Solutions team and contribute to building applications that serve millions of users. The compensation package and company culture perfectly align with my career goals.')
                   ->select('.preferred-start-date', 'negotiable')
                   ->type('.salary-expectations-met', 'Yes, the offer meets my expectations.')
                   ->type('.questions-for-employer', 'I would like to discuss the onboarding process and get more details about the technical stack I will be working with.')
                   ->check('.accept-all-offer-terms')
                   ->click('.confirm-offer-acceptance-button')
                   ->assertSee('Congratulations! Offer accepted successfully!')
                   ->assertSee('Welcome to the Innovative Tech Solutions team!');

            // =======================================================================================
            // PHASE 7: EMPLOYER CONFIRMS HIRE AND CLOSES PROCESS
            // =======================================================================================

            // Step 19: Employer confirms hire
            $browser->visit(config('app.url') . '/login') // Employer session
                   ->waitFor('.login-form')
                   ->type('john.hiring@innovativetech.com')
                   ->type('password', 'password123')
                   ->click('.login-submit-button')
                   ->assertPathIs('/dashboard');

            $browser->visit('/employer/applications')
                   ->click('.job-application-card:first-child')
                   ->click('.confirm-hire-button')
                   ->waitFor('.confirm-hire-modal')
                   ->type('.hire-notes', 'Alex has been confirmed as hired for the Full Stack Software Developer position. HR will coordinate onboarding and paperwork.')
                   ->select('.reported-salary', '$115,000')
                   ->type('.hire-date', now()->addWeeks(6)->format('Y-m-d'))
                   ->check('.generate-offer-letter')
                   ->check('.setup-onboarding-calendar')
                   ->check('.send-welcome-email')
                   ->click('.confirm-hire-final-button')
                   ->assertSee('Hiring confirmed! Welcome Alex to the team.')
                   ->assertSee('Application closed successfully.');

            // =======================================================================================
            // PHASE 8: FINAL VERIFICATION AND COMPLETION
            // =======================================================================================

            // Step 20: Verify complete application journey
            $browser->visit('/employer/recruiting-dashboard')
                   ->waitFor('.recruiting-metrics')
                   ->assertSee('1 - Applications Received')
                   ->assertSee('1 - Applications Reviewed')
                   ->assertSee('1 - Interviews Conducted')
                   ->assertSee('1 - Offers Extended')
                   ->assertSee('1 - Offers Accepted')
                   ->assertSee('1 - Successful Hires')
                   ->assertSee('Successful Hire Rate: 100%');

            // Step 21: Graduate celebrates and updates profile
            $browser->visit(config('app.url') . '/login') // Graduate session
                   ->waitFor('.login-form')
                   ->type('email', 'alex.dev@test-university.com')
                   ->type('password', 'password123')
                   ->click('.login-submit-button')
                   ->assertPathIs('/dashboard');

            // Update profile with new job
            $browser->visit('/profile')
                   ->click('.add-work-experience-button')
                   ->waitFor('.add-experience-modal')
                   ->type('.company-name', 'Innovative Tech Solutions')
                   ->type('.job-title', 'Full Stack Software Developer')
                   ->type('.start-date', now()->addWeeks(6)->format('Y-m-d'))
                   ->check('.is-current-position')
                   ->type('.job-description', 'Developing cutting-edge web applications serving millions of users. Working with React, Node.js, TypeScript, and AWS technologies.')
                   ->click('.save-experience-button')
                   ->assertSee('Work experience added successfully');

            // Share success story
            $browser->visit('/stories/create')
                   ->waitFor('.success-story-form')
                   ->select('.story-category', 'career-growth')
                   ->select('.story-type', 'job-acceptance')
                   ->type('.story-title', 'From Recent Graduate to Full Stack Developer!')
                   ->type('.story-content', 'I am excited to share that I have accepted an offer as a Full Stack Software Developer at Innovative Tech Solutions! This journey started when I was searching for opportunities and found this amazing company...');

            $browser->click('.publish-success-story-button')
                   ->assertSee('Success story published successfully!');

            // Job Application Lifecycle Complete! ✅
        });
    }
}