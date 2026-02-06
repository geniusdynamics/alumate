<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Services\TenantContextService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

/**
 * Analytics Data Validation Service
 *
 * Provides comprehensive data validation for analytics data including
 * event data, session data, user data, metrics data, cohort data,
 * attribution data, custom events, and batch data validation.
 * Implements tenant isolation for secure multi-tenant operations.
 */
class AnalyticsDataValidationService
{
    private const MAX_STRING_LENGTH = 65535;
    private const MAX_ARRAY_SIZE = 1000;
    private const MIN_TIMESTAMP_AGE_DAYS = 365;
    private const MAX_TIMESTAMP_FUTURE_DAYS = 1;

    private TenantContextService $tenantContextService;

    /**
     * Validation error types
     */
    public const ERROR_TYPE_REQUIRED = 'required';
    public const ERROR_TYPE_FORMAT = 'format';
    public const ERROR_TYPE_RANGE = 'range';
    public const ERROR_TYPE_LENGTH = 'length';
    public const ERROR_TYPE_TYPE = 'type';
    public const ERROR_TYPE_UNIQUE = 'unique';
    public const ERROR_TYPE_TENANT = 'tenant';
    public const ERROR_TYPE_UNKNOWN = 'unknown';

    /**
     * Validation severity levels
     */
    public const SEVERITY_CRITICAL = 'critical';
    public const SEVERITY_HIGH = 'high';
    public const SEVERITY_MEDIUM = 'medium';
    public const SEVERITY_LOW = 'low';
    public const SEVERITY_INFO = 'info';

    /**
     * Data quality scores
     */
    public const QUALITY_EXCELLENT = 100;
    public const QUALITY_GOOD = 80;
    public const QUALITY_FAIR = 60;
    public const QUALITY_POOR = 40;
    public const QUALITY_CRITICAL = 0;

    /**
     * @param TenantContextService $tenantContextService
     */
    public function __construct(TenantContextService $tenantContextService)
    {
        $this->tenantContextService = $tenantContextService;
    }

