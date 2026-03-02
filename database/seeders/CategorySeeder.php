<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $categories = [
            [
                "name" => "Electronics",
                "slug" => "/electronics",
                "icon" => "Cpu"
            ],
            [
                "name" => "Fashion & Beauty",
                "slug" => "/fashions",
                "icon" => "Shirt"
            ],
            [
                "name" => "Automotives",
                "slug" => "/automotives",
                "icon" => "CarFront"
            ],
            [
                "name" => "Houses",
                "slug" => "/houses",
                "icon" => "House"
            ],
            [
                "name" => "Furniture & Appliances",
                "slug" => "/furniture",
                "icon" => "Lamp"
            ],
            [
                "name" => "Services",
                "slug" => "/services",
                "icon" => "Wrench"
            ],
            [
                "name" => "Jobs",
                "slug" => "/jobs",
                "icon" => "Briefcase"
            ],
            [
                "name" => "Books",
                "slug" => "/books",
                "icon" => "Book"
            ],
            [
                "name" => "Others",
                "slug" => "/others",
                "icon" => "ShoppingBag"
            ]
        ];
        
        // Remove duplicates (optional)
        $uniqueCategories = collect($categories)->unique(function ($item) {
            return $item['name'].$item['slug'];
        })->values()->all();

        foreach ($uniqueCategories as $category) {
            Category::create($category);
        }
    }
}
