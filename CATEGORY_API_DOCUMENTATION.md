# Category Management API Documentation

## 🏷️ Category Management with Image Upload

All category endpoints require **admin authentication** unless specified otherwise.

**Base URL**: `http://127.0.0.1:8000/api`

---

## 📂 Public Category Endpoints

### 1. Get All Categories (Public)

**GET** `/categories`

**Response (200):**

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "Electronics",
            "slug": "electronics",
            "description": "Latest electronic gadgets and devices",
            "image": null,
            "display_image": "https://via.placeholder.com/200x200/4F46E5/FFFFFF?text=E",
            "parent_id": null,
            "sort_order": 1,
            "is_active": true,
            "product_count": 15,
            "created_at": "2025-10-24T10:00:00.000000Z"
        },
        {
            "id": 7,
            "name": "Smartphones",
            "slug": "smartphones",
            "description": "Latest smartphones from top brands",
            "image": null,
            "display_image": "https://via.placeholder.com/200x200/4F46E5/FFFFFF?text=S",
            "parent_id": 1,
            "sort_order": 1,
            "is_active": true,
            "product_count": 8,
            "created_at": "2025-10-24T10:05:00.000000Z"
        }
    ]
}
```

### 2. Get Single Category (Public)

**GET** `/categories/1`

**Response (200):**

```json
{
    "success": true,
    "data": {
        "id": 1,
        "name": "Electronics",
        "slug": "electronics",
        "description": "Latest electronic gadgets and devices",
        "image": null,
        "display_image": "https://via.placeholder.com/200x200/4F46E5/FFFFFF?text=E",
        "parent_id": null,
        "sort_order": 1,
        "is_active": true,
        "meta_title": "Electronics - Latest Gadgets",
        "meta_description": "Shop the latest electronic gadgets, smartphones, laptops and more",
        "children": [
            {
                "id": 7,
                "name": "Smartphones",
                "slug": "smartphones",
                "display_image": "https://via.placeholder.com/200x200/4F46E5/FFFFFF?text=S"
            },
            {
                "id": 8,
                "name": "Laptops",
                "slug": "laptops",
                "display_image": "https://via.placeholder.com/200x200/4F46E5/FFFFFF?text=L"
            }
        ],
        "products": [
            {
                "id": 1,
                "name": "Wireless Headphones",
                "price": 89.99,
                "sale_price": 79.99,
                "images": ["https://example.com/headphones.jpg"]
            }
        ]
    }
}
```

---

## 👨‍💼 Admin Category Management

### 3. Get All Categories (Admin)

**GET** `/admin/categories?search=electronics&status=active&parent_only=true&page=1&limit=15`
_Requires Admin Authentication_

**Query Parameters:**

-   `search`: Search by name or description
-   `status`: Filter by `active` or `inactive`
-   `parent_only`: Show only parent categories (no subcategories)
-   `page`: Page number (default: 1)
-   `limit`: Items per page (default: 15)

**Response (200):**

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "Electronics",
            "slug": "electronics",
            "description": "Latest electronic gadgets and devices",
            "image": null,
            "display_image": "https://via.placeholder.com/200x200/4F46E5/FFFFFF?text=E",
            "parent_id": null,
            "sort_order": 1,
            "is_active": true,
            "meta_title": "Electronics - Latest Gadgets",
            "meta_description": "Shop the latest electronic gadgets, smartphones, laptops and more",
            "products_count": 15,
            "parent": null,
            "children": [
                {
                    "id": 7,
                    "name": "Smartphones",
                    "slug": "smartphones",
                    "display_image": "https://via.placeholder.com/200x200/4F46E5/FFFFFF?text=S",
                    "products_count": 8
                }
            ],
            "created_at": "2025-10-24T10:00:00.000000Z",
            "updated_at": "2025-10-24T10:00:00.000000Z"
        }
    ],
    "pagination": {
        "current_page": 1,
        "per_page": 15,
        "total": 7,
        "last_page": 1,
        "from": 1,
        "to": 7
    }
}
```

### 4. Get Category Statistics (Admin)

**GET** `/admin/categories/stats`
_Requires Admin Authentication_

**Response (200):**

```json
{
    "success": true,
    "data": {
        "total": 11,
        "active": 10,
        "inactive": 1,
        "withImages": 3,
        "withoutImages": 8,
        "parentCategories": 7,
        "subcategories": 4,
        "topCategories": [
            {
                "id": 1,
                "name": "Electronics",
                "slug": "electronics",
                "image": null,
                "display_image": "https://via.placeholder.com/200x200/4F46E5/FFFFFF?text=E",
                "products_count": 15
            },
            {
                "id": 2,
                "name": "Fashion",
                "slug": "fashion",
                "image": "fashion-category.jpg",
                "display_image": "http://127.0.0.1:8000/storage/categories/fashion-category.jpg",
                "products_count": 12
            }
        ]
    }
}
```

