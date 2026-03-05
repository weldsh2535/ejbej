<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;
use App\Http\Resources\UserResource;

class ProductResource extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    // app/Http/Resources/ProductCollection.php

    public function toArray($request)
    {
        return [
            'data' => $this->collection->map(function ($product) {
                $userData = null;

                // Safely check if user relationship exists and is loaded
                if ($product->relationLoaded('user') && $product->user) {
                    try {
                        $userData = new UserResource($product->user);
                    } catch (\Exception $e) {
                        \Log::warning('Failed to load user for product: ' . $product->id, [
                            'error' => $e->getMessage()
                        ]);

                        // Fallback to basic user data
                        $userData = [
                            'id' => $product->user_id,
                            'full_name' => 'User ' . $product->user_id,
                        ];
                    }
                }

                return [
                    'id' => $product->id,
                    'name' => $product->title,
                    'description' => $product->description,
                    'brand' => $product->brand,
                    'price' => $product->price,
                    'location' => $product->location,
                    'category_id' => $product->category_id,
                    'user' => $userData,
                    'user_id' => $product->user_id, // Keep the user_id as fallback
                    'is_active' => $product->is_active,
                    'image' => $product->image,
                    'image_url' => $this->getImageUrl($product),
                    'created_at' => $product->created_at,
                    'updated_at' => $product->updated_at,
                ];
            }),
            'pagination' => [
                'total' => $this->total(),
                'per_page' => $this->perPage(),
                'current_page' => $this->currentPage(),
                'last_page' => $this->lastPage(),
                'from' => $this->firstItem(),
                'to' => $this->lastItem(),
                'next_page_url' => $this->nextPageUrl(),
                'prev_page_url' => $this->previousPageUrl(),
                'has_more_pages' => $this->hasMorePages(),
            ],
        ];
    }

    private function getImageUrl($product)
    {
        if (!$product->image) {
            return asset('images/default-product.png');
        }

        if (filter_var($product->image, FILTER_VALIDATE_URL)) {
            return $product->image;
        }

        return asset('uploads/products/' . $product->image);
    }
}
