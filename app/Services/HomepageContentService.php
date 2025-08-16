<?php

namespace App\Services;

use App\Models\HomepageContent;
use App\Models\HomepageContentApproval;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HomepageContentService
{
    /**
     * Get all content for a specific audience and section
     */
    public function getContent(string $audience = 'both', ?string $section = null): Collection
    {
        $cacheKey = "homepage_content_{$audience}_{$section}";

        return Cache::remember($cacheKey, 3600, function () use ($audience, $section) {
            $query = HomepageContent::published()
                ->forAudience($audience)
                ->with(['creator', 'approver']);

            if ($section) {
                $query->forSection($section);
            }

            return $query->get();
        });
    }

    /**
     * Get content formatted for frontend consumption
     */
    public function getFormattedContent(string $audience = 'both'): array
    {
        $content = $this->getContent($audience);
        $formatted = [];

        foreach ($content as $item) {
            $section = $item->section;
            $key = $item->key;

            if (! isset($formatted[$section])) {
                $formatted[$section] = [];
            }

            $formatted[$section][$key] = [
                'value' => $item->value,
                'metadata' => $item->metadata,
                'updated_at' => $item->updated_at,
            ];
        }

        return $formatted;
    }

    /**
     * Create or update content
     */
    public function updateContent(
        string $section,
        string $key,
        string $value,
        string $audience = 'both',
        ?array $metadata = null,
        ?string $changeNotes = null
    ): HomepageContent {
        return DB::transaction(function () use ($section, $key, $value, $audience, $metadata, $changeNotes) {
            $content = HomepageContent::where('section', $section)
                ->where('key', $key)
                ->where('audience', $audience)
                ->first();

            if ($content) {
                // Create version before updating
                $content->createVersion($changeNotes);

                $content->update([
                    'value' => $value,
                    'metadata' => $metadata,
                    'status' => 'draft',
                ]);
            } else {
                $content = HomepageContent::create([
                    'section' => $section,
                    'key' => $key,
                    'value' => $value,
                    'audience' => $audience,
                    'metadata' => $metadata,
                    'status' => 'draft',
                    'created_by' => auth()->id(),
                ]);

                // Create initial version
                $content->createVersion('Initial version');
            }

            // Clear cache
            $this->clearContentCache($audience, $section);

            return $content;
        });
    }

    /**
     * Bulk update content
     */
    public function bulkUpdateContent(array $updates): array
    {
        $results = [];

        DB::transaction(function () use ($updates, &$results) {
            foreach ($updates as $update) {
                $results[] = $this->updateContent(
                    $update['section'],
                    $update['key'],
                    $update['value'],
                    $update['audience'] ?? 'both',
                    $update['metadata'] ?? null,
                    $update['change_notes'] ?? null
                );
            }
        });

        return $results;
    }

    /**
     * Request approval for content
     */
    public function requestApproval(int $contentId, ?string $notes = null): HomepageContentApproval
    {
        $content = HomepageContent::findOrFail($contentId);

        // Update status to pending
        $content->update(['status' => 'pending']);

        $approval = $content->requestApproval($notes);

        // Clear cache
        $this->clearContentCache($content->audience, $content->section);

        return $approval;
    }

    /**
     * Approve content
     */
    public function approveContent(int $contentId, ?string $notes = null): void
    {
        $content = HomepageContent::findOrFail($contentId);
        $content->approve(auth()->id(), $notes);

        // Clear cache
        $this->clearContentCache($content->audience, $content->section);
    }

    /**
     * Reject content approval
     */
    public function rejectContent(int $contentId, ?string $notes = null): void
    {
        $content = HomepageContent::findOrFail($contentId);

        $content->update(['status' => 'draft']);

        $content->latestApproval()->update([
            'status' => 'rejected',
            'reviewer_id' => auth()->id(),
            'review_notes' => $notes,
            'reviewed_at' => now(),
        ]);

        // Clear cache
        $this->clearContentCache($content->audience, $content->section);
    }

    /**
     * Publish approved content
     */
    public function publishContent(int $contentId): void
    {
        $content = HomepageContent::findOrFail($contentId);

        if ($content->status !== 'approved') {
            throw new \Exception('Content must be approved before publishing');
        }

        $content->publish();

        // Clear cache
        $this->clearContentCache($content->audience, $content->section);
    }

    /**
     * Get pending approvals
     */
    public function getPendingApprovals(): Collection
    {
        return HomepageContentApproval::pending()
            ->with(['homepageContent', 'requester'])
            ->orderBy('requested_at', 'asc')
            ->get();
    }

    /**
     * Get content history/versions
     */
    public function getContentHistory(int $contentId): Collection
    {
        $content = HomepageContent::findOrFail($contentId);

        return $content->versions()
            ->with('creator')
            ->orderBy('version_number', 'desc')
            ->get();
    }

    /**
     * Revert to a specific version
     */
    public function revertToVersion(int $contentId, int $versionNumber): HomepageContent
    {
        return DB::transaction(function () use ($contentId, $versionNumber) {
            $content = HomepageContent::findOrFail($contentId);
            $version = $content->versions()
                ->where('version_number', $versionNumber)
                ->firstOrFail();

            // Create new version with current content before reverting
            $content->createVersion("Reverted to version {$versionNumber}");

            // Update content with version data
            $content->update([
                'value' => $version->value,
                'metadata' => $version->metadata,
                'status' => 'draft',
            ]);

            // Clear cache
            $this->clearContentCache($content->audience, $content->section);

            return $content;
        });
    }

    /**
     * Export content for backup/migration
     */
    public function exportContent(?string $audience = null): array
    {
        $query = HomepageContent::with(['versions', 'creator']);

        if ($audience) {
            $query->forAudience($audience);
        }

        return $query->get()->toArray();
    }

    /**
     * Import content from backup/migration
     */
    public function importContent(array $contentData): array
    {
        $results = [];

        DB::transaction(function () use ($contentData, &$results) {
            foreach ($contentData as $data) {
                $content = HomepageContent::updateOrCreate(
                    [
                        'section' => $data['section'],
                        'key' => $data['key'],
                        'audience' => $data['audience'],
                    ],
                    [
                        'value' => $data['value'],
                        'metadata' => $data['metadata'],
                        'status' => 'draft',
                        'created_by' => auth()->id(),
                    ]
                );

                $results[] = $content;
            }
        });

        // Clear all cache
        $this->clearAllContentCache();

        return $results;
    }

    /**
     * Preview content changes
     */
    public function previewContent(array $changes, string $audience = 'both'): array
    {
        $currentContent = $this->getFormattedContent($audience);

        // Apply changes to preview
        foreach ($changes as $change) {
            $section = $change['section'];
            $key = $change['key'];
            $value = $change['value'];
            $metadata = $change['metadata'] ?? null;

            if (! isset($currentContent[$section])) {
                $currentContent[$section] = [];
            }

            $currentContent[$section][$key] = [
                'value' => $value,
                'metadata' => $metadata,
                'updated_at' => now(),
                'preview' => true,
            ];
        }

        return $currentContent;
    }

    /**
     * Clear content cache
     */
    private function clearContentCache(string $audience, ?string $section = null): void
    {
        $patterns = [
            "homepage_content_{$audience}_",
            'homepage_content_both_',
        ];

        if ($section) {
            $patterns = [
                "homepage_content_{$audience}_{$section}",
                "homepage_content_both_{$section}",
            ];
        }

        foreach ($patterns as $pattern) {
            Cache::forget($pattern);
        }
    }

    /**
     * Clear all content cache
     */
    private function clearAllContentCache(): void
    {
        $audiences = ['individual', 'institutional', 'both'];
        $sections = ['hero', 'social_proof', 'features', 'success_stories', 'pricing', 'trust'];

        foreach ($audiences as $audience) {
            foreach ($sections as $section) {
                Cache::forget("homepage_content_{$audience}_{$section}");
            }
            Cache::forget("homepage_content_{$audience}_");
        }
    }
}
