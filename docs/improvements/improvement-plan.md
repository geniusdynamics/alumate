# Production Readiness Improvement Plan v1.0

**Version**: 1.0 (Drafted: 2025-09-20T08:15:00Z, Last Updated: 2025-09-20T08:20:00Z).
**Status**: Active (In Progress). Update this file as tasks complete (mark [x], append notes/changes).
**Owner**: Junior Dev (Review: Senior/Architect/Orchestrator Mode).
**Estimated Effort**: 120-160 hours (2-3 weeks, 40 hours/week with reviews).
**Execution Mode**: Use Orchestrator mode for code implementation (apply_diff/write_to_file for changes). Track progress by updating checkboxes [ ] → [-] (in progress) → [x] (done). After each phase, create a dated completion file (e.g., phase1-completion-2025-09-20.md) with outcomes, metrics, timestamps, and lessons. Archive in the folder or subfolder archived-plans/.

**Prerequisites** (Setup Before Starting):
- Environment: PHP 8.3+ (update XAMPP if needed), Node 18+, Composer 2.x, PostgreSQL 17+, Redis (for caching).
- Tools: VS Code, Git, Laravel Debugbar (`composer require --dev barryvdh/laravel-debugbar`), phpstan (`composer require --dev phpstan/phpstan`), Pest (`composer require --dev pestphp/pest --dev pestphp/pest-plugin-laravel`), Artillery (`npm i -g artillery`), MaxMind GeoIP (`composer require geoip2/geoip2 maxmind-db/geolite2-city` – register free account for DB).
- Branching: Work on feature branches (e.g., `improvements/phase1-tenancy`), PR to `improvements/main`. Commit after each sub-task.
- Testing: After every task/sub-task, run `scripts/testing/run-tests.bat --coverage` (target 85%+ coverage). Use `php artisan tinker` for quick verifications.
- Logging: Use `git commit -m "Phase X Task Y: Description"` for traceability.

**Key Principles for Execution**:
- Incremental: Complete one sub-task, test, commit. No big-bang changes.
- Verification: Each task ends with explicit tests/commands. Fail fast – if a sub-task breaks, rollback and debug.
- Documentation: Update this plan with notes (e.g., "Sub-task 1.1.3: Used UUID for tenant IDs"). Link to dated completions.
- Dependencies: Phases are sequential; don't skip.
- Handoff to Orchestrator: For code edits, use tools like apply_diff for targeted changes. If manual, note in PR.
- Future Updates: To build upon, copy this file as "improvement-plan-v2.0.md", modify, update README.md with new version. For new plans, follow the format below.

**Risks and Scope**: Addresses all assessment weaknesses (disabled tenancy, security placeholders, N+1 queries, etc.). Post-completion, project readiness: 90+/100. If issues arise, reference AGENTS.md for standards.

## Phase 1: Core Infrastructure Fixes (Stabilize Foundation)
**Objectives**: Re-enable tenancy/observers, update PHP to fix blockers.
**Risks Addressed**: Data isolation breaches, vulnerabilities.
**Effort**: 20-25 hours. **Dependencies**: None (start here). **Completion File**: phase1-completion-YYYY-MM-DD.md (include test coverage before/after, any issues).

### Task 1.1: Re-enable Multi-Tenancy [ ] (Critical, 8 hours)
   - **Description**: Disabled provider risks data leaks. Re-enable schema isolation with stancl/tenancy.
   - [ ] Sub-task 1.1.1 (30 min): Verify/install package. Command: `composer show stancl/tenancy` (if missing, `composer require stancl/tenancy`). Read docs: https://tenancyforlaravel.com/docs/v3/installation.
   - [ ] Sub-task 1.1.2 (1 hour): Edit config/app.php (lines ~157-158): Uncomment/add `App\Providers\TenancyServiceProvider::class,` to 'providers' array. Save and run `php artisan config:clear`.
   - [ ] Sub-task 1.1.3 (2 hours): Create config/tenancy.php:
     ```
     <?php
     return [
         'tenant_model' => \App\Models\Tenant::class,
         'id_generator' => \Stancl\Tenancy\Generators\UUIDGenerator::class,
         'central_domains' => [env('CENTRAL_DOMAIN', 'localhost')],
         'database' => [
             'central_connection' => env('DB_CONNECTION', 'pgsql'),
             'template_tenant_connection' => null,
             'tenant_database_prefix' => 'tenant_',
             'manage_migrations_on_create' => true,
         ],
         'features' => [
             \Stancl\Tenancy\Features\TenantConfig::class,
         ],
     ];
     ```
     Run `php artisan tenancy:install` (publishes migrations/config). If errors, check composer autoload.
   - [ ] Sub-task 1.1.4 (2 hours): Update app/Services/BaseService.php switchToTenantSchema method:
     ```
     protected function switchToTenantSchema(string $tenantId): void {
         config(['database.connections.tenant.database' => 'tenant_' . $tenantId]);
         DB::purge('tenant');
         DB::reconnect('tenant');
     }
     ```
     Add tenancy middleware to app/Http/Kernel.php $middlewareGroups['web']:
     ```
     \Stancl\Tenancy\Middleware\InitializeTenancyByDomain::class,
     ```
   - [ ] Sub-task 1.1.5 (1.5 hours): Run migrations. Command: `php artisan tenants:migrate`. Create test tenant: `php artisan tinker` then:
     ```
     $tenant = \Stancl\Tenancy\Database\Models\Tenant::create(['id' => (string) \Illuminate\Support\Str::uuid()]);
     ```
     Seed: `php artisan db:seed --class=DemoDataSeeder --tenant=$tenant`.
   - [ ] Sub-task 1.1.6 (1 hour): Test isolation. In tinker (central): Create user in default schema. Switch tenant (`Tenancy::initialize($tenant)`), query users – should be empty. Insert tenant user, switch back – default empty.
   - **Verification**: Run `php artisan test --filter=TenantIsolationTest`. Manual: No cross-tenant queries succeed. Notes: __________. Commit: `git commit -m "Phase 1.1: Re-enabled multi-tenancy"`.

