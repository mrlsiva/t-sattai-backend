<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Create categories if they don't exist
        $electronics = Category::firstOrCreate([
            'slug' => 'electronics'
        ], [
            'name' => 'Electronics',
            'description' => 'Electronic devices and gadgets',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $clothing = Category::firstOrCreate([
            'slug' => 'clothing'
        ], [
            'name' => 'Clothing',
            'description' => 'Fashion and apparel',
            'sort_order' => 2,
            'is_active' => true,
        ]);

        $home = Category::firstOrCreate([
            'slug' => 'home-garden'
        ], [
            'name' => 'Home & Garden',
            'description' => 'Home improvement and garden supplies',
            'sort_order' => 3,
            'is_active' => true,
        ]);

        // Create products
        Product::firstOrCreate([
            'sku' => 'IPHONE15PRO'
        ], [
            'name' => 'iPhone 15 Pro',
            'slug' => 'iphone-15-pro',
            'description' => 'Latest iPhone with A17 Pro chip and titanium design',
            'short_description' => 'Premium smartphone with advanced features',
            'price' => 999.00,
            'sale_price' => 899.00,
            'stock' => 50,
            'category_id' => $electronics->id,
            'images' => [
                'https://images.unsplash.com/photo-1592750475338-74b7b21085ab?w=500',
                'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=500'
            ],
            'weight' => 0.221,
            'specifications' => [
                'Display' => '6.1-inch Super Retina XDR',
                'Chip' => 'A17 Pro',
                'Camera' => '48MP Main',
                'Storage' => '128GB'
            ],
            'is_featured' => true,
            'is_active' => true,
        ]);

        Product::firstOrCreate([
            'sku' => 'MBP14M3'
        ], [
            'name' => 'MacBook Pro 14"',
            'slug' => 'macbook-pro-14',
            'description' => 'Powerful laptop with M3 chip for professional workflows',
            'short_description' => 'High-performance laptop for creators',
            'price' => 1999.00,
            'sale_price' => 1799.00,
            'stock' => 25,
            'category_id' => $electronics->id,
            'images' => [
                'https://images.unsplash.com/photo-1517336714731-489689fd1ca4?w=500',
                'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=500'
            ],
            'weight' => 1.6,
            'specifications' => [
                'Display' => '14.2-inch Liquid Retina XDR',
                'Chip' => 'Apple M3',
                'Memory' => '8GB unified memory',
                'Storage' => '512GB SSD'
            ],
            'is_featured' => true,
            'is_active' => true,
        ]);

        Product::firstOrCreate([
            'sku' => 'TSHIRT001'
        ], [
            'name' => 'Classic T-Shirt',
            'slug' => 'classic-t-shirt',
            'description' => 'Comfortable cotton t-shirt in various colors',
            'short_description' => 'Essential everyday wear',
            'price' => 29.99,
            'sale_price' => 24.99,
            'stock' => 100,
            'category_id' => $clothing->id,
            'images' => [
                'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=500',
                'https://images.unsplash.com/photo-1562157873-818bc0726f68?w=500'
            ],
            'weight' => 0.2,
            'specifications' => [
                'Material' => '100% Cotton',
                'Fit' => 'Regular',
                'Care' => 'Machine wash'
            ],
            'is_featured' => false,
            'is_active' => true,
        ]);

        Product::firstOrCreate([
            'sku' => 'COFFEE001'
        ], [
            'name' => 'Coffee Maker',
            'slug' => 'coffee-maker',
            'description' => 'Programmable coffee maker with thermal carafe',
            'short_description' => 'Perfect coffee every morning',
            'price' => 89.99,
            'sale_price' => 79.99,
            'stock' => 30,
            'category_id' => $home->id,
            'images' => [
                'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?w=500',
                'https://images.unsplash.com/photo-1559056199-641a0ac8b55e?w=500'
            ],
            'weight' => 2.5,
            'specifications' => [
                'Capacity' => '10 cups',
                'Features' => 'Programmable, Auto-shutoff',
                'Carafe' => 'Thermal stainless steel'
            ],
            'is_featured' => true,
            'is_active' => true,
        ]);
    }
}
