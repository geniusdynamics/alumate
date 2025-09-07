<?php

namespace App\Services;

use App\Models\Testimonial;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class TestimonialService
{
    /**
     * Get testimonials with filtering and pagination
     */
    public function getTestimonials(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Testimonial::query();

        // Apply filters
        if (!empty($filters['audience_type'])) {
            $query->byAudienceType($filters['audience_type']);
        }

        if (!empty($filters['industry'])) {
            $query->byIndustry($filters['industry']);
        }

        if (!empty($filters['graduation_year'])) {
            $query->byGraduationYear($filters['graduation_year']);
        }

        if (!empty($filters['graduation_year_range'])) {
            $range = explode('-', $filters['graduation_year_range']);
            if (count($range) === 2) {
                $query->byGraduationYearRange((int)$range[0], (int)$range[1]);
            }
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        } else {
            // Default to approved testimonials for public display
            $query->approved();
        }

        if (!empty($filters['featured'])) {
            $query->featured();
        }

        if (!empty($filters['has_video'])) {
            if ($filters['has_video'] === 'true' || $filters['has_video'] === true) {
                $query->withVideo();
            } else {
                $query->textOnly();
            }
        }

        // Apply sorting
        $sortBy = $filters['sort_by'] ?? 'performance';
        switch ($sortBy) {
            case 'performance':
                $query->byPerformance();
                break;
            case 'random':
                $query->randomized();
                break;
            case 'newest':
                $query->orderByDesc('created_at');
                break;
            case 'oldest':
                $query->orderBy('created_at');
                break;
            case 'rating':
                $query->orderByDesc('rating');
                break;
            default:
                $query->byPerformance();
        }

        return $query->paginate($perPage);
    }

    /**
     * Get testimonials for rotation with variety
     */
    public function getTestimonialsForRotation(array $filters = [], int $limit = 10): Collection
    {
        $query = Testimonial::approved();

        // Apply filters
        if (!empty($filters['audience_type'])) {
            $query->byAudienceType($filters['audience_type']);
        }

        if (!empty($filters['industry'])) {
            $query->byIndustry($filters['industry']);
        }

        if (!empty($filters['graduation_year_range'])) {
            $range = explode('-', $filters['graduation_year_range']);
            if (count($range) === 2) {
                $query->byGraduationYearRange((int)$range[0], (int)$range[1]);
            }
        }

        // Get a mix of featured and regular testimonials
        $featuredCount = (int)($limit * 0.4); // 40% featured
        $regularCount = $limit - $featuredCount;

        $featured = (clone $query)->featured()->randomized()->limit($featuredCount)->get();
        $regular = (clone $query)->where('featured', false)->randomized()->limit($regularCount)->get();

        return $featured->merge($regular)->shuffle();
    }

    /**
     * Create a new testimonial
     */
    public function createTestimonial(array $data): Testimonial
    {
        $this->validateTestimonialData($data);

        // Set tenant_id from authenticated user if not provided
        if (empty($data['tenant_id']) && auth()->check() && auth()->user()->tenant_id) {
            $data['tenant_id'] = auth()->user()->tenant_id;
        }

        $testimonial = Testimonial::create($data);

        Log::info('Testimonial created', [
            'testimonial_id' => $testimonial->id,
            'author_name' => $testimonial->author_name,
            'tenant_id' => $testimonial->tenant_id,
        ]);

        return $testimonial;
    }

    /**
     * Update an existing testimonial
     */
    public function updateTestimonial(Testimonial $testimonial, array $data): Testimonial
    {
        $this->validateTestimonialData($data, $testimonial->id);

        $testimonial->update($data);

        Log::info('Testimonial updated', [
            'testimonial_id' => $testimonial->id,
            'author_name' => $testimonial->author_name,
        ]);

        return $testimonial->fresh();
    }

    /**
     * Delete a testimonial
     */
    public function deleteTestimonial(Testimonial $testimonial): bool
    {
        $testimonialId = $testimonial->id;
        $authorName = $testimonial->author_name;

        $deleted = $testimonial->delete();

        if ($deleted) {
            Log::info('Testimonial deleted', [
                'testimonial_id' => $testimonialId,
                'author_name' => $authorName,
            ]);
        }

        return $deleted;
    }

    /**
     * Approve a testimonial
     */
    public function approveTestimonial(Testimonial $testimonial): bool
    {
        $approved = $testimonial->approve();

        if ($approved) {
            Log::info('Testimonial approved', [
                'testimonial_id' => $testimonial->id,
                'author_name' => $testimonial->author_name,
            ]);
        }

        return $approved;
    }

    /**
     * Reject a testimonial
     */
    public function rejectTestimonial(Testimonial $testimonial): bool
    {
        $rejected = $testimonial->reject();

        if ($rejected) {
            Log::info('Testimonial rejected', [
                'testimonial_id' => $testimonial->id,
                'author_name' => $testimonial->author_name,
            ]);
        }

        return $rejected;
    }

    /**
     * Archive a testimonial
     */
    public function archiveTestimonial(Testimonial $testimonial): bool
    {
        $archived = $testimonial->archive();

        if ($archived) {
            Log::info('Testimonial archived', [
                'testimonial_id' => $testimonial->id,
                'author_name' => $testimonial->author_name,
            ]);
        }

        return $archived;
    }

    /**
     * Set testimonial as featured
     */
    public function setFeatured(Testimonial $testimonial, bool $featured = true): bool
    {
        $updated = $testimonial->setFeatured($featured);

        if ($updated) {
            Log::info('Testimonial featured status updated', [
                'testimonial_id' => $testimonial->id,
                'featured' => $featured,
            ]);
        }

        return $updated;
    }

    /**
     * Track testimonial view
     */
    public function trackView(Testimonial $testimonial): void
    {
        $testimonial->incrementViewCount();
        $testimonial->updateConversionRate();
    }

    /**
     * Track testimonial click
     */
    public function trackClick(Testimonial $testimonial): void
    {
        $testimonial->incrementClickCount();
        $testimonial->updateConversionRate();
    }

    /**
     * Get testimonial performance analytics
     */
    public function getPerformanceAnalytics(array $filters = []): array
    {
        $query = Testimonial::approved();

        // Apply filters
        if (!empty($filters['audience_type'])) {
            $query->byAudienceType($filters['audience_type']);
        }

        if (!empty($filters['industry'])) {
            $query->byIndustry($filters['industry']);
        }

        if (!empty($filters['date_range'])) {
            $range = explode(',', $filters['date_range']);
            if (count($range) === 2) {
                $query->whereBetween('created_at', $range);
            }
        }

        $analytics = $query->selectRaw('
            COUNT(*) as total_testimonials,
            SUM(view_count) as total_views,
            SUM(click_count) as total_clicks,
            AVG(conversion_rate) as avg_conversion_rate,
            AVG(rating) as avg_rating,
            COUNT(CASE WHEN featured = 1 THEN 1 END) as featured_count,
            COUNT(CASE WHEN video_url IS NOT NULL THEN 1 END) as video_count
        ')->first();

        return [
            'total_testimonials' => $analytics->total_testimonials ?? 0,
            'total_views' => $analytics->total_views ?? 0,
            'total_clicks' => $analytics->total_clicks ?? 0,
            'avg_conversion_rate' => round($analytics->avg_conversion_rate ?? 0, 4),
            'avg_rating' => round($analytics->avg_rating ?? 0, 2),
            'featured_count' => $analytics->featured_count ?? 0,
            'video_count' => $analytics->video_count ?? 0,
            'text_count' => ($analytics->total_testimonials ?? 0) - ($analytics->video_count ?? 0),
        ];
    }

    /**
     * Get available filter options
     */
    public function getFilterOptions(string $tenantId): array
    {
        $query = Testimonial::forTenant($tenantId)->approved();

        return [
            'audience_types' => $query->distinct()->pluck('audience_type')->filter()->values(),
            'industries' => $query->distinct()->pluck('industry')->filter()->values(),
            'graduation_years' => $query->distinct()->pluck('graduation_year')->filter()->sort()->values(),
            'graduation_year_ranges' => $this->getGraduationYearRanges($query),
        ];
    }

    /**
     * Export testimonials to array format
     */
    public function exportTestimonials(array $filters = []): array
    {
        $testimonials = $this->getTestimonials($filters, 1000)->items();

        return array_map(function ($testimonial) {
            return [
                'id' => $testimonial->id,
                'author_name' => $testimonial->author_name,
                'author_title' => $testimonial->author_title,
                'author_company' => $testimonial->author_company,
                'graduation_year' => $testimonial->graduation_year,
                'industry' => $testimonial->industry,
                'audience_type' => $testimonial->audience_type,
                'content' => $testimonial->content,
                'rating' => $testimonial->rating,
                'status' => $testimonial->status,
                'featured' => $testimonial->featured,
                'has_video' => $testimonial->hasVideo(),
                'view_count' => $testimonial->view_count,
                'click_count' => $testimonial->click_count,
                'conversion_rate' => $testimonial->conversion_rate,
                'created_at' => $testimonial->created_at->toISOString(),
                'updated_at' => $testimonial->updated_at->toISOString(),
            ];
        }, $testimonials);
    }

    /**
     * Import testimonials from array data
     */
    public function importTestimonials(array $testimonialsData, string $tenantId): array
    {
        $results = [
            'success' => 0,
            'errors' => [],
        ];

        DB::beginTransaction();

        try {
            foreach ($testimonialsData as $index => $data) {
                try {
                    $data['tenant_id'] = $tenantId;
                    $this->createTestimonial($data);
                    $results['success']++;
                } catch (\Exception $e) {
                    $results['errors'][] = [
                        'row' => $index + 1,
                        'error' => $e->getMessage(),
                        'data' => $data,
                    ];
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return $results;
    }

    /**
     * Validate testimonial data
     */
    protected function validateTestimonialData(array $data, ?int $ignoreId = null): void
    {
        $rules = $ignoreId 
            ? Testimonial::getUniqueValidationRules($ignoreId)
            : Testimonial::getValidationRules();

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // Additional business rule validation
        if (!empty($data['video_url']) && empty($data['video_thumbnail'])) {
            throw new ValidationException(
                Validator::make([], []),
                ['video_thumbnail' => ['Video testimonials require a thumbnail image.']]
            );
        }
    }

    /**
     * Get graduation year ranges for filtering
     */
    protected function getGraduationYearRanges($query): array
    {
        $years = $query->distinct()->pluck('graduation_year')->filter()->sort();
        
        if ($years->isEmpty()) {
            return [];
        }

        $minYear = $years->min();
        $maxYear = $years->max();
        $ranges = [];

        // Create 5-year ranges
        for ($start = $minYear; $start <= $maxYear; $start += 5) {
            $end = min($start + 4, $maxYear);
            $ranges[] = "{$start}-{$end}";
        }

        return $ranges;
    }
}