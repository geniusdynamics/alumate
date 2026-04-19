# Alumate — Project Analysis: Issues & Remediation Plan

> **Prepared:** Full codebase review covering routing, backend controllers, frontend components, database, architecture, security, and UX.
> **Scope:** `app/`, `resources/js/`, `routes/`, `database/migrations/`, root-level files.

---

## Executive Summary

The project has **substantial infrastructure and UI scaffolding** in place (multi-tenant architecture, role-based access, analytics, social features, PWA, real-time updates, etc.) but suffers from a large number of critical, high, and medium severity issues that prevent it from being usable in its current state. The most acute problem is that **the sidebar navigation references 40+ named routes that do not exist**, which means the authenticated application UI cannot function at all for any user role. Beyond routing, there are security exposures, broken controllers, architectural inconsistencies, and unimplemented stubs throughout.

---

## Severity Legend

| Symbol | Severity | Meaning |
|--------|----------|---------|
| 🔴 | **Critical** | Application crashes or is completely unusable |
| 🟠 | **High** | A major feature is broken or a security risk exists |
| 🟡 | **Medium** | Degraded UX, incorrect behavior, or technical debt |
| 🔵 | **Low** | Code quality, consistency, or minor UX polish |

---

## 🔴 Critical Issues

---

### C-01 — Navigation Module References 40+ Non-Existent Routes (App-Breaking)

**File:** `resources/js/lib/navigation.ts`

The `graduateMenuItems`, `employerMenuItems`, `institutionAdminMenuItems`, and `superAdminMenuItems` arrays — which power the entire authenticated sidebar — call `route()` (Ziggy) for named routes that are **not registered in any route file**. Because these are evaluated at module initialization time (not lazily), any missing route causes a Ziggy exception that crashes the sidebar component import entirely.

**Missing routes by role:**

*Graduate sidebar:*
- `graduate.applications`
- `jobs.dashboard`
- `career.timeline`
- `career.mentorship-hub`
- `social.timeline`
- `alumni.directory`
- `events.discovery`
- `scholarships.index`
- `stories.index`
- `achievements.index`
- `education.index`
- `assistance.index`

*Employer sidebar:*
- `jobs.dashboard`
- `graduates.search`
- `employer.applications`
- `employer.graduates.search`
- `employer.communications`
- `employer.profile`
- `employer.analytics`

*Institution Admin sidebar:*
- `graduates.index`
- `courses.index`
- `tutors.index`
- `jobs.public.index`
- `companies.index`
- `merge.index`
- `users.index`
- `roles.index`
- `campaigns.index`
- `institution-admin.analytics`
- `institution-admin.analytics.course-roi`
- `institution-admin.analytics.employer-engagement`
- `institution-admin.analytics.community-health`
- `institution-admin.settings.branding`
- `institution-admin.settings.integrations`
- `institution.edit`

*Super Admin sidebar:*
- `institutions.index`
- `super-admin.content`
- `super-admin.activity`
- `super-admin.database`
- `super-admin.performance`
- `super-admin.notifications`
- `super-admin.settings`
- `super-admins.index`
- `security.dashboard`

**Fix required:**
All missing routes must be registered in `routes/web.php` (or an appropriate sub-file) and connected to their controllers. The controllers (`SuperAdminDashboardController::content()`, `::activity()`, `::database()`, `::performance()`, `::notifications()`, `::settings()`, and all institution-admin equivalents) **already exist** — they just have no routes pointing to them.

---

### C-02 — `GraduateDashboardController` Redirects to a Non-Existent Route

**File:** `app/Http/Controllers/GraduateDashboardController.php`

Every method in this controller redirects to `graduates.create` when the user has no tenant context or graduate record:

```php
return redirect()->route('graduates.create')
    ->with('error', 'Please select your institution first.');
```

The route `graduates.create` **does not exist** in any route file. This throws a `RouteNotFoundException`, resulting in a 500 error for every graduate user who does not have a fully configured tenant.

**Fix required:**
Either register the `graduates.create` route pointing to a graduate onboarding/profile-creation page, or redirect to an existing route such as `settings.profile` or a new dedicated institution-selection page.

---

### C-03 — Employer Registration Broken on Main Register Page

**File:** `app/Http/Controllers/Auth/RegisteredUserController.php` + `resources/js/Pages/Auth/Register.vue`

