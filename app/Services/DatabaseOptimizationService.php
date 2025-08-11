<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DatabaseOptimizationService
{
    private array $queryLog = [];

    private array $slowQueries = [];

    private float $slowQueryThreshold = 100; // milliseconds

    public function __construct()
    {
        $this->enableQueryLogging();
    }

    /**
     * Enable query logging for performance monitoring
     */
    public function enableQueryLogging(): void
    {
        DB::listen(function ($query) {
            $this->queryLog[] = [
                'sql' => $query->sql,
                'bindings' => $query->bindings,
                'time' => $query->time,
                'timestamp' => microtime(true),
            ];

            // Track slow queries
            if ($query->time > $this->slowQueryThreshold) {
                $this->slowQueries[] = [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time' => $query->time,
                    'timestamp' => microtime(true),
                    'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10),
                ];

                Log::warning('Slow query detected', [
                    'sql' => $query->sql,
                    'time' => $query->time,
                    'bindings' => $query->bindings,
                ]);
            }
        });
    }

    /**
     * Optimize homepage statistics query
     */
    public function getOptimizedHomepageStatistics(): array
    {
        return Cache::remember('homepage_statistics_optimized', 3600, function () {
            // Use raw queries for better performance
            $statistics = DB::select("
                SELECT 
                    (SELECT COUNT(*) FROM graduates) as total_alumni,
                    (SELECT COUNT(*) FROM graduates WHERE JSON_EXTRACT(employment_status, '$.status') = 'employed') as employed_alumni,
                    (SELECT COUNT(*) FROM jobs WHERE status = 'active') as active_jobs,
                    (SELECT COUNT(DISTINCT employer_id) FROM jobs) as companies_represented,
                    (SELECT COUNT(*) FROM job_applications WHERE status = 'hired') as successful_placements,
                    (SELECT AVG(CAST(JSON_EXTRACT(salary_range, '$.max') AS UNSIGNED)) FROM graduates WHERE JSON_EXTRACT(employment_status, '$.status') = 'employed') as average_salary
            ");

            return [
                'total_alumni' => (int) $statistics[0]->total_alumni,
                'employed_alumni' => (int) $statistics[0]->employed_alumni,
                'employment_rate' => $statistics[0]->total_alumni > 0
                    ? round(($statistics[0]->employed_alumni / $statistics[0]->total_alumni) * 100, 1)
                    : 0,
                'active_jobs' => (int) $statistics[0]->active_jobs,
                'companies_represented' => (int) $statistics[0]->companies_represented,
                'successful_placements' => (int) $statistics[0]->successful_placements,
                'average_salary' => (int) $statistics[0]->average_salary,
                'cached_at' => now()->toISOString(),
            ];
        });
    }

    /**
     * Optimize testimonials query with eager loading
     */
    public function getOptimizedTestimonials(int $limit = 6): array
    {
        return Cache::remember("homepage_testimonials_{$limit}", 1800, function () use ($limit) {
            return DB::select("
                SELECT 
                    g.name,
                    g.profile_image,
                    c.name as course_name,
                    JSON_EXTRACT(g.employment_status, '$.company') as current_company,
                    JSON_EXTRACT(g.employment_status, '$.position') as current_position,
                    t.testimonial,
                    t.rating,
                    g.graduation_year
                FROM graduates g
                INNER JOIN courses c ON g.course_id = c.id
                INNER JOIN testimonials t ON g.id = t.graduate_id
                WHERE t.featured = 1 
                AND t.approved = 1
                AND g.profile_image IS NOT NULL
                ORDER BY t.created_at DESC
                LIMIT ?
            ", [$limit]);
        });
    }

    /**
     * Optimize success stories query
     */
    public function getOptimizedSuccessStories(int $limit = 12): array
    {
        return Cache::remember("homepage_success_stories_{$limit}", 1800, function () use ($limit) {
            return DB::select("
                SELECT 
                    g.id,
                    g.name,
                    g.profile_image,
                    g.graduation_year,
                    c.name as course_name,
                    JSON_EXTRACT(g.employment_status, '$.company') as current_company,
                    JSON_EXTRACT(g.employment_status, '$.position') as current_position,
                    JSON_EXTRACT(g.salary_range, '$.min') as salary_min,
                    JSON_EXTRACT(g.salary_range, '$.max') as salary_max,
                    ss.story_title,
                    ss.story_summary,
                    ss.career_progression,
                    ss.key_achievements,
                    ss.platform_impact
                FROM graduates g
                INNER JOIN courses c ON g.course_id = c.id
                INNER JOIN success_stories ss ON g.id = ss.graduate_id
                WHERE ss.featured = 1 
                AND ss.approved = 1
                AND g.profile_image IS NOT NULL
                ORDER BY ss.created_at DESC
                LIMIT ?
            ", [$limit]);
        });
    }

    /**
     * Optimize job matching query
     */
    public function getOptimizedJobMatches(int $graduateId, int $limit = 10): array
    {
        return Cache::remember("job_matches_{$graduateId}_{$limit}", 900, function () use ($graduateId, $limit) {
            // Get graduate skills first
            $graduate = DB::selectOne("
                SELECT skills, course_id, JSON_EXTRACT(employment_status, '$.status') as employment_status
                FROM graduates 
                WHERE id = ?
            ", [$graduateId]);

            if (! $graduate || $graduate->employment_status === 'employed') {
                return [];
            }

            $skills = json_decode($graduate->skills, true) ?? [];
            $skillsPlaceholder = str_repeat('?,', count($skills) - 1).'?';

            return DB::select("
                SELECT 
                    j.id,
                    j.title,
                    j.description,
                    j.salary_range,
                    j.location,
                    j.required_skills,
                    e.name as employer_name,
                    e.logo as employer_logo,
                    c.name as course_name,
                    (
                        SELECT COUNT(*)
                        FROM JSON_TABLE(j.required_skills, '$[*]' COLUMNS (skill VARCHAR(255) PATH '$')) jt
                        WHERE jt.skill IN ({$skillsPlaceholder})
                    ) as skill_matches
                FROM jobs j
                INNER JOIN employers e ON j.employer_id = e.id
                INNER JOIN courses c ON j.course_id = c.id
                WHERE j.status = 'active'
                AND (j.course_id = ? OR j.course_id IS NULL)
                AND j.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                HAVING skill_matches > 0
                ORDER BY skill_matches DESC, j.created_at DESC
                LIMIT ?
            ", array_merge($skills, [$graduate->course_id, $limit]));
        });
    }

    /**
     * Create optimized database indexes
     */
    public function createOptimizedIndexes(): void
    {
        $indexes = [
            // Graduates table indexes
            "CREATE INDEX IF NOT EXISTS idx_graduates_employment_status ON graduates ((JSON_EXTRACT(employment_status, '$.status')))",
            "CREATE INDEX IF NOT EXISTS idx_graduates_course_employment ON graduates (course_id, (JSON_EXTRACT(employment_status, '$.status')))",
            'CREATE INDEX IF NOT EXISTS idx_graduates_skills ON graduates ((CAST(skills AS CHAR(255) ARRAY)))',
            'CREATE INDEX IF NOT EXISTS idx_graduates_graduation_year ON graduates (graduation_year)',
            'CREATE INDEX IF NOT EXISTS idx_graduates_job_search ON graduates (job_search_active, allow_employer_contact)',

            // Jobs table indexes
            'CREATE INDEX IF NOT EXISTS idx_jobs_status_course ON jobs (status, course_id)',
            'CREATE INDEX IF NOT EXISTS idx_jobs_employer_status ON jobs (employer_id, status)',
            'CREATE INDEX IF NOT EXISTS idx_jobs_created_status ON jobs (created_at, status)',
            'CREATE INDEX IF NOT EXISTS idx_jobs_required_skills ON jobs ((CAST(required_skills AS CHAR(255) ARRAY)))',

            // Job applications indexes
            'CREATE INDEX IF NOT EXISTS idx_job_applications_status ON job_applications (status)',
            'CREATE INDEX IF NOT EXISTS idx_job_applications_job_graduate ON job_applications (job_id, graduate_id)',
            'CREATE INDEX IF NOT EXISTS idx_job_applications_graduate_status ON job_applications (graduate_id, status)',

            // Testimonials indexes
            'CREATE INDEX IF NOT EXISTS idx_testimonials_featured_approved ON testimonials (featured, approved)',
            'CREATE INDEX IF NOT EXISTS idx_testimonials_graduate_approved ON testimonials (graduate_id, approved)',

            // Success stories indexes
            'CREATE INDEX IF NOT EXISTS idx_success_stories_featured_approved ON success_stories (featured, approved)',
            'CREATE INDEX IF NOT EXISTS idx_success_stories_graduate_approved ON success_stories (graduate_id, approved)',

            // Employers indexes
            'CREATE INDEX IF NOT EXISTS idx_employers_verified ON employers (verified)',
            'CREATE INDEX IF NOT EXISTS idx_employers_industry ON employers (industry)',

            // Courses indexes
            'CREATE INDEX IF NOT EXISTS idx_courses_active ON courses (active)',
            'CREATE INDEX IF NOT EXISTS idx_courses_department ON courses (department)',
        ];

        foreach ($indexes as $index) {
            try {
                DB::statement($index);
                Log::info('Created database index', ['index' => $index]);
            } catch (\Exception $e) {
                Log::warning('Failed to create database index', [
                    'index' => $index,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Analyze query performance
     */
    public function analyzeQueryPerformance(): array
    {
        $totalQueries = count($this->queryLog);
        $totalTime = array_sum(array_column($this->queryLog, 'time'));
        $slowQueriesCount = count($this->slowQueries);

        // Group queries by type
        $queryTypes = [];
        foreach ($this->queryLog as $query) {
            $type = $this->getQueryType($query['sql']);
            if (! isset($queryTypes[$type])) {
                $queryTypes[$type] = ['count' => 0, 'total_time' => 0];
            }
            $queryTypes[$type]['count']++;
            $queryTypes[$type]['total_time'] += $query['time'];
        }

        // Find duplicate queries (potential N+1 problems)
        $duplicateQueries = [];
        $queryHashes = [];
        foreach ($this->queryLog as $query) {
            $hash = md5($query['sql']);
            if (! isset($queryHashes[$hash])) {
                $queryHashes[$hash] = ['sql' => $query['sql'], 'count' => 0];
            }
            $queryHashes[$hash]['count']++;
        }

        foreach ($queryHashes as $hash => $data) {
            if ($data['count'] > 1) {
                $duplicateQueries[] = $data;
            }
        }

        return [
            'total_queries' => $totalQueries,
            'total_time' => $totalTime,
            'average_time' => $totalQueries > 0 ? $totalTime / $totalQueries : 0,
            'slow_queries_count' => $slowQueriesCount,
            'slow_queries' => array_slice($this->slowQueries, 0, 10), // Top 10 slowest
            'query_types' => $queryTypes,
            'duplicate_queries' => array_slice($duplicateQueries, 0, 10), // Top 10 duplicates
            'recommendations' => $this->generateOptimizationRecommendations($queryTypes, $duplicateQueries),
        ];
    }

    /**
     * Generate optimization recommendations
     */
    private function generateOptimizationRecommendations(array $queryTypes, array $duplicateQueries): array
    {
        $recommendations = [];

        // Check for excessive SELECT queries
        if (isset($queryTypes['SELECT']) && $queryTypes['SELECT']['count'] > 50) {
            $recommendations[] = 'Consider implementing query result caching to reduce SELECT query count';
        }

        // Check for N+1 query problems
        if (count($duplicateQueries) > 5) {
            $recommendations[] = 'Potential N+1 query problem detected. Consider using eager loading';
        }

        // Check for slow queries
        if (count($this->slowQueries) > 0) {
            $recommendations[] = 'Slow queries detected. Consider adding database indexes or optimizing query structure';
        }

        // Check for excessive INSERT/UPDATE queries
        if (isset($queryTypes['INSERT']) && $queryTypes['INSERT']['count'] > 20) {
            $recommendations[] = 'Consider using bulk insert operations to reduce INSERT query count';
        }

        return $recommendations;
    }

    /**
     * Get query type from SQL
     */
    private function getQueryType(string $sql): string
    {
        $sql = trim(strtoupper($sql));

        if (strpos($sql, 'SELECT') === 0) {
            return 'SELECT';
        }
        if (strpos($sql, 'INSERT') === 0) {
            return 'INSERT';
        }
        if (strpos($sql, 'UPDATE') === 0) {
            return 'UPDATE';
        }
        if (strpos($sql, 'DELETE') === 0) {
            return 'DELETE';
        }
        if (strpos($sql, 'CREATE') === 0) {
            return 'CREATE';
        }
        if (strpos($sql, 'ALTER') === 0) {
            return 'ALTER';
        }
        if (strpos($sql, 'DROP') === 0) {
            return 'DROP';
        }

        return 'OTHER';
    }

    /**
     * Clear query logs
     */
    public function clearQueryLogs(): void
    {
        $this->queryLog = [];
        $this->slowQueries = [];
    }

    /**
     * Get current query statistics
     */
    public function getQueryStatistics(): array
    {
        return [
            'total_queries' => count($this->queryLog),
            'slow_queries' => count($this->slowQueries),
            'total_time' => array_sum(array_column($this->queryLog, 'time')),
            'average_time' => count($this->queryLog) > 0
                ? array_sum(array_column($this->queryLog, 'time')) / count($this->queryLog)
                : 0,
        ];
    }

    /**
     * Optimize database connection settings
     */
    public function optimizeDatabaseConnection(): void
    {
        // Set optimal MySQL settings for performance
        $optimizations = [
            'SET SESSION query_cache_type = ON',
            'SET SESSION query_cache_size = 67108864', // 64MB
            'SET SESSION innodb_buffer_pool_size = 134217728', // 128MB
            'SET SESSION max_connections = 200',
            'SET SESSION innodb_log_file_size = 67108864', // 64MB
            'SET SESSION innodb_flush_log_at_trx_commit = 2',
            'SET SESSION innodb_file_per_table = ON',
        ];

        foreach ($optimizations as $optimization) {
            try {
                DB::statement($optimization);
            } catch (\Exception $e) {
                Log::warning('Failed to apply database optimization', [
                    'optimization' => $optimization,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
