<?php

namespace App\Services;

use App\Models\Event;
use App\Models\ReunionMemory;
use App\Models\ReunionMemoryComment;
use App\Models\ReunionMemoryLike;
use App\Models\ReunionPhoto;
use App\Models\ReunionPhotoComment;
use App\Models\ReunionPhotoLike;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReunionService
{
    public function createReunionEvent(array $data, User $organizer): Event
    {
        $eventData = array_merge($data, [
            'organizer_id' => $organizer->id,
            'is_reunion' => true,
            'type' => 'reunion',
            'enable_photo_sharing' => $data['enable_photo_sharing'] ?? true,
            'enable_memory_wall' => $data['enable_memory_wall'] ?? true,
        ]);

        // Calculate reunion milestone if graduation year is provided
        if (isset($data['graduation_year'])) {
            $eventData['reunion_year_milestone'] = now()->year - $data['graduation_year'];
        }

        // Set default memory collection settings
        if (! isset($eventData['memory_collection_settings'])) {
            $eventData['memory_collection_settings'] = [
                'allow_anonymous_submissions' => false,
                'require_approval' => true,
                'allow_photo_uploads' => true,
                'max_photos_per_memory' => 5,
                'memory_types' => ['story', 'achievement', 'memory', 'tribute', 'update'],
            ];
        }

        return Event::create($eventData);
    }

    public function uploadReunionPhoto(Event $event, UploadedFile $file, User $user, array $data = []): ReunionPhoto
    {
        if (! $event->hasPhotoSharing()) {
            throw new \Exception('Photo sharing is not enabled for this reunion.');
        }

        // Generate unique filename
        $filename = time().'_'.$file->getClientOriginalName();
        $path = "reunion-photos/{$event->id}/".$filename;

        // Store the file
        $file->storeAs('public/'.dirname($path), basename($path));

        // Extract metadata
        $metadata = [
            'original_name' => $file->getClientOriginalName(),
            'dimensions' => $this->getImageDimensions($file),
            'exif' => $this->extractExifData($file),
        ];

        $photoData = array_merge($data, [
            'event_id' => $event->id,
            'uploaded_by' => $user->id,
            'file_path' => $path,
            'file_name' => $filename,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'metadata' => $metadata,
        ]);

        $photo = ReunionPhoto::create($photoData);

        // Generate thumbnail
        $this->generateThumbnail($photo);

        return $photo;
    }

    public function createReunionMemory(Event $event, User $user, array $data): ReunionMemory
    {
        if (! $event->hasMemoryWall()) {
            throw new \Exception('Memory wall is not enabled for this reunion.');
        }

        $memoryData = array_merge($data, [
            'event_id' => $event->id,
            'submitted_by' => $user->id,
        ]);

        // Set approval status based on event settings
        $settings = $event->getMemoryCollectionSettings();
        $memoryData['is_approved'] = ! ($settings['require_approval'] ?? true);

        return ReunionMemory::create($memoryData);
    }

    public function likePhoto(ReunionPhoto $photo, User $user): bool
    {
        if ($photo->isLikedBy($user)) {
            return false;
        }

        DB::transaction(function () use ($photo, $user) {
            ReunionPhotoLike::create([
                'reunion_photo_id' => $photo->id,
                'user_id' => $user->id,
            ]);
            $photo->incrementLikes();
        });

        return true;
    }

    public function unlikePhoto(ReunionPhoto $photo, User $user): bool
    {
        if (! $photo->isLikedBy($user)) {
            return false;
        }

        DB::transaction(function () use ($photo, $user) {
            ReunionPhotoLike::where('reunion_photo_id', $photo->id)
                ->where('user_id', $user->id)
                ->delete();
            $photo->decrementLikes();
        });

        return true;
    }

    public function likeMemory(ReunionMemory $memory, User $user): bool
    {
        if ($memory->isLikedBy($user)) {
            return false;
        }

        DB::transaction(function () use ($memory, $user) {
            ReunionMemoryLike::create([
                'reunion_memory_id' => $memory->id,
                'user_id' => $user->id,
            ]);
            $memory->incrementLikes();
        });

        return true;
    }

    public function unlikeMemory(ReunionMemory $memory, User $user): bool
    {
        if (! $memory->isLikedBy($user)) {
            return false;
        }

        DB::transaction(function () use ($memory, $user) {
            ReunionMemoryLike::where('reunion_memory_id', $memory->id)
                ->where('user_id', $user->id)
                ->delete();
            $memory->decrementLikes();
        });

        return true;
    }

    public function commentOnPhoto(ReunionPhoto $photo, User $user, string $comment): ReunionPhotoComment
    {
        $commentModel = ReunionPhotoComment::create([
            'reunion_photo_id' => $photo->id,
            'user_id' => $user->id,
            'comment' => $comment,
        ]);

        $photo->incrementComments();

        return $commentModel;
    }

    public function commentOnMemory(ReunionMemory $memory, User $user, string $comment): ReunionMemoryComment
    {
        $commentModel = ReunionMemoryComment::create([
            'reunion_memory_id' => $memory->id,
            'user_id' => $user->id,
            'comment' => $comment,
        ]);

        $memory->incrementComments();

        return $commentModel;
    }

    public function getReunionPhotos(Event $event, User $user, array $filters = []): Collection
    {
        $query = $event->reunionPhotos()
            ->approved()
            ->forUser($user)
            ->with(['uploader', 'likes', 'comments.user'])
            ->orderBy('created_at', 'desc');

        if (isset($filters['featured']) && $filters['featured']) {
            $query->featured();
        }

        if (isset($filters['uploaded_by'])) {
            $query->where('uploaded_by', $filters['uploaded_by']);
        }

        return $query->get();
    }

    public function getReunionMemories(Event $event, User $user, array $filters = []): Collection
    {
        $query = $event->reunionMemories()
            ->approved()
            ->forUser($user)
            ->with(['submitter', 'likes', 'comments.user'])
            ->orderBy('created_at', 'desc');

        if (isset($filters['featured']) && $filters['featured']) {
            $query->featured();
        }

        if (isset($filters['type'])) {
            $query->byType($filters['type']);
        }

        if (isset($filters['submitted_by'])) {
            $query->where('submitted_by', $filters['submitted_by']);
        }

        return $query->get();
    }

    public function getReunionsByGraduationYear(int $year, User $user): Collection
    {
        return Event::reunions()
            ->byGraduationYear($year)
            ->published()
            ->where(function ($query) use ($user) {
                $query->where('visibility', 'public')
                    ->orWhere('visibility', 'alumni_only')
                    ->orWhere(function ($subQuery) use ($user) {
                        $subQuery->where('visibility', 'institution_only')
                            ->where('institution_id', $user->institution_id);
                    });
            })
            ->with(['organizer', 'institution'])
            ->orderBy('start_date', 'desc')
            ->get();
    }

    public function getUpcomingReunionMilestones(User $user): Collection
    {
        if (! $user->graduation_year) {
            return collect();
        }

        $currentYear = now()->year;
        $graduationYear = $user->graduation_year;
        $yearsSinceGraduation = $currentYear - $graduationYear;

        // Common reunion milestones
        $milestones = [5, 10, 15, 20, 25, 30, 35, 40, 45, 50];

        $upcomingMilestones = collect($milestones)
            ->filter(function ($milestone) use ($yearsSinceGraduation) {
                return $milestone > $yearsSinceGraduation;
            })
            ->take(3)
            ->map(function ($milestone) use ($graduationYear) {
                return [
                    'milestone' => $milestone,
                    'year' => $graduationYear + $milestone,
                    'graduation_year' => $graduationYear,
                    'years_away' => ($graduationYear + $milestone) - now()->year,
                ];
            });

        return $upcomingMilestones;
    }

    public function generateReunionStatistics(Event $event): array
    {
        $photos = $event->reunionPhotos()->approved()->count();
        $memories = $event->reunionMemories()->approved()->count();
        $registrations = $event->registrations()->whereIn('status', ['registered', 'attended'])->count();
        $checkIns = $event->checkIns()->count();

        return [
            'total_photos' => $photos,
            'total_memories' => $memories,
            'total_registered' => $registrations,
            'total_attended' => $checkIns,
            'attendance_rate' => $registrations > 0 ? round(($checkIns / $registrations) * 100, 2) : 0,
            'engagement_score' => $this->calculateEngagementScore($event),
        ];
    }

    public function suggestReunionCommitteeMembers(Event $event): Collection
    {
        // Get alumni from the same graduation year and institution
        $query = User::where('graduation_year', $event->graduation_year)
            ->where('institution_id', $event->institution_id)
            ->whereHas('roles', function ($roleQuery) {
                $roleQuery->where('name', 'alumni');
            });

        // Exclude current committee members
        $currentCommitteeIds = collect($event->getCommitteeMembers())->pluck('user_id');
        if ($currentCommitteeIds->isNotEmpty()) {
            $query->whereNotIn('id', $currentCommitteeIds);
        }

        return $query->with(['socialProfiles'])
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();
    }

    private function getImageDimensions(UploadedFile $file): ?array
    {
        if (! in_array($file->getMimeType(), ['image/jpeg', 'image/png', 'image/gif'])) {
            return null;
        }

        $imageSize = getimagesize($file->getPathname());
        if ($imageSize === false) {
            return null;
        }

        return [
            'width' => $imageSize[0],
            'height' => $imageSize[1],
        ];
    }

    private function extractExifData(UploadedFile $file): ?array
    {
        if ($file->getMimeType() !== 'image/jpeg') {
            return null;
        }

        $exif = @exif_read_data($file->getPathname());
        if ($exif === false) {
            return null;
        }

        // Extract relevant EXIF data
        return [
            'camera' => $exif['Model'] ?? null,
            'date_taken' => $exif['DateTime'] ?? null,
            'gps' => isset($exif['GPSLatitude']) ? [
                'latitude' => $this->convertGpsCoordinate($exif['GPSLatitude'], $exif['GPSLatitudeRef']),
                'longitude' => $this->convertGpsCoordinate($exif['GPSLongitude'], $exif['GPSLongitudeRef']),
            ] : null,
        ];
    }

    private function convertGpsCoordinate(array $coordinate, string $ref): float
    {
        $degrees = $coordinate[0];
        $minutes = $coordinate[1];
        $seconds = $coordinate[2];

        $decimal = $degrees + ($minutes / 60) + ($seconds / 3600);

        if (in_array($ref, ['S', 'W'])) {
            $decimal *= -1;
        }

        return $decimal;
    }

    private function generateThumbnail(ReunionPhoto $photo): void
    {
        // This would typically use an image processing library like Intervention Image
        // For now, we'll just create a placeholder implementation
        $originalPath = storage_path('app/public/'.$photo->file_path);
        $thumbnailDir = dirname($originalPath).'/thumbnails';

        if (! is_dir($thumbnailDir)) {
            mkdir($thumbnailDir, 0755, true);
        }

        // In a real implementation, you would resize the image here
        // For now, we'll just copy the original as a placeholder
        $thumbnailPath = $thumbnailDir.'/'.pathinfo($photo->file_name, PATHINFO_FILENAME).'_thumb.'.pathinfo($photo->file_name, PATHINFO_EXTENSION);
        copy($originalPath, $thumbnailPath);
    }

    private function calculateEngagementScore(Event $event): float
    {
        $registrations = $event->registrations()->count();
        if ($registrations === 0) {
            return 0;
        }

        $photos = $event->reunionPhotos()->approved()->count();
        $memories = $event->reunionMemories()->approved()->count();
        $checkIns = $event->checkIns()->count();

        // Calculate engagement as a percentage of registered users who engaged
        $engagementActions = $photos + $memories + $checkIns;
        $engagementRate = ($engagementActions / $registrations) * 100;

        return round(min($engagementRate, 100), 2);
    }
}