The `Register.vue` UI clearly presents three selectable roles: **Graduate**, **Employer**, and **Institution**. When a user selects "Employer" and submits, the server-side validation immediately rejects it:

```php
'role' => 'required|string|in:graduate,institution', // employer is not accepted
```

An employer user selecting their role receives a validation error with no explanation. Employers must discover the separate `/employer/register` route on their own, but no link to it is shown on the main register page.

**Fix required:**
Either add `employer` to the validation rule and handle employer registration in `RegisteredUserController`, or add a visible link to `/employer/register` when the user selects the employer role in the UI.

---

### C-04 — Institution Admin Has Only One Registered Route

**File:** `routes/web.php` (lines 154–158)

The entire Institution Admin section has only one registered web route:

```php
Route::get('/dashboard', [InstitutionAdminDashboardController::class, 'index'])->name('dashboard');
```

Yet the sidebar and the `InstitutionAdminDashboardController` contain full implementations for analytics, course ROI, employer engagement, community health, branding, integrations, and institution settings. None of those have routes. Every sidebar link for institution admins (except Dashboard) would either crash on route generation or 404 at runtime.

**Fix required:**
Register all remaining institution-admin routes, e.g.:

```php
Route::get('/analytics', [InstitutionAdminDashboardController::class, 'analytics'])->name('analytics');
Route::get('/analytics/course-roi', [InstitutionAdminDashboardController::class, 'courseRoi'])->name('analytics.course-roi');
Route::get('/analytics/employer-engagement', [InstitutionAdminDashboardController::class, 'employerEngagement'])->name('analytics.employer-engagement');
Route::get('/analytics/community-health', [InstitutionAdminDashboardController::class, 'communityHealth'])->name('analytics.community-health');
// settings routes, etc.
```

---

### C-05 — Super Admin Has 7 Unregistered Routes

**File:** `routes/web.php` (lines 143–153)

Only 7 super-admin routes are registered (`dashboard`, `analytics`, `institutions`, `users`, `employer-verification`, `reports`, `system-health`). The sidebar references 9 additional pages. The `SuperAdminDashboardController` has full implementations for `content()`, `activity()`, `database()`, `performance()`, `notifications()`, and `settings()` — none are routed.

**Fix required:**
Add the missing routes inside the existing `super-admin` route group.

---

## 🟠 High Severity Issues

---

### H-01 — Debug Panel Exposes Form Data on the Login Page

**File:** `resources/js/Pages/Auth/Login.vue` (lines 118–125)

A "Debug Info" block is rendered directly inside the login form, visible to every user:

```html
<!-- Debug Info -->
<div style="...">
    <strong>Debug Info:</strong><br />
    Email: {{ form.email || 'empty' }}<br />
    Password: {{ form.password ? '***' : 'empty' }}<br />
    Processing: {{ form.processing }}<br />
    Errors: {{ Object.keys(form.errors).length > 0 ? JSON.stringify(form.errors) : 'none' }}
</div>
```

This is a security and professionalism concern. It must be removed entirely before any real user sees the page.

**Fix required:** Delete the debug `<div>` block from the template.

---

### H-02 — Login Page `console.log()` Leaks Credentials to Browser Console

**File:** `resources/js/Pages/Auth/Login.vue` (script section)

```ts
const submit = () => {
    console.log('Form submitted:', form.data()); // logs { email, password, remember }
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
```

The user's submitted email and password are logged to the browser console on every login attempt. This is a critical security exposure.

**Fix required:** Remove the `console.log` statement entirely.

---

### H-03 — Login Page Uses Inline Styles Instead of Tailwind CSS

**File:** `resources/js/Pages/Auth/Login.vue`

The entire Login page is built with raw `style=""` attributes (hardcoded hex colors, pixel values) instead of Tailwind CSS classes used everywhere else. This means:

1. The page does not support **dark mode** (hardcoded `background-color: #f3f4f6`).
2. It does not respect the design system or brand colors.
3. It is inconsistent with every other authenticated page.
4. Responsive behavior is not handled properly.

**Fix required:** Rewrite the Login page template using Tailwind CSS classes consistent with `Register.vue` and the project's `AuthLayout.vue`.

---

### H-04 — `alert()` Used Throughout Instead of Toast Notifications

