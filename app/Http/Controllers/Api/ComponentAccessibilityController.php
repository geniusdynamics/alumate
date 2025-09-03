<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Component;
use App\Services\ComponentAccessibilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ComponentAccessibilityController extends Controller
{
    public function __construct(
        private ComponentAccessibilityService $accessibilityService
    ) {}

    /**
     * Assess accessibility for a specific component
     */
    public function assess(Component $component): JsonResponse
    {
        $this->authorize('view', $component);

        try {
            $assessment = $this->accessibilityService->assessComponent($component);

            return response()->json([
                'success' => true,
                'assessment' => $assessment,
                'component' => [
                    'id' => $component->id,
                    'name' => $component->name,
                    'category' => $component->category
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Accessibility assessment failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get accessibility recommendations for a component
     */
    public function recommendations(Component $component): JsonResponse
    {
        $this->authorize('view', $component);

        try {
            $recommendations = $this->accessibilityService->getRecommendations($component);

            return response()->json([
                'success' => true,
                'recommendations' => $recommendations,
                'component' => [
                    'id' => $component->id,
                    'name' => $component->name,
                    'category' => $component->category
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate recommendations',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get accessibility summary for multiple components
     */
    public function summary(Request $request): JsonResponse
    {
        $request->validate([
            'component_ids' => 'required|array|min:1|max:50',
            'component_ids.*' => 'exists:components,id'
        ]);

        try {
            $componentIds = $request->component_ids;
            $summary = $this->accessibilityService->getAccessibilitySummary($componentIds, Auth::user()->tenant_id);

            return response()->json([
                'success' => true,
                'summary' => $summary
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate accessibility summary',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate accessibility compliance for a component configuration
     */
    public function validateConfig(Component $component, Request $request): JsonResponse
    {
        $this->authorize('update', $component);

        $request->validate([
            'config' => 'required|array',
            'accessibility_only' => 'boolean'
        ]);

        try {
            // Temporarily update component config for validation
            $originalConfig = $component->config;
            $component->config = $request->config;

            $assessment = $this->accessibilityService->assessComponent($component);

            // Restore original config
            $component->config = $originalConfig;

            $accessibleOnly = $request->boolean('accessibility_only', false);

            if ($accessibleOnly) {
                $result = [
                    'is_accessible' => $assessment['is_compliant'],
                    'compliance_level' => $assessment['compliance_level'],
                    'score' => $assessment['overall_score'],
                    'grade' => $assessment['grade'],
                    'critical_issues' => array_filter($assessment['issues'], fn($issue) => ($issue['severity'] ?? 'low') === 'high')
                ];
            } else {
                $result = $assessment;
            }

            return response()->json([
                'success' => true,
                'validation' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get WCAG compliance report for tenant components
     */
    public function complianceReport(Request $request): JsonResponse
    {
        $request->validate([
            'category' => 'nullable|in:hero,forms,testimonials,statistics,ctas,media',
            'compliance_level' => 'nullable|in:A,A_AAA,full_compliance',
            'include_components' => 'boolean'
        ]);

        try {
            $tenantId = Auth::user()->tenant_id;
            $category = $request->category;

            // Get all components for the tenant
            $query = Component::forTenant($tenantId);

            if ($category) {
                $query->where('category', $category);
            }

            $components = $query->get();
            $componentIds = $components->pluck('id')->toArray();

            $summary = $this->accessibilityService->getAccessibilitySummary($componentIds, $tenantId);

            // Add compliance metrics
            $report = [
                'summary' => $summary,
                'compliance_metrics' => [
                    'wcag_aa_compliance_rate' => $this->calculateComplianceRate($summary, 'A'),
                    'wcag_aaa_compliance_rate' => $this->calculateComplianceRate($summary, 'A_AAA'),
                    'full_compliance_rate' => $this->calculateComplianceRate($summary, 'full_compliance')
                ],
                'issue_breakdown' => $this->getIssueBreakdown($summary),
                'category_performance' => $this->getCategoryPerformance($components),
                'recommendations' => $this->getGlobalRecommendations($summary),
                'generated_at' => now()->toISOString()
            ];

            if ($request->boolean('include_components', false)) {
                $report['components_assessment'] = $this->accessibilityService->batchAssess($components);
            }

            return response()->json([
                'success' => true,
                'report' => $report
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate compliance report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get accessibility issues for a component with remediation steps
     */
    public function issues(Component $component): JsonResponse
    {
        $this->authorize('view', $component);

        try {
            $assessment = $this->accessibilityService->assessComponent($component);

            $issues = $assessment['issues'];
            $enrichedIssues = [];

            foreach ($issues as $issue) {
                $enrichedIssues[] = [
                    'issue' => $issue,
                    'remediation_steps' => $this->getRemediationSteps($issue),
                    'priority_score' => $this->calculatePriorityScore($issue),
                    'estimated_effort' => $this->estimateEffort($issue),
                    'impact_assessment' => $this->assessImpact($issue)
                ];
            }

            return response()->json([
                'success' => true,
                'component' => [
                    'id' => $component->id,
                    'name' => $component->name,
                    'category' => $component->category
                ],
                'issues' => $enrichedIssues,
                'summary' => [
                    'total_issues' => count($issues),
                    'critical_issues' => count(array_filter($issues, fn($issue) => ($issue['severity'] ?? 'low') === 'high')),
                    'warnings' => count(array_filter($issues, fn($issue) => ($issue['severity'] ?? 'low') === 'medium')),
                    'suggestions' => count(array_filter($issues, fn($issue) => ($issue['severity'] ?? 'low') === 'low'))
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve accessibility issues',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Fix accessibility issues automatically where possible
     */
    public function autoFix(Component $component, Request $request): JsonResponse
    {
        $this->authorize('update', $component);

        $request->validate([
            'issue_ids' => 'nullable|array',
            'issue_ids.*' => 'string',
            'auto_fix_only' => 'boolean'
        ]);

        try {
            $assessment = $this->accessibilityService->assessComponent($component);
            $issues = $assessment['issues'];
            $targetIssues = $request->issue_ids ?
                array_filter($issues, fn($issue) => in_array($issue['rule_id'] ?? '', $request->issue_ids)) :
                $issues;

            $fixes = [];
            $configChanges = [];

            foreach ($targetIssues as $issue) {
                $fix = $this->generateAutoFix($issue, $component);
                if ($fix) {
                    $fixes[] = $fix;
                    $configChanges = array_merge_recursive($configChanges, $fix['config_changes']);
                }
            }

            // Apply fixes if any were found
            if (!empty($configChanges)) {
                $currentConfig = $component->config ?? [];
                $updatedConfig = array_merge_recursive($currentConfig, $configChanges);
                $component->update(['config' => $updatedConfig]);
            }

            return response()->json([
                'success' => true,
                'fixes_applied' => count($fixes),
                'total_issues_found' => count($targetIssues),
                'fixes' => $fixes,
                'config_updated' => !empty($configChanges)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Auto-fix failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear accessibility cache
     */
    public function clearCache(Component $component = null): JsonResponse
    {
        $this->authorize('update', $component ?? Auth::user());

        try {
            $this->accessibilityService->clearCache($component);

            return response()->json([
                'success' => true,
                'message' => $component ? 'Component accessibility cache cleared' : 'All accessibility cache cleared'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear cache',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate compliance rate for given level
     */
    private function calculateComplianceRate(array $summary, string $level): float
    {
        $compliantCount = 0;

        foreach ($summary['component_assessments'] as $assessment) {
            $isCompliant = false;

            switch ($level) {
                case 'A':
                    $isCompliant = $assessment['is_compliant'] || $assessment['score'] >= 70;
                    break;
                case 'A_AAA':
                    $isCompliant = $assessment['score'] >= 85;
                    break;
                case 'full_compliance':
                    $isCompliant = $assessment['is_compliant'] && $assessment['score'] >= 95;
                    break;
            }

            if ($isCompliant) {
                $compliantCount++;
            }
        }

        $totalComponents = count($summary['component_assessments']);
        return $totalComponents > 0 ? round(($compliantCount / $totalComponents) * 100, 2) : 0;
    }

    /**
     * Get breakdown of issues by type and severity
     */
    private function getIssueBreakdown(array $summary): array
    {
        $breakdown = [
            'by_type' => $summary['critical_issues_by_type'] ?? [],
            'by_severity' => [
                'high' => 0,
                'medium' => 0,
                'low' => 0
            ]
        ];

        // This would need component assessments to calculate severity breakdown
        // For now, return structure from summary

        return $breakdown;
    }

    /**
     * Get performance by component category
     */
    private function getCategoryPerformance(Collection $components): array
    {
        $categoryPerformance = [];

        $componentsByCategory = $components->groupBy('category');

        foreach ($componentsByCategory as $category => $categoryComponents) {
            $totalScore = 0;
            $compliantCount = 0;
            $componentIds = $categoryComponents->pluck('id')->toArray();

            if (!empty($componentIds)) {
                $assessments = $this->accessibilityService->getAccessibilitySummary($componentIds, Auth::user()->tenant_id);
                $totalScore = $assessments['average_score'];
                $compliantCount = $assessments['compliance_levels']['compliant'];
            }

            $categoryPerformance[$category] = [
                'component_count' => $categoryComponents->count(),
                'average_score' => round($totalScore, 1),
                'compliant_count' => $compliantCount,
                'compliance_rate' => $categoryComponents->count() > 0 ?
                    round(($compliantCount / $categoryComponents->count()) * 100, 1) : 0
            ];
        }

        return $categoryPerformance;
    }

    /**
     * Get global recommendations based on summary
     */
    private function getGlobalRecommendations(array $summary): array
    {
        $recommendations = [];

        $averageScore = $summary['average_score'];

        if ($averageScore < 70) {
            $recommendations[] = [
                'type' => 'urgent',
                'priority' => 'critical',
                'title' => 'Overall accessibility performance needs improvement',
                'description' => 'Average accessibility score is below 70%. Focus on high-priority issues first.',
                'actions' => [
                    'Implement color contrast improvements across all components',
                    'Add semantic HTML structure to existing components',
                    'Set up automated accessibility testing'
                ]
            ];
        }

        $compliantCount = $summary['compliance_levels']['compliant'];
        $nonCompliantCount = $summary['compliance_levels']['non_compliant'];

        if ($nonCompliantCount > $compliantCount) {
            $recommendations[] = [
                'type' => 'training',
                'priority' => 'high',
                'title' => 'Accessibility training needed for component developers',
                'description' => 'More non-compliant than compliant components suggest training gaps.',
                'actions' => [
                    'Conduct accessibility awareness sessions',
                    'Create component accessibility guidelines',
                    'Implement peer code reviews focused on accessibility'
                ]
            ];
        }

        return $recommendations;
    }

    /**
     * Get remediation steps for a specific issue
     */
    private function getRemediationSteps(array $issue): array
    {
        $ruleId = $issue['rule_id'] ?? '';

        switch ($ruleId) {
            case '1.4.3':
                return [
                    'Step 1: Use contrast ratio tool to measure current colors',
                    'Step 2: Adjust foreground and background colors to meet 4.5:1 ratio',
                    'Step 3: Test both normal and large text sizes',
                    'Step 4: Validate changes with automated tools'
                ];
            case '1.3.1':
                return [
                    'Step 1: Replace generic div/span elements with semantic elements',
                    'Step 2: Use heading elements (h1-h6) for content hierarchy',
                    'Step 3: Implement proper list structures (ul, ol)',
                    'Step 4: Test with screen readers to ensure proper navigation'
                ];
            case '2.1.1':
                return [
                    'Step 1: Ensure all interactive elements are focusable',
                    'Step 2: Implement logical tab order',
                    'Step 3: Add keyboard event handlers for custom components',
                    'Step 4: Test navigation with keyboard-only usage'
                ];
            default:
                return [
                    'Review WCAG guidelines for this success criterion',
                    'Implement appropriate technical solutions',
                    'Test with users and automated tools',
                    'Document exceptions if necessary'
                ];
        }
    }

    /**
     * Calculate priority score for an issue
     */
    private function calculatePriorityScore(array $issue): int
    {
        $severityScore = match($issue['severity'] ?? 'low') {
            'high' => 10,
            'medium' => 5,
            'low' => 2,
            default => 1
        };

        // Rules directly impacting core functionality get higher priority
        $rulePriority = match($issue['rule_id'] ?? '') {
            '1.4.3', '2.1.1', '4.1.2' => 3, // High visibility issues
            '1.3.1', '2.4.6' => 2, // Navigation/screen reader issues
            default => 1
        };

        return min(10, $severityScore + $rulePriority);
    }

    /**
     * Estimate effort required to fix an issue
     */
    private function estimateEffort(array $issue): string
    {
        switch ($issue['severity']) {
            case 'high':
                return 'Medium (requires design and development coordination)';
            case 'medium':
                return 'Low to Medium (development-focused changes)';
            case 'low':
                return 'Low (minor configuration changes)';
            default:
                return 'Unknown (requires investigation)';
        }
    }

    /**
     * Assess impact of an accessibility issue
     */
    private function assessImpact(array $issue): array
    {
        $severity = $issue['severity'] ?? 'low';
        $ruleId = $issue['rule_id'] ?? '';

        $impact = match($severity) {
            'high' => 'Severe impact on accessibility - blocks users from completing tasks',
            'medium' => 'Moderate impact on accessibility - affects user experience',
            'low' => 'Minor impact on accessibility - improves user experience'
        };

        $affectedUsers = match(substr($ruleId, 0, 2)) {
            '1.' => 'Users with visual impairments',
            '2.' => 'Users with motor impairments',
            '3.' => 'Users who need understandable content',
            '4.' => 'Users with assistive technology',
            default => 'Various users with disabilities'
        };

        return [
            'severity_level' => $severity,
            'user_impact' => $impact,
            'affected_users' => $affectedUsers,
            'business_impact' => $this->calculateBusinessImpact($issue)
        ];
    }

    /**
     * Generate automatic fix for an issue
     */
    private function generateAutoFix(array $issue, Component $component): ?array
    {
        $ruleId = $issue['rule_id'] ?? '';

        switch ($ruleId) {
            case '1.3.1':
                // Auto-fix semantic HTML by adding proper tags
                $config = $component->config ?? [];

                if (!isset($config['accessibility'])) {
                    $config['accessibility'] = [];
                }

                $config['accessibility']['semantic_tag'] = match($component->category) {
                    'hero' => 'header',
                    'forms' => 'form',
                    'testimonials' => 'section',
                    'ctas' => 'div',
                    'media' => 'div',
                    default => 'section'
                };

                return [
                    'issue_id' => $ruleId,
                    'description' => 'Added semantic HTML element',
                    'config_changes' => $config
                ];

            case '2.4.7':
                // Auto-fix focus indicators
                $config = $component->config ?? [];

                if (!isset($config['accessibility'])) {
                    $config['accessibility'] = [];
                }

                $config['accessibility']['focus_indicators'] = [
                    'outline' => '2px solid #007bff',
                    'outline_offset' => '2px',
                    'border_radius' => '4px'
                ];

                return [
                    'issue_id' => $ruleId,
                    'description' => 'Added focus indicator styles',
                    'config_changes' => $config
                ];

            default:
                return null; // No auto-fix available
        }
    }

    /**
     * Calculate business impact of accessibility issue
     */
    private function calculateBusinessImpact(array $issue): string
    {
        $severity = $issue['severity'] ?? 'low';

        return match($severity) {
            'high' => 'Legal compliance risk and exclusion of ~20% of potential users',
            'medium' => 'Reduced user satisfaction and potential loss of business',
            'low' => 'Minor impact but contributes to overall accessibility goals'
        };
    }
}