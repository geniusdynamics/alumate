<?php

namespace Database\Seeders;

use App\Models\Achievement;
use Illuminate\Database\Seeder;

class AchievementSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $achievements = [
            // Career Achievements
            [
                'name' => 'First Steps',
                'slug' => 'first-steps',
                'description' => 'Add your first career milestone to your profile',
                'icon' => 'briefcase',
                'category' => Achievement::CATEGORY_CAREER,
                'rarity' => Achievement::RARITY_COMMON,
                'criteria' => [
                    'type' => 'milestone_count',
                    'count' => 1,
                ],
                'points' => 10,
            ],
            [
                'name' => 'Career Chronicler',
                'slug' => 'career-chronicler',
                'description' => 'Document 5 career milestones in your journey',
                'icon' => 'book-open',
                'category' => Achievement::CATEGORY_CAREER,
                'rarity' => Achievement::RARITY_UNCOMMON,
                'criteria' => [
                    'type' => 'milestone_count',
                    'count' => 5,
                ],
                'points' => 25,
            ],
            [
                'name' => 'Rising Star',
                'slug' => 'rising-star',
                'description' => 'Achieve your first promotion',
                'icon' => 'trending-up',
                'category' => Achievement::CATEGORY_CAREER,
                'rarity' => Achievement::RARITY_UNCOMMON,
                'criteria' => [
                    'type' => 'milestone_count',
                    'milestone_type' => 'promotion',
                    'count' => 1,
                ],
                'points' => 30,
            ],
            [
                'name' => 'Career Climber',
                'slug' => 'career-climber',
                'description' => 'Earn 3 promotions in your career',
                'icon' => 'arrow-trending-up',
                'category' => Achievement::CATEGORY_CAREER,
                'rarity' => Achievement::RARITY_RARE,
                'criteria' => [
                    'type' => 'career_progression',
                    'promotions' => 3,
                ],
                'points' => 75,
            ],
            [
                'name' => 'Award Winner',
                'slug' => 'award-winner',
                'description' => 'Receive your first professional award or recognition',
                'icon' => 'trophy',
                'category' => Achievement::CATEGORY_CAREER,
                'rarity' => Achievement::RARITY_RARE,
                'criteria' => [
                    'type' => 'milestone_count',
                    'milestone_type' => 'award',
                    'count' => 1,
                ],
                'points' => 50,
            ],

            // Education Achievements
            [
                'name' => 'Lifelong Learner',
                'slug' => 'lifelong-learner',
                'description' => 'Add your first certification or continuing education milestone',
                'icon' => 'academic-cap',
                'category' => Achievement::CATEGORY_EDUCATION,
                'rarity' => Achievement::RARITY_COMMON,
                'criteria' => [
                    'type' => 'milestone_count',
                    'milestone_type' => 'certification',
                    'count' => 1,
                ],
                'points' => 15,
            ],
            [
                'name' => 'Knowledge Seeker',
                'slug' => 'knowledge-seeker',
                'description' => 'Earn 3 professional certifications',
                'icon' => 'certificate',
                'category' => Achievement::CATEGORY_EDUCATION,
                'rarity' => Achievement::RARITY_UNCOMMON,
                'criteria' => [
                    'type' => 'milestone_count',
                    'milestone_type' => 'certification',
                    'count' => 3,
                ],
                'points' => 40,
            ],
            [
                'name' => 'Advanced Degree',
                'slug' => 'advanced-degree',
                'description' => 'Complete an advanced degree or major educational milestone',
                'icon' => 'graduation-cap',
                'category' => Achievement::CATEGORY_EDUCATION,
                'rarity' => Achievement::RARITY_RARE,
                'criteria' => [
                    'type' => 'milestone_count',
                    'milestone_type' => 'education',
                    'count' => 1,
                ],
                'points' => 60,
            ],

            // Community Achievements
            [
                'name' => 'Welcome Aboard',
                'slug' => 'welcome-aboard',
                'description' => 'Complete your profile and join the alumni community',
                'icon' => 'user-plus',
                'category' => Achievement::CATEGORY_COMMUNITY,
                'rarity' => Achievement::RARITY_COMMON,
                'criteria' => [
                    'type' => 'profile_completion',
                    'completion_percentage' => 60,
                ],
                'points' => 5,
            ],
            [
                'name' => 'Connector',
                'slug' => 'connector',
                'description' => 'Make your first 5 alumni connections',
                'icon' => 'users',
                'category' => Achievement::CATEGORY_COMMUNITY,
                'rarity' => Achievement::RARITY_COMMON,
                'criteria' => [
                    'type' => 'connection_count',
                    'count' => 5,
                ],
                'points' => 20,
            ],
            [
                'name' => 'Social Butterfly',
                'slug' => 'social-butterfly',
                'description' => 'Connect with 25 fellow alumni',
                'icon' => 'heart',
                'category' => Achievement::CATEGORY_COMMUNITY,
                'rarity' => Achievement::RARITY_UNCOMMON,
                'criteria' => [
                    'type' => 'connection_count',
                    'count' => 25,
                ],
                'points' => 35,
            ],
            [
                'name' => 'Community Champion',
                'slug' => 'community-champion',
                'description' => 'Build a network of 100+ alumni connections',
                'icon' => 'globe',
                'category' => Achievement::CATEGORY_COMMUNITY,
                'rarity' => Achievement::RARITY_RARE,
                'criteria' => [
                    'type' => 'connection_count',
                    'count' => 100,
                ],
                'points' => 80,
            ],
            [
                'name' => 'Conversation Starter',
                'slug' => 'conversation-starter',
                'description' => 'Actively participate in community discussions',
                'icon' => 'chat-bubble-left-right',
                'category' => Achievement::CATEGORY_COMMUNITY,
                'rarity' => Achievement::RARITY_COMMON,
                'criteria' => [
                    'type' => 'community_participation',
                    'activity_count' => 5,
                ],
                'points' => 15,
            ],
            [
                'name' => 'Influencer',
                'slug' => 'influencer',
                'description' => 'Create content that resonates with the community',
                'icon' => 'megaphone',
                'category' => Achievement::CATEGORY_COMMUNITY,
                'rarity' => Achievement::RARITY_UNCOMMON,
                'criteria' => [
                    'type' => 'post_engagement',
                    'min_likes' => 10,
                    'min_posts' => 3,
                ],
                'points' => 45,
            ],

            // Milestone Achievements
            [
                'name' => 'Profile Complete',
                'slug' => 'profile-complete',
                'description' => 'Complete 100% of your alumni profile',
                'icon' => 'check-circle',
                'category' => Achievement::CATEGORY_MILESTONE,
                'rarity' => Achievement::RARITY_UNCOMMON,
                'criteria' => [
                    'type' => 'profile_completion',
                    'completion_percentage' => 100,
                ],
                'points' => 25,
            ],
            [
                'name' => 'Milestone Master',
                'slug' => 'milestone-master',
                'description' => 'Document 10 significant career milestones',
                'icon' => 'flag',
                'category' => Achievement::CATEGORY_MILESTONE,
                'rarity' => Achievement::RARITY_RARE,
                'criteria' => [
                    'type' => 'milestone_count',
                    'count' => 10,
                ],
                'points' => 70,
            ],

            // Special Achievements
            [
                'name' => 'Early Adopter',
                'slug' => 'early-adopter',
                'description' => 'One of the first 100 alumni to join the platform',
                'icon' => 'rocket-launch',
                'category' => Achievement::CATEGORY_SPECIAL,
                'rarity' => Achievement::RARITY_LEGENDARY,
                'criteria' => [
                    'type' => 'manual_award', // This would be manually awarded
                ],
                'points' => 200,
                'is_auto_awarded' => false,
            ],
            [
                'name' => 'Platform Pioneer',
                'slug' => 'platform-pioneer',
                'description' => 'Helped shape the alumni platform in its early days',
                'icon' => 'star',
                'category' => Achievement::CATEGORY_SPECIAL,
                'rarity' => Achievement::RARITY_EPIC,
                'criteria' => [
                    'type' => 'manual_award',
                ],
                'points' => 150,
                'is_auto_awarded' => false,
            ],
            [
                'name' => 'Anniversary Celebration',
                'slug' => 'anniversary-celebration',
                'description' => 'Celebrating your first year on the platform',
                'icon' => 'cake',
                'category' => Achievement::CATEGORY_SPECIAL,
                'rarity' => Achievement::RARITY_UNCOMMON,
                'criteria' => [
                    'type' => 'manual_award', // Would be awarded via scheduled job
                ],
                'points' => 30,
                'is_auto_awarded' => false,
            ],
        ];

        foreach ($achievements as $achievementData) {
            Achievement::create($achievementData);
        }
    }
}
