<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use Illuminate\Support\Facades\Cache;

/**
 * Analytics Orchestration Service
 *
 * Thin facade that delegates to the EXISTING specialized analytics services.
 * Does NOT replace any existing service — it coordinates them through a single entry point.
 *
 * Usage:
 *   $analytics = app(AnalyticsOrchestrationService::class);
 *   $data = $analytics->getEngagementMetrics();
 *
 * All 33 existing services remain intact and fully functional.
 */
class AnalyticsOrchestrationService
{
    public function __construct(
        // Core monitoring & metrics (existing services)
        private AnalyticsMonitoringService $monitoring,
        private AnalyticsMetricsCollectionService $metrics,
        private AnalyticsLoggingService $logging,

        // Data management (existing services)
        private AnalyticsDataValidationService $validation,
        private AnalyticsDataExportImportService $exportImport,
        private AnalyticsDataSyncService $dataSync,
        private AnalyticsDataArchivingService $archiving,

        // Insights & analysis (existing services)
        private AutomatedInsightsService $insights,
        private CohortAnalysisService $cohort,
        private BehaviorFlowService $behaviorFlow,
        private SessionRecordingService $sessionRecording,

        // Attribution (existing services)
        private AttributionTrackingService $attribution,

        // Custom events (existing services)
        private CustomEventTrackingService $customEvents,

        // External integrations (existing services)
        private GoogleAnalyticsService $googleAnalytics,
        private MatomoService $matomo,
        private SyncService $sync,

        // Privacy & compliance (existing services)
        private ConsentService $consent,
        private PrivacyComplianceService $privacy,
        private PrivacyAuditService $privacyAudit,
        private AnalyticsComplianceReportingService $complianceReporting,

        // Learning analytics (existing services)
        private LearningAnalyticsService $learning,
        private CareerPredictionService $careerPrediction,

        // Error & security (existing services)
        private AnalyticsErrorHandlerService $errorHandler,
        private AnalyticsSecurityHardeningService $security,
        private AnalyticsAuditLoggingService $auditLogging,

        // Backup & recovery (existing services)
        private AnalyticsBackupRecoveryService $backup,
        private AnalyticsDisasterRecoveryService $disasterRecovery,

        // Performance (existing services)
        private AnalyticsPerformanceOptimizer $performance,

        // Dashboard (existing services)
        private AnalyticsDashboardIntegrationService $dashboard,
    ) {}

    // ==================== Core Metrics ====================

    public function getEngagementMetrics(array $filters = []): array
    {
        return $this->metrics->getMetrics($filters);
    }

    public function getMonitoringDashboard(): array
    {
        return $this->monitoring->getMonitoringDashboard();
    }

    public function checkHealth(): array
    {
        return $this->monitoring->checkHealth();
    }

    // ==================== Reports & Export ====================

    public function exportData(string $format, array $filters = []): string
    {
        return match ($format) {
            'csv' => $this->exportImport->exportToCSV($filters),
            'json' => $this->exportImport->exportToJSON($filters),
            'excel' => $this->exportImport->exportToExcel($filters),
            default => throw new \InvalidArgumentException("Unsupported format: {$format}"),
        };
    }

    public function importData(string $format, string $data): array
    {
        return match ($format) {
            'csv' => $this->exportImport->importFromCSV($data),
            'json' => $this->exportImport->importFromJSON($data),
            default => throw new \InvalidArgumentException("Unsupported format: {$format}"),
        };
    }

    // ==================== Dashboard ====================

    public function getDashboardMetrics(string $period = '30d'): array
    {
        return $this->dashboard->getDashboardData($period);
    }

    public function getAnalyticsSummary(): array
    {
        return Cache::remember('analytics.summary', 300, function () {
            return [
                'monitoring' => $this->monitoring->getMonitoringDashboard(),
                'metrics_count' => $this->metrics->getMetricsSummary()['total'] ?? 0,
                'last_updated' => now()->toISOString(),
            ];
        });
    }

    // ==================== Cohort Analysis ====================

    public function getCohortAnalysis(string $period = 'monthly'): array
    {
        return $this->cohort->analyzeCohort($period);
    }

    public function compareCohorts(string $cohortA, string $cohortB): array
    {
        return $this->cohort->compareCohorts($cohortA, $cohortB);
    }

    // ==================== Attribution ====================

    public function getAttributionData(array $filters = []): array
    {
        return $this->attribution->getAttributionSummary($filters);
    }

    // ==================== Custom Events ====================

    public function getCustomEvents(array $filters = []): array
    {
        return $this->customEvents->listEvents($filters);
    }

    public function trackCustomEvent(string $event, array $data = []): void
    {
        $this->customEvents->trackEvent($event, $data);
    }

    // ==================== External Integrations ====================

    public function getGoogleAnalyticsData(array $config = []): array
    {
        return $this->googleAnalytics->getReport($config);
    }

    public function getMatomoData(array $config = []): array
    {
        return $this->matomo->getReport($config);
    }

    public function syncExternalData(string $platform, array $config = []): array
    {
        return $this->dataSync->syncData($platform, $config);
    }

    // ==================== Privacy ====================

    public function checkConsent(string $type, int $userId): bool
    {
        return $this->consent->hasConsent($type, $userId);
    }

    public function getPrivacyComplianceStatus(): array
    {
        return $this->privacy->checkDataRetention();
    }

    public function getComplianceReport(string $type = 'gdpr'): array
    {
        return $this->complianceReporting->generateComplianceReport($type);
    }

    // ==================== Learning Analytics ====================

    public function getLearningAnalytics(int $userId): array
    {
        return $this->learning->getLearningProgress($userId);
    }

    public function getCareerPredictions(int $userId): array
    {
        return $this->careerPrediction->getPredictionScore($userId);
    }

    // ==================== Error Tracking ====================

    public function trackError(string $type, string $message, array $context = []): void
    {
        $this->errorHandler->handleError($type, $message, $context);
    }

    public function getRecentErrors(int $limit = 20): array
    {
        return $this->errorHandler->getErrorReport($limit);
    }

    // ==================== Behavior Flow ====================

    public function getBehaviorFlow(array $filters = []): array
    {
        return $this->behaviorFlow->analyzeBehaviorFlow($filters);
    }

    // ==================== Insights ====================

    public function getInsights(array $filters = []): array
    {
        return $this->insights->generateInsights($filters);
    }

    // ==================== Data Validation ====================

    public function validateData(string $type, array $data): array
    {
        return match ($type) {
            'event' => $this->validation->validateEventData($data),
            'session' => $this->validation->validateSessionData($data),
            'user' => $this->validation->validateUserData($data),
            'metrics' => $this->validation->validateMetricsData($data),
            'cohort' => $this->validation->validateCohortData($data),
            'attribution' => $this->validation->validateAttributionData($data),
            'custom_event' => $this->validation->validateCustomEventData($data),
            default => throw new \InvalidArgumentException("Unknown validation type: {$type}"),
        };
    }

    // ==================== Backup & Recovery ====================

    public function createBackup(array $config = []): array
    {
        return $this->backup->createBackup($config);
    }

    public function restoreBackup(string $backupId): array
    {
        return $this->backup->restoreBackup($backupId);
    }

    // ==================== Security ====================

    public function scanVulnerabilities(): array
    {
        return $this->security->scanVulnerabilities();
    }

    // ==================== Audit Logging ====================

    public function getAuditLogs(array $filters = []): array
    {
        return $this->auditLogging->getAuditLogs($filters);
    }

    // ==================== Performance ====================

    public function optimizeQuery(string $query): array
    {
        return $this->performance->optimizeQuery($query);
    }
}
