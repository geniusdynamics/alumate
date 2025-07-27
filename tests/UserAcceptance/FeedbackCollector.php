<?php

namespace Tests\UserAcceptance;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

/**
 * User Feedback Collector for User Acceptance Testing
 * 
 * This class provides functionality to collect, store, and analyze
 * user feedback during the testing process.
 */
class FeedbackCollector
{
    private $feedbackData = [];
    private $sessionId;

    public function __construct()
    {
        $this->sessionId = uniqid('uat_session_');
    }

    /**
     * Collect feedback for a specific test scenario
     */
    public function collectTestFeedback($testId, $testName, $userRole, $feedback)
    {
        $feedbackEntry = [
            'session_id' => $this->sessionId,
            'test_id' => $testId,
            'test_name' => $testName,
            'user_role' => $userRole,
            'feedback' => $feedback,
            'timestamp' => now()->toISOString(),
            'severity' => $this->determineSeverity($feedback),
            'category' => $this->categorizeIssue($feedback),
        ];

        $this->feedbackData[] = $feedbackEntry;
        $this->saveFeedbackToFile($feedbackEntry);

        return $feedbackEntry;
    }

    /**
     * Collect bug report
     */
    public function collectBugReport($testId, $bugData)
    {
        $bugReport = [
            'session_id' => $this->sessionId,
            'test_id' => $testId,
            'type' => 'bug_report',
            'title' => $bugData['title'],
            'description' => $bugData['description'],
            'steps_to_reproduce' => $bugData['steps_to_reproduce'],
            'expected_result' => $bugData['expected_result'],
            'actual_result' => $bugData['actual_result'],
            'severity' => $bugData['severity'] ?? 'medium',
            'priority' => $bugData['priority'] ?? 'medium',
            'browser' => $bugData['browser'] ?? 'unknown',
            'os' => $bugData['os'] ?? 'unknown',
            'screenshot_path' => $bugData['screenshot_path'] ?? null,
            'timestamp' => now()->toISOString(),
        ];

        $this->feedbackData[] = $bugReport;
        $this->saveBugReportToFile($bugReport);

        return $bugReport;
    }

    /**
     * Collect usability feedback
     */
    public function collectUsabilityFeedback($testId, $usabilityData)
    {
        $usabilityFeedback = [
            'session_id' => $this->sessionId,
            'test_id' => $testId,
            'type' => 'usability_feedback',
            'ease_of_use_rating' => $usabilityData['ease_of_use_rating'], // 1-5 scale
            'navigation_rating' => $usabilityData['navigation_rating'], // 1-5 scale
            'visual_design_rating' => $usabilityData['visual_design_rating'], // 1-5 scale
            'performance_rating' => $usabilityData['performance_rating'], // 1-5 scale
            'overall_satisfaction' => $usabilityData['overall_satisfaction'], // 1-5 scale
            'positive_aspects' => $usabilityData['positive_aspects'],
            'improvement_suggestions' => $usabilityData['improvement_suggestions'],
            'most_difficult_task' => $usabilityData['most_difficult_task'],
            'most_helpful_feature' => $usabilityData['most_helpful_feature'],
            'additional_comments' => $usabilityData['additional_comments'],
            'timestamp' => now()->toISOString(),
        ];

        $this->feedbackData[] = $usabilityFeedback;
        $this->saveUsabilityFeedbackToFile($usabilityFeedback);

        return $usabilityFeedback;
    }

    /**
     * Collect performance feedback
     */
    public function collectPerformanceFeedback($testId, $performanceData)
    {
        $performanceFeedback = [
            'session_id' => $this->sessionId,
            'test_id' => $testId,
            'type' => 'performance_feedback',
            'page_load_time' => $performanceData['page_load_time'],
            'response_time_rating' => $performanceData['response_time_rating'], // 1-5 scale
            'system_responsiveness' => $performanceData['system_responsiveness'], // 1-5 scale
            'slow_operations' => $performanceData['slow_operations'],
            'timeout_issues' => $performanceData['timeout_issues'],
            'browser_performance' => $performanceData['browser_performance'],
            'mobile_performance' => $performanceData['mobile_performance'] ?? null,
            'timestamp' => now()->toISOString(),
        ];

        $this->feedbackData[] = $performanceFeedback;
        $this->savePerformanceFeedbackToFile($performanceFeedback);

        return $performanceFeedback;
    }