**Files:** Multiple components including `AddCareerModal.vue`, `CareerTimeline.vue`, `CoffeeChatRequestModal.vue`, `ApplicationModal.vue`, `BecomeMentorForm.vue`, `AlumniSuggestionsWidget.vue`, `CalendarSync.vue`, `Analytics/CustomEventManager.vue`, `EmailMarketing/CampaignBuilder.vue`, and more.

The application has `vue-toastification` installed and initialized in `app.ts`, but dozens of components call native browser `alert()` for user feedback — blocking, unstyled, and visually jarring:

```ts
alert('Coffee chat request sent successfully!');
alert('File size must be less than 5MB');
alert(`Applying: ${suggestion.action}`);
alert(`${provider} connection would open OAuth flow here`);
```

**Fix required:** Replace all `alert()` calls with `useToast()` from `vue-toastification`. Success messages should use `toast.success()`, errors should use `toast.error()`.

---

### H-05 — Broken Job Analytics: Summing Auto-Increment IDs Instead of Counts

**File:** `app/Http/Controllers/JobController.php` (lines 65–68)

```php
'total_applications' => $employer->jobs()->withSum('applications', 'id')->get()->sum('applications_sum_id') ?? 0,
'avg_applications_per_job' => $employer->jobs()->withAvg('applications', 'id')->get()->avg('applications_avg_id') ?? 0,
```

`withSum('applications', 'id')` sums the `id` column (auto-increment primary key) of the applications table — not the count. The numbers returned are meaningless (e.g., sum of IDs 1+5+7 = 13, not "3 applications"). Additionally, this executes two separate queries loading all jobs into memory.

**Fix required:**

```php
'total_applications' => $employer->jobs()->withCount('applications')->get()->sum('applications_count'),
'avg_applications_per_job' => round($employer->jobs()->withCount('applications')->get()->avg('applications_count') ?? 0, 1),
```

---

### H-06 — Multiple Redundant COUNT Queries in Job Analytics (N+1 Pattern)

**File:** `app/Http/Controllers/JobController.php` (lines 59–78)

The analytics block makes **8 separate database queries** that could be collapsed into one or two:

```php
'total_jobs'       => Job::where('employer_id', $employer->id)->count(),
'active_jobs'      => Job::where('employer_id', $employer->id)->where('status', 'active')->count(),
'pending_jobs'     => Job::where('employer_id', $employer->id)->where('status', 'pending_approval')->count(),
'expired_jobs'     => Job::where('employer_id', $employer->id)->where('status', 'expired')->count(),
'filled_jobs'      => Job::where('employer_id', $employer->id)->where('status', 'filled')->count(),
// ...
```

**Fix required:** Use a single grouped query:

```php
$statusCounts = Job::where('employer_id', $employer->id)
    ->selectRaw('status, COUNT(*) as count')
    ->groupBy('status')
    ->pluck('count', 'status');
```

---

### H-07 — Observer Infinite Loops Disabled — Audit Trail Non-Functional

**File:** `app/Providers/AppServiceProvider.php`

```php
// \App\Models\User::observe(\App\Observers\UserObserver::class); // Temporarily disabled - causes infinite loop
// \App\Models\EducationHistory::observe(\App\Observers\EducationHistoryObserver::class); // Temporarily disabled - causes infinite loop
```

User and EducationHistory observers are commented out permanently due to an infinite loop bug, meaning all audit logging and event tracking for these models is silently not working.

**Fix required:** Diagnose the infinite loop (likely the observer itself triggers a model event, which re-fires the observer). Use `User::withoutEvents()` inside the observer when making updates, or use `updateQuietly()`.

---

### H-08 — Tenancy Provider Query Logging and Error Handling Disabled

**File:** `app/Providers/TenancyServiceProvider.php`

```php
// $this->setupQueryLogging(); // Temporarily disabled to fix circular dependency
// $this->setupErrorHandling(); // Temporarily disabled to fix circular dependency
```

Two important bootstrapping methods are disabled. Query logging and custom error handling with tenant context are inactive.

**Fix required:** Resolve the circular dependency (likely by using `app()->make()` lazily inside the closures rather than injecting at construction time) and re-enable both methods.

---

### H-09 — Employer Auto-Created With Placeholder Data, No Onboarding

**File:** `app/Http/Controllers/EmployerDashboardController.php` (lines 22–31)

```php
$employer = \App\Models\Employer::create([
    'company_name' => 'Your Company',
    'industry' => 'Technology',
    'company_size' => 'small',
    'verification_status' => 'pending',
    'approved' => false,
    'contact_person_name' => $user->name,
    'contact_person_email' => $user->email,
]);
```

