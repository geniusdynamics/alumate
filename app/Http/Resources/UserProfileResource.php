<?php

namespace App\Http\Resources;

use App\Services\TenantContextService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $tenantContext = app(TenantContextService::class)->getCurrentTenant();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'full_name' => $this->full_name,
            'initials' => $this->initials,
            'avatar_url' => $this->avatar_url,
            'email_verified_at' => $this->email_verified_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'roles' => $this->whenLoaded('roles'),
            'permissions' => $this->whenLoaded('permissions'),
            'student_profile' => $this->whenLoaded('student'),
            'graduate_profile' => $this->whenLoaded('graduate'),
            'institution_profile' => $this->whenLoaded('institution'),
            'current_tenant' => $tenantContext ? new TenantSummaryResource($tenantContext) : null,
            'accessible_tenants' => TenantSummaryResource::collection($this->whenLoaded('tenants')),
        ];
    }
}