    /**
     * Generate comprehensive feedback report
     */
    public function generateFeedbackReport()
    {
        $report = [
            'session_id' => $this->sessionId,
            'generated_at' => now()->toISOString(),
            'total_feedback_entries' => count($this->feedbackData),
            'summary' => $this->generateFeedbackSummary(),
            'bug_reports' => $this->getBugReports(),
            'usability_feedback' => $this->getUsabilityFeedback(),
            'performance_feedback' => $this->getPerformanceFeedback(),
            'recommendations' => $this->generateRecommendations(),
            'detailed_feedback' => $this->feedbackData,
        ];

        $this->saveFeedbackReport($report);
        return $report;
    }

    /**
     * Generate feedback summary statistics
     */
    private function generateFeedbackSummary()
    {
        $bugReports = collect($this->feedbackData)->where('type', 'bug_report');
        $usabilityFeedback = collect($this->feedbackData)->where('type', 'usability_feedback');
        $performanceFeedback = collect($this->feedbackData)->where('type', 'performance_feedback');

        $severityCounts = $bugReports->groupBy('severity')->map->count();
        $priorityCounts = $bugReports->groupBy('priority')->map->count();

        $avgUsabilityRatings = [];
        if ($usabilityFeedback->count() > 0) {
            $avgUsabilityRatings = [
                'ease_of_use' => $usabilityFeedback->avg('ease_of_use_rating'),
                'navigation' => $usabilityFeedback->avg('navigation_rating'),
                'visual_design' => $usabilityFeedback->avg('visual_design_rating'),
                'performance' => $usabilityFeedback->avg('performance_rating'),
                'overall_satisfaction' => $usabilityFeedback->avg('overall_satisfaction'),
            ];
        }

        return [
            'total_bug_reports' => $bugReports->count(),
            'total_usability_feedback' => $usabilityFeedback->count(),
            'total_performance_feedback' => $performanceFeedback->count(),
            'bug_severity_distribution' => $severityCounts->toArray(),
            'bug_priority_distribution' => $priorityCounts->toArray(),
            'average_usability_ratings' => $avgUsabilityRatings,
            'critical_issues_count' => $bugReports->where('severity', 'critical')->count(),
            'high_priority_issues_count' => $bugReports->where('priority', 'high')->count(),
        ];
    }

    /**
     * Get all bug reports
     */
    private function getBugReports()
    {
        return collect($this->feedbackData)
            ->where('type', 'bug_report')
            ->values()
            ->toArray();
    }

    /**
     * Get all usability feedback
     */
    private function getUsabilityFeedback()
    {
        return collect($this->feedbackData)
            ->where('type', 'usability_feedback')
            ->values()
            ->toArray();
    }

    /**
     * Get all performance feedback
     */
    private function getPerformanceFeedback()
    {
        return collect($this->feedbackData)
            ->where('type', 'performance_feedback')
            ->values()
            ->toArray();
    }

    /**
     * Generate recommendations based on feedback
     */
    private function generateRecommendations()
    {
        $recommendations = [];
        $bugReports = collect($this->feedbackData)->where('type', 'bug_report');
        $usabilityFeedback = collect($this->feedbackData)->where('type', 'usability_feedback');

        // Critical bug recommendations
        $criticalBugs = $bugReports->where('severity', 'critical');
        if ($criticalBugs->count() > 0) {
            $recommendations[] = [
                'type' => 'critical',
                'title' => 'Address Critical Bugs Immediately',
                'description' => "Found {$criticalBugs->count()} critical bugs that must be fixed before release.",
                'priority' => 'high',
                'affected_areas' => $criticalBugs->pluck('test_id')->unique()->values()->toArray(),
            ];
        }

        // Usability recommendations
        $lowUsabilityRatings = $usabilityFeedback->filter(function($feedback) {
            return $feedback['overall_satisfaction'] < 3;
        });

        if ($lowUsabilityRatings->count() > 0) {
            $recommendations[] = [
                'type' => 'usability',
                'title' => 'Improve User Experience',
                'description' => "Multiple users reported low satisfaction scores. Focus on navigation and ease of use improvements.",
                'priority' => 'medium',
                'affected_areas' => $lowUsabilityRatings->pluck('test_id')->unique()->values()->toArray(),
            ];
        }

        // Performance recommendations
        $performanceIssues = collect($this->feedbackData)
            ->where('type', 'performance_feedback')
            ->where('response_time_rating', '<', 3);

        if ($performanceIssues->count() > 0) {
            $recommendations[] = [
                'type' => 'performance',
                'title' => 'Optimize System Performance',
                'description' => "Users reported slow response times. Consider performance optimization.",
                'priority' => 'medium',
                'affected_areas' => $performanceIssues->pluck('test_id')->unique()->values()->toArray(),
            ];
        }

        return $recommendations;
    }

