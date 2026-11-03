<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Client */
class ClientResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'city' => $this->city,
            // بنعتمد على lazy loading هنا (مش whenLoaded) عشان 'farms' يفضل
            // موجود دايماً في الرد (ولو مصفوفة فاضية) حتى لو المتحكم مبعتلوش eager load.
            'farms' => FarmSummaryResource::collection($this->farms),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
