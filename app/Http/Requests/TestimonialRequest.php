<?php

namespace App\Http\Requests;

use App\Models\Testimonial;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TestimonialRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization is handled in the controller
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $testimonialId = $this->route('testimonial')?->id;
        
        $rules = [
            'author_name' => 'required|string|max:255|min:2',
            'author_title' => 'nullable|string|max:255',
            'author_company' => 'nullable|string|max:255',
            'author_photo' => 'nullable|string|max:500|url',
            'graduation_year' => 'nullable|integer|min:1900|max:2100',
            'industry' => 'nullable|string|max:100',
            'audience_type' => ['required', Rule::in(Testimonial::AUDIENCE_TYPES)],
            'content' => 'required|string|min:10|max:2000',
            'video_url' => 'nullable|string|max:500|url',
            'video_thumbnail' => 'nullable|string|max:500|url',
            'rating' => 'nullable|integer|min:1|max:5',
            'status' => ['nullable', Rule::in(Testimonial::STATUSES)],
            'featured' => 'nullable|boolean',
            'metadata' => 'nullable|array',
        ];

        // Add tenant_id validation for creation
        if ($this->isMethod('POST')) {
            $rules['tenant_id'] = 'nullable|exists:tenants,id';
        }

        return $rules;
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'author_name.required' => 'The author name is required.',
            'author_name.min' => 'The author name must be at least 2 characters.',
            'author_name.max' => 'The author name may not be greater than 255 characters.',
            'author_photo.url' => 'The author photo must be a valid URL.',
            'graduation_year.integer' => 'The graduation year must be a valid year.',
            'graduation_year.min' => 'The graduation year must be at least 1900.',
            'graduation_year.max' => 'The graduation year may not be greater than 2100.',
            'industry.max' => 'The industry may not be greater than 100 characters.',
            'audience_type.required' => 'The audience type is required.',
            'audience_type.in' => 'The audience type must be one of: individual, institution, employer.',
            'content.required' => 'The testimonial content is required.',
            'content.min' => 'The testimonial content must be at least 10 characters.',
            'content.max' => 'The testimonial content may not be greater than 2000 characters.',
            'video_url.url' => 'The video URL must be a valid URL.',
            'video_thumbnail.url' => 'The video thumbnail must be a valid URL.',
            'rating.integer' => 'The rating must be a number.',
            'rating.min' => 'The rating must be at least 1.',
            'rating.max' => 'The rating may not be greater than 5.',
            'status.in' => 'The status must be one of: pending, approved, rejected, archived.',
            'featured.boolean' => 'The featured field must be true or false.',
            'metadata.array' => 'The metadata must be a valid array.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Validate video testimonial requirements
            if ($this->filled('video_url') && !$this->filled('video_thumbnail')) {
                $validator->errors()->add('video_thumbnail', 'Video testimonials require a thumbnail image.');
            }

            // Validate that video thumbnail is only provided with video URL
            if ($this->filled('video_thumbnail') && !$this->filled('video_url')) {
                $validator->errors()->add('video_url', 'Video thumbnail requires a video URL.');
            }

            // Validate graduation year is not in the future
            if ($this->filled('graduation_year') && $this->graduation_year > now()->year) {
                $validator->errors()->add('graduation_year', 'The graduation year cannot be in the future.');
            }
        });
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default status for new testimonials
        if ($this->isMethod('POST') && !$this->has('status')) {
            $this->merge(['status' => 'pending']);
        }

        // Set tenant_id from authenticated user if not provided
        if ($this->isMethod('POST') && !$this->has('tenant_id') && auth()->check()) {
            $this->merge(['tenant_id' => auth()->user()->tenant_id]);
        }

        // Ensure featured is boolean
        if ($this->has('featured')) {
            $this->merge(['featured' => $this->boolean('featured')]);
        }

        // Clean up empty strings to null
        $fieldsToNullify = ['author_title', 'author_company', 'author_photo', 'industry', 'video_url', 'video_thumbnail'];
        foreach ($fieldsToNullify as $field) {
            if ($this->has($field) && $this->$field === '') {
                $this->merge([$field => null]);
            }
        }
    }
}