When an employer logs in without a profile, one is silently created with dummy data. The employer then sees "Your Company" displayed throughout their dashboard with no prompt to complete their profile.

**Fix required:** Redirect to an employer onboarding/profile-completion page instead of auto-creating a fake record.

---

### H-10 — Tenant Route File is Still Default Placeholder

**File:** `routes/tenant.php`

The entire tenant-specific routing is the default Stancl Tenancy boilerplate:

```php
Route::get('/', function () {
    return 'This is your multi-tenant application. The id of the current tenant is ' . tenant('id');
});
```

If the application is accessed via a tenant domain/subdomain, this plain string is returned instead of the actual application.

**Fix required:** Implement proper tenant routing (or clarify whether the app uses path/header-based tenancy exclusively and this file is intentionally unused, then document that).

---

### H-11 — Graduate Dashboard: Dead Code Null Check After `create()`

**File:** `app/Http/Controllers/GraduateDashboardController.php` (lines ~83–104)

```php
// Create graduate record if it doesn't exist
$graduate = Graduate::create([...]);

// ... later ...

if (!$graduate) {
    return redirect()->route('graduates.create') // unreachable - create() never returns null
        ->with('error', 'Unable to access graduate profile.');
}
```

`Eloquent::create()` either returns the created model or throws an exception — it never returns `null`. The second null check at line ~101 is dead code. Real failures (e.g., DB constraint violations) would manifest as unhandled exceptions instead of a user-friendly redirect.

**Fix required:** Wrap the `create()` call in a `try/catch` to handle real failure scenarios.

---

## 🟡 Medium Severity Issues

---

### M-01 — Three Conflicting Admin Layout Components

**Files:**
- `resources/js/Layouts/AdminLayout.vue` — plain HTML/CSS, hardcoded nav links, uses custom CSS classes
- `resources/js/Components/AdminLayout.vue` — dark-themed, uses Heroicons, `NavLink` component, has sidebar + topbar
- `resources/js/Layouts/AdminLayout_fixed.vue` — yet another variant

SuperAdmin pages (`SuperAdmin/Dashboard.vue`, `SuperAdmin/Analytics.vue`, etc.) import from `@/Components/AdminLayout.vue`. Institution Admin pages (`InstitutionAdmin/Analytics/*.vue`, `InstitutionAdmin/Settings/*.vue`) import from `@/Layouts/AdminLayout.vue`. These render completely different layouts.

**Fix required:** Consolidate to a single `AdminLayout.vue` in `resources/js/Layouts/`. Delete the other two or at minimum rename them clearly as deprecated.

---

### M-02 — `AppLayout.vue` Is an Unnecessary Wrapper

**File:** `resources/js/Layouts/AppLayout.vue`

This layout just proxies to `DefaultLayout.vue` with no added logic:

```ts
// AppLayout.vue
<DefaultLayout :title="title" :breadcrumbs="breadcrumbs">
    <slot />
</DefaultLayout>
```

Some pages use `AppLayout`, others use `DefaultLayout` directly. The indirection adds confusion without value.

**Fix required:** Remove `AppLayout.vue` and update all importing pages to use `DefaultLayout.vue` directly. Or give `AppLayout` a distinct purpose and document it.

---

### M-03 — Dashboard Pages Have No TypeScript Prop Types

**Files:** `Pages/Dashboard/Graduate.vue`, `Pages/Dashboard/Employer.vue`, `Pages/Dashboard/InstitutionAdmin.vue`, `Pages/Dashboard/SuperAdmin.vue`

All four dashboard pages define props with untyped `Object`:

```ts
defineProps({
    graduate: Object,
    statistics: Object,
    recentActivities: Object,
    jobRecommendations: Array,
});
```

This disables TypeScript checking for all dashboard data. Accessing `props.statistics.profile_completion` (as done in these files) has no type safety.

**Fix required:** Define TypeScript interfaces for each dashboard's props and data shapes.

---

### M-04 — `console.log()` Left in Production

**Files:** Widespread across components (40+ occurrences found)

Notable examples include components logging sensitive or internal state in production bundles:

