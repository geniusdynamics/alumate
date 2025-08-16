<?php

namespace App\Services;

use App\Models\SuccessStory;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SuccessStoryService
{
    public function createStory(array $data, User $user): SuccessStory
    {
        // Handle media uploads
        if (isset($data['media_files'])) {
            $data['media_urls'] = $this->uploadMediaFiles($data['media_files']);
            unset($data['media_files']);
        }

        // Handle featured image upload
        if (isset($data['featured_image_file'])) {
            $data['featured_image'] = $this->uploadFeaturedImage($data['featured_image_file']);
            unset($data['featured_image_file']);
        }

        // Set user_id
        $data['user_id'] = $user->id;

        // Auto-populate user data if not provided
        if (! isset($data['graduation_year']) && $user->graduations->isNotEmpty()) {
            $data['graduation_year'] = $user->graduations->first()->graduation_year;
        }

        if (! isset($data['degree_program']) && $user->graduations->isNotEmpty()) {
            $data['degree_program'] = $user->graduations->first()->degree;
        }

        return SuccessStory::create($data);
    }

    public function updateStory(SuccessStory $story, array $data): SuccessStory
    {
        // Handle media uploads
        if (isset($data['media_files'])) {
            $data['media_urls'] = $this->uploadMediaFiles($data['media_files']);
            unset($data['media_files']);
        }

        // Handle featured image upload
        if (isset($data['featured_image_file'])) {
            // Delete old featured image
            if ($story->featured_image) {
                Storage::disk('public')->delete($story->featured_image);
            }
            $data['featured_image'] = $this->uploadFeaturedImage($data['featured_image_file']);
            unset($data['featured_image_file']);
        }

        $story->update($data);

        return $story->fresh();
    }

    public function getStoriesWithFilters(array $filters = [], int $perPage = 12): LengthAwarePaginator
    {
        $query = SuccessStory::with('user')->published();

        // Apply filters
        if (isset($filters['industry']) && $filters['industry']) {
            $query->byIndustry($filters['industry']);
        }

        if (isset($filters['achievement_type']) && $filters['achievement_type']) {
            $query->byAchievementType($filters['achievement_type']);
        }

        if (isset($filters['graduation_year']) && $filters['graduation_year']) {
            $query->byGraduationYear($filters['graduation_year']);
        }

        if (isset($filters['tags']) && is_array($filters['tags'])) {
            foreach ($filters['tags'] as $tag) {
                $query->whereJsonContains('tags', $tag);
            }
        }

        if (isset($filters['search']) && $filters['search']) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('summary', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Sort by featured first, then by published date
        $query->orderBy('is_featured', 'desc')
            ->orderBy('published_at', 'desc');

        return $query->paginate($perPage);
    }

    public function getFeaturedStories(int $limit = 6): \Illuminate\Database\Eloquent\Collection
    {
        return SuccessStory::with('user')
            ->featured()
            ->orderBy('featured_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getRecommendedStories(User $user, int $limit = 6): \Illuminate\Database\Eloquent\Collection
    {
        $query = SuccessStory::with('user')->published();

        // Recommend based on user's industry, graduation year, or interests
        if ($user->industry) {
            $query->where('industry', $user->industry);
        } elseif ($user->graduations->isNotEmpty()) {
            $graduationYear = $user->graduations->first()->graduation_year;
            $query->where('graduation_year', $graduationYear);
        }

        return $query->orderBy('view_count', 'desc')
            ->orderBy('published_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getStoriesByDemographics(array $demographics, int $limit = 12): \Illuminate\Database\Eloquent\Collection
    {
        $query = SuccessStory::with('user')->published();

        foreach ($demographics as $key => $value) {
            $query->whereJsonContains("demographics->{$key}", $value);
        }

        return $query->orderBy('published_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getAnalytics(): array
    {
        $totalStories = SuccessStory::published()->count();
        $featuredStories = SuccessStory::featured()->count();
        $totalViews = SuccessStory::published()->sum('view_count');
        $totalShares = SuccessStory::published()->sum('share_count');

        $topIndustries = SuccessStory::published()
            ->selectRaw('industry, COUNT(*) as count')
            ->whereNotNull('industry')
            ->groupBy('industry')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();

        $topAchievementTypes = SuccessStory::published()
            ->selectRaw('achievement_type, COUNT(*) as count')
            ->groupBy('achievement_type')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();

        return [
            'total_stories' => $totalStories,
            'featured_stories' => $featuredStories,
            'total_views' => $totalViews,
            'total_shares' => $totalShares,
            'top_industries' => $topIndustries,
            'top_achievement_types' => $topAchievementTypes,
        ];
    }

    private function uploadMediaFiles(array $files): array
    {
        $uploadedFiles = [];

        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $filename = Str::uuid().'.'.$file->getClientOriginalExtension();
                $path = $file->storeAs('success-stories/media', $filename, 'public');
                $uploadedFiles[] = $path;
            }
        }

        return $uploadedFiles;
    }

    private function uploadFeaturedImage(UploadedFile $file): string
    {
        $filename = Str::uuid().'.'.$file->getClientOriginalExtension();

        return $file->storeAs('success-stories/featured', $filename, 'public');
    }

    public function deleteStory(SuccessStory $story): bool
    {
        // Delete associated media files
        if ($story->featured_image) {
            Storage::disk('public')->delete($story->featured_image);
        }

        if ($story->media_urls) {
            foreach ($story->media_urls as $mediaUrl) {
                Storage::disk('public')->delete($mediaUrl);
            }
        }

        return $story->delete();
    }
}
