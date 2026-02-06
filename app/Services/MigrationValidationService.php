<?php
// ABOUTME: Migration validation service for verifying data integrity and migration success in schema-based tenancy
// ABOUTME: Provides comprehensive validation tools for ensuring successful migration from hybrid to schema-based architecture

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class MigrationValidationService
{
    protected TenantContextService $tenantService;
    protected SchemaUtilityService $schemaUtility;
    
    public function __construct(
        TenantContextService $tenantService,
        SchemaUtilityService $schemaUtility
    ) {
        $this->tenantService = $tenantService;
        $this->schemaUtility = $schemaUtility;
    }
    
    /**
     * Validate complete migration for a tenant
     */
    public function validateTenantMigration(Tenant $tenant): array
    {
        $validation = [
            'tenant_id' => $tenant->id,
            'tenant_name' => $tenant->name,
            'schema_name' => $tenant->schema_name,
            'overall_status' => 'pending',
            'validations' => [],
            'data_integrity' => [],
            'performance_metrics' => [],
            'errors' => [],
            'warnings' => []
        ];
        
        try {
            // 1. Validate schema structure
            $schemaValidation = $this->validateSchemaStructure($tenant->schema_name);
            $validation['validations']['schema_structure'] = $schemaValidation;
            
            if (!$schemaValidation['valid']) {
                $validation['errors'] = array_merge($validation['errors'], $schemaValidation['errors']);
            }
            
            // 2. Validate data migration
            $dataValidation = $this->validateDataMigration($tenant);
            $validation['validations']['data_migration'] = $dataValidation;
            
            if (!$dataValidation['valid']) {
                $validation['errors'] = array_merge($validation['errors'], $dataValidation['errors']);
            }
            
            // 3. Validate data integrity
            $integrityValidation = $this->validateDataIntegrity($tenant);
            $validation['data_integrity'] = $integrityValidation;
            
            if (!$integrityValidation['valid']) {
                $validation['errors'] = array_merge($validation['errors'], $integrityValidation['errors']);
            }
            
            // 4. Validate relationships
            $relationshipValidation = $this->validateRelationships($tenant);
            $validation['validations']['relationships'] = $relationshipValidation;
            
            if (!$relationshipValidation['valid']) {
                $validation['errors'] = array_merge($validation['errors'], $relationshipValidation['errors']);
            }
            
            // 5. Performance validation
            $performanceValidation = $this->validatePerformance($tenant);
            $validation['performance_metrics'] = $performanceValidation;
            
            $validation['warnings'] = array_merge($validation['warnings'], $performanceValidation['warnings']);
            
            // 6. Validate tenant isolation
            $isolationValidation = $this->validateTenantIsolation($tenant);
            $validation['validations']['tenant_isolation'] = $isolationValidation;
            
            if (!$isolationValidation['valid']) {
                $validation['errors'] = array_merge($validation['errors'], $isolationValidation['errors']);
            }
            
            // Determine overall status
            $validation['overall_status'] = empty($validation['errors']) ? 'passed' : 'failed';
            
            Log::info('Tenant migration validation completed', [
                'tenant_id' => $tenant->id,
                'status' => $validation['overall_status'],
                'error_count' => count($validation['errors']),
                'warning_count' => count($validation['warnings'])
            ]);
            
        } catch (Exception $e) {
            $validation['overall_status'] = 'error';
            $validation['errors'][] = 'Validation failed: ' . $e->getMessage();
            
            Log::error('Tenant migration validation error', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage()
            ]);
        }
        
        return $validation;
    }
    
    /**
     * Validate schema structure
     */
    protected function validateSchemaStructure(string $schemaName): array
    {
        return $this->schemaUtility->validateSchemaStructure($schemaName);
    }
    
    /**
     * Validate data migration completeness
     */
    protected function validateDataMigration(Tenant $tenant): array
    {
        $validation = [
            'valid' => true,
            'errors' => [],
            'warnings' => [],
            'record_counts' => []
        ];
        
        try {
            $schemaName = $tenant->schema_name;
            
            // Compare record counts between old and new schemas
            $tables = ['students', 'courses', 'enrollments', 'grades', 'activity_logs'];
            
            foreach ($tables as $table) {
                // Count records in old hybrid table (with tenant_id)
                $oldCount = $this->getHybridTableCount($table, $tenant->id);
                
                // Count records in new schema table
                $newCount = $this->getSchemaTableCount($schemaName, $table);
                
                $validation['record_counts'][$table] = [
                    'old_count' => $oldCount,
                    'new_count' => $newCount,
                    'difference' => $newCount - $oldCount
                ];
                
                if ($newCount < $oldCount) {
                    $validation['valid'] = false;
                    $validation['errors'][] = "Data loss detected in {$table}: {$oldCount} -> {$newCount}";
                } elseif ($newCount > $oldCount) {
                    $validation['warnings'][] = "Extra records in {$table}: {$oldCount} -> {$newCount}";
                }
            }
            
        } catch (Exception $e) {
            $validation['valid'] = false;
            $validation['errors'][] = 'Data migration validation failed: ' . $e->getMessage();
        }
        
        return $validation;
    }
    
    /**
     * Get record count from hybrid table
     */
    protected function getHybridTableCount(string $table, int $tenantId): int
    {
        try {
            return DB::table($table)->where('tenant_id', $tenantId)->count();
        } catch (Exception $e) {
            Log::warning("Could not count hybrid table records", [
                'table' => $table,
                'tenant_id' => $tenantId,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }
    
    /**
     * Get record count from schema table
     */
    protected function getSchemaTableCount(string $schemaName, string $table): int
    {
        try {
            return DB::table("{$schemaName}.{$table}")->count();
        } catch (Exception $e) {
            Log::warning("Could not count schema table records", [
                'schema' => $schemaName,
                'table' => $table,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }
    
    /**
     * Validate data integrity
     */
    protected function validateDataIntegrity(Tenant $tenant): array
    {
        $validation = [
            'valid' => true,
            'errors' => [],
            'warnings' => [],
            'checks' => []
        ];
        
        try {
            $schemaName = $tenant->schema_name;
            
            // Set tenant context for validation
            $this->tenantService->setTenant($tenant);
            
            // 1. Validate foreign key relationships
            $fkValidation = $this->validateForeignKeys($schemaName);
            $validation['checks']['foreign_keys'] = $fkValidation;
            
            if (!$fkValidation['valid']) {
                $validation['valid'] = false;
                $validation['errors'] = array_merge($validation['errors'], $fkValidation['errors']);
            }
            
            // 2. Validate unique constraints
            $uniqueValidation = $this->validateUniqueConstraints($schemaName);
            $validation['checks']['unique_constraints'] = $uniqueValidation;
            
            if (!$uniqueValidation['valid']) {
                $validation['valid'] = false;
                $validation['errors'] = array_merge($validation['errors'], $uniqueValidation['errors']);
            }
            
            // 3. Validate data consistency
            $consistencyValidation = $this->validateDataConsistency($schemaName);
            $validation['checks']['data_consistency'] = $consistencyValidation;
            
            if (!$consistencyValidation['valid']) {
                $validation['valid'] = false;
                $validation['errors'] = array_merge($validation['errors'], $consistencyValidation['errors']);
            }
            
            // 4. Validate calculated fields
            $calculatedValidation = $this->validateCalculatedFields($schemaName);
            $validation['checks']['calculated_fields'] = $calculatedValidation;
            
            $validation['warnings'] = array_merge($validation['warnings'], $calculatedValidation['warnings']);
            
        } catch (Exception $e) {
            $validation['valid'] = false;
            $validation['errors'][] = 'Data integrity validation failed: ' . $e->getMessage();
        } finally {
            $this->tenantService->clearTenant();
        }
        
        return $validation;
    }
    
    /**
     * Validate foreign key relationships
     */
    protected function validateForeignKeys(string $schemaName): array
    {
        $validation = [
            'valid' => true,
            'errors' => [],
            'orphaned_records' => []
        ];
        
        $checks = [
            // Enrollments -> Students
            [
                'table' => 'enrollments',
                'column' => 'student_id',
                'reference_table' => 'students',
                'reference_column' => 'id'
            ],
            // Enrollments -> Courses
            [
                'table' => 'enrollments',
                'column' => 'course_id',
                'reference_table' => 'courses',
                'reference_column' => 'id'
            ],
            // Grades -> Students
            [
                'table' => 'grades',
                'column' => 'student_id',
                'reference_table' => 'students',
                'reference_column' => 'id'
            ],
            // Grades -> Courses
            [
                'table' => 'grades',
                'column' => 'course_id',
                'reference_table' => 'courses',
                'reference_column' => 'id'
            ],
            // Grades -> Enrollments
            [
                'table' => 'grades',
                'column' => 'enrollment_id',
                'reference_table' => 'enrollments',
                'reference_column' => 'id'
            ]
        ];
        
        foreach ($checks as $check) {
            $orphanedCount = DB::select("
                SELECT COUNT(*) as count
                FROM {$schemaName}.{$check['table']} t
                LEFT JOIN {$schemaName}.{$check['reference_table']} r 
                    ON t.{$check['column']} = r.{$check['reference_column']}
                WHERE r.{$check['reference_column']} IS NULL
                    AND t.{$check['column']} IS NOT NULL
            ")[0]->count;
            
            if ($orphanedCount > 0) {
                $validation['valid'] = false;
                $validation['errors'][] = "Found {$orphanedCount} orphaned records in {$check['table']}.{$check['column']}";
                $validation['orphaned_records'][] = [
                    'table' => $check['table'],
                    'column' => $check['column'],
                    'count' => $orphanedCount
                ];
            }
        }
        
        return $validation;
    }
    
    /**
     * Validate unique constraints
     */
    protected function validateUniqueConstraints(string $schemaName): array
    {
        $validation = [
            'valid' => true,
            'errors' => [],
            'duplicates' => []
        ];
        
        $uniqueChecks = [
            [
                'table' => 'students',
                'columns' => ['email'],
                'description' => 'student email'
            ],
            [
                'table' => 'students',
                'columns' => ['student_id'],
                'description' => 'student ID'
            ],
            [
                'table' => 'courses',
                'columns' => ['course_code'],
                'description' => 'course code'
            ],
            [
                'table' => 'enrollments',
                'columns' => ['student_id', 'course_id', 'semester', 'academic_year'],
                'description' => 'enrollment combination'
            ]
        ];
        
        foreach ($uniqueChecks as $check) {
            $columnList = implode(', ', $check['columns']);
            $groupBy = implode(', ', $check['columns']);
            
            $duplicates = DB::select("
                SELECT {$columnList}, COUNT(*) as count
                FROM {$schemaName}.{$check['table']}
                GROUP BY {$groupBy}
                HAVING COUNT(*) > 1
            ");
            
            if (!empty($duplicates)) {
                $validation['valid'] = false;
                $validation['errors'][] = "Duplicate {$check['description']} found in {$check['table']}";
                $validation['duplicates'][] = [
                    'table' => $check['table'],
                    'description' => $check['description'],
                    'count' => count($duplicates),
                    'examples' => array_slice($duplicates, 0, 5)
                ];
            }
        }
        
        return $validation;
    }
    
    /**
     * Validate data consistency
     */
    protected function validateDataConsistency(string $schemaName): array
    {
        $validation = [
            'valid' => true,
            'errors' => [],
            'warnings' => []
        ];
        
        // Check enrollment-grade consistency
        $inconsistentGrades = DB::select("
            SELECT COUNT(*) as count
            FROM {$schemaName}.grades g
            LEFT JOIN {$schemaName}.enrollments e 
                ON g.enrollment_id = e.id
                AND g.student_id = e.student_id
                AND g.course_id = e.course_id
            WHERE e.id IS NULL
        ")[0]->count;
        
        if ($inconsistentGrades > 0) {
            $validation['valid'] = false;
            $validation['errors'][] = "Found {$inconsistentGrades} grades with inconsistent enrollment data";
        }
        
        // Check for invalid grade percentages
        $invalidGrades = DB::select("
            SELECT COUNT(*) as count
            FROM {$schemaName}.grades
            WHERE percentage < 0 OR percentage > 100
        ")[0]->count;
        
        if ($invalidGrades > 0) {
            $validation['errors'][] = "Found {$invalidGrades} grades with invalid percentages";
        }
        
        // Check for future enrollment dates
        $futureEnrollments = DB::select("
            SELECT COUNT(*) as count
            FROM {$schemaName}.enrollments
            WHERE enrolled_at > NOW()
        ")[0]->count;
        
        if ($futureEnrollments > 0) {
            $validation['warnings'][] = "Found {$futureEnrollments} enrollments with future dates";
        }
        
        return $validation;
    }
    
    /**
     * Validate calculated fields
     */
    protected function validateCalculatedFields(string $schemaName): array
    {
        $validation = [
            'warnings' => []
        ];
        
        // Check grade percentage calculations
        $incorrectPercentages = DB::select("
            SELECT COUNT(*) as count
            FROM {$schemaName}.grades
            WHERE points_possible > 0
                AND ABS(percentage - (points_earned / points_possible * 100)) > 0.01
        ")[0]->count;
        
        if ($incorrectPercentages > 0) {
            $validation['warnings'][] = "Found {$incorrectPercentages} grades with incorrect percentage calculations";
        }
        
        return $validation;
    }
    
    /**
     * Validate relationships between tables
     */
    protected function validateRelationships(Tenant $tenant): array
    {
        $validation = [
            'valid' => true,
            'errors' => [],
            'relationship_counts' => []
        ];
        
        try {
            $schemaName = $tenant->schema_name;
            
            // Validate student-enrollment relationships
            $studentEnrollments = DB::select("
                SELECT 
                    s.id as student_id,
                    COUNT(e.id) as enrollment_count
                FROM {$schemaName}.students s
                LEFT JOIN {$schemaName}.enrollments e ON s.id = e.student_id
                GROUP BY s.id
                HAVING COUNT(e.id) = 0
            ");
            
            $validation['relationship_counts']['students_without_enrollments'] = count($studentEnrollments);
            
            // Validate course-enrollment relationships
            $courseEnrollments = DB::select("
                SELECT 
                    c.id as course_id,
                    COUNT(e.id) as enrollment_count
                FROM {$schemaName}.courses c
                LEFT JOIN {$schemaName}.enrollments e ON c.id = e.course_id
                GROUP BY c.id
                HAVING COUNT(e.id) = 0
            ");
            
            $validation['relationship_counts']['courses_without_enrollments'] = count($courseEnrollments);
            
            // Validate enrollment-grade relationships
            $enrollmentGrades = DB::select("
                SELECT 
                    e.id as enrollment_id,
                    COUNT(g.id) as grade_count
                FROM {$schemaName}.enrollments e
                LEFT JOIN {$schemaName}.grades g ON e.id = g.enrollment_id
                GROUP BY e.id
                HAVING COUNT(g.id) = 0
            ");
            
            $validation['relationship_counts']['enrollments_without_grades'] = count($enrollmentGrades);
            
        } catch (Exception $e) {
            $validation['valid'] = false;
            $validation['errors'][] = 'Relationship validation failed: ' . $e->getMessage();
        }
        
        return $validation;
    }
    
    /**
     * Validate tenant isolation
     */
    protected function validateTenantIsolation(Tenant $tenant): array
    {
        $validation = [
            'valid' => true,
            'errors' => [],
            'isolation_checks' => []
        ];
        
        try {
            $schemaName = $tenant->schema_name;
            
            // Check that schema exists and is isolated
            if (!$this->schemaUtility->schemaExists($schemaName)) {
                $validation['valid'] = false;
                $validation['errors'][] = "Tenant schema '{$schemaName}' does not exist";
                return $validation;
            }
            
            // Verify no cross-tenant data leakage by checking if any records
            // in the schema reference data from other tenants
            $this->tenantService->setTenant($tenant);
            
            // Test tenant context isolation
            $testQuery = DB::table('students')->count();
            $validation['isolation_checks']['can_query_schema'] = $testQuery >= 0;
            
            // Verify search path is correctly set
            $searchPath = DB::select("SHOW search_path")[0]->search_path;
            $validation['isolation_checks']['search_path'] = $searchPath;
            $validation['isolation_checks']['schema_in_path'] = strpos($searchPath, $schemaName) !== false;
            
            if (!$validation['isolation_checks']['schema_in_path']) {
                $validation['valid'] = false;
                $validation['errors'][] = "Schema '{$schemaName}' not found in search path: {$searchPath}";
            }
            
        } catch (Exception $e) {
            $validation['valid'] = false;
            $validation['errors'][] = 'Tenant isolation validation failed: ' . $e->getMessage();
        } finally {
            $this->tenantService->clearTenant();
        }
        
        return $validation;
    }
    
    /**
     * Validate performance metrics
     */
    protected function validatePerformance(Tenant $tenant): array
    {
        $validation = [
            'warnings' => [],
            'metrics' => []
        ];
        
        try {
            $schemaName = $tenant->schema_name;
            
            // Get schema statistics
            $stats = $this->schemaUtility->getSchemaStatistics($schemaName);
            $validation['metrics']['schema_stats'] = $stats;
            
            // Check for performance issues
            if ($stats['total_size_bytes'] > 1024 * 1024 * 1024) { // 1GB
                $validation['warnings'][] = "Large schema size: {$stats['total_size_human']}";
            }
            
            // Test query performance
            $startTime = microtime(true);
            $this->tenantService->setTenant($tenant);
            
            // Simple query performance test
            DB::table('students')->limit(100)->get();
            
            $queryTime = (microtime(true) - $startTime) * 1000; // Convert to milliseconds
            $validation['metrics']['sample_query_time_ms'] = round($queryTime, 2);
            
            if ($queryTime > 1000) { // More than 1 second
                $validation['warnings'][] = "Slow query performance: {$queryTime}ms";
            }
            
        } catch (Exception $e) {
            $validation['warnings'][] = 'Performance validation failed: ' . $e->getMessage();
        } finally {
            $this->tenantService->clearTenant();
        }
        
        return $validation;
    }
    
    /**
     * Generate validation report
     */
    public function generateValidationReport(array $validationResults): string
    {
        $report = "# Tenant Migration Validation Report\n\n";
        $report .= "Generated: " . now()->toDateTimeString() . "\n\n";
        
        $totalTenants = count($validationResults);
        $passedTenants = count(array_filter($validationResults, fn($r) => $r['overall_status'] === 'passed'));
        $failedTenants = $totalTenants - $passedTenants;
        
        $report .= "## Summary\n";
        $report .= "- Total Tenants: {$totalTenants}\n";
        $report .= "- Passed: {$passedTenants}\n";
        $report .= "- Failed: {$failedTenants}\n\n";
        
        foreach ($validationResults as $result) {
            $report .= "## Tenant: {$result['tenant_name']} (ID: {$result['tenant_id']})\n";
            $report .= "**Status:** {$result['overall_status']}\n";
            $report .= "**Schema:** {$result['schema_name']}\n\n";
            
            if (!empty($result['errors'])) {
                $report .= "### Errors\n";
                foreach ($result['errors'] as $error) {
                    $report .= "- {$error}\n";
                }
                $report .= "\n";
            }
            
            if (!empty($result['warnings'])) {
                $report .= "### Warnings\n";
                foreach ($result['warnings'] as $warning) {
                    $report .= "- {$warning}\n";
                }
                $report .= "\n";
            }
            
            if (isset($result['validations']['data_migration']['record_counts'])) {
                $report .= "### Record Counts\n";
                foreach ($result['validations']['data_migration']['record_counts'] as $table => $counts) {
                    $report .= "- {$table}: {$counts['old_count']} → {$counts['new_count']}";
                    if ($counts['difference'] !== 0) {
                        $report .= " (" . ($counts['difference'] > 0 ? '+' : '') . "{$counts['difference']})";
                    }
                    $report .= "\n";
                }
                $report .= "\n";
            }
        }
        
        return $report;
    }
}