<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'event_date' => $this->event_date,
            'location' => $this->location,
            'google_map_location_link' => $this->google_map_location_link,
            'category' => $this->category,
            'target_audience' => $this->target_audience,
            'event_type' => $this->event_type,
            'status' => $this->status,
            'cost_amount' => $this->cost_amount,
            'register_on_site' => $this->register_on_site,
            'registration_link' => $this->registration_link,
            'event_image' => $this->event_image ? asset('storage/' . $this->event_image) : null,
            'attachments' => $this->attachments ? collect($this->attachments)->map(function($att) {
                return [
                    'file_name' => $att['file_name'] ?? null,
                    'path' => isset($att['path']) ? asset('storage/' . $att['path']) : null,
                    'size' => $att['size'] ?? null
                ];
            }) : [],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

