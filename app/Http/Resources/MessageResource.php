<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;


class MessageResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'conversation_id' => $this->conversation_id,
            'sender' => new UserResource($this->whenLoaded('sender')),
            'message' => $this->message,
            'type' => $this->type,
            'metadata' => $this->metadata,
            'is_read' => $this->is_read,
            'read_at' => $this->read_at,
            'is_mine' => $this->sender_id === $request->user()->id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