### Task 1.2: Re-enable Observers [ ] (High, 6 hours)
   - **Description**: Disabled observers break auditing. Re-enable with error handling.
   - [ ] Sub-task 1.2.1 (1 hour): Edit app/Providers/AppServiceProvider.php boot() method (around line 22):
     ```
     try {
         \App\Models\User::observe(\App\Observers\UserObserver::class);
         \App\Models\EducationHistory::observe(\App\Observers\EducationHistoryObserver::class);
     } catch (\Exception $e) {
         \Illuminate\Support\Facades\Log::error('Observer registration failed: ' . $e->getMessage());
     }
     ```
     Run `php artisan config:clear`.
   - [ ] Sub-task 1.2.2 (2 hours): Review/update app/Observers/UserObserver.php and EducationHistoryObserver.php. In UserObserver saving event:
     ```
     public function saving(User $user) {
         app(\App\Services\SecurityService::class)->logDataAccess('user', $user->id, 'update', true, 'observer');
     }
     ```
     Ensure EducationHistoryObserver logs changes similarly. Add try-catch in each event method.
   - [ ] Sub-task 1.2.3 (1.5 hours): Integrate with listeners. In app/Listeners/LogUserActivity.php, ensure it triggers on observer events (e.g., user updated → activity logged).
   - [ ] Sub-task 1.2.4 (1.5 hours): Test. Command: `php artisan tinker`. Create user: `User::create(['name' => 'Test', 'email' => 'test@example.com']);`. Update: `$user->update(['name' => 'Updated']);`. Query: `ActivityLog::where('description', 'like', '%updated%')->count();` (should >0). Check SecurityEvent for logs.
   - **Verification**: Run tests/Feature/UserTestingServiceTest.php. Manual: Verify no exceptions in storage/logs/laravel.log. Notes: ___________. Commit: `git commit -m "Phase 1.2: Re-enabled observers with logging"`.

### Task 1.3: Update PHP and Dependencies [ ] (Medium, 6-11 hours)
   - **Description**: Align with AGENTS.md for security/compatibility.
   - [ ] Sub-task 1.3.1 (2 hours): Edit composer.json: Change "php": "^8.2" to "^8.3". Run `composer update --with-all-dependencies`. If conflicts (e.g., fakerphp/faker), update to compatible versions (e.g., "^1.23.1").
   - [ ] Sub-task 1.3.2 (2 hours): Audit dependencies. Command: `composer outdated --direct`. Update critical (e.g., "laravel/framework": "^12.0" to latest patch). Run `composer update laravel/framework`.
   - [ ] Sub-task 1.3.3 (1.5 hours): Add validation in app/Providers/AppServiceProvider.php boot():
     ```
     if (app()->environment('production')) {
         $requiredKeys = ['APP_KEY', 'DB_PASSWORD', 'SENTRY_LARAVEL_DSN', 'DB_HOST'];
         foreach ($requiredKeys as $key) {
             if (empty(env($key))) {
                 throw new \RuntimeException("Production environment variable missing: {$key}");
             }
         }
     }
     ```
     Test: `php artisan config:cache` (should throw if missing in .env).
   - [ ] Sub-task 1.3.4 (1-6 hours): Test compatibility. Switch to PHP 8.3 (update xampp path if needed). Run `composer install --no-dev`, then full test suite. Fix deprecations (e.g., update string methods).
   - **Verification**: Command: `php -v` (8.3.x). `composer validate`. Full tests pass without warnings. Notes: ___________. Commit: `git commit -m "Phase 1.3: Updated PHP 8.3 and deps"`.

## Phase 2: Security and Compliance Enhancements
**Status**: [ ] Pending.
**Effort**: 30-40 hours. **Dependencies**: Phase 1. **Completion File**: phase2-completion-YYYY-MM-DD.md.

