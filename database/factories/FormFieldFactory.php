<?php

namespace Database\Factories;

use App\Models\FormField;
use App\Models\FormBuilder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FormField>
 */
class FormFieldFactory extends Factory
{
    protected $model = FormField::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $fieldTypes = ['text', 'email', 'phone', 'textarea', 'select', 'radio', 'checkbox', 'date', 'number'];
        $fieldType = $this->faker->randomElement($fieldTypes);

        return [
            'form_id' => FormBuilder::factory(),
            'field_type' => $fieldType,
            'field_name' => $this->faker->slug(2),
            'field_label' => $this->faker->words(2, true),
            'field_placeholder' => $this->faker->sentence(3),
            'field_options' => $this->getFieldOptions($fieldType),
            'validation_rules' => [],
            'conditional_logic' => [],
            'order_index' => $this->faker->numberBetween(0, 10),
            'is_required' => $this->faker->boolean(30),
            'is_visible' => true,
            'crm_field_mapping' => []
        ];
    }

    private function getFieldOptions(string $fieldType): array
    {
        if (in_array($fieldType, ['select', 'radio', 'checkbox'])) {
            return [
                ['value' => 'option1', 'label' => 'Option 1'],
                ['value' => 'option2', 'label' => 'Option 2'],
                ['value' => 'option3', 'label' => 'Option 3']
            ];
        }

        return [];
    }

    public function required(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_required' => true
        ]);
    }

    public function withConditionalLogic(): static
    {
        return $this->state(fn (array $attributes) => [
            'conditional_logic' => [
                'logic' => 'and',
                'rules' => [
                    [
                        'field' => 'trigger_field',
                        'operator' => 'equals',
                        'value' => 'show'
                    ]
                ]
            ]
        ]);
    }

    public function withCrmMapping(string $crmField): static
    {
        return $this->state(fn (array $attributes) => [
            'crm_field_mapping' => [
                'crm_field' => $crmField
            ]
        ]);
    }

    public function email(): static
    {
        return $this->state(fn (array $attributes) => [
            'field_type' => 'email',
            'field_name' => 'email',
            'field_label' => 'Email Address',
            'field_placeholder' => 'Enter your email address',
            'field_options' => []
        ]);
    }

    public function text(): static
    {
        return $this->state(fn (array $attributes) => [
            'field_type' => 'text',
            'field_options' => []
        ]);
    }

    public function select(): static
    {
        return $this->state(fn (array $attributes) => [
            'field_type' => 'select',
            'field_options' => [
                ['value' => 'option1', 'label' => 'Option 1'],
                ['value' => 'option2', 'label' => 'Option 2']
            ]
        ]);
    }
}
