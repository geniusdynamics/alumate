<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Tests\UserAcceptance\FeedbackCollector;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class TestingController extends Controller
{
    private $feedbackCollector;

    public function __construct()
    {
        $this->feedbackCollector = new FeedbackCollector();
    }

    /**
     * Show the feedback form
     */
    public function showFeedbackForm()
    {
        return view('testing.feedback-form');
    }

    /**
     * Handle feedback form submission
     */
    public function submitFeedback(Request $request)
    {
        try {
            $type = $request->input('type');
            
            switch ($type) {
                case 'general':
                    return $this->handleGeneralFeedback($request);
                case 'bug':
                    return $this->handleBugReport($request);
                case 'usability':
                    return $this->handleUsabilityFeedback($request);
                case 'performance':
                    return $this->handlePerformanceFeedback($request);
                default:
                    return response()->json(['success' => false, 'message' => 'Invalid feedback type'], 400);
            }
        } catch (\Exception $e) {
            Log::error('Feedback submission error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error occurred'], 500);
        }
    }

    /**
     * Handle general feedback submission
     */
    private function handleGeneralFeedback(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'test_id' => 'nullable|string|max:50',
            'user_role' => 'required|string|in:super_admin,institution_admin,employer,graduate,tester',
            'feedback' => 'required|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $feedback = $this->feedbackCollector->collectTestFeedback(
            $request->input('test_id'),
            'General Feedback',
            $request->input('user_role'),
            $request->input('feedback')
        );

        Log::info('General feedback collected', ['feedback_id' => $feedback['session_id']]);

        return response()->json([
            'success' => true,
            'message' => 'Feedback submitted successfully',
            'feedback_id' => $feedback['session_id']
        ]);
    }

    /**
     * Handle bug report submission
     */
    private function handleBugReport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'test_id' => 'nullable|string|max:50',
            'title' => 'required|string|max:200',
            'description' => 'required|string|max:2000',
            'steps_to_reproduce' => 'required|string|max:2000',
            'expected_result' => 'nullable|string|max:1000',
            'actual_result' => 'nullable|string|max:1000',
            'severity' => 'required|string|in:critical,high,medium,low',
            'browser' => 'nullable|string|max:100',
            'os' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $bugData = [
            'title' => $request->input('title'),
            'description' => $request->input('description'),
            'steps_to_reproduce' => $request->input('steps_to_reproduce'),
            'expected_result' => $request->input('expected_result'),
            'actual_result' => $request->input('actual_result'),
            'severity' => $request->input('severity'),
            'priority' => $this->determinePriorityFromSeverity($request->input('severity')),
            'browser' => $request->input('browser'),
            'os' => $request->input('os'),
        ];

        $bugReport = $this->feedbackCollector->collectBugReport(
            $request->input('test_id'),
            $bugData
        );

        Log::warning('Bug report submitted', [
            'bug_id' => $bugReport['session_id'],
            'severity' => $bugData['severity'],
            'title' => $bugData['title']
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Bug report submitted successfully',
            'bug_id' => $bugReport['session_id']
        ]);
    }

    /**
     * Handle usability feedback submission
     */
    private function handleUsabilityFeedback(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'test_id' => 'nullable|string|max:50',
            'ease_of_use_rating' => 'nullable|integer|min:1|max:5',
            'navigation_rating' => 'nullable|integer|min:1|max:5',
            'visual_design_rating' => 'nullable|integer|min:1|max:5',
            'performance_rating' => 'nullable|integer|min:1|max:5',
            'overall_satisfaction' => 'nullable|integer|min:1|max:5',
            'positive_aspects' => 'nullable|string|max:1000',
            'improvement_suggestions' => 'nullable|string|max:1000',
            'most_difficult_task' => 'nullable|string|max:1000',
            'most_helpful_feature' => 'nullable|string|max:1000',
            'additional_comments' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $usabilityData = [
            'ease_of_use_rating' => $request->input('ease_of_use_rating'),
            'navigation_rating' => $request->input('navigation_rating'),
            'visual_design_rating' => $request->input('visual_design_rating'),
            'performance_rating' => $request->input('performance_rating'),
            'overall_satisfaction' => $request->input('overall_satisfaction'),
            'positive_aspects' => $request->input('positive_aspects'),
            'improvement_suggestions' => $request->input('improvement_suggestions'),
            'most_difficult_task' => $request->input('most_difficult_task'),
            'most_helpful_feature' => $request->input('most_helpful_feature'),
            'additional_comments' => $request->input('additional_comments'),
        ];

        $usabilityFeedback = $this->feedbackCollector->collectUsabilityFeedback(
            $request->input('test_id'),
            $usabilityData
        );

        Log::info('Usability feedback collected', [
            'feedback_id' => $usabilityFeedback['session_id'],
            'overall_satisfaction' => $usabilityData['overall_satisfaction']
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Usability feedback submitted successfully',
            'feedback_id' => $usabilityFeedback['session_id']
        ]);
    }

    /**
     * Handle performance feedback submission
     */
    private function handlePerformanceFeedback(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'test_id' => 'nullable|string|max:50',
            'page_load_time' => 'nullable|numeric|min:0',
            'response_time_rating' => 'nullable|integer|min:1|max:5',
            'system_responsiveness' => 'nullable|integer|min:1|max:5',
            'slow_operations' => 'nullable|string|max:1000',
            'timeout_issues' => 'nullable|string|max:1000',
            'browser_performance' => 'nullable|string|max:1000',
            'mobile_performance' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $performanceData = [
            'page_load_time' => $request->input('page_load_time'),
            'response_time_rating' => $request->input('response_time_rating'),
            'system_responsiveness' => $request->input('system_responsiveness'),
            'slow_operations' => $request->input('slow_operations'),
            'timeout_issues' => $request->input('timeout_issues'),
            'browser_performance' => $request->input('browser_performance'),
            'mobile_performance' => $request->input('mobile_performance'),
        ];

        $performanceFeedback = $this->feedbackCollector->collectPerformanceFeedback(
            $request->input('test_id'),
            $performanceData
        );

        Log::info('Performance feedback collected', [
            'feedback_id' => $performanceFeedback['session_id'],
            'response_time_rating' => $performanceData['response_time_rating']
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Performance feedback submitted successfully',
            'feedback_id' => $performanceFeedback['session_id']
        ]);
    }

    /**
     * Get feedback summary for admin review
     */
    public function getFeedbackSummary()
    {
        $summary = $this->feedbackCollector->getSessionSummary();
        return response()->json($summary);
    }

    /**
     * Generate and download feedback report
     */
    public function generateFeedbackReport()
    {
        try {
            $report = $this->feedbackCollector->generateFeedbackReport();
            
            return response()->json([
                'success' => true,
                'message' => 'Feedback report generated successfully',
                'report' => $report
            ]);
        } catch (\Exception $e) {
            Log::error('Feedback report generation error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate feedback report'
            ], 500);
        }
    }

    /**
     * Export feedback to CSV
     */
    public function exportFeedbackCsv()
    {
        try {
            $filename = $this->feedbackCollector->exportFeedbackToCsv();
            
            return response()->json([
                'success' => true,
                'message' => 'Feedback exported to CSV successfully',
                'filename' => $filename
            ]);
        } catch (\Exception $e) {
            Log::error('Feedback CSV export error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to export feedback to CSV'
            ], 500);
        }
    }

    /**
     * Determine priority based on severity
     */
    private function determinePriorityFromSeverity($severity)
    {
        switch ($severity) {
            case 'critical':
                return 'high';
            case 'high':
                return 'high';
            case 'medium':
                return 'medium';
            case 'low':
                return 'low';
            default:
                return 'medium';
        }
    }
}