### Task 2.1: Implement Security Placeholders [ ] (High, 12 hours)
   - **Description**: Complete stubs in SecurityService for production protection.
   - [ ] Sub-task 2.1.1 (3 hours): Install GeoIP: `composer require geoip2/geoip2 maxmind-db/geolite2-city`. Download DB: Register at maxmind.com, `curl -o storage/app/geoip/GeoLite2-City.mmdb "https://download.maxmind.com/app/geoip_download?..."`. Replace SecurityService.php isIpBlocked:
     ```
     use GeoIp2\Database\Reader;
     public function isIpBlocked(string $ip): bool {
         try {
             $reader = new Reader(storage_path('app/geoip/GeoLite2-City.mmdb'));
             $record = $reader->city($ip);
             $riskyCodes = ['XX', 'YY']; // Configure high-risk countries
             return $record && in_array($record->country->isoCode, $riskyCodes);
         } catch (\Exception $e) {
             \Log::warning('GeoIP failed: ' . $e->getMessage());
             return false;
         }
     }
     ```
   - [ ] Sub-task 2.1.2 (3 hours): Implement checkSuspiciousPatterns in SecurityService.php:
     ```
     public function checkSuspiciousPatterns(\Illuminate\Http\Request $request): bool {
         $content = $request->getContent();
         $patterns = [
             '/\b(UNION|SELECT|DROP|INSERT|DELETE|UPDATE)\b/i',
             '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/i',
             '/on\w+\s*=/i',
         ];
         foreach ($patterns as $pattern) {
             if (preg_match($pattern, $content)) {
                 $this->logSecurityEvent('MALICIOUS_REQUEST', 'CRITICAL', 'Suspicious pattern matched', ['pattern' => $pattern]);
                 return true;
             }
         }
         return false;
     }
     ```
     Create app/Http/Middleware/SecurityCheck.php:
     ```
     namespace App\Http\Middleware;
     use Closure;
     class SecurityCheck {
         public function handle($request, Closure $next) {
             if (app(\App\Services\SecurityService::class)->checkSuspiciousPatterns($request)) {
                 abort(403, 'Suspicious activity detected');
             }
             return $next($request);
         }
     }
     ```
     Register in app/Http/Kernel.php $middlewareGroups['web']: `\App\Http\Middleware\SecurityCheck::class,`.
   - [ ] Sub-task 2.1.3 (3 hours): Enhance rate limiting in SecurityService.php detectRateLimitViolation:
     ```
     use Illuminate\Support\Facades\RateLimiter;
     use Illuminate\Cache\RateLimiting\Limit;
     public function detectRateLimitViolation(string $identifier, int $maxAttempts = 5, int $decayMinutes = 1): bool {
         $key = 'security:' . $identifier;
         if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
             $executesAt = RateLimiter::availableIn($key);
             \RateLimiter::hit($key, $decayMinutes);
             throw new \Illuminate\Cache\RateLimiting\TooManyAttemptsException('Too many requests.');
         }
         \RateLimiter::hit($key, $decayMinutes);
         return false;
     }
     ```
     Use in controllers (e.g., login: wrap with try-catch for TooManyAttempts).
   - [ ] Sub-task 2.1.4 (3 hours): Test. Simulate: Curl POST with SQL payload to form endpoint – expect 403/log in SecurityEvent. Run `php artisan test --filter=SecuritySystemTest`. Add test case in tests/Feature/Security/MaliciousRequestTest.php.
   - **Verification**: Manual curl: `curl -X POST http://localhost:8080/api/form -d "input=union select"` – 403 response, event logged. Notes: ___________. Commit: `git commit -m "Phase 2.1: Implemented security placeholders"`.

