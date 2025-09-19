<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LandingPage;
use App\Models\PageVersion;
use App\Services\VersionControlService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class VersionControlController extends Controller
{
    public function __construct(
        private VersionControlService $versionControlService
    ) {}

    /**
     * Get version history for a page.
     */
    public function index(LandingPage $page): JsonResponse
    {
        $versions = $this->versionControlService->getVersionHistory($page);

        return response()->json([
            'success' => true,
            'data' => $versions,
        ]);
    }

    /**
     * Create a new version.
     */
    public function store(Request $request, LandingPage $page): JsonResponse
    {
        $validated = $request->validate([
            'grapejs_data' => 'required|array',
            'change_summary' => 'nullable|string|max:255',
            'metadata' => 'nullable|array',
        ]);

        try {
            $version = $this->versionControlService->createVersion(
                $page,
                $validated['grapejs_data'],
                $request->user(),
                $validated['change_summary'] ?? null,
                $validated['metadata'] ?? null
            );

            return response()->json([
                'success' => true,
                'data' => $version->load('creator:id,name,email'),
                'message' => 'Version created successfully',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create version: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a specific version.
     */
    public function show(LandingPage $page, PageVersion $version): JsonResponse
    {
        if ($version->page_id !== $page->id) {
            return response()->json([
                'success' => false,
                'message' => 'Version does not belong to this page',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $version->load('creator:id,name,email'),
        ]);
    }

    /**
     * Rollback to a specific version.
     */
    public function rollback(Request $request, LandingPage $page, PageVersion $version): JsonResponse
    {
        if ($version->page_id !== $page->id) {
            return response()->json([
                'success' => false,
                'message' => 'Version does not belong to this page',
            ], 404);
        }

        try {
            $newVersion = $this->versionControlService->rollbackToVersion(
                $page,
                $version->version_number,
                $request->user()
            );

            return response()->json([
                'success' => true,
                'data' => $newVersion->load('creator:id,name,email'),
                'message' => "Successfully rolled back to version {$version->version_number}",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to rollback: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Publish a specific version.
     */
    public function publish(LandingPage $page, PageVersion $version): JsonResponse
    {
        if ($version->page_id !== $page->id) {
            return response()->json([
                'success' => false,
                'message' => 'Version does not belong to this page',
            ], 404);
        }

        try {
            $publishedVersion = $this->versionControlService->publishVersion($version);

            return response()->json([
                'success' => true,
                'data' => $publishedVersion->load('creator:id,name,email'),
                'message' => "Version {$version->version_number} published successfully",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to publish version: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Compare two versions.
     */
    public function compare(
        LandingPage $page,
        PageVersion $version1,
        PageVersion $version2
    ): JsonResponse {
        if ($version1->page_id !== $page->id || $version2->page_id !== $page->id) {
            return response()->json([
                'success' => false,
                'message' => 'One or both versions do not belong to this page',
            ], 404);
        }

        try {
            $comparison = $this->versionControlService->compareVersions($version1, $version2);

            return response()->json([
                'success' => true,
                'data' => $comparison,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to compare versions: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Auto-save current state.
     */
    public function autoSave(Request $request, LandingPage $page): JsonResponse
    {
        $validated = $request->validate([
            'grapejs_data' => 'required|array',
        ]);

        try {
            $version = $this->versionControlService->autoSaveVersion(
                $page,
                $validated['grapejs_data'],
                $request->user()
            );

            return response()->json([
                'success' => true,
                'data' => $version,
                'message' => 'Auto-save completed',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Auto-save failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get the currently published version.
     */
    public function published(LandingPage $page): JsonResponse
    {
        $publishedVersion = $this->versionControlService->getPublishedVersion($page);

        if (!$publishedVersion) {
            return response()->json([
                'success' => false,
                'message' => 'No published version found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $publishedVersion->load('creator:id,name,email'),
        ]);
    }
}
