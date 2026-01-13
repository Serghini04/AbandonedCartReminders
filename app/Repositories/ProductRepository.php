<?php

namespace App\Repositories;

use App\Models\Product;

class ProductRepository
{
    public function findOrFail(int $productId): Product
    {
        return Product::findOrFail($productId);
    }

    public function all()
    {
        return Product::all();
    }

    public function create(array $data): Product
    {
        return Product::create($data);
    }
}