### 5. Create New Category (Admin)

**POST** `/admin/categories`
_Requires Admin Authentication_

**Content-Type**: `multipart/form-data` (for file upload) or `application/json`

**Request Body (Form Data):**

```
name: "Smart Home Devices"
description: "Connected devices for your smart home"
parent_id: 1
sort_order: 3
is_active: true
meta_title: "Smart Home Devices - IoT Products"
meta_description: "Transform your home with smart IoT devices"
image: [FILE] (optional - max 2MB, jpg/png/gif/svg)
```

**Request Body (JSON without image):**

```json
{
    "name": "Smart Home Devices",
    "description": "Connected devices for your smart home",
    "parent_id": 1,
    "sort_order": 3,
    "is_active": true,
    "meta_title": "Smart Home Devices - IoT Products",
    "meta_description": "Transform your home with smart IoT devices"
}
```

**Response (201):**

```json
{
    "success": true,
    "message": "Category created successfully",
    "data": {
        "id": 12,
        "name": "Smart Home Devices",
        "slug": "smart-home-devices",
        "description": "Connected devices for your smart home",
        "image": "1730367890_abc123def4.jpg",
        "display_image": "http://127.0.0.1:8000/storage/categories/1730367890_abc123def4.jpg",
        "parent_id": 1,
        "sort_order": 3,
        "is_active": true,
        "meta_title": "Smart Home Devices - IoT Products",
        "meta_description": "Transform your home with smart IoT devices",
        "parent": {
            "id": 1,
            "name": "Electronics",
            "slug": "electronics"
        },
        "children": [],
        "created_at": "2025-10-24T11:30:00.000000Z",
        "updated_at": "2025-10-24T11:30:00.000000Z"
    }
}
```

### 6. Get Single Category (Admin)

**GET** `/admin/categories/1`
_Requires Admin Authentication_

**Response (200):**

```json
{
    "success": true,
    "data": {
        "id": 1,
        "name": "Electronics",
        "slug": "electronics",
        "description": "Latest electronic gadgets and devices",
        "image": null,
        "display_image": "https://via.placeholder.com/200x200/4F46E5/FFFFFF?text=E",
        "parent_id": null,
        "sort_order": 1,
        "is_active": true,
        "meta_title": "Electronics - Latest Gadgets",
        "meta_description": "Shop the latest electronic gadgets, smartphones, laptops and more",
        "parent": null,
        "children": [
            {
                "id": 7,
                "name": "Smartphones",
                "slug": "smartphones",
                "display_image": "https://via.placeholder.com/200x200/4F46E5/FFFFFF?text=S"
            }
        ],
        "products": [
            {
                "id": 1,
                "name": "Wireless Headphones",
                "slug": "wireless-headphones",
                "price": 89.99,
                "sale_price": 79.99,
                "stock_quantity": 25,
                "is_featured": true,
                "category_id": 1
            }
        ],
        "created_at": "2025-10-24T10:00:00.000000Z",
        "updated_at": "2025-10-24T10:00:00.000000Z"
    }
}
```

### 7. Update Category (Admin)

**PUT** `/admin/categories/1`
_Requires Admin Authentication_

**Content-Type**: `multipart/form-data` (for file upload) or `application/json`

**Request Body:**

```json
{
    "name": "Consumer Electronics",
    "description": "Updated description for consumer electronics",
    "sort_order": 1,
    "is_active": true,
    "meta_title": "Consumer Electronics - Updated",
    "meta_description": "Updated meta description"
}
```

**Response (200):**

```json
{
    "success": true,
    "message": "Category updated successfully",
    "data": {
        "id": 1,
        "name": "Consumer Electronics",
        "slug": "consumer-electronics",
        "description": "Updated description for consumer electronics",
        "image": null,
        "display_image": "https://via.placeholder.com/200x200/4F46E5/FFFFFF?text=C",
        "parent_id": null,
        "sort_order": 1,
        "is_active": true,
        "meta_title": "Consumer Electronics - Updated",
        "meta_description": "Updated meta description",
        "parent": null,
        "children": [],
        "updated_at": "2025-10-24T12:00:00.000000Z"
    }
}
```

### 8. Delete Category (Admin)

**DELETE** `/admin/categories/1`
_Requires Admin Authentication_

**Response (200):**

```json
{
    "success": true,
    "message": "Category deleted successfully"
}
```

**Error Response (400) - Has Products:**

```json
{
    "success": false,
    "message": "Cannot delete category with existing products"
}
```

