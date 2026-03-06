<?php
// app/Http/Resources/ProductResource.php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the product resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $userData = null;

        // Safely check if user relationship exists and is loaded
        if ($this->relationLoaded('user') && $this->user) {
            try {
                $userData = new UserResource($this->user);
            } catch (\Exception $e) {
                \Log::warning('Failed to load user for product: ' . $this->id, [
                    'error' => $e->getMessage()
                ]);

                // Fallback to basic user data
                $userData = [
                    'id' => $this->user_id,
                    'full_name' => 'User ' . $this->user_id,
                ];
            }
        }

        return [
            'id' => $this->id,
            'name' => $this->title,
            'description' => $this->description,
            'brand' => $this->brand,
            'price' => $this->price,
            'location' => $this->location,
            'category_id' => $this->category_id,
            'user' => $userData,
            'user_id' => $this->user_id,
            'is_active' => $this->is_active,
            'images' => ProductImageResource::collection($this->whenLoaded('images')),
            'primary_image' => $this->primary_image_url,
            'all_images' => $this->image_urls,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Customize the response for a request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Http\Response  $response
     * @return array
     */
    public function with($request)
    {
        return [
            'success' => true,
            'message' => 'Product retrieved successfully',
        ];
    }
}