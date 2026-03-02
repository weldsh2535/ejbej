<?php

namespace Database\Seeders;

use App\Models\SubCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SubCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $subcategories = [
            // Electronics (category_id: 1)
            ['category_id' => 1, 'name' => 'Mobile Phones', 'slug' => '/electronics/mobile-phones'],
            ['category_id' => 1, 'name' => 'Tablets', 'slug' => '/electronics/tablets'],
            ['category_id' => 1, 'name' => 'Computers & Laptops', 'slug' => '/electronics/computers'],
            ['category_id' => 1, 'name' => 'Gadgets', 'slug' => '/electronics/gadgets'],
            ['category_id' => 1, 'name' => 'Mobile Phones', 'slug' => '/electronics/mobile-phones'],
            ['category_id' => 1, 'name' => 'Tablets', 'slug' => '/electronics/tablets'],
            ['category_id' => 1, 'name' => 'Computers & Laptops', 'slug' => '/electronics/computers'],
            ['category_id' => 1, 'name' => 'Gadgets', 'slug' => '/electronics/gadgets'],
            
            // Fashion (category_id: 2)
            ['category_id' => 2, 'name' => 'Clothing', 'slug' => '/fashion/clothing'],
            ['category_id' => 2, 'name' => 'Accessories', 'slug' => '/fashion/accessories'],
            ['category_id' => 2, 'name' => 'Beauty', 'slug' => '/fashion/beauty'],
            ['category_id' => 2, 'name' => 'Shoes', 'slug' => '/fashion/shoes'],
            ['category_id' => 2, 'name' => 'Bags', 'slug' => '/fashion/bags'],
            ['category_id' => 2, 'name' => 'Jewelry', 'slug' => '/fashion/jewelry'],
            ['category_id' => 2, 'name' => 'Watches', 'slug' => '/fashion/watches'],
            
            // Automotives (category_id: 3)
            ['category_id' => 3, 'name' => 'Cars', 'slug' => '/automotives/cars'],
            ['category_id' => 3, 'name' => 'Bikes', 'slug' => '/automotives/bikes'],
            ['category_id' => 3, 'name' => 'Spare Parts', 'slug' => '/automotives/spare-parts'],
            
            // Houses (category_id: 4)
            ['category_id' => 4, 'name' => 'Rent', 'slug' => '/houses/rent'],
            ['category_id' => 4, 'name' => 'Sale', 'slug' => '/houses/sale'],
            ['category_id' => 4, 'name' => 'Land', 'slug' => '/houses/land'],
            
            // Furniture (category_id: 5)
            ['category_id' => 5, 'name' => 'Furniture', 'slug' => '/furniture/furniture'],
            ['category_id' => 5, 'name' => 'Appliances', 'slug' => '/furniture/appliances'],
            
            // Services (category_id: 6)
            ['category_id' => 6, 'name' => 'Services', 'slug' => '/services/services'],
            ['category_id' => 6, 'name' => 'Jobs', 'slug' => '/services/jobs'],
            ['category_id' => 6, 'name' => 'Events', 'slug' => '/services/events'],
            ['category_id' => 6, 'name' => 'Tutors', 'slug' => '/services/tutors'],
            ['category_id' => 6, 'name' => 'Other', 'slug' => '/services/other'],
            
            // Jobs (category_id: 7)
            ['category_id' => 7, 'name' => 'Jobs', 'slug' => '/jobs/jobs'],
            ['category_id' => 7, 'name' => 'Freelancing', 'slug' => '/jobs/freelancing'],
            ['category_id' => 7, 'name' => 'Other', 'slug' => '/jobs/other'],
            ['category_id' => 7, 'name' => 'Software', 'slug' => '/jobs/software'],
            ['category_id' => 7, 'name' => 'Design', 'slug' => '/jobs/design'],
            
            // Books (category_id: 8)
            ['category_id' => 8, 'name' => 'Fiction', 'slug' => '/books/fiction'],
            ['category_id' => 8, 'name' => 'Non-Fiction', 'slug' => '/books/non-fiction'],
            ['category_id' => 8, 'name' => 'Other', 'slug' => '/books/other']
        ];
        
        // Remove duplicates (optional)
        $uniqueSubcategories = collect($subcategories)->unique(function ($item) {
            return $item['category_id'].$item['name'].$item['slug'];
        })->values()->all();
        
        foreach ($uniqueSubcategories as $subcategory) {
            SubCategory::create($subcategory);
        }
    }
}
