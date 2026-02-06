<?php

namespace App\Services;

use App\Models\Circle;
use App\Models\Connection;
use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class AlumniDirectoryService extends BaseService
{
    /**
     * Get filtered alumni with pagination
     */
    public function getFilteredAlumni(array $filters, array $pagination = [])
    {
        $query = $this->buildFilterQuery($filters);

        $perPage = $pagination['per_page'] ?? 20;
        $page = $pagination['page'] ?? 1;

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Build database query from filter parameters
     */
    public function buildFilterQuery(array $filters): Builder
    {
        $query = User::query()
            ->with([
                'educations.institution',
                'workExperiences' => function ($q) {
                    $q->where('is_current', true)->orWhere(function ($subQ) {
                        $subQ->whereNull('end_date');
                    });
                },
                'socialProfiles',
                'circles',
                'groups',
            ])
            ->where('is_active', true);

        // Search query
        if (! empty($filters['search'])) {
            $searchTerm = $filters['search'];
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'ILIKE', "%{$searchTerm}%")
                    ->orWhere('bio', 'ILIKE', "%{$searchTerm}%")
                    ->orWhere('location', 'ILIKE', "%{$searchTerm}%")
                    ->orWhereHas('workExperiences', function ($workQ) use ($searchTerm) {
                        $workQ->where('company', 'ILIKE', "%{$searchTerm}%")
                            ->orWhere('title', 'ILIKE', "%{$searchTerm}%");
                    })
                    ->orWhereHas('educations', function ($eduQ) use ($searchTerm) {
                        $eduQ->whereHas('institution', function ($instQ) use ($searchTerm) {
                            $instQ->where('name', 'ILIKE', "%{$searchTerm}%");
                        });
                    });
            });
        }

        // Graduation year range
        if (! empty($filters['graduation_year_from']) || ! empty($filters['graduation_year_to'])) {
            $query->whereHas('educations', function ($eduQ) use ($filters) {
                if (! empty($filters['graduation_year_from'])) {
                    $eduQ->where('graduation_year', '>=', $filters['graduation_year_from']);
                }
                if (! empty($filters['graduation_year_to'])) {
                    $eduQ->where('graduation_year', '<=', $filters['graduation_year_to']);
                }
            });
        }

        // Location filter
        if (! empty($filters['location'])) {
            $query->where('location', 'ILIKE', "%{$filters['location']}%");
        }

        // Industry filter
        if (! empty($filters['industries']) && is_array($filters['industries'])) {
            $query->whereHas('workExperiences', function ($workQ) use ($filters) {
                $workQ->whereIn('industry', $filters['industries']);
            });
        }

        // Company filter
        if (! empty($filters['company'])) {
            $query->whereHas('workExperiences', function ($workQ) use ($filters) {
                $workQ->where('company', 'ILIKE', "%{$filters['company']}%");
            });
        }

        // Skills filter
        if (! empty($filters['skills']) && is_array($filters['skills'])) {
            $query->where(function ($q) use ($filters) {
                foreach ($filters['skills'] as $skill) {
                    $q->orWhereJsonContains('skills', $skill);
                }
            });
        }

        // Current role/title filter
        if (! empty($filters['current_role'])) {
            $query->whereHas('workExperiences', function ($workQ) use ($filters) {
                $workQ->where('is_current', true)
                    ->where('title', 'ILIKE', "%{$filters['current_role']}%");
            });
        }

        // Institution filter
        if (! empty($filters['institutions']) && is_array($filters['institutions'])) {
            $query->whereHas('educations', function ($eduQ) use ($filters) {
                $eduQ->whereIn('institution_id', $filters['institutions']);
            });
        }

        // Circle filter
        if (! empty($filters['circles']) && is_array($filters['circles'])) {
            $query->whereHas('circles', function ($circleQ) use ($filters) {
                $circleQ->whereIn('circles.id', $filters['circles']);
            });
        }

        // Group filter
        if (! empty($filters['groups']) && is_array($filters['groups'])) {
            $query->whereHas('groups', function ($groupQ) use ($filters) {
                $groupQ->whereIn('groups.id', $filters['groups']);
            });
        }

        // Sort options
        $sortBy = $filters['sort_by'] ?? 'name';
        $sortOrder = $filters['sort_order'] ?? 'asc';

        switch ($sortBy) {
            case 'graduation_year':
                $query->leftJoin('educations', 'users.id', '=', 'educations.user_id')
                    ->orderBy('educations.graduation_year', $sortOrder)
                    ->select('users.*');
                break;
            case 'location':
                $query->orderBy('location', $sortOrder);
                break;
            case 'created_at':
                $query->orderBy('created_at', $sortOrder);
                break;
            default:
                $query->orderBy('name', $sortOrder);
        }

        return $query;
    }

    /**
     * Get available filter options with counts
     */
    public function getAvailableFilters(): array
    {
        return [
            'graduation_years' => $this->getGraduationYearRange(),
            'locations' => $this->getTopLocations(),
            'industries' => $this->getTopIndustries(),
            'companies' => $this->getTopCompanies(),
            'skills' => $this->getTopSkills(),
            'institutions' => $this->getInstitutions(),
            'circles' => $this->getActiveCircles(),
            'groups' => $this->getActiveGroups(),
        ];
    }

    /**
     * Get detailed alumni profile with privacy controls
     */
    public function getAlumniProfile(int $userId, User $currentUser): ?User
    {
        $alumni = User::with([
            'educations.institution',
            'workExperiences' => function ($q) {
                $q->orderBy('start_date', 'desc');
            },
            'socialProfiles',
            'circles',
            'groups.institution',
            'achievements',
            'certifications',
        ])->find($userId);

        if (! $alumni) {
            return null;
        }

        // Apply privacy controls
        $alumni = $this->applyPrivacyControls($alumni, $currentUser);

        // Add computed attributes
        $alumni->mutual_connections = $this->getMutualConnections($alumni, $currentUser);
        $alumni->shared_circles = $this->getSharedCircles($alumni, $currentUser);
        $alumni->shared_groups = $this->getSharedGroups($alumni, $currentUser);
        $alumni->connection_status = $this->getConnectionStatus($alumni, $currentUser);

        return $alumni;
    }

    /**
     * Get mutual connections between two users
     */
    public function getMutualConnections(User $alumni, User $currentUser): array
    {
        $alumniConnections = $alumni->connections()->pluck('connected_user_id')->toArray();
        $currentUserConnections = $currentUser->connections()->pluck('connected_user_id')->toArray();

        $mutualIds = array_intersect($alumniConnections, $currentUserConnections);

        return User::whereIn('id', $mutualIds)
            ->select('id', 'name', 'avatar_url')
            ->limit(10)
            ->get()
            ->toArray();
    }

    /**
     * Get shared circles between two users
     */
    public function getSharedCircles(User $alumni, User $currentUser): array
    {
        return $alumni->circles()
            ->whereIn('circles.id', $currentUser->circles()->pluck('circles.id'))
            ->select('circles.id', 'circles.name', 'circles.type')
            ->get()
            ->toArray();
    }

    /**
     * Get shared groups between two users
     */
    public function getSharedGroups(User $alumni, User $currentUser): array
    {
        return $alumni->groups()
            ->whereIn('groups.id', $currentUser->groups()->pluck('groups.id'))
            ->select('groups.id', 'groups.name', 'groups.type')
            ->get()
            ->toArray();
    }

    /**
     * Get connection status between two users
     */
    public function getConnectionStatus(User $alumni, User $currentUser): string
    {
        if ($alumni->id === $currentUser->id) {
            return 'self';
        }

        $connection = Connection::where(function ($q) use ($alumni, $currentUser) {
            $q->where('user_id', $currentUser->id)
                ->where('connected_user_id', $alumni->id);
        })->orWhere(function ($q) use ($alumni, $currentUser) {
            $q->where('user_id', $alumni->id)
                ->where('connected_user_id', $currentUser->id);
        })->first();

        if (! $connection) {
            return 'none';
        }

        return $connection->status;
    }

    /**
     * Apply privacy controls to alumni profile
     */
    private function applyPrivacyControls(User $alumni, User $currentUser): User
    {
        $privacySettings = $alumni->privacy_settings ?? [];
        $connectionStatus = $this->getConnectionStatus($alumni, $currentUser);

        // Hide contact information based on privacy settings
        if (! $this->canViewContactInfo($alumni, $currentUser, $connectionStatus)) {
            $alumni->email = null;
            $alumni->phone = null;
        }

        // Hide work experience details if not connected
        if (! $this->canViewWorkDetails($alumni, $currentUser, $connectionStatus)) {
            $alumni->workExperiences->each(function ($work) {
                $work->salary = null;
                $work->detailed_description = null;
            });
        }

        return $alumni;
    }

    /**
     * Check if current user can view contact information
     */
    private function canViewContactInfo(User $alumni, User $currentUser, string $connectionStatus): bool
    {
        $setting = $alumni->privacy_settings['contact_info'] ?? 'connections';

        switch ($setting) {
            case 'public':
                return true;
            case 'connections':
                return $connectionStatus === 'accepted';
            case 'private':
                return $alumni->id === $currentUser->id;
            default:
                return false;
        }
    }

    /**
     * Check if current user can view work details
     */
    private function canViewWorkDetails(User $alumni, User $currentUser, string $connectionStatus): bool
    {
        $setting = $alumni->privacy_settings['work_details'] ?? 'public';

        switch ($setting) {
            case 'public':
                return true;
            case 'connections':
                return $connectionStatus === 'accepted';
            case 'private':
                return $alumni->id === $currentUser->id;
            default:
                return true;
        }
    }

    /**
     * Get graduation year range for filters
     */
    private function getGraduationYearRange(): array
    {
        $years = DB::table('educations')
            ->selectRaw('MIN(graduation_year) as min_year, MAX(graduation_year) as max_year')
            ->whereNotNull('graduation_year')
            ->first();

        return [
            'min' => $years->min_year ?? date('Y') - 50,
            'max' => $years->max_year ?? date('Y'),
        ];
    }

    /**
     * Get top locations for filters
     */
    private function getTopLocations(): array
    {
        return DB::table('users')
            ->select('location', DB::raw('COUNT(*) as count'))
            ->whereNotNull('location')
            ->where('location', '!=', '')
            ->groupBy('location')
            ->orderBy('count', 'desc')
            ->limit(50)
            ->get()
            ->toArray();
    }

    /**
     * Get top industries for filters
     */
    private function getTopIndustries(): array
    {
        return DB::table('work_experiences')
            ->select('industry', DB::raw('COUNT(DISTINCT user_id) as count'))
            ->whereNotNull('industry')
            ->where('industry', '!=', '')
            ->groupBy('industry')
            ->orderBy('count', 'desc')
            ->limit(30)
            ->get()
            ->toArray();
    }

    /**
     * Get top companies for filters
     */
    private function getTopCompanies(): array
    {
        return DB::table('work_experiences')
            ->select('company', DB::raw('COUNT(DISTINCT user_id) as count'))
            ->whereNotNull('company')
            ->where('company', '!=', '')
            ->groupBy('company')
            ->orderBy('count', 'desc')
            ->limit(100)
            ->get()
            ->toArray();
    }

    /**
     * Get top skills for filters
     */
    private function getTopSkills(): array
    {
        $skills = DB::table('users')
            ->whereNotNull('skills')
            ->pluck('skills')
            ->filter()
            ->flatMap(function ($skillsJson) {
                return json_decode($skillsJson, true) ?? [];
            })
            ->countBy()
            ->sortDesc()
            ->take(100)
            ->map(function ($count, $skill) {
                return ['skill' => $skill, 'count' => $count];
            })
            ->values()
            ->toArray();

        return $skills;
    }

    /**
     * Get institutions for filters
     */
    private function getInstitutions(): array
    {
        return DB::table('institutions')
            ->select('id', 'name', DB::raw('COUNT(educations.id) as alumni_count'))
            ->leftJoin('educations', 'institutions.id', '=', 'educations.institution_id')
            ->groupBy('institutions.id', 'institutions.name')
            ->orderBy('alumni_count', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Get active circles for filters
     */
    private function getActiveCircles(): array
    {
        return Circle::select('id', 'name', 'type', 'member_count')
            ->where('member_count', '>', 0)
            ->orderBy('member_count', 'desc')
            ->limit(50)
            ->get()
            ->toArray();
    }

    /**
     * Get active groups for filters
     */
    private function getActiveGroups(): array
    {
        return Group::select('id', 'name', 'type', 'member_count')
            ->where('member_count', '>', 0)
            ->where('privacy', '!=', 'secret')
            ->orderBy('member_count', 'desc')
            ->limit(50)
            ->get()
            ->toArray();
    }
}