    /**
     * Validate analytics event data
     *
     * @param array $event Event data to validate
     * @return array Validation result with errors and quality score
     */
    public function validateEventData(array $event): array
    {
        $errors = [];
        $checks = [];
        $score = self::QUALITY_EXCELLENT;

        // Required fields check
        $requiredFields = ['event_name', 'user_id', 'occurred_at'];
        foreach ($requiredFields as $field) {
            if (!isset($event[$field])) {
                $errors[] = $this->createError(
                    self::ERROR_TYPE_REQUIRED,
                    $field,
                    "Field '{$field}' is required",
                    self::SEVERITY_CRITICAL
                );
                $score -= 20;
                $checks[$field] = false;
            } else {
                $checks[$field] = true;
            }
        }

        // Validate event_name
        if (isset($event['event_name'])) {
            if (!is_string($event['event_name'])) {
                $errors[] = $this->createError(
                    self::ERROR_TYPE_TYPE,
                    'event_name',
                    'Event name must be a string',
                    self::SEVERITY_HIGH
                );
                $score -= 15;
            } elseif (strlen($event['event_name']) > 255) {
                $errors[] = $this->createError(
                    self::ERROR_TYPE_LENGTH,
                    'event_name',
                    'Event name exceeds maximum length of 255 characters',
                    self::SEVERITY_MEDIUM
                );
                $score -= 5;
            } elseif (!preg_match('/^[a-z][a-z0-9_]*$/', $event['event_name'])) {
                $errors[] = $this->createError(
                    self::ERROR_TYPE_FORMAT,
                    'event_name',
                    'Event name must be lowercase alphanumeric with underscores',
                    self::SEVERITY_MEDIUM
                );
                $score -= 10;
            }
        }

        // Validate user_id
        if (isset($event['user_id'])) {
            if (!is_int($event['user_id']) && !is_string($event['user_id'])) {
                $errors[] = $this->createError(
                    self::ERROR_TYPE_TYPE,
                    'user_id',
                    'User ID must be an integer or string',
                    self::SEVERITY_HIGH
                );
                $score -= 15;
            }
        }

        // Validate occurred_at timestamp
        if (isset($event['occurred_at'])) {
            $timestampCheck = $this->validateTimestamp($event['occurred_at']);
            if (!$timestampCheck['valid']) {
                $errors[] = $this->createError(
                    self::ERROR_TYPE_FORMAT,
                    'occurred_at',
                    $timestampCheck['message'],
                    self::SEVERITY_HIGH
                );
                $score -= 15;
            }
        }

        // Validate session_id if present
        if (isset($event['session_id'])) {
            if (!is_string($event['session_id']) || strlen($event['session_id']) > 255) {
                $errors[] = $this->createError(
                    self::ERROR_TYPE_FORMAT,
                    'session_id',
                    'Session ID must be a string with max 255 characters',
                    self::SEVERITY_MEDIUM
                );
                $score -= 5;
            }
        }

        // Validate properties if present
        if (isset($event['properties']) && !is_array($event['properties'])) {
            $errors[] = $this->createError(
                self::ERROR_TYPE_TYPE,
                'properties',
                'Properties must be an array',
                self::SEVERITY_HIGH
            );
            $score -= 10;
        } elseif (isset($event['properties'])) {
            $propertiesCheck = $this->validateProperties($event['properties']);
            $errors = array_merge($errors, $propertiesCheck['errors']);
            $score -= $propertiesCheck['score_deduction'];
        }

        // Validate tenant_id for isolation
        if (isset($event['tenant_id'])) {
            $tenantCheck = $this->validateTenantId($event['tenant_id']);
            if (!$tenantCheck['valid']) {
                $errors[] = $this->createError(
                    self::ERROR_TYPE_TENANT,
                    'tenant_id',
                    $tenantCheck['message'],
                    self::SEVERITY_CRITICAL
                );
                $score -= 25;
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'quality_score' => max(0, $score),
            'checks' => $checks,
            'validated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Validate session data
     *
     * @param array $session Session data to validate
     * @return array Validation result with errors and quality score
     */
    public function validateSessionData(array $session): array
    {
        $errors = [];
        $checks = [];
        $score = self::QUALITY_EXCELLENT;

        // Required fields check
        $requiredFields = ['session_id', 'user_id', 'start_time'];
        foreach ($requiredFields as $field) {
            if (!isset($session[$field])) {
                $errors[] = $this->createError(
                    self::ERROR_TYPE_REQUIRED,
                    $field,
                    "Field '{$field}' is required",
                    self::SEVERITY_CRITICAL
                );
                $score -= 20;
                $checks[$field] = false;
            } else {
                $checks[$field] = true;
            }
        }

        // Validate session_id
        if (isset($session['session_id'])) {
            if (!is_string($session['session_id']) || strlen($session['session_id']) > 255) {
                $errors[] = $this->createError(
                    self::ERROR_TYPE_FORMAT,
                    'session_id',
                    'Session ID must be a string with max 255 characters',
                    self::SEVERITY_HIGH
                );
                $score -= 15;
            }
        }

        // Validate user_id
        if (isset($session['user_id'])) {
            if (!is_int($session['user_id']) && !is_string($session['user_id'])) {
                $errors[] = $this->createError(
                    self::ERROR_TYPE_TYPE,
                    'user_id',
                    'User ID must be an integer or string',
                    self::SEVERITY_HIGH
                );
                $score -= 15;
            }
        }

        // Validate start_time
        if (isset($session['start_time'])) {
            $timestampCheck = $this->validateTimestamp($session['start_time']);
            if (!$timestampCheck['valid']) {
                $errors[] = $this->createError(
                    self::ERROR_TYPE_FORMAT,
                    'start_time',
                    $timestampCheck['message'],
                    self::SEVERITY_HIGH
                );
                $score -= 15;
            }
        }

        // Validate end_time if present and session is complete
        if (isset($session['end_time'])) {
            $timestampCheck = $this->validateTimestamp($session['end_time']);
            if (!$timestampCheck['valid']) {
                $errors[] = $this->createError(
                    self::ERROR_TYPE_FORMAT,
                    'end_time',
                    $timestampCheck['message'],
                    self::SEVERITY_HIGH
                );
                $score -= 10;
            } elseif (isset($session['start_time'])) {
                $startTime = Carbon::parse($session['start_time']);
                $endTime = Carbon::parse($session['end_time']);
                if ($endTime->lessThan($startTime)) {
                    $errors[] = $this->createError(
                        self::ERROR_TYPE_RANGE,
                        'end_time',
                        'End time cannot be before start time',
                        self::SEVERITY_CRITICAL
                    );
                    $score -= 20;
                }
            }
        }

        // Validate duration if present
        if (isset($session['duration_seconds'])) {
            if (!is_numeric($session['duration_seconds']) || $session['duration_seconds'] < 0) {
                $errors[] = $this->createError(
                    self::ERROR_TYPE_RANGE,
                    'duration_seconds',
                    'Duration must be a non-negative number',
                    self::SEVERITY_MEDIUM
                );
                $score -= 10;
            } elseif ($session['duration_seconds'] > 86400) {
                $errors[] = $this->createError(
                    self::ERROR_TYPE_RANGE,
                    'duration_seconds',
                    'Duration exceeds maximum of 24 hours',
                    self::SEVERITY_LOW
                );
                $score -= 5;
            }
        }

        // Validate page_count if present
        if (isset($session['page_count'])) {
            if (!is_int($session['page_count']) || $session['page_count'] < 0) {
                $errors[] = $this->createError(
                    self::ERROR_TYPE_RANGE,
                    'page_count',
                    'Page count must be a non-negative integer',
                    self::SEVERITY_LOW
                );
                $score -= 5;
            } elseif ($session['page_count'] > 1000) {
                $errors[] = $this->createError(
                    self::ERROR_TYPE_RANGE,
                    'page_count',
                    'Page count exceeds maximum of 1000',
                    self::SEVERITY_LOW
                );
                $score -= 2;
            }
        }

        // Validate tenant_id for isolation
        if (isset($session['tenant_id'])) {
            $tenantCheck = $this->validateTenantId($session['tenant_id']);
            if (!$tenantCheck['valid']) {
                $errors[] = $this->createError(
                    self::ERROR_TYPE_TENANT,
                    'tenant_id',
                    $tenantCheck['message'],
                    self::SEVERITY_CRITICAL
                );
                $score -= 25;
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'quality_score' => max(0, $score),
            'checks' => $checks,
            'validated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Validate user data for analytics
     *
     * @param array $user User data to validate
     * @return array Validation result with errors and quality score
     */
    public function validateUserData(array $user): array
    {
        $errors = [];
        $checks = [];
        $score = self::QUALITY_EXCELLENT;

        // Required fields check
        $requiredFields = ['id'];
        foreach ($requiredFields as $field) {
            if (!isset($user[$field])) {
                $errors[] = $this->createError(
                    self::ERROR_TYPE_REQUIRED,
                    $field,
                    "Field '{$field}' is required",
                    self::SEVERITY_CRITICAL
                );
                $score -= 20;
                $checks[$field] = false;
            } else {
                $checks[$field] = true;
            }
        }

        // Validate id
        if (isset($user['id'])) {
            if (!is_int($user['id']) && !is_string($user['id'])) {
                $errors[] = $this->createError(
                    self::ERROR_TYPE_TYPE,
                    'id',
                    'User ID must be an integer or string',
                    self::SEVERITY_HIGH
                );
                $score -= 15;
            }
        }

        // Validate email if present
        if (isset($user['email'])) {
            if (!filter_var($user['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = $this->createError(
                    self::ERROR_TYPE_FORMAT,
                    'email',
                    'Invalid email format',
                    self::SEVERITY_HIGH
                );
                $score -= 10;
            }
        }

        // Validate graduation_year if present
        if (isset($user['graduation_year'])) {
            $currentYear = (int) date('Y');
            if (!is_int($user['graduation_year']) || 
                $user['graduation_year'] < 1900 || 
                $user['graduation_year'] > (int) ($currentYear + 10)) {
                $maxYear = $currentYear + 10;
                $errors[] = $this->createError(
                    self::ERROR_TYPE_RANGE,
                    'graduation_year',
                    "Graduation year must be between 1900 and {$maxYear}",
                    self::SEVERITY_MEDIUM
                );
                $score -= 10;
            }
        }

        // Validate tenant_id for isolation
        if (isset($user['tenant_id'])) {
            $tenantCheck = $this->validateTenantId($user['tenant_id']);
            if (!$tenantCheck['valid']) {
                $errors[] = $this->createError(
                    self::ERROR_TYPE_TENANT,
                    'tenant_id',
                    $tenantCheck['message'],
                    self::SEVERITY_CRITICAL
                );
                $score -= 25;
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'quality_score' => max(0, $score),
            'checks' => $checks,
            'validated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Validate metrics data
     *
     * @param array $metrics Metrics data to validate
     * @return array Validation result with errors and quality score
     */
    public function validateMetricsData(array $metrics): array
    {
        $errors = [];
        $checks = [];
        $score = self::QUALITY_EXCELLENT;

        // Validate period if present
        if (isset($metrics['period'])) {
            if (!is_array($metrics['period']) || 
                !isset($metrics['period']['start']) || 
                !isset($metrics['period']['end'])) {
                $errors[] = $this->createError(
                    self::ERROR_TYPE_FORMAT,
                    'period',
                    'Period must be an array with start and end keys',
                    self::SEVERITY_HIGH
                );
                $score -= 15;
            } else {
                $startCheck = $this->validateTimestamp($metrics['period']['start']);
                $endCheck = $this->validateTimestamp($metrics['period']['end']);
                
                if (!$startCheck['valid']) {
                    $errors[] = $this->createError(
                        self::ERROR_TYPE_FORMAT,
                        'period.start',
                        $startCheck['message'],
                        self::SEVERITY_HIGH
                    );
                    $score -= 10;
                }
                
                if (!$endCheck['valid']) {
                    $errors[] = $this->createError(
                        self::ERROR_TYPE_FORMAT,
                        'period.end',
                        $endCheck['message'],
                        self::SEVERITY_HIGH
                    );
                    $score -= 10;
                } elseif ($startCheck['valid']) {
                    $start = Carbon::parse($metrics['period']['start']);
                    $end = Carbon::parse($metrics['period']['end']);
                    if ($end->lessThan($start)) {
                        $errors[] = $this->createError(
                            self::ERROR_TYPE_RANGE,
                            'period',
                            'End date cannot be before start date',
                            self::SEVERITY_CRITICAL
                        );
                        $score -= 20;
                    }
                }
            }
        }

        // Validate numeric metrics
        $numericMetrics = ['page_views', 'unique_visitors', 'sessions', 'bounce_rate', 'avg_session_duration'];
        foreach ($numericMetrics as $metric) {
            if (isset($metrics[$metric])) {
                if (!is_numeric($metrics[$metric])) {
                    $errors[] = $this->createError(
                        self::ERROR_TYPE_TYPE,
                        $metric,
                        "{$metric} must be numeric",
                        self::SEVERITY_MEDIUM
                    );
                    $score -= 5;
                } elseif ($metrics[$metric] < 0 && $metric !== 'bounce_rate') {
                    $errors[] = $this->createError(
                        self::ERROR_TYPE_RANGE,
                        $metric,
                        "{$metric} cannot be negative",
                        self::SEVERITY_MEDIUM
                    );
                    $score -= 5;
                }
            }
        }

        // Validate bounce_rate specifically
        if (isset($metrics['bounce_rate'])) {
            if ($metrics['bounce_rate'] < 0 || $metrics['bounce_rate'] > 100) {
                $errors[] = $this->createError(
                    self::ERROR_TYPE_RANGE,
                    'bounce_rate',
                    'Bounce rate must be between 0 and 100',
                    self::SEVERITY_MEDIUM
                );
                $score -= 10;
            }
        }

        // Validate tenant_id for isolation
        if (isset($metrics['tenant_id'])) {
            $tenantCheck = $this->validateTenantId($metrics['tenant_id']);
            if (!$tenantCheck['valid']) {
                $errors[] = $this->createError(
                    self::ERROR_TYPE_TENANT,
                    'tenant_id',
                    $tenantCheck['message'],
                    self::SEVERITY_CRITICAL
                );
                $score -= 25;
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'quality_score' => max(0, $score),
            'checks' => $checks,
            'validated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Validate cohort data
     *
     * @param array $cohort Cohort data to validate
     * @return array Validation result with errors and quality score
     */
    public function validateCohortData(array $cohort): array
    {
        $errors = [];
        $checks = [];
        $score = self::QUALITY_EXCELLENT;

        // Required fields check
        $requiredFields = ['name'];
        foreach ($requiredFields as $field) {
            if (!isset($cohort[$field])) {
                $errors[] = $this->createError(
                    self::ERROR_TYPE_REQUIRED,
                    $field,
                    "Field '{$field}' is required",
                    self::SEVERITY_CRITICAL
                );
                $score -= 20;
                $checks[$field] = false;
            } else {
                $checks[$field] = true;
            }
        }

        // Validate name
        if (isset($cohort['name'])) {
            if (!is_string($cohort['name'])) {
                $errors[] = $this->createError(
                    self::ERROR_TYPE_TYPE,
                    'name',
                    'Cohort name must be a string',
                    self::SEVERITY_HIGH
                );
                $score -= 10;
            } elseif (strlen($cohort['name']) > 255) {
                $errors[] = $this->createError(
                    self::ERROR_TYPE_LENGTH,
                    'name',
                    'Cohort name exceeds maximum length of 255 characters',
                    self::SEVERITY_LOW
                );
                $score -= 5;
            }
        }

        // Validate criteria if present
        if (isset($cohort['criteria']) && !is_array($cohort['criteria'])) {
            $errors[] = $this->createError(
                self::ERROR_TYPE_TYPE,
                'criteria',
                'Criteria must be an array',
                self::SEVERITY_HIGH
            );
            $score -= 15;
        }

        // Validate members_count if present
        if (isset($cohort['members_count'])) {
            if (!is_int($cohort['members_count']) || $cohort['members_count'] < 0) {
                $errors[] = $this->createError(
                    self::ERROR_TYPE_RANGE,
                    'members_count',
                    'Members count must be a non-negative integer',
                    self::SEVERITY_MEDIUM
                );
                $score -= 10;
            }
        }

        // Validate acquisition_date if present
        if (isset($cohort['acquisition_date'])) {
            $timestampCheck = $this->validateTimestamp($cohort['acquisition_date']);
            if (!$timestampCheck['valid']) {
                $errors[] = $this->createError(
                    self::ERROR_TYPE_FORMAT,
                    'acquisition_date',
                    $timestampCheck['message'],
                    self::SEVERITY_MEDIUM
                );
                $score -= 5;
            }
        }

        // Validate tenant_id for isolation
        if (isset($cohort['tenant_id'])) {
            $tenantCheck = $this->validateTenantId($cohort['tenant_id']);
            if (!$tenantCheck['valid']) {
                $errors[] = $this->createError(
                    self::ERROR_TYPE_TENANT,
                    'tenant_id',
                    $tenantCheck['message'],
                    self::SEVERITY_CRITICAL
                );
                $score -= 25;
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'quality_score' => max(0, $score),
            'checks' => $checks,
            'validated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Validate attribution data
     *
     * @param array $attribution Attribution data to validate
     * @return array Validation result with errors and quality score
     */
    public function validateAttributionData(array $attribution): array
    {
        $errors = [];
        $checks = [];
        $score = self::QUALITY_EXCELLENT;

        // Required fields check
        $requiredFields = ['user_id', 'source', 'timestamp'];
        foreach ($requiredFields as $field) {
            if (!isset($attribution[$field])) {
                $errors[] = $this->createError(
                    self::ERROR_TYPE_REQUIRED,
                    $field,
                    "Field '{$field}' is required",
                    self::SEVERITY_CRITICAL
                );
                $score -= 20;
                $checks[$field] = false;
            } else {
                $checks[$field] = true;
            }
        }

        // Validate user_id
        if (isset($attribution['user_id'])) {
            if (!is_int($attribution['user_id']) && !is_string($attribution['user_id'])) {
                $errors[] = $this->createError(
                    self::ERROR_TYPE_TYPE,
                    'user_id',
                    'User ID must be an integer or string',
                    self::SEVERITY_HIGH
                );
                $score -= 15;
            }
        }

        // Validate source
        if (isset($attribution['source'])) {
            if (!is_string($attribution['source'])) {
                $errors[] = $this->createError(
                    self::ERROR_TYPE_TYPE,
                    'source',
                    'Source must be a string',
                    self::SEVERITY_HIGH
                );
                $score -= 10;
            } elseif (strlen($attribution['source']) > 255) {
                $errors[] = $this->createError(
                    self::ERROR_TYPE_LENGTH,
                    'source',
                    'Source exceeds maximum length of 255 characters',
                    self::SEVERITY_LOW
                );
                $score -= 5;
            }
        }

        // Validate timestamp
        if (isset($attribution['timestamp'])) {
            $timestampCheck = $this->validateTimestamp($attribution['timestamp']);
            if (!$timestampCheck['valid']) {
                $errors[] = $this->createError(
                    self::ERROR_TYPE_FORMAT,
                    'timestamp',
                    $timestampCheck['message'],
                    self::SEVERITY_HIGH
                );
                $score -= 15;
            }
        }

        // Validate event_type if present
        if (isset($attribution['event_type'])) {
            $validEventTypes = ['page_view', 'click', 'form_submit', 'purchase', 'signup', 'login'];
            if (!in_array($attribution['event_type'], $validEventTypes)) {
                $errors[] = $this->createError(
                    self::ERROR_TYPE_FORMAT,
                    'event_type',
                    'Invalid event type. Must be one of: ' . implode(', ', $validEventTypes),
                    self::SEVERITY_MEDIUM
                );
                $score -= 10;
            }
        }

        // Validate value if present
        if (isset($attribution['value'])) {
            if (!is_numeric($attribution['value'])) {
                $errors[] = $this->createError(
                    self::ERROR_TYPE_TYPE,
                    'value',
                    'Value must be numeric',
                    self::SEVERITY_MEDIUM
                );
                $score -= 10;
            } elseif ($attribution['value'] < 0) {
                $errors[] = $this->createError(
                    self::ERROR_TYPE_RANGE,
                    'value',
                    'Value cannot be negative',
                    self::SEVERITY_MEDIUM
                );
                $score -= 10;
            }
        }

        // Validate tenant_id for isolation
        if (isset($attribution['tenant_id'])) {
            $tenantCheck = $this->validateTenantId($attribution['tenant_id']);
            if (!$tenantCheck['valid']) {
                $errors[] = $this->createError(
                    self::ERROR_TYPE_TENANT,
                    'tenant_id',
                    $tenantCheck['message'],
                    self::SEVERITY_CRITICAL
                );
                $score -= 25;
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'quality_score' => max(0, $score),
            'checks' => $checks,
            'validated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Validate custom event data
     *
     * @param array $customEvent Custom event data to validate
     * @return array Validation result with errors and quality score
     */
    public function validateCustomEventData(array $customEvent): array
    {
        $errors = [];
        $checks = [];
        $score = self::QUALITY_EXCELLENT;

        // Required fields check
        $requiredFields = ['name', 'user_id'];
        foreach ($requiredFields as $field) {
            if (!isset($customEvent[$field])) {
                $errors[] = $this->createError(
                    self::ERROR_TYPE_REQUIRED,
                    $field,
                    "Field '{$field}' is required",
                    self::SEVERITY_CRITICAL
                );
                $score -= 20;
                $checks[$field] = false;
            } else {
                $checks[$field] = true;
            }
        }

        // Validate name
        if (isset($customEvent['name'])) {
            if (!is_string($customEvent['name'])) {
                $errors[] = $this->createError(
                    self::ERROR_TYPE_TYPE,
                    'name',
                    'Event name must be a string',
                    self::SEVERITY_HIGH
                );
                $score -= 10;
            } elseif (strlen($customEvent['name']) > 255) {
                $errors[] = $this->createError(
                    self::ERROR_TYPE_LENGTH,
                    'name',
                    'Event name exceeds maximum length of 255 characters',
                    self::SEVERITY_MEDIUM
                );
                $score -= 5;
            }
        }

        // Validate user_id
        if (isset($customEvent['user_id'])) {
            if (!is_int($customEvent['user_id']) && !is_string($customEvent['user_id'])) {
                $errors[] = $this->createError(
                    self::ERROR_TYPE_TYPE,
                    'user_id',
                    'User ID must be an integer or string',
                    self::SEVERITY_HIGH
                );
                $score -= 15;
            }
        }

        // Validate definition_id if present
        if (isset($customEvent['definition_id'])) {
            if (!is_int($customEvent['definition_id']) || $customEvent['definition_id'] <= 0) {
                $errors[] = $this->createError(
                    self::ERROR_TYPE_RANGE,
                    'definition_id',
                    'Definition ID must be a positive integer',
                    self::SEVERITY_HIGH
                );
                $score -= 10;
            }
        }

        // Validate data_json if present
        if (isset($customEvent['data_json']) && !is_array($customEvent['data_json'])) {
            $errors[] = $this->createError(
                self::ERROR_TYPE_TYPE,
                'data_json',
                'Data must be an array',
                self::SEVERITY_MEDIUM
            );
            $score -= 5;
        }

        // Validate tenant_id for isolation
        if (isset($customEvent['tenant_id'])) {
            $tenantCheck = $this->validateTenantId($customEvent['tenant_id']);
            if (!$tenantCheck['valid']) {
                $errors[] = $this->createError(
                    self::ERROR_TYPE_TENANT,
                    'tenant_id',
                    $tenantCheck['message'],
                    self::SEVERITY_CRITICAL
                );
                $score -= 25;
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'quality_score' => max(0, $score),
            'checks' => $checks,
            'validated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Validate batch data
     *
     * @param array $data Batch data to validate
     * @return array Validation result with errors and quality score
     */
    public function validateBatchData(array $data): array
    {
        $errors = [];
        $checks = [];
        $score = self::QUALITY_EXCELLENT;

        // Check if data is an array
        if (!is_array($data)) {
            $errors[] = $this->createError(
                self::ERROR_TYPE_TYPE,
                'data',
                'Batch data must be an array',
                self::SEVERITY_CRITICAL
            );
            $score -= 30;
            
            return [
                'valid' => false,
                'errors' => $errors,
                'quality_score' => 0,
                'checks' => $checks,
                'validated_at' => now()->toIso8601String(),
            ];
        }

        // Check batch size
        $batchSize = count($data);
        if ($batchSize === 0) {
            $errors[] = $this->createError(
                self::ERROR_TYPE_RANGE,
                'data',
                'Batch data cannot be empty',
                self::SEVERITY_HIGH
            );
            $score -= 20;
        } elseif ($batchSize > self::MAX_ARRAY_SIZE) {
            $errors[] = $this->createError(
                self::ERROR_TYPE_RANGE,
                'data',
                "Batch size exceeds maximum of " . self::MAX_ARRAY_SIZE . " records",
                self::SEVERITY_HIGH
            );
            $score -= 15;
        }

        // Validate each item in batch
        $itemErrors = [];
        $totalScore = $score;
        $validCount = 0;

        foreach ($data as $index => $item) {
            if (!is_array($item)) {
                $itemErrors[] = [
                    'index' => $index,
                    'errors' => [$this->createError(
                        self::ERROR_TYPE_TYPE,
                        'item',
                        'Each batch item must be an array',
                        self::SEVERITY_CRITICAL
                    )],
                ];
                $totalScore -= 10;
                continue;
            }

            // Detect item type and validate accordingly
            $itemValidation = $this->detectAndValidateItem($item);
            
            if (!$itemValidation['valid']) {
                $itemErrors[] = [
                    'index' => $index,
                    'errors' => $itemValidation['errors'],
                ];
                $totalScore -= $itemValidation['score_deduction'];
            } else {
                $validCount++;
                $totalScore += $itemValidation['quality_score'];
            }
        }

        $checks['batch_size'] = $batchSize;
        $checks['valid_items'] = $validCount;
        $checks['invalid_items'] = count($itemErrors);

        // Add item errors to main errors
        foreach ($itemErrors as $itemError) {
            $errors[] = [
                'type' => 'item_validation',
                'index' => $itemError['index'],
                'errors' => $itemError['errors'],
            ];
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'quality_score' => max(0, min(100, $totalScore / max(1, $batchSize))),
            'checks' => $checks,
            'batch_size' => $batchSize,
            'valid_items' => $validCount,
            'invalid_items' => count($itemErrors),
            'validated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Get validation report for analytics data quality
     *
     * @param string $dataType Type of data to generate report for
     * @param array $filters Optional filters to apply
     * @return array Validation report
     */
    public function getValidationReport(string $dataType, array $filters = []): array
    {
        $tenantId = $this->tenantContextService->getCurrentTenantId();
        
        $report = [
            'data_type' => $dataType,
            'tenant_id' => $tenantId,
            'generated_at' => now()->toIso8601String(),
            'period' => $filters['period'] ?? null,
            'summary' => [
                'total_records' => 0,
                'valid_records' => 0,
                'invalid_records' => 0,
                'quality_score' => 0,
                'critical_errors' => 0,
                'high_errors' => 0,
                'medium_errors' => 0,
                'low_errors' => 0,
            ],
            'error_breakdown' => [],
            'recommendations' => [],
        ];

        try {
            // This would typically fetch data from database based on filters
            // For now, return the structure
            return $report;
        } catch (Exception $e) {
            Log::error('Failed to generate validation report', [
                'data_type' => $dataType,
                'filters' => $filters,
                'error' => $e->getMessage(),
            ]);
            
            return $report;
        }
    }

    /**
     * Fix validation errors automatically where possible
     *
     * @param array $errors Validation errors to fix
     * @param array $data Original data
     * @return array Fixed data with report
     */
    public function fixValidationErrors(array $errors, array $data): array
    {
        $fixedData = $data;
        $fixes = [];
        $failedFixes = [];

        foreach ($errors as $index => $error) {
            if (isset($error['field'])) {
                $field = $error['field'];
                $type = $error['type'] ?? self::ERROR_TYPE_UNKNOWN;
                
                switch ($type) {
                    case self::ERROR_TYPE_LENGTH:
                        if (isset($fixedData[$field]) && is_string($fixedData[$field])) {
                            $fixedData[$field] = substr($fixedData[$field], 0, 255);
                            $fixes[] = [
                                'field' => $field,
                                'type' => 'truncated',
                                'original_length' => strlen($data[$field] ?? ''),
                                'new_length' => strlen($fixedData[$field]),
                            ];
                        }
                        break;
                        
                    case self::ERROR_TYPE_FORMAT:
                        if ($field === 'email' && isset($fixedData[$field])) {
                            $fixedData[$field] = filter_var($fixedData[$field], FILTER_SANITIZE_EMAIL);
                            $fixes[] = [
                                'field' => $field,
                                'type' => 'sanitized',
                            ];
                        }
                        break;
                        
                    case self::ERROR_TYPE_RANGE:
                        if (isset($fixedData[$field]) && is_numeric($fixedData[$field])) {
                            if ($fixedData[$field] < 0) {
                                $fixedData[$field] = 0;
                                $fixes[] = [
                                    'field' => $field,
                                    'type' => 'corrected_negative',
                                    'original' => $data[$field],
                                    'corrected' => 0,
                                ];
                            }
                        }
                        break;
                        
                    default:
                        $failedFixes[] = [
                            'field' => $field,
                            'type' => $type,
                            'reason' => 'Cannot automatically fix this error type',
                        ];
                        break;
                }
            }
        }

        return [
            'fixed_data' => $fixedData,
            'fixes_applied' => count($fixes),
            'failed_fixes' => $failedFixes,
            'fix_details' => $fixes,
            'fixed_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Validate a timestamp
     *
     * @param mixed $timestamp
     * @return array Validation result
     */
    private function validateTimestamp($timestamp): array
    {
        try {
            $date = Carbon::parse($timestamp);
            $now = now();
            $minDate = $now->copy()->subDays(self::MIN_TIMESTAMP_AGE_DAYS);
            $maxDate = $now->copy()->addDays(self::MAX_TIMESTAMP_FUTURE_DAYS);
            
            if ($date->lessThan($minDate)) {
                return [
                    'valid' => false,
                    'message' => 'Timestamp is too old (older than ' . self::MIN_TIMESTAMP_AGE_DAYS . ' days)',
                ];
            }
            
            if ($date->greaterThan($maxDate)) {
                return [
                    'valid' => false,
                    'message' => 'Timestamp is in the future',
                ];
            }
            
            return ['valid' => true];
        } catch (\Exception $e) {
            return [
                'valid' => false,
                'message' => 'Invalid timestamp format',
            ];
        }
    }

    /**
     * Validate properties array
     *
     * @param array $properties
     * @return array Validation result with errors and score deduction
     */
    private function validateProperties(array $properties): array
    {
        $errors = [];
        $scoreDeduction = 0;
        
        if (count($properties) > 100) {
            $errors[] = $this->createError(
                self::ERROR_TYPE_LENGTH,
                'properties',
                'Properties array exceeds maximum size of 100 items',
                self::SEVERITY_LOW
            );
            $scoreDeduction += 5;
        }
        
        foreach ($properties as $key => $value) {
            if (!is_string($key) || strlen($key) > 255) {
                $errors[] = $this->createError(
                    self::ERROR_TYPE_LENGTH,
                    "properties[{$key}]",
                    'Property key must be a string with max 255 characters',
                    self::SEVERITY_LOW
                );
                $scoreDeduction += 2;
            }
        }
        
        return [
            'errors' => $errors,
            'score_deduction' => $scoreDeduction,
        ];
    }

    /**
     * Validate tenant ID
     *
     * @param string $tenantId
     * @return array Validation result
     */
    private function validateTenantId(string $tenantId): array
    {
        $currentTenantId = $this->tenantContextService->getCurrentTenantId();
        
        if ($currentTenantId && $tenantId !== $currentTenantId) {
            return [
                'valid' => false,
                'message' => 'Tenant ID mismatch - data belongs to a different tenant',
            ];
        }
        
        return ['valid' => true];
    }

    /**
     * Detect item type and validate accordingly
     *
     * @param array $item
     * @return array Validation result
     */
    private function detectAndValidateItem(array $item): array
    {
        // Check if it's an event
        if (isset($item['event_name']) || isset($item['occurred_at'])) {
            return $this->validateEventData($item);
        }
        
        // Check if it's a session
        if (isset($item['session_id']) || isset($item['start_time'])) {
            return $this->validateSessionData($item);
        }
        
        // Check if it's a user
        if (isset($item['email']) || (isset($item['id']) && count($item) <= 5)) {
            return $this->validateUserData($item);
        }
        
        // Check if it's custom event
        if (isset($item['definition_id']) || isset($item['data_json'])) {
            return $this->validateCustomEventData($item);
        }
        
        // Default: basic validation
        return [
            'valid' => true,
            'errors' => [],
            'quality_score' => 80,
            'score_deduction' => 0,
        ];
    }

    /**
     * Create a validation error
     *
     * @param string $type Error type
     * @param string $field Field name
     * @param string $message Error message
     * @param string $severity Error severity
     * @return array Error object
     */
    private function createError(string $type, string $field, string $message, string $severity): array
    {
        return [
            'type' => $type,
            'field' => $field,
            'message' => $message,
            'severity' => $severity,
        ];
    }
}
