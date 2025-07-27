<?php

namespace Tests\UserAcceptance;

use App\Models\User;
use App\Models\Tenant;
use App\Models\Domain;
use App\Models\Graduate;
use App\Models\GraduateProfile;
use App\Models\Course;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\Employer;
use App\Models\Announcement;
use App\Models\Notification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

/**
 * Test Data Sets for User Acceptance Testing
 * 
 * This class provides comprehensive test data sets for all user roles
 * and scenarios in the Graduate Tracking System.
 */
class TestDataSets
{
    /**
     * Create comprehensive test data for all scenarios
     */
    public static function createAllTestData()
    {
        DB::beginTransaction();
        
        try {
            // Create roles and permissions first
            self::createRolesAndPermissions();
            
            // Create test institutions (tenants)
            $institutions = self::createTestInstitutions();
            
            // Create test users for each role
            $users = self::createTestUsers($institutions);
            
            // Create test courses
            $courses = self::createTestCourses($institutions);
            
            // Create test graduates
            $graduates = self::createTestGraduates($institutions, $courses);
            
            // Create test employers
            $employers = self::createTestEmployers();
            
            // Create test jobs
            $jobs = self::createTestJobs($employers, $courses);
            
            // Create test job applications
            $applications = self::createTestJobApplications($graduates, $jobs);
            
            // Create test announcements
            $announcements = self::createTestAnnouncements($institutions, $users);
            
            // Create test notifications
            $notifications = self::createTestNotifications($users);
            
            DB::commit();
            
            return [
                'institutions' => $institutions,
                'users' => $users,
                'courses' => $courses,
                'graduates' => $graduates,
                'employers' => $employers,
                'jobs' => $jobs,
                'applications' => $applications,
                'announcements' => $announcements,
                'notifications' => $notifications,
            ];
            
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Create roles and permissions for testing
     */
    public static function createRolesAndPermissions()
    {
        // Create roles
        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin']);
        $institutionAdminRole = Role::firstOrCreate(['name' => 'Institution Admin']);
        $employerRole = Role::firstOrCreate(['name' => 'Employer']);
        $graduateRole = Role::firstOrCreate(['name' => 'Graduate']);
        $tutorRole = Role::firstOrCreate(['name' => 'Tutor']);

        // Create permissions
        $permissions = [
            'manage_institutions',
            'manage_users',
            'manage_graduates',
            'manage_courses',
            'manage_jobs',
            'manage_employers',
            'view_analytics',
            'manage_announcements',
            'manage_notifications',
            'verify_employers',
            'export_data',
            'import_data',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Assign permissions to roles
        $superAdminRole->syncPermissions($permissions);
        $institutionAdminRole->syncPermissions([
            'manage_graduates',
            'manage_courses',
            'view_analytics',
            'manage_announcements',
            'export_data',
            'import_data',
        ]);

        return [
            'super_admin' => $superAdminRole,
            'institution_admin' => $institutionAdminRole,
            'employer' => $employerRole,
            'graduate' => $graduateRole,
            'tutor' => $tutorRole,
        ];
    }

    /**
     * Create test institutions (tenants)
     */
    public static function createTestInstitutions()
    {
        $institutions = [];

        // Primary test institution
        $institution1 = Tenant::create([
            'id' => 'test-university',
            'name' => 'Test University',
            'address' => '123 University Ave, Test City, TC 12345',
            'phone' => '+1-555-0123',
            'email' => 'admin@testuniversity.edu',
            'website' => 'https://testuniversity.edu',
            'established_year' => 1950,
            'type' => 'university',
            'status' => 'active',
            'settings' => json_encode([
                'timezone' => 'UTC',
                'currency' => 'USD',
                'language' => 'en',
            ]),
        ]);

        Domain::create([
            'domain' => 'test-university.localhost',
            'tenant_id' => $institution1->id,
        ]);

        $institutions['test_university'] = $institution1;

        // Secondary test institution
        $institution2 = Tenant::create([
            'id' => 'tech-college',
            'name' => 'Tech College',
            'address' => '456 Tech Blvd, Innovation City, IC 67890',
            'phone' => '+1-555-0456',
            'email' => 'info@techcollege.edu',
            'website' => 'https://techcollege.edu',
            'established_year' => 1985,
            'type' => 'college',
            'status' => 'active',
            'settings' => json_encode([
                'timezone' => 'UTC',
                'currency' => 'USD',
                'language' => 'en',
            ]),
        ]);

        Domain::create([
            'domain' => 'tech-college.localhost',
            'tenant_id' => $institution2->id,
        ]);

        $institutions['tech_college'] = $institution2;

        return $institutions;
    }

    /**
     * Create test users for all roles
     */
    public static function createTestUsers($institutions)
    {
        $users = [];

        // Super Admin (central system)
        $superAdmin = User::create([
            'name' => 'Super Administrator',
            'email' => 'superadmin@system.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => 'Super Admin',
            'status' => 'active',
        ]);
        $superAdmin->assignRole('Super Admin');
        $users['super_admin'] = $superAdmin;

        // Institution Admins
        foreach ($institutions as $key => $institution) {
            $admin = User::create([
                'name' => ucwords(str_replace('_', ' ', $key)) . ' Admin',
                'email' => "admin@{$key}.edu",
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'role' => 'Institution Admin',
                'status' => 'active',
                'tenant_id' => $institution->id,
            ]);
            $admin->assignRole('Institution Admin');
            $users["admin_{$key}"] = $admin;
        }

        // Test Employers
        $employer1 = User::create([
            'name' => 'Tech Corp HR Manager',
            'email' => 'hr@techcorp.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => 'Employer',
            'status' => 'active',
        ]);
        $employer1->assignRole('Employer');
        $users['employer_tech_corp'] = $employer1;

        $employer2 = User::create([
            'name' => 'StartupXYZ Recruiter',
            'email' => 'jobs@startupxyz.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => 'Employer',
            'status' => 'active',
        ]);
        $employer2->assignRole('Employer');
        $users['employer_startup'] = $employer2;

        // Test Graduates
        $graduateData = [
            [
                'name' => 'John Smith',
                'email' => 'john.smith@student.testuniversity.edu',
                'tenant' => 'test-university',
            ],
            [
                'name' => 'Jane Doe',
                'email' => 'jane.doe@student.testuniversity.edu',
                'tenant' => 'test-university',
            ],
            [
                'name' => 'Mike Johnson',
                'email' => 'mike.johnson@student.techcollege.edu',
                'tenant' => 'tech-college',
            ],
            [
                'name' => 'Sarah Wilson',
                'email' => 'sarah.wilson@student.techcollege.edu',
                'tenant' => 'tech-college',
            ],
        ];

        foreach ($graduateData as $index => $data) {
            $graduate = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'role' => 'Graduate',
                'status' => 'active',
                'tenant_id' => $data['tenant'],
            ]);
            $graduate->assignRole('Graduate');
            $users["graduate_" . ($index + 1)] = $graduate;
        }

        return $users;
    }

    /**
     * Create test courses
     */
    public static function createTestCourses($institutions)
    {
        $courses = [];

        // Courses for Test University
        $testUniversityCourses = [
            [
                'name' => 'Computer Science',
                'code' => 'CS101',
                'description' => 'Comprehensive computer science program covering programming, algorithms, and software engineering.',
                'duration_years' => 4,
                'level' => 'bachelor',
                'department' => 'Computer Science',
                'skills' => json_encode(['Programming', 'Algorithms', 'Software Engineering', 'Database Design']),
                'career_paths' => json_encode(['Software Developer', 'Systems Analyst', 'Database Administrator']),
            ],
            [
                'name' => 'Business Administration',
                'code' => 'BA201',
                'description' => 'Business administration program focusing on management, finance, and marketing.',
                'duration_years' => 4,
                'level' => 'bachelor',
                'department' => 'Business',
                'skills' => json_encode(['Management', 'Finance', 'Marketing', 'Strategic Planning']),
                'career_paths' => json_encode(['Business Manager', 'Financial Analyst', 'Marketing Specialist']),
            ],
        ];

        foreach ($testUniversityCourses as $courseData) {
            $courseData['tenant_id'] = $institutions['test_university']->id;
            $courseData['created_at'] = now();
            $courseData['updated_at'] = now();
            $course = Course::create($courseData);
            $courses['test_university'][] = $course;
        }

        // Courses for Tech College
        $techCollegeCourses = [
            [
                'name' => 'Web Development',
                'code' => 'WD301',
                'description' => 'Intensive web development program covering frontend and backend technologies.',
                'duration_years' => 2,
                'level' => 'diploma',
                'department' => 'Information Technology',
                'skills' => json_encode(['HTML/CSS', 'JavaScript', 'PHP', 'React', 'Node.js']),
                'career_paths' => json_encode(['Web Developer', 'Frontend Developer', 'Full Stack Developer']),
            ],
            [
                'name' => 'Digital Marketing',
                'code' => 'DM401',
                'description' => 'Digital marketing program focusing on online marketing strategies and tools.',
                'duration_years' => 2,
                'level' => 'diploma',
                'department' => 'Marketing',
                'skills' => json_encode(['SEO', 'Social Media Marketing', 'Google Analytics', 'Content Marketing']),
                'career_paths' => json_encode(['Digital Marketing Specialist', 'SEO Analyst', 'Social Media Manager']),
            ],
        ];

        foreach ($techCollegeCourses as $courseData) {
            $courseData['tenant_id'] = $institutions['tech_college']->id;
            $courseData['created_at'] = now();
            $courseData['updated_at'] = now();
            $course = Course::create($courseData);
            $courses['tech_college'][] = $course;
        }

        return $courses;
    }

    /**
     * Create test graduates with profiles
     */
    public static function createTestGraduates($institutions, $courses)
    {
        $graduates = [];

        // Graduate data with varying completion levels and employment status
        $graduateData = [
            [
                'student_id' => 'TU2020001',
                'first_name' => 'John',
                'last_name' => 'Smith',
                'email' => 'john.smith@student.testuniversity.edu',
                'phone' => '+1-555-1001',
                'date_of_birth' => '1998-05-15',
                'gender' => 'male',
                'graduation_year' => 2022,
                'employment_status' => 'employed',
                'current_job_title' => 'Software Developer',
                'current_employer' => 'Tech Solutions Inc.',
                'current_salary' => 75000,
                'tenant' => 'test_university',
                'course_index' => 0, // Computer Science
            ],
            [
                'student_id' => 'TU2020002',
                'first_name' => 'Jane',
                'last_name' => 'Doe',
                'email' => 'jane.doe@student.testuniversity.edu',
                'phone' => '+1-555-1002',
                'date_of_birth' => '1999-08-22',
                'gender' => 'female',
                'graduation_year' => 2023,
                'employment_status' => 'seeking',
                'tenant' => 'test_university',
                'course_index' => 1, // Business Administration
            ],
            [
                'student_id' => 'TC2021001',
                'first_name' => 'Mike',
                'last_name' => 'Johnson',
                'email' => 'mike.johnson@student.techcollege.edu',
                'phone' => '+1-555-2001',
                'date_of_birth' => '2000-03-10',
                'gender' => 'male',
                'graduation_year' => 2023,
                'employment_status' => 'employed',
                'current_job_title' => 'Web Developer',
                'current_employer' => 'Digital Agency Pro',
                'current_salary' => 55000,
                'tenant' => 'tech_college',
                'course_index' => 0, // Web Development
            ],
            [
                'student_id' => 'TC2021002',
                'first_name' => 'Sarah',
                'last_name' => 'Wilson',
                'email' => 'sarah.wilson@student.techcollege.edu',
                'phone' => '+1-555-2002',
                'date_of_birth' => '1999-11-05',
                'gender' => 'female',
                'graduation_year' => 2023,
                'employment_status' => 'self_employed',
                'current_job_title' => 'Freelance Digital Marketer',
                'current_salary' => 45000,
                'tenant' => 'tech_college',
                'course_index' => 1, // Digital Marketing
            ],
        ];

        foreach ($graduateData as $data) {
            $tenantKey = $data['tenant'];
            $tenant = $institutions[$tenantKey];
            $course = $courses[$tenantKey][$data['course_index']];

            // Create graduate record
            $graduate = Graduate::create([
                'student_id' => $data['student_id'],
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'date_of_birth' => $data['date_of_birth'],
                'gender' => $data['gender'],
                'course_id' => $course->id,
                'graduation_year' => $data['graduation_year'],
                'employment_status' => $data['employment_status'],
                'current_job_title' => $data['current_job_title'] ?? null,
                'current_employer' => $data['current_employer'] ?? null,
                'current_salary' => $data['current_salary'] ?? null,
                'tenant_id' => $tenant->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Create graduate profile
            $profile = GraduateProfile::create([
                'graduate_id' => $graduate->id,
                'bio' => "I am a {$data['graduation_year']} graduate with a passion for {$course->name}.",
                'skills' => json_encode(json_decode($course->skills)[0] ?? []),
                'certifications' => json_encode([]),
                'languages' => json_encode(['English']),
                'interests' => json_encode(['Technology', 'Innovation']),
                'linkedin_url' => "https://linkedin.com/in/{$data['first_name']}.{$data['last_name']}",
                'github_url' => $course->name === 'Computer Science' || $course->name === 'Web Development' 
                    ? "https://github.com/{$data['first_name']}{$data['last_name']}" : null,
                'portfolio_url' => "https://{$data['first_name']}{$data['last_name']}.com",
                'availability_status' => $data['employment_status'] === 'seeking' ? 'available' : 'not_available',
                'preferred_job_types' => json_encode(['full_time']),
                'preferred_locations' => json_encode(['Remote', 'New York', 'San Francisco']),
                'expected_salary_min' => 50000,
                'expected_salary_max' => 80000,
                'profile_visibility' => 'public',
                'contact_preferences' => json_encode(['email', 'linkedin']),
                'profile_completion' => 85,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $graduates[] = [
                'graduate' => $graduate,
                'profile' => $profile,
            ];
        }

        return $graduates;
    }

    /**
     * Create test employers
     */
    public static function createTestEmployers()
    {
        $employers = [];

        $employerData = [
            [
                'company_name' => 'Tech Solutions Inc.',
                'company_description' => 'Leading technology solutions provider specializing in software development and IT consulting.',
                'industry' => 'Technology',
                'company_size' => '100-500',
                'website' => 'https://techsolutions.com',
                'address' => '789 Tech Park, Silicon Valley, CA 94000',
                'contact_person' => 'HR Manager',
                'contact_email' => 'hr@techsolutions.com',
                'contact_phone' => '+1-555-3001',
                'verification_status' => 'verified',
                'verification_documents' => json_encode(['business_license.pdf', 'tax_certificate.pdf']),
            ],
            [
                'company_name' => 'StartupXYZ',
                'company_description' => 'Innovative startup focused on digital transformation and emerging technologies.',
                'industry' => 'Technology',
                'company_size' => '10-50',
                'website' => 'https://startupxyz.com',
                'address' => '123 Innovation St, Austin, TX 78701',
                'contact_person' => 'Recruiter',
                'contact_email' => 'jobs@startupxyz.com',
                'contact_phone' => '+1-555-3002',
                'verification_status' => 'pending',
                'verification_documents' => json_encode(['business_registration.pdf']),
            ],
            [
                'company_name' => 'Digital Agency Pro',
                'company_description' => 'Full-service digital agency providing web development and marketing services.',
                'industry' => 'Marketing',
                'company_size' => '50-100',
                'website' => 'https://digitalagencypro.com',
                'address' => '456 Creative Blvd, Los Angeles, CA 90210',
                'contact_person' => 'Talent Acquisition',
                'contact_email' => 'careers@digitalagencypro.com',
                'contact_phone' => '+1-555-3003',
                'verification_status' => 'verified',
                'verification_documents' => json_encode(['business_license.pdf', 'insurance_certificate.pdf']),
            ],
        ];

        foreach ($employerData as $data) {
            $employer = Employer::create(array_merge($data, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
            $employers[] = $employer;
        }

        return $employers;
    }

    /**
     * Create test jobs
     */
    public static function createTestJobs($employers, $courses)
    {
        $jobs = [];

        $jobData = [
            [
                'title' => 'Junior Software Developer',
                'description' => 'We are looking for a motivated junior software developer to join our growing team.',
                'requirements' => 'Bachelor\'s degree in Computer Science or related field. Knowledge of programming languages such as Java, Python, or C++.',
                'responsibilities' => 'Develop and maintain software applications. Collaborate with senior developers. Participate in code reviews.',
                'employment_type' => 'full_time',
                'experience_level' => 'entry',
                'salary_min' => 60000,
                'salary_max' => 80000,
                'location' => 'San Francisco, CA',
                'remote_work' => true,
                'benefits' => json_encode(['Health Insurance', 'Dental Insurance', '401k', 'Flexible Hours']),
                'skills_required' => json_encode(['Programming', 'Java', 'Python', 'Problem Solving']),
                'application_deadline' => now()->addDays(30),
                'status' => 'active',
                'employer_index' => 0, // Tech Solutions Inc.
            ],
            [
                'title' => 'Frontend Developer',
                'description' => 'Join our startup as a frontend developer and help build amazing user experiences.',
                'requirements' => 'Experience with React, JavaScript, HTML, CSS. Portfolio of web development projects.',
                'responsibilities' => 'Build responsive web applications. Collaborate with designers. Optimize for performance.',
                'employment_type' => 'full_time',
                'experience_level' => 'mid',
                'salary_min' => 70000,
                'salary_max' => 90000,
                'location' => 'Austin, TX',
                'remote_work' => true,
                'benefits' => json_encode(['Health Insurance', 'Stock Options', 'Flexible PTO']),
                'skills_required' => json_encode(['React', 'JavaScript', 'HTML/CSS', 'UI/UX']),
                'application_deadline' => now()->addDays(45),
                'status' => 'active',
                'employer_index' => 1, // StartupXYZ
            ],
            [
                'title' => 'Digital Marketing Specialist',
                'description' => 'We need a creative digital marketing specialist to drive our online presence.',
                'requirements' => 'Degree in Marketing or related field. Experience with SEO, social media, and analytics.',
                'responsibilities' => 'Manage social media campaigns. Optimize SEO. Analyze marketing metrics.',
                'employment_type' => 'full_time',
                'experience_level' => 'entry',
                'salary_min' => 45000,
                'salary_max' => 65000,
                'location' => 'Los Angeles, CA',
                'remote_work' => false,
                'benefits' => json_encode(['Health Insurance', 'Professional Development', 'Creative Environment']),
                'skills_required' => json_encode(['SEO', 'Social Media Marketing', 'Google Analytics', 'Content Creation']),
                'application_deadline' => now()->addDays(20),
                'status' => 'active',
                'employer_index' => 2, // Digital Agency Pro
            ],
        ];

        foreach ($jobData as $data) {
            $employer = $employers[$data['employer_index']];
            unset($data['employer_index']);

            $job = Job::create(array_merge($data, [
                'employer_id' => $employer->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
            $jobs[] = $job;
        }

        return $jobs;
    }

    /**
     * Create test job applications
     */
    public static function createTestJobApplications($graduates, $jobs)
    {
        $applications = [];

        // Create various application scenarios
        $applicationData = [
            [
                'graduate_index' => 0, // John Smith
                'job_index' => 0, // Junior Software Developer
                'status' => 'hired',
                'cover_letter' => 'I am excited to apply for the Junior Software Developer position...',
                'applied_at' => now()->subDays(15),
                'interview_date' => now()->subDays(5),
                'notes' => 'Excellent technical skills and great cultural fit.',
            ],
            [
                'graduate_index' => 1, // Jane Doe
                'job_index' => 0, // Junior Software Developer
                'status' => 'rejected',
                'cover_letter' => 'I would like to be considered for the Software Developer role...',
                'applied_at' => now()->subDays(10),
                'notes' => 'Good candidate but looking for more technical experience.',
            ],
            [
                'graduate_index' => 2, // Mike Johnson
                'job_index' => 1, // Frontend Developer
                'status' => 'interview',
                'cover_letter' => 'As a web development graduate, I am passionate about frontend technologies...',
                'applied_at' => now()->subDays(7),
                'interview_date' => now()->addDays(2),
                'notes' => 'Strong portfolio, scheduled for technical interview.',
            ],
            [
                'graduate_index' => 3, // Sarah Wilson
                'job_index' => 2, // Digital Marketing Specialist
                'status' => 'pending',
                'cover_letter' => 'I am interested in the Digital Marketing Specialist position...',
                'applied_at' => now()->subDays(3),
                'notes' => 'Application under review.',
            ],
        ];

        foreach ($applicationData as $data) {
            $graduate = $graduates[$data['graduate_index']]['graduate'];
            $job = $jobs[$data['job_index']];

            $application = JobApplication::create([
                'graduate_id' => $graduate->id,
                'job_id' => $job->id,
                'status' => $data['status'],
                'cover_letter' => $data['cover_letter'],
                'resume_path' => "resumes/{$graduate->first_name}_{$graduate->last_name}_resume.pdf",
                'applied_at' => $data['applied_at'],
                'interview_date' => $data['interview_date'] ?? null,
                'notes' => $data['notes'],
                'created_at' => $data['applied_at'],
                'updated_at' => now(),
            ]);

            $applications[] = $application;
        }

        return $applications;
    }

    /**
     * Create test announcements
     */
    public static function createTestAnnouncements($institutions, $users)
    {
        $announcements = [];

        $announcementData = [
            [
                'title' => 'System Maintenance Scheduled',
                'content' => 'The system will undergo maintenance on Sunday from 2 AM to 6 AM EST.',
                'type' => 'maintenance',
                'scope' => 'all',
                'priority' => 'high',
                'is_published' => true,
                'published_at' => now()->subDays(2),
                'expires_at' => now()->addDays(5),
                'created_by' => $users['super_admin']->id,
                'tenant_id' => null, // System-wide
            ],
            [
                'title' => 'New Job Opportunities Available',
                'content' => 'Several new job opportunities have been posted by verified employers.',
                'type' => 'general',
                'scope' => 'graduates',
                'priority' => 'normal',
                'is_published' => true,
                'published_at' => now()->subDays(1),
                'expires_at' => now()->addDays(14),
                'created_by' => $users['admin_test_university']->id,
                'tenant_id' => $institutions['test_university']->id,
            ],
            [
                'title' => 'Career Fair Next Month',
                'content' => 'Join us for our annual career fair featuring top employers from the tech industry.',
                'type' => 'event',
                'scope' => 'graduates',
                'priority' => 'high',
                'is_published' => true,
                'is_pinned' => true,
                'published_at' => now(),
                'expires_at' => now()->addDays(30),
                'created_by' => $users['admin_tech_college']->id,
                'tenant_id' => $institutions['tech_college']->id,
            ],
        ];

        foreach ($announcementData as $data) {
            $announcement = Announcement::create(array_merge($data, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
            $announcements[] = $announcement;
        }

        return $announcements;
    }

    /**
     * Create test notifications
     */
    public static function createTestNotifications($users)
    {
        $notifications = [];

        $notificationData = [
            [
                'user_id' => $users['graduate_1']->id,
                'title' => 'Application Status Update',
                'message' => 'Your application for Junior Software Developer has been updated to "Hired".',
                'type' => 'application_update',
                'data' => json_encode(['job_title' => 'Junior Software Developer', 'status' => 'hired']),
                'read_at' => null,
            ],
            [
                'user_id' => $users['graduate_2']->id,
                'title' => 'New Job Match',
                'message' => 'A new job matching your profile has been posted: Business Analyst.',
                'type' => 'job_match',
                'data' => json_encode(['job_title' => 'Business Analyst', 'company' => 'Business Corp']),
                'read_at' => null,
            ],
            [
                'user_id' => $users['employer_tech_corp']->id,
                'title' => 'New Application Received',
                'message' => 'You have received a new application for Junior Software Developer position.',
                'type' => 'new_application',
                'data' => json_encode(['job_title' => 'Junior Software Developer', 'applicant' => 'John Smith']),
                'read_at' => now()->subHours(2),
            ],
        ];

        foreach ($notificationData as $data) {
            $notification = Notification::create(array_merge($data, [
                'created_at' => now()->subHours(rand(1, 24)),
                'updated_at' => now(),
            ]));
            $notifications[] = $notification;
        }

        return $notifications;
    }

    /**
     * Create performance testing data (large datasets)
     */
    public static function createPerformanceTestData($count = 1000)
    {
        DB::beginTransaction();
        
        try {
            $institutions = self::createTestInstitutions();
            $courses = self::createTestCourses($institutions);
            
            // Create large number of graduates
            $graduates = [];
            for ($i = 1; $i <= $count; $i++) {
                $tenant = array_rand($institutions);
                $course = $courses[$tenant][array_rand($courses[$tenant])];
                
                $graduate = Graduate::create([
                    'student_id' => "PERF{$i}",
                    'first_name' => "TestGrad{$i}",
                    'last_name' => "LastName{$i}",
                    'email' => "testgrad{$i}@test.edu",
                    'phone' => "+1-555-" . str_pad($i, 4, '0', STR_PAD_LEFT),
                    'date_of_birth' => now()->subYears(rand(22, 30))->format('Y-m-d'),
                    'gender' => ['male', 'female'][rand(0, 1)],
                    'course_id' => $course->id,
                    'graduation_year' => rand(2020, 2024),
                    'employment_status' => ['employed', 'seeking', 'self_employed'][rand(0, 2)],
                    'tenant_id' => $institutions[$tenant]->id,
                ]);
                
                $graduates[] = $graduate;
            }
            
            DB::commit();
            return $graduates;
            
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Clean up all test data
     */
    public static function cleanupTestData()
    {
        DB::beginTransaction();
        
        try {
            // Delete in reverse order of creation to respect foreign key constraints
            Notification::where('title', 'LIKE', '%Test%')->delete();
            Announcement::where('title', 'LIKE', '%Test%')->delete();
            JobApplication::whereHas('graduate', function($query) {
                $query->where('email', 'LIKE', '%test%');
            })->delete();
            Job::whereHas('employer', function($query) {
                $query->where('company_name', 'LIKE', '%Test%');
            })->delete();
            Employer::where('company_name', 'LIKE', '%Test%')->delete();
            GraduateProfile::whereHas('graduate', function($query) {
                $query->where('email', 'LIKE', '%test%');
            })->delete();
            Graduate::where('email', 'LIKE', '%test%')->delete();
            Course::where('name', 'LIKE', '%Test%')->delete();
            User::where('email', 'LIKE', '%test%')->delete();
            Domain::where('domain', 'LIKE', '%.localhost')->delete();
            Tenant::where('name', 'LIKE', '%Test%')->delete();
            
            DB::commit();
            
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }
}