- `Analytics/CustomEventManager.vue` — `console.log('Applying optimization:', suggestion)` followed by `alert()`
- `Calendar/CalendarSync.vue` — `console.log('Connecting to ${provider}...')`
- `Components/Developer/SdkGenerator.vue` — `console.log('Downloading SDK:', filename)`
- `DefaultLayout.vue` — `console.log('PWA Ready:', status)`, `console.log('App is offline')`, etc.

**Fix required:** Remove all `console.log` calls from production code. Use a proper logging utility that is no-op in production (e.g., a `logger` wrapper that checks `import.meta.env.DEV`).

---

### M-05 — Hard-Coded Placeholder Email Addresses

**File:** `app/Http/Controllers/Api/FormController.php` (lines ~821–831)

```php
$routingMap = [
    'technical_support' => ['support@company.com', 'tech@company.com'],
    'sales'             => ['sales@company.com'],
    'demo_request'      => ['sales@company.com', 'demos@company.com'],
    'partnership'       => ['partnerships@company.com'],
    'media'             => ['press@company.com'],
    'privacy'           => ['privacy@company.com', 'legal@company.com'],
    'bug_report'        => ['support@company.com', 'dev@company.com'],
    'feature_request'   => ['product@company.com'],
    'general'           => ['info@company.com'],
];
```

All routing destinations are placeholder `@company.com` addresses hardcoded in PHP. Any contact form submission goes nowhere real.

**Fix required:** Move these to the `.env` file and/or a config file (`config/contact.php`), or store them as configurable institution settings in the database.

---

### M-06 — Duplicate / Conflicting Migration Files

**Directory:** `database/migrations/`

Several tables have more than one `create_*` migration file:

| Table | Duplicate files |
|-------|----------------|
| `tenants` | `2019_09_15_000010_create_tenants_table.php` AND `2024_01_01_000000_create_tenants_table.php` |
| `domains` | `2019_09_15_000020_create_domains_table.php` AND `2025_09_05_080622_create_domains_table.php` |
| `job_applications` | `2024_01_01_000009_create_job_applications_table.php` AND `2025_07_30_121159_create_job_applications_table.php` |
| `ab_tests` | `2025_08_10_035235_create_ab_tests_table.php` AND `2025_08_16_095409_create_a_b_tests_table.php` |
| `ab_test_assignments` | `2025_08_10_035238_create_ab_test_assignments_table.php` AND `2025_08_16_095456_create_a_b_test_assignments_table.php` |
| `ab_test_conversions` | `2025_08_10_035251_create_ab_test_conversions_table.php` AND `2025_08_16_095507_create_a_b_test_conversions_table.php` |
| `consents` | `2025_09_29_071956_create_consents_table.php` AND `2025_10_02_000000_create_consents_table.php` |
| `template_ab_tests` | `2025_09_04_143732_create_template_ab_tests_table.php` AND `2025_09_05_082500_create_template_ab_tests_table.php` |
| `migrations` | `2025_09_11_164130_create_migrations_table.php` — This migration creates the `migrations` table, which already exists as Laravel's internal table. This will always fail. |

Running `php artisan migrate` on a fresh database will fail due to these conflicts.

**Fix required:** Identify the canonical migration for each table, delete or consolidate duplicates, and ensure `php artisan migrate:fresh` runs without errors.

---

### M-07 — `TODO` Comments Mark Unimplemented Notification Sending

**Files:** `app/Http/Controllers/Api/UserFlowController.php`, `app/Http/Controllers/SpeakerBureauController.php`

```php
// TODO: Send notification to referrer
// TODO: Send notification to connector
// TODO: Send notification to speaker or admin
```

These are key user-facing actions (job referral requests, networking introductions, speaker booking requests) where the other party receives no notification whatsoever. Submitting these forms silently succeeds with no follow-up.

**Fix required:** Implement notification dispatch using Laravel's notification system (`Notification::send()`), at minimum sending an email notification.

---

### M-08 — Institution Admin Analytics Pages Use Direct `axios` Calls Without Auth Headers

**Files:** `Pages/InstitutionAdmin/Analytics/CommunityHealth.vue`, `CourseROI.vue`, `EmployerEngagement.vue`

These pages make direct `axios.get()` calls on `onMounted` without using Inertia's data loading mechanisms. The endpoints they call (`/institution-admin/analytics/course-roi`, etc.) do not exist as registered routes (see C-04), so every analytics page loads and immediately displays a loading state that never resolves.

