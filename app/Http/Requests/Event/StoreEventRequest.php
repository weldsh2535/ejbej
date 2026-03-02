<?php

declare(strict_types=1);

namespace App\Http\Requests\Event;

use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;

class StoreEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Event::class);
    }


    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_time' => 'required',
            'end_time' => 'nullable|after_or_equal:start_time',
            'event_date' => 'required|date',
            'location' => 'nullable|string|max:2000',
            'google_map_location_link' => 'nullable|url|max:2000',
            'category' => 'nullable|string|max:255',
            'register_on_site' => 'nullable|boolean',
            'registration_link' => 'nullable|url|max:2000',
            'cost_amount' => 'nullable|numeric|min:0',
            'event_type' => 'required|string|in:in-person,virtual,online',
            'target_audience' => 'nullable|string|max:255',
            'status' => 'nullable|string|in:draft,created,reviewed,approved,published,archived',
            'event_image' => 'nullable|image|max:10240',
            'attachments.*' => 'nullable|file|max:10240',
        ];
    }
}
