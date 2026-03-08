<?php

namespace App\Http\Resources\Conversation;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Products\ProductResource as ProductsProductResource;
use App\Http\Resources\Users\UserResource as UsersUserResource;

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
            'sender' => new UsersUserResource($this->whenLoaded('sender')),
            'product' => new ProductsProductResource($this->whenLoaded('product')),
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
