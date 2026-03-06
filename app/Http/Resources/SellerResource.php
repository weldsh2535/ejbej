<?php
// app/Http/Resources/SellerResource.php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SellerResource extends JsonResource
{
    public function toArray($request)
    {
        // Handle name safely
        $name = $this->name 
            ?? $this->full_name 
            ?? ($this->first_name && $this->last_name ? $this->first_name . ' ' . $this->last_name : null)
            ?? 'Unknown Seller';

        // Handle dates safely
        $joinedDate = null;
        $joinedHuman = null;
        
        if ($this->created_at) {
            try {
                $joinedDate = $this->created_at->format('Y-m-d');
                $joinedHuman = $this->created_at->diffForHumans();
            } catch (\Exception $e) {
                // If date formatting fails, just leave as null
            }
        }

        return [
            'id' => $this->id,
            'name' => $name,
            'email' => $this->email,
            'phone' => $this->phone,
            'profile_image' => $this->profile_image ? asset('storage/' . $this->profile_image) : null,
            'joined_date' => $joinedDate,
            'joined_human' => $joinedHuman,
            'products_count' => $this->when(isset($this->products_count), $this->products_count ?? 0),
            'verified' => $this->email_verified_at ? true : false,
            'has_products' => ($this->products_count ?? 0) > 0,
        ];
    }
}