**Fix required:** Once the routes are registered, these pages should use Inertia deferred props or server-side data from the controller instead of client-side axios calls, following the existing pattern in the application.

---

### M-09 — `sticky-mobile` CSS Class Has No Tailwind Definition

**File:** `resources/js/Layouts/DefaultLayout.vue`

```html
<div class="sticky-mobile lg:hidden">
```

`sticky-mobile` is not a standard Tailwind class, and there is no custom CSS definition found in any stylesheet. On smaller screens, the mobile header element has no `position: sticky` behavior — it just flows normally.

**Fix required:** Replace `sticky-mobile` with the correct Tailwind classes: `sticky top-0 z-40 bg-white dark:bg-gray-900 shadow-sm`.

---

### M-10 — Register Page Has Three Role Options But Institution Registration Is Incomplete

**File:** `app/Http/Controllers/Auth/RegisteredUserController.php`

When a user selects the "Institution" role and registers:
1. They are assigned the `institution-admin` role.
2. No `Institution` record is created for them.
3. They are redirected to `institution-admin.dashboard`.
4. The institution admin dashboard controller (`InstitutionAdminDashboardController::index()`) calls methods like `getBasicStats()` that query graduates, courses, and employment data — all of which depend on an `Institution` model that does not exist for this user.

The result is a series of null reference errors or empty data on first login.

**Fix required:** After institution user creation, also create an `Institution` record and associate it with the user. Redirect the new institution admin to an onboarding wizard (the `TenantOnboardingController` already exists for this purpose).

---

### M-11 — Super Admin Dashboard Stats May Throw Null Errors

**File:** `resources/js/Pages/Dashboard/SuperAdmin.vue`

```html
<p class="text-2xl font-bold">{{ stats.institutions }}</p>
<p class="text-2xl font-bold">{{ stats.graduates }}</p>
```

The component accesses `stats.institutions` directly with no null guard. If `stats` is `null` or `undefined` (e.g., if the controller fails to pass it), Vue throws a runtime error and the dashboard renders blank.

**Fix required:** Add null guards: `{{ stats?.institutions ?? 0 }}` or use `withDefaults(defineProps<Props>(), { stats: {} })`.

---

### M-12 — PWA Offline Route Returns Blade View, Not Inertia

**File:** `routes/web.php`

```php
Route::get('/offline', function () {
    return view('offline'); // Blade view
})->name('offline');
```

Every other page in the application is rendered via Inertia. This route returns a raw Blade view, which means it has a completely different visual design with no layout consistency.

**Fix required:** Either render `Inertia::render('Offline')` pointing to `resources/js/Pages/Offline.vue` (which already exists), or ensure the Blade `offline` view matches the application's design.

---

## 🔵 Low Severity / Code Quality Issues

---

### L-01 — 20+ Debug/Utility PHP Scripts at Project Root

The following files are scattered at the project root and should not be in a production codebase:

`check_columns.php`, `check_constraints.php`, `check_database_health.php`, `check_demo_users.php`, `check_graduates_table.php`, `check_migrations.php`, `check_tenant_courses.php`, `check_tenant_schema.php`, `check_tenant_tables.php`, `check_tenants.php`, `create_behavior_flow_test.php`, `create_graduates_table.php`, `create_missing_tenant_tables.php`, `create_tenant_migrations_table.php`, `debug_course_creation.php`, `debug_course_issue.php`, `debug_detailed_seeder.php`, `debug_seeder_issue.php`, `add_behavior_flow_types.php`, `add_deleted_at_to_tenant_courses.php`, `add_routes.php`, `add_user_id_to_graduates.php`, `test.txt`, `test_output.txt`, `test_result.txt`, `pest_output.txt`, `query` (no extension), `alumate` (no extension).

These are ad-hoc debugging scripts and output files that were committed to the repo. They expose internal schema knowledge and should not be present.

**Fix required:** Delete all of these files. Any reusable logic should be extracted into proper Artisan commands (`php artisan make:command`).

---

### L-02 — Five Redundant Project Completion Documentation Files

The following documentation files exist at the root level and are redundant:

- `PROJECT_COMPLETION_FINAL.md`
- `PROJECT_COMPLETION_SUMMARY.md`
- `VERIFICATION_REPORT.md`
- `VERIFICATION_REPORT_PROJECT_COMPLETION.md`
- `GEMINI.md`

These appear to be AI-generated progress tracking documents. They clutter the repo and may contain misleading "completed" status for features that are still broken.