### Task 2.2: Enhance GDPR Tools [ ] (Medium, 10 hours)
   - **Description**: Add encryption/ZIP for exports, webhook for withdrawal.
   - [ ] Sub-task 2.2.1 (3 hours): In app/Services/GdprComplianceService.php, add encrypt/decrypt:
     ```
     private function encryptExport(string $data): string {
         $key = base64_decode(env('GDPR_ENCRYPTION_KEY', ''));
         $iv = base64_decode(env('GDPR_IV', ''));
         if (empty($key) || empty($iv)) throw new \Exception('GDPR keys missing');
         return openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
     }
     private function decryptExport(string $encrypted): string {
         $key = base64_decode(env('GDPR_ENCRYPTION_KEY', ''));
         $iv = base64_decode(env('GDPR_IV', ''));
         return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
     }
     ```
     Update handleAccessRequest: `$encrypted = $this->encryptExport(json_encode($personalData)); Storage::put('gdpr_exports/' . $filename, $encrypted);`. Add to .env.example: `GDPR_ENCRYPTION_KEY=base64:your32bytekey`, `GDPR_IV=base64:16byteiv`. Generate keys: `php artisan tinker` then `$key = base64_encode(random_bytes(32));`.
   - [ ] Sub-task 2.2.2 (4 hours): Implement ZIP in createPortableExport:
     ```
     use ZipArchive;
     private function createPortableExport(array $portableData, string $filename): void {
         $zipPath = storage_path('app/gdpr_exports/' . $filename);
         $zip = new ZipArchive();
         if ($zip->open($zipPath, ZipArchive::CREATE) !== TRUE) throw new \Exception('ZIP creation failed');
         $zip->addFromString('leads.csv', $this->arrayToCsv($portableData['leads.csv']));
         $zip->addFromString('users.csv', $this->arrayToCsv($portableData['users.csv']));
         $zip->addFromString('metadata.json', json_encode(['exported_at' => now()]));
         $zip->close();
     }
     private function arrayToCsv(array $data): string {
         if (empty($data)) return '';
         $output = fopen('php://temp', 'r+');
         fputcsv($output, array_keys($data[0])); // Headers
         foreach ($data as $row) fputcsv($output, $row);
         rewind($output);
         return stream_get_contents($output);
     }
     ```
     Call in handlePortabilityRequest/handleAccessRequest.
   - [ ] Sub-task 2.2.3 (2 hours): Add webhook in withdrawMarketingConsent:
     ```
     foreach ($leads as $lead) {
         \App\Jobs\SyncLeadToCrm::dispatch($lead, ['action' => 'unsubscribe_marketing']);
     }
     ```
     Ensure SyncLeadToCrm job handles 'unsubscribe'.
   - [ ] Sub-task 2.2.4 (1 hour): Test. Request access/erasure – verify ZIP created with CSVs, encrypted JSON. Decrypt: `php artisan tinker` then `$decrypted = app(GdprComplianceService::class)->decryptExport(Storage::get('gdpr_exports/test.json'));`.
   - **Verification**: Integration test: Create lead, request export – ZIP downloads with data. Manual: Verify encryption (garbled without key). Notes: ___________. Commit: `git commit -m "Phase 2.2: Enhanced GDPR tools"`.

### Task 2.3: OWASP Compliance and Global Sanitization [ ] (Medium, 8 hours)
   - **Description**: Add middleware for input sanitization, enforce OWASP rules.
   - [ ] Sub-task 2.3.1 (3 hours): Create app/Http/Middleware/SanitizeInput.php:
     ```
     <?php
     namespace App\Http\Middleware;
     use Closure;
     use App\Rules\ContentFilter;
     class SanitizeInput {
         public function handle($request, Closure $next) {
             $sanitized = $request->all();
             foreach ($sanitized as $key => $value) {
                 if (is_string($value)) {
                     $sanitized[$key] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                     (new ContentFilter())->validate($key, $sanitized[$key], fn($fail) => $fail('Invalid input detected'));
                 }
             }
             $request->merge($sanitized);
             return $next($request);
         }
     }
     ```
     Register in app/Http/Kernel.php $middlewareGroups['web'] (append to end):
     ```
     \App\Http\Middleware\SanitizeInput::class,
     ```
   - [ ] Sub-task 2.3.2 (2 hours): Install phpcs: `composer require --dev squizlabs/php_codesniffer`. Create phpcs.xml in root:
     ```
     <?xml version="1.0"?>
     <ruleset name="Laravel">
         <description>PSR12 + OWASP</description>
         <rule ref="PSR12">
             <exclude name="Generic.WhiteSpace.ScopeIndent.IncorrectExactIndentation"/>
         </rule>
         <rule ref="Squiz.Commenting.FileComment">
             <exclude name="Internal.NoCode"/>
         </rule>
         <!-- Add OWASP rules via custom sniffs if needed -->
     </ruleset>
     ```
     Run `vendor/bin/phpcs app/ --standard=phpcs.xml --report=summary`.
   - [ ] Sub-task 2.3.3 (2 hours): Add OWASP tests in tests/Feature/Security/OwaspTest.php:
     ```
     test('prevents XSS', function () {
         $response = $this->post('/api/form', ['input' => '<script>alert(1)</script>']);
         $response->assertStatus(422);
         $this->assertDatabaseHas('security_events', ['description' => 'Suspicious pattern']);
     });
     test('prevents SQL injection', function () {
         $response = $this->post('/api/search', ['query' => 'union select']);
         $response->assertStatus(403);
     });
     ```
   - [ ] Sub-task 2.3.4 (1 hour): Verify. Run phpcs – 0 errors. Tests pass.
   - **Verification**: Manual: POST form with `<script>alert(1)</script>` – 422 response, sanitized in DB. phpcs clean. Notes: ___________. Commit: `git commit -m "Phase 2.3: OWASP compliance middleware"`.

## Phase 3: Performance and Scalability Improvements
**Status**: [ ] Pending.
**Effort**: 25-30 hours. **Dependencies**: Phases 1-2. **Completion File**: phase3-completion-YYYY-MM-DD.md.

