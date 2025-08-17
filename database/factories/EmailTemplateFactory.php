<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EmailTemplate>
 */
class EmailTemplateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $user = User::factory()->create();

        return [
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'category' => $this->faker->randomElement(['newsletter', 'announcement', 'event', 'fundraising', 'engagement']),
            'html_content' => $this->generateHtmlContent(),
            'text_content' => $this->faker->paragraphs(3, true),
            'variables' => [
                'first_name' => 'User\'s first name',
                'last_name' => 'User\'s last name',
                'full_name' => 'User\'s full name',
                'email' => 'User\'s email address',
                'current_role' => 'User\'s current job title',
                'current_company' => 'User\'s current company',
            ],
            'is_default' => false,
            'is_active' => true,
            'created_by' => $user->id,
            'tenant_id' => $user->tenant_id,
        ];
    }

    public function newsletter(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'newsletter',
            'name' => 'Newsletter Template - '.$this->faker->word(),
        ]);
    }

    public function welcome(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'engagement',
            'name' => 'Welcome Email Template',
            'html_content' => $this->generateWelcomeHtml(),
        ]);
    }

    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    protected function generateHtmlContent(): string
    {
        return '
            <html>
            <head>
                <title>{{subject}}</title>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .header { background-color: #f4f4f4; padding: 20px; text-align: center; }
                    .content { padding: 20px; }
                    .footer { background-color: #f4f4f4; padding: 10px; text-align: center; font-size: 12px; }
                </style>
            </head>
            <body>
                <div class="header">
                    <h1>Alumni Network</h1>
                </div>
                <div class="content">
                    <h2>Hello {{first_name}}!</h2>
                    <p>'.$this->faker->paragraphs(2, true).'</p>
                    <p>Best regards,<br>The Alumni Team</p>
                </div>
                <div class="footer">
                    <p>&copy; '.date('Y').' Alumni Network. All rights reserved.</p>
                </div>
            </body>
            </html>
        ';
    }

    protected function generateWelcomeHtml(): string
    {
        return '
            <html>
            <head>
                <title>Welcome to Alumni Network</title>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .welcome-banner { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 40px 20px; text-align: center; }
                    .content { padding: 30px 20px; }
                    .cta-button { display: inline-block; background-color: #007cba; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                </style>
            </head>
            <body>
                <div class="welcome-banner">
                    <h1>Welcome {{first_name}}!</h1>
                    <p>You\'re now part of our amazing alumni community</p>
                </div>
                <div class="content">
                    <p>Dear {{full_name}},</p>
                    <p>We\'re thrilled to welcome you to our alumni network! As a member, you\'ll have access to:</p>
                    <ul>
                        <li>Networking opportunities with fellow alumni</li>
                        <li>Career development resources</li>
                        <li>Exclusive events and reunions</li>
                        <li>Mentorship programs</li>
                    </ul>
                    <p>Get started by completing your profile and connecting with other alumni in your field.</p>
                    <a href="#" class="cta-button">Complete Your Profile</a>
                    <p>Welcome aboard!</p>
                </div>
            </body>
            </html>
        ';
    }
}