**Fix required:** Delete these files. Keep `AGENTS.md` and `README.md` only.

---

### L-03 — `AdminLayout_fixed.vue` is an Untracked Alternate Layout

**File:** `resources/js/Layouts/AdminLayout_fixed.vue`

This file exists alongside `AdminLayout.vue` in the same directory but is never imported by any component. It appears to be an experimental copy.

**Fix required:** Delete it.

---

### L-04 — `components.d.ts` and `auto-imports.d.ts` at Root Level

These are auto-generated files from the `unplugin-vue-components` / `unplugin-auto-import` Vite plugins. They are generated at root level rather than in a proper output directory. If these plugins are used, configure them to output to `resources/js/` or exclude them from source tracking.

---

### L-05 — `prerender.config.js` Unused / Misconfigured

**File:** `prerender.config.js` (root)

This file exists at root level but the `vite.config.ts` does not reference it. It is dead configuration.

**Fix required:** Delete it or integrate it properly into the Vite build configuration if prerendering is intentionally planned.

---

### L-06 — Navigation Items Use Hardcoded String Route Names as Icon Values

**File:** `resources/js/Pages/Dashboard/Graduate.vue` and `Employer.vue`

```ts
const quickActions = [
    { name: 'Social Timeline', href: 'social.timeline', icon: 'chat', color: '...' },
    { name: 'Job Dashboard',   href: 'jobs.dashboard',  icon: 'briefcase', color: '...' },
```

The `href` values are route name strings, not actual URLs. They are passed directly to `<Link :href="item.href">` without calling `route()`. This means every quick action link points to a literal string like `"social.timeline"` rather than `/social/timeline`.

**Fix required:** Wrap each `href` value with `route()`: `href: route('social.timeline')`.

---

### L-07 — `composer-setup.php` Committed to Repository Root

**File:** `composer-setup.php` (root)

This is the Composer installer bootstrap script. It is a one-time setup tool and should not be committed to the repository.

**Fix required:** Delete it and add to `.gitignore`.

---

### L-08 — `docker-compose.yml` and `docker-compose.prod.yml` Are Empty/Minimal

Both Docker Compose files exist at root level but appear to be incomplete stubs. Given the app uses `composer run dev` (with `concurrently`) as its development workflow, the Docker files may be dead configuration.

**Fix required:** Either flesh out the Docker configuration to match the actual stack (PostgreSQL, Redis, PHP-FPM, Nginx, Node) or delete the files to avoid confusion.

---

## Architecture & Design Issues

---

### A-01 — Hybrid Multi-Tenant Architecture Adds Complexity Without Clear Benefit

The application has two competing tenancy systems simultaneously:

1. **Stancl Tenancy v3** (installed, partially configured in `routes/tenant.php`) using domain-based tenant resolution.
2. **Custom `TenantContextService`** + `TenantMiddleware` using subdomain, domain, header, and query-parameter resolution with schema-based isolation.

These two systems overlap and conflict. `TenantMiddleware` redirects to `/tenant-not-found` if no tenant is resolved, but the Stancl middleware (`InitializeTenancyByDomain`) handles its own resolution in `routes/tenant.php`. For development on localhost, the custom `TenantMiddleware` skips resolution (correct), but the Stancl system's `PreventAccessFromCentralDomains` middleware in `routes/tenant.php` would block all central domain access.

**Recommendation:** Decide on one tenancy strategy and remove the other. The custom `TenantContextService` + schema approach is more thoroughly implemented and should likely be the canonical one. The Stancl tenancy package can be kept for its migration tooling but the domain-based routing in `routes/tenant.php` should be replaced with the application's actual routing.

---

### A-02 — Graduate Records Are Scoped to Tenant Schema But Users Are Central

