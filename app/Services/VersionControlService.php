<?php

namespace App\Services;

use App\Models\LandingPage;
use App\Models\PageVersion;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class VersionControlService
{
    public function createVersion(
        LandingPage $page,
        array $grapeJSData,
        User $user,
        ?string $changeSummary = null,
        ?array $metadata = null
    ): PageVersion {
        return DB::transaction(function () use ($page, $grapeJSData, $user, $changeSummary, $metadata) {
            $latestVersion = PageVersion::forPage($page->id)
                ->orderBy('version_number', 'desc')
                ->first();

            $versionNumber = $latestVersion ? $latestVersion->version_number + 1 : 1;

            return PageVersion::create([
                'page_id' => $page->id,
                'version_number' => $versionNumber,
                'grapejs_data' => $grapeJSData,
                'metadata' => $metadata ?? [],
                'change_summary' => $changeSummary,
                'created_by' => $user->id,
                'is_published' => false,
            ]);
        });
    }

    public function getVersionHistory(LandingPage $page, int $limit = 50): Collection
    {
        return PageVersion::forPage($page->id)
            ->with(['creator:id,name,email'])
            ->orderBy('version_number', 'desc')
            ->limit($limit)
            ->get();
    }

    public function rollbackToVersion(LandingPage $page, int $versionNumber, User $user): PageVersion
    {
        return DB::transaction(function () use ($page, $versionNumber, $user) {
            $targetVersion = PageVersion::forPage($page->id)
                ->where('version_number', $versionNumber)
                ->firstOrFail();

            // Create a new version with the rolled-back data
            $newVersion = $this->createVersion(
                $page,
                $targetVersion->grapejs_data,
                $user,
                "Rolled back to version {$versionNumber}",
                [
                    'rollback_from_version' => $this->getLatestVersionNumber($page),
                    'rollback_to_version' => $versionNumber,
                    'rollback_timestamp' => now()->toISOString(),
                ]
            );

            // Update the page with the rolled-back data
            $page->update([
                'configuration' => $targetVersion->grapejs_data,
                'updated_at' => now(),
            ]);

            return $newVersion;
        });
    }

    public function publishVersion(PageVersion $version): PageVersion
    {
        return DB::transaction(function () use ($version) {
            // Unpublish any currently published version
            PageVersion::forPage($version->page_id)
                ->where('is_published', true)
                ->update(['is_published' => false]);

            // Publish the selected version
            $version->update([
                'is_published' => true,
                'published_at' => now(),
            ]);

            // Update the main page with published version data
            $version->page->update([
                'configuration' => $version->grapejs_data,
                'updated_at' => now(),
            ]);

            return $version->fresh();
        });
    }

    public function getPublishedVersion(LandingPage $page): ?PageVersion
    {
        return PageVersion::forPage($page->id)
            ->published()
            ->first();
    }

    public function compareVersions(PageVersion $version1, PageVersion $version2): array
    {
        return [
            'version_1' => [
                'number' => $version1->version_number,
                'created_at' => $version1->created_at,
                'creator' => $version1->creator->name,
                'data' => $version1->grapejs_data,
            ],
            'version_2' => [
                'number' => $version2->version_number,
                'created_at' => $version2->created_at,
                'creator' => $version2->creator->name,
                'data' => $version2->grapejs_data,
            ],
            'differences' => $this->calculateDifferences(
                $version1->grapejs_data,
                $version2->grapejs_data
            ),
        ];
    }

    public function getLatestVersionNumber(LandingPage $page): int
    {
        $latestVersion = PageVersion::forPage($page->id)
            ->orderBy('version_number', 'desc')
            ->first();

        return $latestVersion ? $latestVersion->version_number : 0;
    }

    public function autoSaveVersion(
        LandingPage $page,
        array $grapeJSData,
        User $user
    ): PageVersion {
        // Check if there's a recent auto-save (within last 5 minutes)
        $recentAutoSave = PageVersion::forPage($page->id)
            ->where('created_by', $user->id)
            ->where('change_summary', 'LIKE', 'Auto-save%')
            ->where('created_at', '>=', now()->subMinutes(5))
            ->orderBy('version_number', 'desc')
            ->first();

        if ($recentAutoSave) {
            // Update the existing auto-save
            $recentAutoSave->update([
                'grapejs_data' => $grapeJSData,
                'updated_at' => now(),
            ]);

            return $recentAutoSave;
        }

        // Create new auto-save version
        return $this->createVersion(
            $page,
            $grapeJSData,
            $user,
            'Auto-save at ' . now()->format('H:i:s'),
            ['auto_save' => true]
        );
    }

    private function calculateDifferences(array $data1, array $data2): array
    {
        // Simple difference calculation - in production, you might want
        // to use a more sophisticated diff algorithm
        $differences = [];

        // Compare HTML structure
        if (isset($data1['html']) && isset($data2['html'])) {
            if ($data1['html'] !== $data2['html']) {
                $differences['html'] = 'HTML structure changed';
            }
        }

        // Compare CSS
        if (isset($data1['css']) && isset($data2['css'])) {
            if ($data1['css'] !== $data2['css']) {
                $differences['css'] = 'Styles changed';
            }
        }

        // Compare components
        if (isset($data1['components']) && isset($data2['components'])) {
            $components1 = count($data1['components']);
            $components2 = count($data2['components']);
            
            if ($components1 !== $components2) {
                $differences['components'] = "Component count changed from {$components1} to {$components2}";
            }
        }

        return $differences;
    }
}
