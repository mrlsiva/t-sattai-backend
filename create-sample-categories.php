<?php

use Illuminate\Support\Facades\DB;
use App\Models\Category;

require_once 'vendor/autoload.php';

// Initialize Laravel
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🏷️ Creating Sample Categories with Placeholders...\n";
echo "==================================================\n\n";

try {
    // Sample categories data
    $categories = [
        [
            'name' => 'Electronics',
            'description' => 'Latest electronic gadgets and devices',
            'sort_order' => 1,
            'is_active' => true,
            'meta_title' => 'Electronics - Latest Gadgets',
            'meta_description' => 'Shop the latest electronic gadgets, smartphones, laptops and more'
        ],
        [
            'name' => 'Fashion',
            'description' => 'Trendy clothing and accessories for all occasions',
            'sort_order' => 2,
            'is_active' => true,
            'meta_title' => 'Fashion - Clothing & Accessories',
            'meta_description' => 'Discover the latest fashion trends in clothing and accessories'
        ],
        [
            'name' => 'Home & Garden',
            'description' => 'Everything you need for your home and garden',
            'sort_order' => 3,
            'is_active' => true,
            'meta_title' => 'Home & Garden - Home Improvement',
            'meta_description' => 'Transform your home and garden with our quality products'
        ],
        [
            'name' => 'Sports & Fitness',
            'description' => 'Sports equipment and fitness accessories',
            'sort_order' => 4,
            'is_active' => true,
            'meta_title' => 'Sports & Fitness Equipment',
            'meta_description' => 'Get fit with our range of sports and fitness equipment'
        ],
        [
            'name' => 'Books & Media',
            'description' => 'Books, movies, music and educational content',
            'sort_order' => 5,
            'is_active' => true,
            'meta_title' => 'Books & Media - Entertainment',
            'meta_description' => 'Explore our collection of books, movies, and media content'
        ],
        [
            'name' => 'Automotive',
            'description' => 'Car accessories and automotive parts',
            'sort_order' => 6,
            'is_active' => true,
            'meta_title' => 'Automotive Parts & Accessories',
            'meta_description' => 'Quality automotive parts and accessories for your vehicle'
        ]
    ];

    foreach ($categories as $categoryData) {
        $category = Category::updateOrCreate(
            ['name' => $categoryData['name']],
            $categoryData
        );

        echo "✅ Category: {$category->name}\n";
        echo "   - Slug: {$category->slug}\n";
        echo "   - Placeholder Image: {$category->display_image}\n";
        echo "   - Status: " . ($category->is_active ? 'Active' : 'Inactive') . "\n\n";
    }

    // Create some subcategories
    echo "📂 Creating Subcategories...\n\n";

    $electronicsParent = Category::where('name', 'Electronics')->first();
    $fashionParent = Category::where('name', 'Fashion')->first();

    $subcategories = [
        [
            'name' => 'Smartphones',
            'description' => 'Latest smartphones from top brands',
            'parent_id' => $electronicsParent->id,
            'sort_order' => 1,
            'is_active' => true
        ],
        [
            'name' => 'Laptops',
            'description' => 'High-performance laptops for work and gaming',
            'parent_id' => $electronicsParent->id,
            'sort_order' => 2,
            'is_active' => true
        ],
        [
            'name' => "Men's Clothing",
            'description' => 'Stylish clothing for men',
            'parent_id' => $fashionParent->id,
            'sort_order' => 1,
            'is_active' => true
        ],
        [
            'name' => "Women's Clothing",
            'description' => 'Fashionable clothing for women',
            'parent_id' => $fashionParent->id,
            'sort_order' => 2,
            'is_active' => true
        ]
    ];

    foreach ($subcategories as $subData) {
        $subcategory = Category::updateOrCreate(
            ['name' => $subData['name']],
            $subData
        );

        echo "   ├── Subcategory: {$subcategory->name}\n";
        echo "   │   - Parent: " . $subcategory->parent->name . "\n";
        echo "   │   - Placeholder Image: {$subcategory->display_image}\n";
        echo "   └── Status: " . ($subcategory->is_active ? 'Active' : 'Inactive') . "\n\n";
    }

    echo "📊 Category Statistics:\n";
    $totalCategories = Category::count();
    $activeCategories = Category::where('is_active', true)->count();
    $parentCategories = Category::whereNull('parent_id')->count();
    $subcategories = Category::whereNotNull('parent_id')->count();
    $withoutImages = Category::whereNull('image')->count();

    echo "   Total Categories: {$totalCategories}\n";
    echo "   Active Categories: {$activeCategories}\n";
    echo "   Parent Categories: {$parentCategories}\n";
    echo "   Subcategories: {$subcategories}\n";
    echo "   Categories without images: {$withoutImages}\n\n";

    echo "🎉 Sample categories created successfully!\n";
    echo "All categories have placeholder images with their first letter.\n\n";

    echo "🔗 API Endpoints Available:\n";
    echo "   GET    /api/admin/categories          # List all categories\n";
    echo "   GET    /api/admin/categories/stats    # Category statistics\n";
    echo "   POST   /api/admin/categories          # Create category\n";
    echo "   GET    /api/admin/categories/{id}     # Get specific category\n";
    echo "   PUT    /api/admin/categories/{id}     # Update category\n";
    echo "   DELETE /api/admin/categories/{id}     # Delete category\n";
    echo "   POST   /api/admin/categories/{id}/image    # Upload image\n";
    echo "   DELETE /api/admin/categories/{id}/image    # Remove image\n\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}