<?php

declare(strict_types=1);

namespace App\Services\Homepage;

use App\Models\SuccessStory;
use App\Models\Testimonial;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Homepage Testimonial Service
 *
 * Manages testimonials and success stories for the homepage.
 * Replaces the getTestimonials() method from the monolithic HomepageService.
 */
class HomepageTestimonialService
{
    private const CACHE_TTL = 3600; // 1 hour

    /**
     * Get testimonials filtered by audience type
     */
    public function getTestimonials(string $audience): Collection
    {
        return Cache::remember("homepage.testimonials.{$audience}", self::CACHE_TTL, function () use ($audience) {
            if ($audience === 'institutional') {
                return $this->getInstitutionalTestimonials();
            }

            return $this->getIndividualTestimonials();
        });
    }

    /**
     * Get individual-focused testimonials
     */
    private function getIndividualTestimonials(): Collection
    {
        // Try to load from database first
        $testimonials = Testimonial::where('audience_type', 'individual')
            ->where('is_featured', true)
            ->where('is_approved', true)
            ->orderBy('created_at', 'desc')
            ->limit(6)
            ->get();

        if ($testimonials->isNotEmpty()) {
            return $testimonials->map(function ($testimonial) {
                return [
                    'id' => $testimonial->id,
                    'quote' => $testimonial->content,
                    'author' => [
                        'name' => $testimonial->author_name,
                        'graduation_year' => $testimonial->graduation_year,
                        'current_role' => $testimonial->author_title,
                        'current_company' => $testimonial->author_company,
                        'profile_image' => $testimonial->author_image,
                    ],
                    'metrics' => [
                        'salary_increase' => $testimonial->salary_increase_percentage ?? null,
                        'time_to_placement' => $testimonial->time_to_placement_days ?? null,
                    ],
                ];
            });
        }

        // Fallback to curated testimonials
        return $this->getCuratedIndividualTestimonials();
    }

    /**
     * Get institutional-focused testimonials
     */
    private function getInstitutionalTestimonials(): Collection
    {
        // Try to load from database first
        $testimonials = Testimonial::where('audience_type', 'institutional')
            ->where('is_featured', true)
            ->where('is_approved', true)
            ->orderBy('created_at', 'desc')
            ->limit(6)
            ->get();

        if ($testimonials->isNotEmpty()) {
            return $testimonials->map(function ($testimonial) {
                return [
                    'id' => $testimonial->id,
                    'quote' => $testimonial->content,
                    'institution' => [
                        'name' => $testimonial->institution_name,
                        'type' => $testimonial->institution_type,
                        'logo' => $testimonial->institution_logo,
                    ],
                    'administrator' => [
                        'name' => $testimonial->administrator_name,
                        'title' => $testimonial->administrator_title,
                        'profile_image' => $testimonial->administrator_image,
                    ],
                    'results' => [
                        'engagement_increase' => $testimonial->engagement_increase_percentage ?? null,
                        'app_downloads' => $testimonial->app_downloads ?? null,
                        'event_attendance_increase' => $testimonial->event_attendance_increase ?? null,
                    ],
                ];
            });
        }

        // Fallback to curated testimonials
        return $this->getCuratedInstitutionalTestimonials();
    }

    /**
     * Get success stories for homepage
     */
    public function getSuccessStories(int $limit = 3): Collection
    {
        return Cache::remember('homepage.success_stories', self::CACHE_TTL, function () use ($limit) {
            return SuccessStory::where('is_published', true)
                ->where('is_featured', true)
                ->orderBy('published_at', 'desc')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Curated fallback testimonials (when database is empty)
     */
    private function getCuratedIndividualTestimonials(): Collection
    {
        return collect([
            [
                'id' => 1,
                'quote' => 'This platform helped me land my dream job at Google. The alumni connections were invaluable.',
                'author' => [
                    'name' => 'Sarah Chen',
                    'graduation_year' => 2019,
                    'current_role' => 'Software Engineer',
                    'current_company' => 'Google',
                    'profile_image' => '/images/testimonials/sarah-chen.jpg',
                ],
                'metrics' => [
                    'salary_increase' => 65,
                    'time_to_placement' => 45,
                ],
            ],
            [
                'id' => 2,
                'quote' => 'The mentorship program connected me with industry leaders who guided my career transition.',
                'author' => [
                    'name' => 'Michael Rodriguez',
                    'graduation_year' => 2016,
                    'current_role' => 'Product Manager',
                    'current_company' => 'Microsoft',
                    'profile_image' => '/images/testimonials/michael-rodriguez.jpg',
                ],
                'metrics' => [
                    'salary_increase' => 45,
                    'career_advancement' => 'Senior to Director',
                ],
            ],
        ]);
    }

    /**
     * Curated institutional fallback testimonials
     */
    private function getCuratedInstitutionalTestimonials(): Collection
    {
        return collect([
            [
                'id' => 3,
                'quote' => 'Our alumni engagement increased by 400% after implementing the branded mobile app.',
                'institution' => [
                    'name' => 'Stanford University',
                    'type' => 'university',
                    'logo' => '/images/institutions/stanford-logo.png',
                ],
                'administrator' => [
                    'name' => 'Dr. Jennifer Walsh',
                    'title' => 'Director of Alumni Relations',
                    'profile_image' => '/images/testimonials/jennifer-walsh.jpg',
                ],
                'results' => [
                    'engagement_increase' => 400,
                    'app_downloads' => 15000,
                    'event_attendance_increase' => 250,
                ],
            ],
        ]);
    }
}
