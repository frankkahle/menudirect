<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ManagedSiteResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'business_name' => $this->business_name,
            'status' => $this->status,
            'plan' => $this->plan,
            'plan_id' => $this->restaurant_plan_id,
            'ordering_enabled' => (bool) $this->ordering_enabled,
            'owner_id' => $this->client_id,
            'archived_at' => $this->archived_at,
            'public_url' => $this->getPublicUrl(),
        ];
    }
}