### Task 3.1: Eliminate N+1 Queries [ ] (High, 10 hours)
   - **Description**: Use Debugbar to identify/fix in services for faster loads.
   - [ ] Sub-task 3.1.1 (2 hours): Install Debugbar if missing: `composer require --dev barryvdh/laravel-debugbar`. Run `php artisan serve`, visit alumni directory – note N+1 in queries tab (e.g., circles loop).
   - [ ] Sub-task 3.1.2 (5 hours): Fix in key services. For app/Services/AlumniRecommendationService.php getRecommendationsForUser:
     ```
     public function getRecommendationsForUser(User $user, int $limit = 10): Collection {
         return User::with(['circles.users', 'groups.members', 'location'])
             ->where('id', '!=', $user->id)
             ->whereHas('circles', fn($q) => $q->where('user_id', $user->id)) // Example filter
             ->limit($limit)
             ->get();
     }
     ```
     Similar for AlumniMapService::getAlumniWithLocations: Add `with('location')`. CareerTimelineService::getTimelineForUser: `with('milestones')`. Add `select(['id', 'name', 'email'])` to minimize.
   - [ ] Sub-task 3.1.3 (2 hours): Add N+1 rule to phpstan.neon (use extension if available, or note in docs/development/testing.md).
   - [ ] Sub-task 3.1.4 (1 hour): Test. Reload pages – Debugbar shows <10 queries/request.
   - **Verification**: Run tests/Performance/AlumniPlatformPerformanceTest.php. Load test: Create artillery.yml for homepage (10 concurrent, 1min), run `artillery run artillery.yml` – avg response <500ms. Notes: ___________. Commit: `git commit -m "Phase 3.1: Fixed N+1 queries"`.

### Task 3.2: Automate Caching Invalidation [ ] (Medium, 8 hours)
   - **Description**: Ensure caches refresh on data changes.
   - [ ] Sub-task 3.2.1 (2 hours): Edit app/Services/CrossTenantSyncService.php performBidirectionalSync (after sync line):
     ```
     app(\App\Services\CachingStrategyService::class)->invalidateRelatedCaches('tenant', $tenantId);
     ```
   - [ ] Sub-task 3.2.2 (3 hours): Hook observers (from Phase 1). In app/Observers/UserObserver.php saved event:
     ```
     app(\App\Services\ComponentCachingService::class)->invalidateComponentCacheForUser($user->id);
     app(\App\Services\CachingStrategyService::class)->invalidateRelatedCaches('user', $user->id);
     ```
     Add method in ComponentCachingService.php:
     ```
     public function invalidateComponentCacheForUser(int $userId): void {
         $this->invalidatePatternCache("user:{$userId}:*");
         $this->invalidatePatternCache("cache:user:{$userId}:*");
     }
     ```
   - [ ] Sub-task 3.2.3 (2 hours): Create app/Jobs/WarmCacheJob.php:
     ```
     <?php
     namespace App\Jobs;
     use Illuminate\Bus\Queueable;
     use Illuminate\Contracts\Queue\ShouldQueue;
     use Illuminate\Foundation\Bus\Dispatchable;
     use Illuminate\Queue\InteractsWithQueue;
     use Illuminate\Queue\SerializesModels;
     class WarmCacheJob implements ShouldQueue {
         use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
         public function handle() {
             app(\App\Services\CachingStrategyService::class)->warmCache();
             app(\App\Services\ComponentCachingService::class)->preloadFrequentlyUsedComponents(tenant_id: auth()->user()->tenant_id ?? 1, limit: 20);
         }
     }
     ```
     Schedule in app/Console/Kernel.php schedule method:
     ```
     $schedule->job(new WarmCacheJob)->hourly()->onOneServer();
     ```
   - [ ] Sub-task 3.2.4 (1 hour): Test. Update user – `redis-cli keys "*user:{$userId}*"` should show no matches after invalidation. Run job: `php artisan queue:work --once`.
   - **Verification**: Cache metrics (CachingStrategyService::getCacheMetrics()) show >80% hit rate after updates. Notes: ___________. Commit: `git commit -m "Phase 3.2: Automated caching invalidation"`.

### Task 3.3: Prepare for Horizontal Scaling [ ] (Medium, 7 hours)
   - **Description**: Config clusters, document K8s for multi-node.
   - [ ] Sub-task 3.3.1 (2 hours): Edit config/cache.php for Redis cluster:
     ```
     'redis' => [
         'client' => env('REDIS_CLIENT', 'phpredis'),
         'options' => [
             'cluster' => env('REDIS_CLUSTER', 'redis'),
             'prefix' => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_') . '_database_'),
             'throw' => env('REDIS_EXCEPTIONS', false),
             'ssl' => env('REDIS_SSL', false),
         ],
         'clusters' => [
             'default' => [
                 [
                     'host' => env('REDIS_HOST', '127.0.0.1'),
                     'password' => env('REDIS_PASSWORD', null),
                     'port' => env('REDIS_PORT', 6379),
                     'database' => env('REDIS_DB', 0),
                 ],
             ],
         ],
     ],
     ```
     Add to .env: `REDIS_CLUSTER=redis` for production.
   - [ ] Sub-task 3.3.2 (3 hours): Create infrastructure/k8s/deployment.yaml:
     ```
     apiVersion: apps/v1
     kind: Deployment
     metadata:
       name: alumate-app
       namespace: production
     spec:
       replicas: 3
       selector:
         matchLabels:
           app: alumate
       template:
         metadata:
           labels:
             app: alumate
         spec:
           containers:
           - name: app
             image: your-registry/alumate:latest
             ports:
             - containerPort: 8080
             resources:
               requests:
                 cpu: "100m"
                 memory: "256Mi"
               limits:
                 cpu: "500m"
                 memory: "512Mi"
             envFrom:
             - secretRef:
                 name: alumate-secrets
             volumeMounts:
             - name: storage
               mountPath: /var/www/storage
           volumes:
           - name: storage
             persistentVolumeClaim:
               claimName: alumate-storage-pvc
     ---
     apiVersion: autoscaling/v2
     kind: HorizontalPodAutoscaler
     metadata:
       name: alumate-hpa
     spec:
       scaleTargetRef:
         apiVersion: apps/v1
         kind: Deployment
         name: alumate-app
       minReplicas: 3
       maxReplicas: 10
       metrics:
       - type: Resource
         resource:
           name: cpu
           target:
             type: Utilization
             averageUtilization: 50
     ```
     Add service.yaml for LoadBalancer.
   - [ ] Sub-task 3.3.3 (2 hours): Test scaling. Install Octane (`composer require laravel/octane`); run `php artisan octane:start --server=swoole --workers=4`. Simulate load with Artillery.
   - **Verification**: Local: Run with 4 workers, no session loss. K8s: Use minikube `kubectl apply -f infrastructure/k8s/` – scales on CPU >50%. Notes: ___________. Commit: `git commit -m "Phase 3.3: Horizontal scaling prep"`.

