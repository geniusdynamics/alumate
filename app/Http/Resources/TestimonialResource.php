<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TestimonialResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            
            // Author information
            'author' => [
                'name' => $this->author_name,
                'title' => $this->author_title,
                'company' => $this->author_company,
                'photo' => $this->author_photo,
                'display_name' => $this->author_display_name,
            ],
            
            // Categorization
            'graduation_year' => $this->graduation_year,
            'industry' => $this->industry,
            'audience_type' => $this->audience_type,
            
            // Content
            'content' => $this->content,
            'truncated_content' => $this->truncated_content,
            'rating' => $this->rating,
            
            // Video content
            'video' => [
                'url' => $this->video_url,
                'thumbnail' => $this->video_thumbnail,
                'has_video' => $this->hasVideo(),
            ],
            
            // Status and moderation
            'status' => $this->status,
            'featured' => $this->featured,
            'is_approved' => $this->isApproved(),
            'is_pending' => $this->isPending(),
            'is_rejected' => $this->isRejected(),
            
            // Performance metrics
            'performance' => [
                'view_count' => $this->view_count,
                'click_count' => $this->click_count,
                'conversion_rate' => (float) $this->conversion_rate,
            ],
            
            // Additional metadata
            'metadata' => $this->metadata,
            
            // Timestamps
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            
            // Conditional fields based on user permissions
            'admin_fields' => $this->when(
                $request->user()?->can('moderate', $this->resource),
                [
                    'moderation_actions' => [
                        'can_approve' => $this->isPending(),
                        'can_reject' => $this->isPending() || $this->isApproved(),
                        'can_archive' => !$this->status === 'archived',
                        'can_feature' => $this->isApproved(),
                    ],
                ]
            ),
        ];
    }

    /**
     * Get additional data that should be returned with the resource array.
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'resource_type' => 'testimonial',
                'version' => '1.0',
            ],
        ];
    }
}