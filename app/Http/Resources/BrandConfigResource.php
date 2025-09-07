<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BrandConfigResource extends JsonResource
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
            'name' => $this->name,
            'primary_color' => $this->primary_color,
            'secondary_color' => $this->secondary_color,
            'accent_color' => $this->accent_color,
            'font_family' => $this->font_family,
            'heading_font_family' => $this->heading_font_family,
            'body_font_family' => $this->body_font_family,
            'logo_url' => $this->logo_url,
            'favicon_url' => $this->favicon_url,
            'custom_css' => $this->custom_css,
            'font_weights' => $this->font_weights,
            'brand_colors' => $this->brand_colors,
            'typography_settings' => $this->typography_settings,
            'spacing_settings' => $this->spacing_settings,
            'is_default' => $this->is_default,
            'is_active' => $this->is_active,
            'effective_config' => $this->whenLoaded($this->getEffectiveConfig()),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'creator' => $this->whenLoaded('creator', function () {
                return [
                    'id' => $this->creator->id,
                    'name' => $this->creator->name,
                    'email' => $this->creator->email,
                ];
            }),
            'updater' => $this->whenLoaded('updater', function () {
                return [
                    'id' => $this->updater->id,
                    'name' => $this->updater->name,
                    'email' => $this->updater->email,
                ];
            }),
        ];
    }
}