## Phase 4: Documentation and Deployment Unification
**Status**: [ ] Pending.
**Effort**: 15-20 hours. **Dependencies**: Phases 1-3. **Completion File**: phase4-completion-YYYY-MM-DD.md.

### Task 4.1: Unified Deployment Playbook [ ] (High, 8 hours)
   - **Description**: Standardize with zero-downtime, Docker/K8s.
   - [ ] Sub-task 4.1.1 (2 hours): Create docs/deployment/deploy.md:
     ```
     # Deployment Guide (Zero-Downtime)
     ## Prerequisites
     - Docker/K8s cluster ready.
     - Secrets in K8s (kubectl create secret generic alumate-secrets --from-literal=DB_PASSWORD=pass).
     ## Steps
     1. Build: docker build -t alumate:latest .
     2. Push: docker push your-registry/alumate:latest.
     3. Deploy: kubectl set image deployment/alumate-app app=alumate:latest.
     4. Migrate: kubectl exec -it deployment/alumate-app -- php artisan migrate --force.
     5. Verify: kubectl rollout status deployment/alumate-app.
     ## Rollback: kubectl rollout undo deployment/alumate-app.
     ## Local: docker-compose up -d (includes PostgreSQL/Redis).
     ```
   - [ ] Sub-task 4.1.2 (3 hours): Dockerfile (root):
     ```
     FROM php:8.3-fpm-alpine
     RUN apk add --no-cache $PHPIZE_DEPS && pecl install redis && docker-php-ext-enable redis pdo_pgsql
     WORKDIR /var/www
     COPY . .
     RUN composer install --no-dev --optimize-autoloader && npm ci --production && npm run build
     CMD ["php-fpm"]
     ```
     docker-compose.yml:
     ```
     version: '3.8'
     services:
       app:
         build: .
         ports:
           - "8080:9000"
         depends_on:
           - db
           - redis
       db:
         image: postgres:17
         environment:
           POSTGRES_DB: alumate
           POSTGRES_USER: postgres
           POSTGRES_PASSWORD: password
       redis:
         image: redis:alpine
     ```
   - [ ] Sub-task 4.1.3 (2 hours): .github/workflows/ci.yml (GitHub Actions):
     ```
     name: CI/CD Pipeline
     on: [push, pull_request]
     jobs:
       test:
         runs-on: ubuntu-latest
         steps:
         - uses: actions/checkout@v4
         - name: Setup PHP
           uses: shivammathur/setup-php@v2
           with: { php-version: '8.3', extensions: pdo_pgsql }
         - run: composer install --prefer-dist --no-progress
         - run: npm ci
         - run: scripts/testing/run-tests.bat --coverage
         - run: ./vendor/bin/phpstan analyse --level=8
         - run: npm run lint && npm run test
         - name: Build
           run: npm run build
       deploy:
         needs: test
         if: github.ref == 'refs/heads/main'
         runs-on: ubuntu-latest
         steps:
         - uses: actions/checkout@v4
         - name: Deploy to Staging
           run: |
             echo "Deploy script here (e.g., kubectl apply)"
     ```
   - [ ] Sub-task 4.1.4 (1 hour): Test. Run `docker-compose up`, verify app at localhost:8080. Push to GitHub, check CI runs.
   - **Verification**: Docker runs without errors; CI green on PR. Notes: ___________. Commit: `git commit -m "Phase 4.1: Unified deployment playbook"`.

