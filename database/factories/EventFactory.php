<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventFactory extends Factory
{
    protected $model = Event::class;

    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('now', '+6 months');
        $endDate = (clone $startDate)->add(new \DateInterval('PT'.$this->faker->numberBetween(1, 8).'H'));

        $types = ['networking', 'reunion', 'webinar', 'workshop', 'social', 'professional', 'fundraising', 'other'];
        $formats = ['in_person', 'virtual', 'hybrid'];
        $visibilities = ['public', 'alumni_only', 'institution_only', 'private'];

        $format = $this->faker->randomElement($formats);
        $type = $this->faker->randomElement($types);

        return [
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->paragraphs(3, true),
            'short_description' => $this->faker->sentence(10),
            'media_urls' => $this->faker->boolean(30) ? [$this->faker->imageUrl(800, 400)] : null,
            'type' => $type,
            'format' => $format,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'timezone' => $this->faker->randomElement(['UTC', 'America/New_York', 'America/Chicago', 'America/Denver', 'America/Los_Angeles']),

            // Location details based on format
            'venue_name' => $format !== 'virtual' ? $this->faker->company.' Conference Center' : null,
            'venue_address' => $format !== 'virtual' ? $this->faker->address : null,
            'latitude' => $format !== 'virtual' ? $this->faker->latitude : null,
            'longitude' => $format !== 'virtual' ? $this->faker->longitude : null,
            'virtual_link' => $format !== 'in_person' ? 'https://zoom.us/j/'.$this->faker->numerify('###########') : null,
            'virtual_instructions' => $format !== 'in_person' ? $this->faker->paragraph : null,

            // Capacity and registration
            'max_capacity' => $this->faker->boolean(70) ? $this->faker->numberBetween(10, 500) : null,
            'current_attendees' => 0,
            'requires_approval' => $this->faker->boolean(20),
            'ticket_price' => $this->faker->boolean(30) ? $this->faker->randomFloat(2, 0, 100) : null,
            'registration_status' => 'open',
            'registration_deadline' => $this->faker->boolean(40) ? $this->faker->dateTimeBetween('now', $startDate) : null,

            // Organizer and visibility
            'organizer_id' => User::factory(),
            'institution_id' => $this->faker->boolean(60) ? Institution::factory() : null,
            'visibility' => $this->faker->randomElement($visibilities),
            'target_circles' => $this->faker->boolean(40) ? $this->faker->randomElements([1, 2, 3, 4, 5], $this->faker->numberBetween(1, 3)) : null,
            'target_groups' => $this->faker->boolean(40) ? $this->faker->randomElements([1, 2, 3, 4, 5], $this->faker->numberBetween(1, 3)) : null,

            // Event settings
            'settings' => [
                'send_reminders' => $this->faker->boolean(80),
                'allow_waitlist' => $this->faker->boolean(60),
                'auto_approve' => $this->faker->boolean(70),
            ],
            'allow_guests' => $this->faker->boolean(40),
            'max_guests_per_attendee' => $this->faker->numberBetween(1, 5),
            'enable_networking' => $this->faker->boolean(80),
            'enable_checkin' => $this->faker->boolean(90),

            // Status and metadata
            'status' => $this->faker->randomElement(['draft', 'published']),
            'tags' => $this->faker->boolean(60) ? $this->faker->randomElements([
                'networking', 'career', 'alumni', 'professional-development',
                'social', 'fundraising', 'reunion', 'workshop', 'webinar',
            ], $this->faker->numberBetween(1, 4)) : null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'published',
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
        ]);
    }

    public function upcoming(): static
    {
        return $this->state(function (array $attributes) {
            $startDate = $this->faker->dateTimeBetween('+1 day', '+3 months');
            $endDate = (clone $startDate)->add(new \DateInterval('PT'.$this->faker->numberBetween(1, 8).'H'));

            return [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => 'published',
            ];
        });
    }

    public function past(): static
    {
        return $this->state(function (array $attributes) {
            $startDate = $this->faker->dateTimeBetween('-6 months', '-1 day');
            $endDate = (clone $startDate)->add(new \DateInterval('PT'.$this->faker->numberBetween(1, 8).'H'));

            return [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => 'completed',
            ];
        });
    }

    public function virtual(): static
    {
        return $this->state(fn (array $attributes) => [
            'format' => 'virtual',
            'venue_name' => null,
            'venue_address' => null,
            'latitude' => null,
            'longitude' => null,
            'virtual_link' => 'https://zoom.us/j/'.$this->faker->numerify('###########'),
            'virtual_instructions' => $this->faker->paragraph,
        ]);
    }

    public function inPerson(): static
    {
        return $this->state(fn (array $attributes) => [
            'format' => 'in_person',
            'venue_name' => $this->faker->company.' Conference Center',
            'venue_address' => $this->faker->address,
            'latitude' => $this->faker->latitude,
            'longitude' => $this->faker->longitude,
            'virtual_link' => null,
            'virtual_instructions' => null,
        ]);
    }

    public function networking(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'networking',
            'enable_networking' => true,
            'tags' => ['networking', 'professional-development', 'career'],
        ]);
    }

    public function reunion(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'reunion',
            'tags' => ['reunion', 'alumni', 'social'],
        ]);
    }

    public function withCapacity(int $capacity): static
    {
        return $this->state(fn (array $attributes) => [
            'max_capacity' => $capacity,
        ]);
    }

    public function withPrice(float $price): static
    {
        return $this->state(fn (array $attributes) => [
            'ticket_price' => $price,
        ]);
    }

    public function requiresApproval(): static
    {
        return $this->state(fn (array $attributes) => [
            'requires_approval' => true,
        ]);
    }

    public function allowsGuests(int $maxGuests = 2): static
    {
        return $this->state(fn (array $attributes) => [
            'allow_guests' => true,
            'max_guests_per_attendee' => $maxGuests,
        ]);
    }
}
