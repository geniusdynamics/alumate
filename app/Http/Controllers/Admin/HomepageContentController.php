<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\HomepageContentService;
use App\Models\HomepageContent;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class HomepageContentController extends Controller
{
    public function __construct(
        private HomepageContentService $contentService
    ) {}

    /**
     * Display the content management interface
     */
    public function index(): Response
    {
        $content = HomepageContent::with(['creator', 'approver', 'latestApproval'])
            ->orderBy('section')
            ->orderBy('key')
            ->get();

        $pendingApprovals = $this->contentService->getPendingApprovals();

        return Inertia::render('Admin/HomepageContent/Index', [
            'content' => $content,
            'pendingApprovals' => $pendingApprovals,
            'sections' => $this->getAvailableSections(),
            'audiences' => ['individual', 'institutional', 'both'],
        ]);
    }

    /**
     * Get content for a specific audience
     */
    public function getContent(Request $request): JsonResponse
    {
        $audience = $request->get('audience', 'both');
        $section = $request->get('section');

        $content = $this->contentService->getFormattedContent($audience);

        return response()->json([
            'content' => $content,
            'audience' => $audience,
        ]);
    }

    /**
     * Update content
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'section' => 'required|string|max:50',
            'key' => 'required|string|max:100',
            'value' => 'required|string',
            'audience' => 'required|in:individual,institutional,both',
            'metadata' => 'nullable|array',
            'change_notes' => 'nullable|string|max:500',
        ]);

        try {
            $content = $this->contentService->updateContent(
                $validated['section'],
                $validated['key'],
                $validated['value'],
                $validated['audience'],
                $validated['metadata'] ?? null,
                $validated['change_notes'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Content updated successfully',
                'content' => $content->load(['creator', 'approver']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update content: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk update content
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'updates' => 'required|array',
            'updates.*.section' => 'required|string|max:50',
            'updates.*.key' => 'required|string|max:100',
            'updates.*.value' => 'required|string',
            'updates.*.audience' => 'required|in:individual,institutional,both',
            'updates.*.metadata' => 'nullable|array',
            'updates.*.change_notes' => 'nullable|string|max:500',
        ]);

        try {
            $results = $this->contentService->bulkUpdateContent($validated['updates']);

            return response()->json([
                'success' => true,
                'message' => 'Content updated successfully',
                'updated_count' => count($results),
                'content' => $results,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update content: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Request approval for content
     */
    public function requestApproval(Request $request, int $contentId): JsonResponse
    {
        $validated = $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            $approval = $this->contentService->requestApproval(
                $contentId,
                $validated['notes'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Approval requested successfully',
                'approval' => $approval->load(['requester']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to request approval: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Approve content
     */
    public function approve(Request $request, int $contentId): JsonResponse
    {
        $validated = $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            $this->contentService->approveContent(
                $contentId,
                $validated['notes'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Content approved successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve content: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reject content approval
     */
    public function reject(Request $request, int $contentId): JsonResponse
    {
        $validated = $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            $this->contentService->rejectContent(
                $contentId,
                $validated['notes'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Content approval rejected',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject content: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Publish approved content
     */
    public function publish(int $contentId): JsonResponse
    {
        try {
            $this->contentService->publishContent($contentId);

            return response()->json([
                'success' => true,
                'message' => 'Content published successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to publish content: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get content history/versions
     */
    public function history(int $contentId): JsonResponse
    {
        try {
            $history = $this->contentService->getContentHistory($contentId);

            return response()->json([
                'success' => true,
                'history' => $history,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get content history: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Revert to a specific version
     */
    public function revert(Request $request, int $contentId): JsonResponse
    {
        $validated = $request->validate([
            'version_number' => 'required|integer|min:1',
        ]);

        try {
            $content = $this->contentService->revertToVersion(
                $contentId,
                $validated['version_number']
            );

            return response()->json([
                'success' => true,
                'message' => 'Content reverted successfully',
                'content' => $content->load(['creator', 'approver']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to revert content: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Preview content changes
     */
    public function preview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'changes' => 'required|array',
            'changes.*.section' => 'required|string',
            'changes.*.key' => 'required|string',
            'changes.*.value' => 'required|string',
            'changes.*.metadata' => 'nullable|array',
            'audience' => 'required|in:individual,institutional,both',
        ]);

        try {
            $preview = $this->contentService->previewContent(
                $validated['changes'],
                $validated['audience']
            );

            return response()->json([
                'success' => true,
                'preview' => $preview,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate preview: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export content
     */
    public function export(Request $request): JsonResponse
    {
        $audience = $request->get('audience');

        try {
            $content = $this->contentService->exportContent($audience);

            return response()->json([
                'success' => true,
                'content' => $content,
                'exported_at' => now(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export content: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Import content
     */
    public function import(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'content' => 'required|array',
            'content.*.section' => 'required|string',
            'content.*.key' => 'required|string',
            'content.*.value' => 'required|string',
            'content.*.audience' => 'required|in:individual,institutional,both',
            'content.*.metadata' => 'nullable|array',
        ]);

        try {
            $results = $this->contentService->importContent($validated['content']);

            return response()->json([
                'success' => true,
                'message' => 'Content imported successfully',
                'imported_count' => count($results),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to import content: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get available sections for content
     */
    private function getAvailableSections(): array
    {
        return [
            'hero' => 'Hero Section',
            'social_proof' => 'Social Proof',
            'features' => 'Features Showcase',
            'success_stories' => 'Success Stories',
            'value_calculator' => 'Value Calculator',
            'platform_preview' => 'Platform Preview',
            'institutional_features' => 'Institutional Features',
            'branded_apps' => 'Branded Apps',
            'enterprise_testimonials' => 'Enterprise Testimonials',
            'pricing' => 'Pricing',
            'trust_security' => 'Trust & Security',
            'integration_ecosystem' => 'Integration Ecosystem',
            'conversion_ctas' => 'Conversion CTAs',
        ];
    }
}