### Task 4.2: Enhance API and Accessibility Docs [ ] (Medium, 7 hours)
   - **Description**: Generate Swagger, add axe-core CI.
   - [ ] Sub-task 4.2.1 (3 hours): Install L5-Swagger: `composer require --dev darkaonline/l5-swagger`. In controllers (e.g., app/Http/Controllers/UserController.php), add annotations:
     ```
     /**
      * @OA\Get(
      *     path="/api/users",
      *     summary="Get users",
      *     @OA\Response(response=200, description="Users list")
      * )
      */
     public function index() { ... }
     ```
     Run `php artisan l5-swagger:generate`. Update docs/api/reference.md: "Access Swagger at /api/documentation".
   - [ ] Sub-task 4.2.2 (2 hours): Accessibility CI. Install axe-core: `npm i --save-dev @axe-core/playwright`. Add to package.json scripts: `"test:accessibility": "playwright test tests/accessibility/"`. Create tests/accessibility/homepage.spec.ts:
     ```
     import { test, expect } from '@playwright/test';
     import AxeBuilder from '@axe-core/playwright';
     test('WCAG Compliance', async ({ page }) => {
         await page.goto('http://localhost:8080');
         const accessibilityScanResults = await new AxeBuilder({ page }).analyze();
         expect(accessibilityScanResults.violations).toEqual([]);
     });
     ```
     Run `npx playwright test`.
   - [ ] Sub-task 4.2.3 (1 hour): Cross-platform scripts. Create scripts/testing/run-tests.sh:
     ```
     #!/bin/bash
     php artisan test:comprehensive --coverage --report
     npm run test
     ```
     Make executable: `chmod +x scripts/testing/run-tests.sh`.
   - [ ] Sub-task 4.2.4 (1 hour): Update docs/component-library/accessibility.md: "Run axe-core: npx playwright test tests/accessibility/".
   - **Verification**: Swagger at /api/documentation renders endpoints. `npm run test:accessibility` passes 0 violations. Notes: ___________. Commit: `git commit -m "Phase 4.2: Enhanced API/accessibility docs"`.

## Phase 5: Testing and Code Cleanup
**Status**: [ ] Pending.
**Effort**: 20-25 hours. **Dependencies**: Phases 1-4. **Completion File**: phase5-completion-YYYY-MM-DD.md.

