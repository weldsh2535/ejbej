<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\ProductResource;

class ConversationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        $userId = $request->user()->id;
        $otherUser = $this->buyer_id === $userId ? $this->seller : $this->buyer;

        return [
            'id' => $this->id,
            // 'product' => new ProductResource($this->whenLoaded('product')),
            'other_user' => [
                'id' => $otherUser->id,
                'full_name' => $otherUser->full_name,
                'username' => $otherUser->username,
                'profile_image_url' => $otherUser->profile_image_url,
            ],
            'title' => $this->title ?? $this->product->title,
            'status' => $this->status,
            'last_message' => new MessageResource($this->whenLoaded('latestMessage')),
            'unread_count' => $this->unreadMessagesCount($userId),
            'last_message_at' => $this->last_message_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
