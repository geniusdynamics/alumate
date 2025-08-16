<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Forum;
use App\Models\ForumTopic;
use App\Models\ForumPost;
use App\Models\ForumTag;
use App\Models\User;

class ForumSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create sample tags
        $tags = [
            ['name' => 'Career Advice', 'color' => '#3B82F6', 'is_featured' => true],
            ['name' => 'Networking', 'color' => '#10B981', 'is_featured' => true],
            ['name' => 'Job Opportunities', 'color' => '#F59E0B', 'is_featured' => true],
            ['name' => 'Mentorship', 'color' => '#8B5CF6', 'is_featured' => true],
            ['name' => 'Industry Trends', 'color' => '#EF4444', 'is_featured' => false],
            ['name' => 'Alumni Events', 'color' => '#06B6D4', 'is_featured' => false],
            ['name' => 'Success Stories', 'color' => '#84CC16', 'is_featured' => false],
            ['name' => 'Tech Discussion', 'color' => '#6366F1', 'is_featured' => false],
        ];

        foreach ($tags as $tagData) {
            ForumTag::create([
                'name' => $tagData['name'],
                'slug' => \Str::slug($tagData['name']),
                'color' => $tagData['color'],
                'is_featured' => $tagData['is_featured'],
                'usage_count' => rand(5, 50),
            ]);
        }

        // Create sample forums
        $forums = [
            [
                'name' => 'General Discussion',
                'description' => 'A place for general discussions and community conversations',
                'color' => '#3B82F6',
                'icon' => '💬',
                'visibility' => 'public',
                'sort_order' => 1,
            ],
            [
                'name' => 'Career Development',
                'description' => 'Share career advice, job opportunities, and professional growth tips',
                'color' => '#10B981',
                'icon' => '🚀',
                'visibility' => 'public',
                'sort_order' => 2,
            ],
            [
                'name' => 'Networking Hub',
                'description' => 'Connect with fellow alumni and expand your professional network',
                'color' => '#F59E0B',
                'icon' => '🤝',
                'visibility' => 'public',
                'sort_order' => 3,
            ],
            [
                'name' => 'Industry Insights',
                'description' => 'Discuss industry trends, market analysis, and future predictions',
                'color' => '#8B5CF6',
                'icon' => '📊',
                'visibility' => 'public',
                'sort_order' => 4,
            ],
            [
                'name' => 'Mentorship Circle',
                'description' => 'Find mentors, offer guidance, and share learning experiences',
                'color' => '#EF4444',
                'icon' => '🎓',
                'visibility' => 'public',
                'sort_order' => 5,
            ],
        ];

        $createdForums = [];
        foreach ($forums as $forumData) {
            $forum = Forum::create([
                'name' => $forumData['name'],
                'description' => $forumData['description'],
                'slug' => \Str::slug($forumData['name']),
                'color' => $forumData['color'],
                'icon' => $forumData['icon'],
                'visibility' => $forumData['visibility'],
                'is_active' => true,
                'sort_order' => $forumData['sort_order'],
            ]);
            $createdForums[] = $forum;
        }

        // Get some users to create topics and posts
        $users = User::limit(10)->get();
        if ($users->isEmpty()) {
            // Create some sample users if none exist
            $users = collect();
            for ($i = 1; $i <= 5; $i++) {
                $users->push(User::create([
                    'name' => "Sample User {$i}",
                    'email' => "user{$i}@example.com",
                    'password' => bcrypt('password'),
                    'email_verified_at' => now(),
                ]));
            }
        }

        // Create sample topics for each forum
        $sampleTopics = [
            'General Discussion' => [
                'Welcome to our Alumni Community!',
                'Share your favorite college memories',
                'What are you working on these days?',
                'Alumni meetup ideas for 2025',
            ],
            'Career Development' => [
                'Tips for transitioning to a new industry',
                'How to negotiate salary effectively',
                'Building a strong LinkedIn profile',
                'Remote work best practices',
            ],
            'Networking Hub' => [
                'Introduce yourself here!',
                'Looking for connections in tech industry',
                'Alumni in healthcare - let\'s connect',
                'Startup founders and entrepreneurs',
            ],
            'Industry Insights' => [
                'AI impact on our industry',
                'Market trends for 2025',
                'Sustainable business practices',
                'Digital transformation insights',
            ],
            'Mentorship Circle' => [
                'New graduate seeking career guidance',
                'Offering mentorship in marketing',
                'How to be an effective mentor',
                'Success stories from mentorship',
            ],
        ];

        $allTags = ForumTag::all();

        foreach ($createdForums as $forum) {
            $topics = $sampleTopics[$forum->name] ?? [];
            
            foreach ($topics as $topicTitle) {
                $user = $users->random();
                
                $topic = ForumTopic::create([
                    'forum_id' => $forum->id,
                    'user_id' => $user->id,
                    'title' => $topicTitle,
                    'slug' => \Str::slug($topicTitle),
                    'content' => $this->generateSampleContent($topicTitle),
                    'is_approved' => true,
                    'posts_count' => rand(1, 10),
                    'views_count' => rand(10, 100),
                    'last_post_at' => now()->subDays(rand(0, 30)),
                    'last_post_user_id' => $users->random()->id,
                ]);

                // Attach random tags
                $randomTags = $allTags->random(rand(1, 3));
                foreach ($randomTags as $tag) {
                    $topic->tags()->attach($tag->id);
                    $tag->incrementUsage();
                }

                // Create some sample posts for each topic
                $postCount = rand(1, 5);
                for ($i = 0; $i < $postCount; $i++) {
                    ForumPost::create([
                        'topic_id' => $topic->id,
                        'user_id' => $users->random()->id,
                        'content' => $this->generateSamplePostContent(),
                        'is_approved' => true,
                        'likes_count' => rand(0, 15),
                        'created_at' => now()->subDays(rand(0, 30)),
                    ]);
                }
            }

            // Update forum statistics
            $forum->update([
                'topics_count' => $forum->topics()->count(),
                'posts_count' => ForumPost::whereHas('topic', function ($query) use ($forum) {
                    $query->where('forum_id', $forum->id);
                })->count(),
                'last_activity_at' => now()->subDays(rand(0, 7)),
            ]);
        }
    }

    private function generateSampleContent($title): string
    {
        $templates = [
            "I wanted to start a discussion about {$title}. What are your thoughts and experiences?",
            "Hi everyone! I'm curious to hear your perspectives on {$title}. Let's share our insights.",
            "This topic about {$title} has been on my mind lately. I'd love to hear from the community.",
            "Looking to learn more about {$title}. Any advice or resources you can share?",
            "I've been thinking about {$title} and would appreciate your input and experiences.",
        ];

        return $templates[array_rand($templates)];
    }

    private function generateSamplePostContent(): string
    {
        $responses = [
            "Great question! I've had similar experiences and here's what I learned...",
            "Thanks for bringing this up. In my experience, the key is to focus on...",
            "I completely agree with this perspective. I'd also add that...",
            "This is really insightful. I've found that the best approach is to...",
            "Excellent point! I've been in a similar situation and what worked for me was...",
            "I appreciate you sharing this. From my professional experience...",
            "This resonates with me. I think it's important to consider...",
            "Really valuable discussion. I'd like to add my perspective on...",
        ];

        return $responses[array_rand($responses)];
    }
}
