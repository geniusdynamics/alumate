<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Comment>
 */
class CommentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Comment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'post_id' => Post::factory(),
            'user_id' => User::factory(),
            'parent_id' => null,
            'content' => $this->faker->paragraph(),
            'mentions' => [],
            'metadata' => [],
        ];
    }

    /**
     * Create a reply comment.
     */
    public function reply(Comment $parentComment): static
    {
        return $this->state(function (array $attributes) use ($parentComment) {
            return [
                'post_id' => $parentComment->post_id,
                'parent_id' => $parentComment->id,
            ];
        });
    }

    /**
     * Create a comment with mentions.
     */
    public function withMentions(?array $usernames = null): static
    {
        return $this->state(function (array $attributes) use ($usernames) {
            $mentions = $usernames ?? [$this->faker->userName(), $this->faker->userName()];
            $content = $this->faker->paragraph();

            // Add mentions to content
            foreach ($mentions as $username) {
                $content .= " @{$username}";
            }

            return [
                'content' => $content,
                'mentions' => $mentions,
            ];
        });
    }
}
