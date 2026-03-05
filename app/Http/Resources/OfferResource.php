<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\UserResource;
use App\Http\Resources\ProductResource;

class OfferResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'conversation_id' => $this->conversation_id,
            'sender' => new UserResource($this->whenLoaded('sender')),
            'product' => new ProductResource($this->whenLoaded('product')),
            'offered_price' => $this->offered_price,
            'quantity' => $this->quantity,
            'total' => $this->offered_price * $this->quantity,
            'notes' => $this->notes,
            'status' => $this->status,
            'expires_at' => $this->expires_at,
            'is_expired' => $this->isExpired(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