    /**
     * Determine issue severity based on feedback content
     */
    private function determineSeverity($feedback)
    {
        $criticalKeywords = ['crash', 'error', 'broken', 'not working', 'failed'];
        $highKeywords = ['slow', 'difficult', 'confusing', 'problem'];
        $mediumKeywords = ['improvement', 'suggestion', 'could be better'];

        $feedbackLower = strtolower($feedback);

        foreach ($criticalKeywords as $keyword) {
            if (strpos($feedbackLower, $keyword) !== false) {
                return 'critical';
            }
        }

        foreach ($highKeywords as $keyword) {
            if (strpos($feedbackLower, $keyword) !== false) {
                return 'high';
            }
        }

        foreach ($mediumKeywords as $keyword) {
            if (strpos($feedbackLower, $keyword) !== false) {
                return 'medium';
            }
        }

        return 'low';
    }

    /**
     * Categorize issue based on feedback content
     */
    private function categorizeIssue($feedback)
    {
        $categories = [
            'ui' => ['interface', 'design', 'layout', 'visual', 'button', 'form'],
            'functionality' => ['feature', 'function', 'work', 'operation', 'process'],
            'performance' => ['slow', 'fast', 'speed', 'load', 'response', 'time'],
            'usability' => ['easy', 'difficult', 'user', 'experience', 'navigation'],
            'data' => ['information', 'data', 'record', 'save', 'load', 'export'],
        ];

        $feedbackLower = strtolower($feedback);

        foreach ($categories as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($feedbackLower, $keyword) !== false) {
                    return $category;
                }
            }
        }

        return 'general';
    }

    /**
     * Save individual feedback to file
     */
    private function saveFeedbackToFile($feedback)
    {
        $filename = "feedback/uat-feedback-{$this->sessionId}.jsonl";
        Storage::append($filename, json_encode($feedback));
    }

    /**
     * Save bug report to file
     */
    private function saveBugReportToFile($bugReport)
    {
        $filename = "feedback/uat-bugs-{$this->sessionId}.jsonl";
        Storage::append($filename, json_encode($bugReport));
    }

    /**
     * Save usability feedback to file
     */
    private function saveUsabilityFeedbackToFile($usabilityFeedback)
    {
        $filename = "feedback/uat-usability-{$this->sessionId}.jsonl";
        Storage::append($filename, json_encode($usabilityFeedback));
    }

    /**
     * Save performance feedback to file
     */
    private function savePerformanceFeedbackToFile($performanceFeedback)
    {
        $filename = "feedback/uat-performance-{$this->sessionId}.jsonl";
        Storage::append($filename, json_encode($performanceFeedback));
    }

    /**
     * Save comprehensive feedback report
     */
    private function saveFeedbackReport($report)
    {
        $filename = "feedback/uat-report-{$this->sessionId}-" . date('Y-m-d-H-i-s') . ".json";
        Storage::put($filename, json_encode($report, JSON_PRETTY_PRINT));
    }

    /**
     * Export feedback to CSV for analysis
     */
    public function exportFeedbackToCsv()
    {
        $csvData = [];
        $csvData[] = [
            'Session ID',
            'Test ID',
            'Type',
            'Severity',
            'Category',
            'Timestamp',
            'Feedback/Description'
        ];

        foreach ($this->feedbackData as $feedback) {
            $csvData[] = [
                $feedback['session_id'],
                $feedback['test_id'] ?? '',
                $feedback['type'] ?? 'general_feedback',
                $feedback['severity'] ?? '',
                $feedback['category'] ?? '',
                $feedback['timestamp'],
                $feedback['feedback'] ?? $feedback['description'] ?? ''
            ];
        }

        $filename = "feedback/uat-feedback-export-{$this->sessionId}-" . date('Y-m-d-H-i-s') . ".csv";
        
        $csvContent = '';
        foreach ($csvData as $row) {
            $csvContent .= implode(',', array_map(function($field) {
                return '"' . str_replace('"', '""', $field) . '"';
            }, $row)) . "\n";
        }

        Storage::put($filename, $csvContent);
        return $filename;
    }

    /**
     * Get session feedback summary
     */
    public function getSessionSummary()
    {
        return [
            'session_id' => $this->sessionId,
            'total_entries' => count($this->feedbackData),
            'bug_reports' => collect($this->feedbackData)->where('type', 'bug_report')->count(),
            'usability_feedback' => collect($this->feedbackData)->where('type', 'usability_feedback')->count(),
            'performance_feedback' => collect($this->feedbackData)->where('type', 'performance_feedback')->count(),
            'critical_issues' => collect($this->feedbackData)->where('severity', 'critical')->count(),
            'high_priority_issues' => collect($this->feedbackData)->where('priority', 'high')->count(),
        ];
    }
}