The `User` model is in the central (public) schema while `Graduate` records are in tenant schemas. When a user logs in without a selected institution (`institution_id` is null on `User`), `GraduateDashboardController` redirects them to `graduates.create` (which doesn't exist). There is no UI flow to let a user select or join an institution after registration.

**Recommendation:** Build an institution-selection/join page for new graduate users who haven't been assigned to a tenant yet.

---

### A-03 — Inertia Shared Props Missing Key Data

**File:** `app/Http/Middleware/HandleInertiaRequests.php`

The Inertia shared props pass `auth.user` with `roles` and `permissions`, but do not pass:
- `auth.user.avatar` — used in sidebar user display
- `auth.user.institution_id` — needed for tenant context in frontend
- `app.logo` — referenced as `$page.props.app?.logo` in `DefaultLayout.vue` but never shared
- `app.name` — referenced as `$page.props.app?.name` in `Login.vue` but the shared prop is `name`, not `app.name`

**Fix required:** Add the missing fields to the `share()` method, structured under an `app` key:

```php
'app' => [
    'name' => config('app.name'),
    'logo' => config('app.logo_url', '/images/logo.png'),
],
'auth' => [
    'user' => $request->user() ? [
        // ... existing fields ...
        'avatar' => $request->user()->avatar,
        'institution_id' => $request->user()->institution_id,
    ] : null,
],
```

---

## Prioritized Fix Roadmap

The following order is recommended for making the application functional:

### Phase 1 — Make the App Loadable (Blockers)

| # | Issue | Files |
|---|-------|-------|
| 1 | Register all missing web routes (C-01, C-04, C-05) | `routes/web.php` |
| 2 | Fix `graduates.create` redirect target (C-02) | `GraduateDashboardController.php` |
| 3 | Fix employer registration role validation (C-03) | `RegisteredUserController.php` |
| 4 | Remove login debug panel (H-01) | `Login.vue` |
| 5 | Remove login `console.log` of credentials (H-02) | `Login.vue` |

### Phase 2 — Stabilise Core Features

| # | Issue | Files |
|---|-------|-------|
| 6 | Rewrite Login page with Tailwind CSS (H-03) | `Login.vue` |
| 7 | Fix institution registration flow (M-10) | `RegisteredUserController.php` |
| 8 | Fix employer dashboard auto-create (H-09) | `EmployerDashboardController.php` |
| 9 | Fix observer infinite loops (H-07) | `UserObserver.php`, `EducationHistoryObserver.php` |
| 10 | Add missing Inertia shared props (A-03) | `HandleInertiaRequests.php` |
| 11 | Fix quick action `href` values in dashboards (L-06) | `Graduate.vue`, `Employer.vue` |

### Phase 3 — Data Integrity & Analytics

| # | Issue | Files |
|---|-------|-------|
| 12 | Fix job analytics `withSum` bug (H-05) | `JobController.php` |
| 13 | Consolidate job analytics queries (H-06) | `JobController.php` |
| 14 | Resolve duplicate migrations (M-06) | `database/migrations/` |
| 15 | Re-enable query logging (H-08) | `TenancyServiceProvider.php` |
| 16 | Implement TODO notifications (M-07) | `UserFlowController.php`, `SpeakerBureauController.php` |

### Phase 4 — UX & UI Consistency

| # | Issue | Files |
|---|-------|-------|
| 17 | Replace all `alert()` with toast notifications (H-04) | Multiple components |
| 18 | Consolidate admin layouts (M-01) | `Layouts/`, `Components/` |
| 19 | Remove `AppLayout.vue` wrapper (M-02) | Multiple pages |
| 20 | Fix `sticky-mobile` class (M-09) | `DefaultLayout.vue` |
| 21 | Fix PWA offline route (M-12) | `routes/web.php` |
| 22 | Add TypeScript interfaces to dashboard props (M-03) | All `Dashboard/*.vue` |
| 23 | Move contact emails to config (M-05) | `Api/FormController.php` |

### Phase 5 — Codebase Cleanup

| # | Issue | Files |
|---|-------|-------|
| 24 | Delete all root-level debug scripts (L-01) | Root directory |
| 25 | Delete redundant documentation files (L-02) | Root directory |
| 26 | Delete `AdminLayout_fixed.vue` (L-03) | `Layouts/` |
| 27 | Remove all `console.log` from production code (M-04) | Multiple components |
| 28 | Delete `composer-setup.php` (L-07) | Root directory |
| 29 | Implement or delete Docker Compose files (L-08) | Root directory |
| 30 | Implement tenant routing or document strategy (A-01) | `routes/tenant.php` |

---

## Issue Count Summary

| Severity | Count |
|----------|-------|
| 🔴 Critical | 5 |
| 🟠 High | 11 |
| 🟡 Medium | 12 |
| 🔵 Low | 8 |
| Architecture | 3 |
| **Total** | **39** |

---

*Document generated from full codebase review. All line numbers reference the state of the codebase at time of analysis.*