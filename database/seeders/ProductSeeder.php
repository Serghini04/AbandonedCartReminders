<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    public function run()
    {
        Product::create([
            'name' => 'Laptop',
            'price' => 999.99,
            'description' => 'High-end laptop'
        ]);
        
        Product::create([
            'name' => 'Mouse',
            'price' => 25.50,
            'description' => 'Wireless mouse'
        ]);
    }
}