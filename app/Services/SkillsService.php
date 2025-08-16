<?php

namespace App\Services;

use App\Models\LearningResource;
use App\Models\Skill;
use App\Models\SkillEndorsement;
use App\Models\User;
use App\Models\UserSkill;
use Illuminate\Support\Collection;

class SkillsService
{
    public function addSkillToUser(User $user, array $skillData): UserSkill
    {
        // Find or create skill
        $skill = Skill::firstOrCreate(
            ['name' => $skillData['skill_name']],
            [
                'category' => $skillData['category'] ?? 'General',
                'description' => $skillData['description'] ?? null,
                'is_verified' => false,
            ]
        );

        // Create or update user skill
        return UserSkill::updateOrCreate(
            [
                'user_id' => $user->id,
                'skill_id' => $skill->id,
            ],
            [
                'proficiency_level' => $skillData['proficiency_level'],
                'years_experience' => $skillData['years_experience'] ?? 0,
            ]
        );
    }

    public function endorseUserSkill(int $userSkillId, User $endorser, ?string $message = null): SkillEndorsement
    {
        $userSkill = UserSkill::findOrFail($userSkillId);

        // Prevent self-endorsement
        if ($userSkill->user_id === $endorser->id) {
            throw new \InvalidArgumentException('Users cannot endorse their own skills');
        }

        return SkillEndorsement::create([
            'user_skill_id' => $userSkillId,
            'endorser_id' => $endorser->id,
            'message' => $message,
        ]);
    }

    public function getSkillSuggestions(User $user): Collection
    {
        // Get skills from user's career timeline and connections
        $careerSkills = $this->getSkillsFromCareer($user);
        $connectionSkills = $this->getSkillsFromConnections($user);
        $industrySkills = $this->getSkillsFromIndustry($user);

        // Combine and rank suggestions
        $suggestions = $careerSkills
            ->merge($connectionSkills)
            ->merge($industrySkills)
            ->groupBy('id')
            ->map(function ($group) {
                $skill = $group->first();
                $skill->suggestion_score = $group->count();

                return $skill;
            })
            ->sortByDesc('suggestion_score')
            ->take(10);

        return $suggestions->values();
    }

    public function trackSkillProgression(User $user, int $skillId): array
    {
        $userSkill = UserSkill::where('user_id', $user->id)
            ->where('skill_id', $skillId)
            ->first();

        if (! $userSkill) {
            return [];
        }

        // Get endorsement history
        $endorsements = SkillEndorsement::where('user_skill_id', $userSkill->id)
            ->orderBy('created_at')
            ->get()
            ->groupBy(function ($endorsement) {
                return $endorsement->created_at->format('Y-m');
            });

        // Calculate progression metrics
        $progression = [];
        foreach ($endorsements as $month => $monthEndorsements) {
            $progression[] = [
                'month' => $month,
                'endorsement_count' => $monthEndorsements->count(),
                'total_endorsements' => $endorsements->flatten()->where('created_at', '<=', $monthEndorsements->last()->created_at)->count(),
            ];
        }

        return [
            'skill' => $userSkill->skill,
            'current_level' => $userSkill->proficiency_level,
            'years_experience' => $userSkill->years_experience,
            'total_endorsements' => $userSkill->endorsed_count,
            'progression' => $progression,
        ];
    }

    public function recommendLearningResources(User $user, int $skillId): Collection
    {
        $userSkill = UserSkill::where('user_id', $user->id)
            ->where('skill_id', $skillId)
            ->first();

        $proficiencyLevel = $userSkill ? $userSkill->proficiency_level : 'Beginner';

        // Get resources for this skill
        $resources = LearningResource::bySkill($skillId)
            ->highRated(3.5)
            ->orderBy('rating', 'desc')
            ->limit(10)
            ->get();

        // Filter by proficiency level
        return $resources->filter(function ($resource) use ($proficiencyLevel) {
            return $this->isResourceAppropriate($resource, $proficiencyLevel);
        });
    }

    public function getSkillsGapAnalysis(User $user): array
    {
        // Get user's current skills
        $userSkills = $user->userSkills()->with('skill')->get();

        // Get skills from similar professionals in their field
        $recommendedSkills = $this->getRecommendedSkillsForCareer($user);

        // Find gaps
        $currentSkillIds = $userSkills->pluck('skill_id')->toArray();
        $gaps = $recommendedSkills->whereNotIn('id', $currentSkillIds);

        return [
            'current_skills' => $userSkills,
            'recommended_skills' => $recommendedSkills,
            'skill_gaps' => $gaps,
            'gap_count' => $gaps->count(),
        ];
    }

    private function getSkillsFromCareer(User $user): Collection
    {
        // Get skills from career milestones and job titles
        $careerData = $user->careerTimeline()->with('milestones')->get();

        // Extract skills from job titles and descriptions
        $skills = collect();
        foreach ($careerData as $timeline) {
            foreach ($timeline->milestones as $milestone) {
                $extractedSkills = $this->extractSkillsFromText($milestone->title.' '.$milestone->description);
                $skills = $skills->merge($extractedSkills);
            }
        }

        return $skills->unique('id');
    }

    private function getSkillsFromConnections(User $user): Collection
    {
        // Get skills from user's connections
        return Skill::whereHas('userSkills.user.connections', function ($query) use ($user) {
            $query->where('connected_user_id', $user->id)
                ->orWhere('user_id', $user->id);
        })->get();
    }

    private function getSkillsFromIndustry(User $user): Collection
    {
        // Get popular skills in user's industry
        $industry = $user->industry ?? 'Technology';

        return Skill::whereHas('userSkills.user', function ($query) use ($industry) {
            $query->where('industry', $industry);
        })
            ->withCount('userSkills')
            ->orderBy('user_skills_count', 'desc')
            ->limit(20)
            ->get();
    }

    private function getRecommendedSkillsForCareer(User $user): Collection
    {
        $jobTitle = $user->current_position ?? 'Software Developer';

        // Get skills from users with similar job titles
        return Skill::whereHas('userSkills.user', function ($query) use ($jobTitle) {
            $query->where('current_position', 'like', "%{$jobTitle}%");
        })
            ->withCount('userSkills')
            ->orderBy('user_skills_count', 'desc')
            ->limit(15)
            ->get();
    }

    private function extractSkillsFromText(string $text): Collection
    {
        // Simple skill extraction - in production, use NLP or predefined skill database
        $commonSkills = [
            'JavaScript', 'Python', 'Java', 'PHP', 'React', 'Vue.js', 'Angular',
            'Node.js', 'Laravel', 'Django', 'Spring', 'MySQL', 'PostgreSQL',
            'MongoDB', 'Redis', 'Docker', 'Kubernetes', 'AWS', 'Azure',
            'Project Management', 'Leadership', 'Communication', 'Problem Solving',
        ];

        $foundSkills = collect();
        foreach ($commonSkills as $skillName) {
            if (stripos($text, $skillName) !== false) {
                $skill = Skill::where('name', $skillName)->first();
                if ($skill) {
                    $foundSkills->push($skill);
                }
            }
        }

        return $foundSkills;
    }

    private function isResourceAppropriate(LearningResource $resource, string $proficiencyLevel): bool
    {
        $levelMapping = [
            'Beginner' => ['Course', 'Article'],
            'Intermediate' => ['Course', 'Video', 'Workshop'],
            'Advanced' => ['Workshop', 'Certification', 'Book'],
            'Expert' => ['Certification', 'Book', 'Workshop'],
        ];

        return in_array($resource->type, $levelMapping[$proficiencyLevel] ?? []);
    }
}