**Error Response (400) - Has Subcategories:**

```json
{
    "success": false,
    "message": "Cannot delete category with subcategories"
}
```

---

## 🖼️ Image Management

### 9. Upload/Update Category Image (Admin)

**POST** `/admin/categories/1/image`
_Requires Admin Authentication_

**Content-Type**: `multipart/form-data`

**Request Body:**

```
image: [FILE] (required - max 2MB, jpg/png/gif/svg)
```

**Response (200):**

```json
{
    "success": true,
    "message": "Category image updated successfully",
    "data": {
        "image": "1730368123_xyz789abc1.jpg",
        "display_image": "http://127.0.0.1:8000/storage/categories/1730368123_xyz789abc1.jpg"
    }
}
```

**Error Response (422):**

```json
{
    "success": false,
    "message": "Invalid image file",
    "errors": {
        "image": ["The image must be a file of type: jpeg, png, jpg, gif, svg."]
    }
}
```

### 10. Remove Category Image (Admin)

**DELETE** `/admin/categories/1/image`
_Requires Admin Authentication_

**Response (200):**

```json
{
    "success": true,
    "message": "Category image removed successfully",
    "data": {
        "image": null,
        "display_image": "https://via.placeholder.com/200x200/4F46E5/FFFFFF?text=E"
    }
}
```

**Error Response (400):**

```json
{
    "success": false,
    "message": "Category has no image to remove"
}
```

---

## 🎨 Placeholder Image System

### Automatic Placeholder Generation

When a category has **no uploaded image**, the system automatically generates a placeholder:

**Format**: `https://via.placeholder.com/200x200/4F46E5/FFFFFF?text={FIRST_LETTER}`

**Examples:**

-   **Electronics** → `https://via.placeholder.com/200x200/4F46E5/FFFFFF?text=E`
-   **Fashion** → `https://via.placeholder.com/200x200/4F46E5/FFFFFF?text=F`
-   **Smart Home** → `https://via.placeholder.com/200x200/4F46E5/FFFFFF?text=S`

**Colors:**

-   Background: `#4F46E5` (Indigo)
-   Text: `#FFFFFF` (White)
-   Size: `200x200` pixels

---

## 📁 File Storage Structure

**Upload Directory**: `storage/app/public/categories/`
**Public URL**: `http://127.0.0.1:8000/storage/categories/`

**File Naming Convention**: `{timestamp}_{random_string}.{extension}`

**Example**: `1730368123_xyz789abc1.jpg`

---

## ✅ Validation Rules

### Create/Update Category

```
name: required|string|max:255|unique
description: nullable|string|max:1000
parent_id: nullable|exists:categories,id
sort_order: nullable|integer|min:0
is_active: boolean
meta_title: nullable|string|max:255
meta_description: nullable|string|max:500
image: nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048
```

### Image Upload Only

```
image: required|image|mimes:jpeg,png,jpg,gif,svg|max:2048
```

---

## 🔧 Frontend Integration Examples

### JavaScript/React Usage

#### Get Categories with Images

```javascript
const response = await fetch("/api/categories");
const data = await response.json();

data.data.forEach((category) => {
    console.log(`${category.name}: ${category.display_image}`);
    // Always use display_image - it handles both uploaded images and placeholders
});
```

#### Upload Category Image

```javascript
const formData = new FormData();
formData.append("image", imageFile);

const response = await fetch(`/api/admin/categories/${categoryId}/image`, {
    method: "POST",
    headers: {
        Authorization: `Bearer ${token}`,
    },
    body: formData,
});
```

#### Create Category with Image

```javascript
const formData = new FormData();
formData.append("name", "New Category");
formData.append("description", "Category description");
formData.append("image", imageFile);

const response = await fetch("/api/admin/categories", {
    method: "POST",
    headers: {
        Authorization: `Bearer ${token}`,
    },
    body: formData,
});
```

---

## 📊 Sample Data Available

After running the setup script, you have:

-   **7 Parent Categories**: Electronics, Fashion, Home & Garden, Sports & Fitness, Books & Media, Automotive
-   **4 Subcategories**: Smartphones, Laptops, Men's Clothing, Women's Clothing
-   **All with Placeholder Images**: Each showing the first letter of the category name
-   **SEO Ready**: Meta titles and descriptions included

---

## 🚀 Production Considerations

1. **Image Optimization**: Consider adding image resizing/compression
2. **CDN**: Upload images to cloud storage (AWS S3, etc.) for production
3. **Cache**: Cache category data for better performance
4. **Validation**: Add server-side image validation (dimensions, file type verification)
5. **Backup**: Regular backup of uploaded images

**Ready to integrate with your admin panel!** 🎉