### Task 5.1: Migrate to Pest and Boost Coverage [ ] (Medium, 10 hours)
   - **Description**: Switch from PHPUnit for better expressiveness; ensure 85% coverage.
   - [ ] Sub-task 5.1.1 (2 hours): Create root pest.php:
     ```
     <?php
     uses(\Tests\TestCase::class)->in('tests/Feature');
     uses(\Tests\TestCase::class)->in('tests/Integration');
     uses(\Tests\UnitTestCase::class)->in('tests/Unit');
     expects()->toBeInDatabase('users');
     it('uses Pest syntax')->todo();
     ```
     Run `php artisan pest:install` if needed. Test: `php artisan test` (should detect Pest).
   - [ ] Sub-task 5.1.2 (5 hours): Convert key tests. E.g., tests/Feature/TenantIsolationTest.php to tests/Feature/TenantIsolationTest.php (Pest style):
     ```
     <?php
     tests/Feature/TenantIsolationTest.php
     it('isolates tenant data', function () {
         $tenant1 = \Stancl\Tenancy\Database\Models\Tenant::create(['id' => (string) \Illuminate\Support\Str::uuid()]);
         \Stancl\Tenancy\Database\Concerns\BelongsToTenantScopes::initialize($tenant1);
         \App\Models\User::create(['name' => 'User1']);
         $tenant2 = \Stancl\Tenancy\Database\Models\Tenant::create(['id' => (string) \Illuminate\Support\Str::uuid()]);
         \Stancl\Tenancy\Database\Concerns\BelongsToTenantScopes::initialize($tenant2);
         expect(\App\Models\User::count())->toBe(0);
     });
     ```
     Convert 10+ tests: TenantIsolationTest, SecuritySystemTest, Performance/HomepagePerformanceTest.
   - [ ] Sub-task 5.1.3 (2 hours): Update phpunit.xml for coverage:
     ```
     <phpunit>
         <coverage processUncoveredFiles="true">
             <include>
                 <directory suffix=".php">./app</directory>
             </include>
             <report type="html" target="tests/reports/coverage"/>
         </coverage>
     </phpunit>
     ```
     Run `php artisan test --coverage` – review report, add missing (e.g., GDPR tests in Integration/GdprTest.php: it('handles erasure', fn() => { ... expect anonymized email ... }).
   - [ ] Sub-task 5.1.4 (1 hour): Verify coverage >85%. If low, add dataset tests for edge cases.
   - **Verification**: `php artisan test` passes all. Coverage report in tests/reports/coverage shows 85%+. Notes: ___________. Commit: `git commit -m "Phase 5.1: Migrated to Pest, 85% coverage"`.

### Task 5.2: Audit and Consolidate Services [ ] (Medium, 10 hours)
   - **Description**: Reduce bloat by merging redundancies.
   - [ ] Sub-task 5.2.1 (3 hours): Audit services. Command: `find app/Services -name "*.php" | wc -l` (expect ~50). Grep calls: `grep -r "new [ServiceName]" app/ | wc -l`. Identify pairs: EmailAnalyticsService + EmailTrackingService (merge tracking methods to Analytics).
     - Merge: Move trackOpen/trackClick from EmailTrackingService to EmailAnalyticsService. Delete EmailTrackingService.php.
   - [ ] Sub-task 5.2.2 (4 hours): Deprecate in old service (before delete):
     ```
     /**
      * @deprecated 1.0 Use EmailAnalyticsService::trackOpen instead. Remove in 2.0.
      */
     public function trackOpen(...) {
         return app(EmailAnalyticsService::class)->trackOpen(...);
     }
     ```
     Global replace calls: `grep -r "EmailTrackingService" app/Http/Controllers/` – replace with EmailAnalyticsService.
     Run `composer dump-autoload`.
   - [ ] Sub-task 5.2.3 (2 hours): Remove unused (e.g., if DonorCrmService calls =0): Delete file, update autoload. Confirm no breaks: `php artisan route:list`.
   - [ ] Sub-task 5.2.4 (1 hour): Document policy in docs/improvements/deprecation-policy.md:
     ```
     # Deprecation Policy
     - Mark with @deprecated PHPDoc.
     - Redirect to replacement.
     - Remove after 2 releases or 6 months.
     - Announce in changelog.md.
     ```
   - **Verification**: phpstan analyses pass (`./vendor/bin/phpstan analyse`). Code lines reduced ~10% (wc -l app/Services before/after). Notes: ___________. Commit: `git commit -m "Phase 5.2: Consolidated services, removed bloat"`.

## Phase 6: Final Audit, Validation, and Handoff
**Status**: [ ] Pending.
**Effort**: 10-20 hours. **Dependencies**: All prior. **Completion File**: phase6-completion-YYYY-MM-DD.md (final summary).

### Task 6.1: Comprehensive Audits [ ] (High, 5 hours)
   - **Description**: Validate all changes.
   - [ ] Sub-task 6.1.1 (1 hour): Run phpstan: `./vendor/bin/phpstan analyse --level=8 app/`. Fix any errors (e.g., strict types).
   - [ ] Sub-task 6.1.2 (2 hours): Security scan. Install: `composer require --dev enlightn/enlightn`. Run `php artisan enlightn` – address issues (e.g., enable CSRF if missing).
   - [ ] Sub-task 6.1.3 (1 hour): Load test. Create tests/load.yml for Artillery:
     ```
     config:
       target: "localhost:8080"
       phases:
         - duration: 60
           arrivalRate: 10
     scenarios:
       - flow:
         - get:
             url: "/"
     ```
     Run `artillery run tests/load.yml` – ensure avg <500ms, no errors.
   - [ ] Sub-task 6.1.4 (1 hour): Document in docs/improvements/audit-report.md: Paste outputs, note fixes.
   - **Verification**: All audits pass (0 errors). Notes: ___________. Commit: `git commit -m "Phase 6.1: Audits passed"`.

### Task 6.2: Staging Validation and Handoff Prep [ ] (High, 5-15 hours)
   - **Description**: Deploy/test in staging, prepare for Orchestrator.
   - [ ] Sub-task 6.2.1 (3 hours): Deploy to staging per docs/deployment/deploy.md. Verify: Create tenants, test isolation (query cross-tenant), observers (update user → logs), security (simulate XSS – blocked), performance (load test <500ms).
   - [ ] Sub-task 6.2.2 (3 hours): Manual QA. Follow docs/MANUAL_QA_REPORT.md: Test login/form submission/multi-tenant switch/GDPR erasure. Log issues in docs/improvements/qa-notes.md.
   - [ ] Sub-task 6.2.3 (2 hours): Update root README.md:
     ```
     ## Post-Improvement Status (v1.0)
     - PHP 8.3 enabled.
     - Full multi-tenancy with observers.
     - 85% test coverage.
     - Security/Performance fixes complete.
     - See docs/improvements/ for details.
     ```
   - [ ] Sub-task 6.2.4 (2-7 hours): Handoff prep. Copy this plan to orchestrator-todo.md (mirror structure for code mode). List pending code changes (e.g., "Apply diff to config/app.php for uncomment").
   - **Verification**: Staging URL functional (no errors in logs). All tests green. Ready for switch_mode to orchestrator. Notes: ___________. Commit: `git commit -m "Phase 6.2: Staging validated, handoff ready"`.

**Next Steps After Completion**:
- Create phase6-completion-2025-09-20.md: Summarize outcomes (e.g., "All phases done, coverage 90%, no vulnerabilities"), metrics (before/after readiness 65→95), lessons (e.g., "Tenancy enable fixed 5 bugs"), reviewer sign-off.
- Archive: Move this file to archived-plans/improvement-plan-v1.0-completed.md.
- New Improvements: For future, copy this format, update README.md "Active Plans" section with link/status.
- Review: Tag @architect for approval. If changes needed, edit this file or create v1.1.

This plan ensures systematic, traceable improvements. Questions? Update here or open GitHub issue.