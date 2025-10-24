# Complete E-commerce API Documentation with Sample Data

## Base URL

```
http://127.0.0.1:8000/api
```

## Authentication

Most endpoints require authentication using Bearer tokens:

```
Authorization: Bearer {your_access_token}
```

---

## 🔐 Authentication Endpoints

### 1. User Registration

**POST** `/auth/register`

**Request Body:**

```json
{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123"
}
```

**Response (201):**

```json
{
    "success": true,
    "message": "User registered successfully",
    "data": {
        "user": {
            "id": 8,
            "name": "John Doe",
            "email": "john@example.com",
            "role": "customer",
            "is_active": true,
            "email_verified_at": null,
            "created_at": "2025-10-23T14:30:00.000000Z",
            "updated_at": "2025-10-23T14:30:00.000000Z"
        },
        "token": "1|abcdef123456789..."
    }
}
```

### 2. User Login

**POST** `/auth/login`

**Request Body:**

```json
{
    "email": "admin@test.com",
    "password": "password"
}
```

**Response (200):**

```json
{
    "success": true,
    "message": "Login successful",
    "data": {
        "user": {
            "id": 6,
            "name": "Test Admin",
            "email": "admin@test.com",
            "role": "admin",
            "is_active": true,
            "email_verified_at": "2025-10-23T14:18:26.000000Z",
            "created_at": "2025-10-23T14:18:26.000000Z",
            "updated_at": "2025-10-23T14:18:26.000000Z"
        },
        "token": "2|xyz789abc123def..."
    }
}
```

### 3. Get User Profile

**GET** `/auth/profile`
_Requires Authentication_

**Headers:**

```
Authorization: Bearer 2|xyz789abc123def...
```

**Response (200):**

```json
{
    "success": true,
    "data": {
        "id": 6,
        "name": "Test Admin",
        "email": "admin@test.com",
        "role": "admin",
        "is_active": true,
        "email_verified_at": "2025-10-23T14:18:26.000000Z",
        "created_at": "2025-10-23T14:18:26.000000Z",
        "updated_at": "2025-10-23T14:18:26.000000Z"
    }
}
```

### 4. Update Profile

**PUT** `/auth/profile`
_Requires Authentication_

**Request Body:**

```json
{
    "name": "Test Admin Updated",
    "email": "admin-updated@test.com"
}
```

**Response (200):**

```json
{
    "success": true,
    "message": "Profile updated successfully",
    "data": {
        "id": 6,
        "name": "Test Admin Updated",
        "email": "admin-updated@test.com",
        "role": "admin",
        "is_active": true,
        "email_verified_at": "2025-10-23T14:18:26.000000Z",
        "created_at": "2025-10-23T14:18:26.000000Z",
        "updated_at": "2025-10-23T14:30:45.000000Z"
    }
}
```

### 5. Change Password

**PUT** `/auth/change-password`
_Requires Authentication_

**Request Body:**

```json
{
    "current_password": "password",
    "new_password": "newpassword123",
    "new_password_confirmation": "newpassword123"
}
```

**Response (200):**

```json
{
    "success": true,
    "message": "Password changed successfully"
}
```

### 6. Logout

**POST** `/auth/logout`
_Requires Authentication_

**Response (200):**

```json
{
    "success": true,
    "message": "Logged out successfully"
}
```

### 7. Logout All Devices

**POST** `/auth/logout-all`
_Requires Authentication_

**Response (200):**

```json
{
    "success": true,
    "message": "Logged out from all devices successfully"
}
```

---

## 🛍️ Products & Categories

### 8. Get All Products

**GET** `/products?page=1&limit=12&search=headphone&category_id=1`

**Response (200):**

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "Wireless Bluetooth Headphones",
            "description": "High-quality wireless headphones with noise cancellation",
            "price": 89.99,
            "sale_price": 79.99,
            "sku": "WBH-001",
            "stock_quantity": 25,
            "is_featured": true,
            "status": "active",
            "images": [
                "https://example.com/headphones-1.jpg",
                "https://example.com/headphones-2.jpg"
            ],
            "category": {
                "id": 1,
                "name": "Electronics",
                "slug": "electronics"
            },
            "average_rating": 4.5,
            "review_count": 12,
            "created_at": "2025-10-23T10:00:00.000000Z"
        },
        {
            "id": 2,
            "name": "Gaming Headset Pro",
            "description": "Professional gaming headset with 7.1 surround sound",
            "price": 149.99,
            "sale_price": null,
            "sku": "GHP-002",
            "stock_quantity": 15,
            "is_featured": false,
            "status": "active",
            "images": ["https://example.com/gaming-headset-1.jpg"],
            "category": {
                "id": 1,
                "name": "Electronics",
                "slug": "electronics"
            },
            "average_rating": 4.8,
            "review_count": 8,
            "created_at": "2025-10-23T11:00:00.000000Z"
        }
    ],
    "pagination": {
        "current_page": 1,
        "per_page": 12,
        "total": 25,
        "last_page": 3,
        "from": 1,
        "to": 12
    }
}
```

### 9. Get Featured Products

**GET** `/products/featured?limit=8`

**Response (200):**

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "Wireless Bluetooth Headphones",
            "price": 89.99,
            "sale_price": 79.99,
            "images": ["https://example.com/headphones-1.jpg"],
            "average_rating": 4.5,
            "review_count": 12,
            "category": {
                "id": 1,
                "name": "Electronics"
            }
        },
        {
            "id": 3,
            "name": "Smart Watch Series X",
            "price": 299.99,
            "sale_price": 249.99,
            "images": ["https://example.com/smartwatch-1.jpg"],
            "average_rating": 4.7,
            "review_count": 25,
            "category": {
                "id": 1,
                "name": "Electronics"
            }
        }
    ]
}
```

### 10. Get Single Product

**GET** `/products/1`

**Response (200):**

```json
{
    "success": true,
    "data": {
        "id": 1,
        "name": "Wireless Bluetooth Headphones",
        "description": "High-quality wireless headphones with noise cancellation. Features include 30-hour battery life, quick charge, and premium audio drivers.",
        "price": 89.99,
        "sale_price": 79.99,
        "sku": "WBH-001",
        "stock_quantity": 25,
        "is_featured": true,
        "status": "active",
        "images": [
            "https://example.com/headphones-1.jpg",
            "https://example.com/headphones-2.jpg",
            "https://example.com/headphones-3.jpg"
        ],
        "specifications": {
            "battery_life": "30 hours",
            "connectivity": "Bluetooth 5.0",
            "weight": "250g",
            "color": "Black"
        },
        "category": {
            "id": 1,
            "name": "Electronics",
            "slug": "electronics"
        },
        "reviews": [
            {
                "id": 1,
                "user_name": "Sarah Johnson",
                "rating": 5,
                "comment": "Excellent sound quality and battery life!",
                "created_at": "2025-10-20T15:30:00.000000Z"
            },
            {
                "id": 2,
                "user_name": "Mike Chen",
                "rating": 4,
                "comment": "Great headphones, very comfortable for long use.",
                "created_at": "2025-10-18T09:15:00.000000Z"
            }
        ],
        "average_rating": 4.5,
        "review_count": 12,
        "related_products": [2, 5, 8],
        "created_at": "2025-10-23T10:00:00.000000Z"
    }
}
```

### 11. Get All Categories

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
            "image": "https://example.com/categories/electronics.jpg",
            "product_count": 15,
            "is_active": true,
            "sort_order": 1
        },
        {
            "id": 2,
            "name": "Fashion",
            "slug": "fashion",
            "description": "Trendy clothing and accessories",
            "image": "https://example.com/categories/fashion.jpg",
            "product_count": 28,
            "is_active": true,
            "sort_order": 2
        },
        {
            "id": 3,
            "name": "Home & Garden",
            "slug": "home-garden",
            "description": "Everything for your home and garden",
            "image": "https://example.com/categories/home.jpg",
            "product_count": 12,
            "is_active": true,
            "sort_order": 3
        }
    ]
}
```

### 12. Get Category Details

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
        "image": "https://example.com/categories/electronics.jpg",
        "product_count": 15,
        "is_active": true,
        "sort_order": 1,
        "products": [
            {
                "id": 1,
                "name": "Wireless Bluetooth Headphones",
                "price": 89.99,
                "sale_price": 79.99,
                "images": ["https://example.com/headphones-1.jpg"],
                "average_rating": 4.5
            },
            {
                "id": 2,
                "name": "Gaming Headset Pro",
                "price": 149.99,
                "sale_price": null,
                "images": ["https://example.com/gaming-headset-1.jpg"],
                "average_rating": 4.8
            }
        ],
        "subcategories": []
    }
}
```

---

## 🛒 Shopping Cart

### 13. Get Cart Items

**GET** `/cart`
_Requires Authentication_

**Response (200):**

```json
{
    "success": true,
    "data": {
        "items": [
            {
                "id": 1,
                "product_id": 1,
                "product_name": "Wireless Bluetooth Headphones",
                "product_sku": "WBH-001",
                "product_image": "https://example.com/headphones-1.jpg",
                "price": 79.99,
                "quantity": 2,
                "subtotal": 159.98,
                "product": {
                    "id": 1,
                    "name": "Wireless Bluetooth Headphones",
                    "stock_quantity": 25,
                    "status": "active"
                }
            },
            {
                "id": 2,
                "product_id": 3,
                "product_name": "Smart Watch Series X",
                "product_sku": "SWX-003",
                "product_image": "https://example.com/smartwatch-1.jpg",
                "price": 249.99,
                "quantity": 1,
                "subtotal": 249.99,
                "product": {
                    "id": 3,
                    "name": "Smart Watch Series X",
                    "stock_quantity": 8,
                    "status": "active"
                }
            }
        ],
        "summary": {
            "items_count": 3,
            "subtotal": 409.97,
            "tax": 32.8,
            "shipping": 9.99,
            "total": 452.76
        }
    }
}
```

### 14. Add Item to Cart

**POST** `/cart`
_Requires Authentication_

**Request Body:**

```json
{
    "product_id": 1,
    "quantity": 2
}
```

**Response (201):**

```json
{
    "success": true,
    "message": "Item added to cart successfully",
    "data": {
        "id": 3,
        "product_id": 1,
        "product_name": "Wireless Bluetooth Headphones",
        "product_sku": "WBH-001",
        "product_image": "https://example.com/headphones-1.jpg",
        "price": 79.99,
        "quantity": 2,
        "subtotal": 159.98,
        "created_at": "2025-10-23T14:45:00.000000Z"
    }
}
```

### 15. Update Cart Item

**PUT** `/cart/1`
_Requires Authentication_

**Request Body:**

```json
{
    "quantity": 3
}
```

**Response (200):**

```json
{
    "success": true,
    "message": "Cart item updated successfully",
    "data": {
        "id": 1,
        "product_id": 1,
        "product_name": "Wireless Bluetooth Headphones",
        "product_sku": "WBH-001",
        "product_image": "https://example.com/headphones-1.jpg",
        "price": 79.99,
        "quantity": 3,
        "subtotal": 239.97,
        "updated_at": "2025-10-23T14:50:00.000000Z"
    }
}
```

### 16. Remove Cart Item

**DELETE** `/cart/1`
_Requires Authentication_

**Response (200):**

```json
{
    "success": true,
    "message": "Item removed from cart successfully"
}
```

### 17. Clear Cart

**DELETE** `/cart`
_Requires Authentication_

**Response (200):**

```json
{
    "success": true,
    "message": "Cart cleared successfully"
}
```

---

## 💳 Payment System

### 18. Create Payment Intent

**POST** `/payments/create-intent`
_Requires Authentication_

**Request Body:**

```json
{
    "amount": 45276,
    "currency": "usd",
    "shipping_address": {
        "name": "John Doe",
        "address": "123 Main St",
        "city": "New York",
        "state": "NY",
        "postal_code": "10001",
        "country": "US"
    }
}
```

**Response (200):**

```json
{
    "success": true,
    "data": {
        "client_secret": "pi_1234567890_secret_abcdef123456",
        "payment_intent_id": "pi_1234567890",
        "amount": 45276,
        "currency": "usd",
        "status": "requires_payment_method"
    }
}
```

### 19. Confirm Payment

**POST** `/payments/confirm`
_Requires Authentication_

**Request Body:**

```json
{
    "payment_intent_id": "pi_1234567890",
    "shipping_address": {
        "name": "John Doe",
        "address": "123 Main St",
        "city": "New York",
        "state": "NY",
        "postal_code": "10001",
        "country": "US"
    }
}
```

**Response (200):**

```json
{
    "success": true,
    "message": "Payment confirmed and order created successfully",
    "data": {
        "order": {
            "id": 15,
            "order_number": "ORD-2025-1023-0015",
            "status": "confirmed",
            "payment_reference": "pi_1234567890",
            "total_amount": 452.76,
            "shipping_address": {
                "name": "John Doe",
                "address": "123 Main St",
                "city": "New York",
                "state": "NY",
                "postal_code": "10001",
                "country": "US"
            },
            "items": [
                {
                    "id": 28,
                    "product_id": 1,
                    "product_name": "Wireless Bluetooth Headphones",
                    "product_sku": "WBH-001",
                    "quantity": 2,
                    "price": 79.99,
                    "total": 159.98
                },
                {
                    "id": 29,
                    "product_id": 3,
                    "product_name": "Smart Watch Series X",
                    "product_sku": "SWX-003",
                    "quantity": 1,
                    "price": 249.99,
                    "total": 249.99
                }
            ],
            "created_at": "2025-10-23T15:00:00.000000Z"
        }
    }
}
```

---

## 📦 Orders Management

### 20. Get User Orders

**GET** `/orders?page=1&limit=10&status=delivered`
_Requires Authentication_

**Response (200):**

```json
{
    "success": true,
    "data": [
        {
            "id": 15,
            "order_number": "ORD-2025-1023-0015",
            "status": "delivered",
            "payment_reference": "pi_1234567890",
            "total_amount": 452.76,
            "shipping_address": {
                "name": "John Doe",
                "address": "123 Main St",
                "city": "New York",
                "state": "NY",
                "postal_code": "10001",
                "country": "US"
            },
            "items_count": 3,
            "created_at": "2025-10-23T15:00:00.000000Z",
            "updated_at": "2025-10-23T16:30:00.000000Z"
        },
        {
            "id": 14,
            "order_number": "ORD-2025-1022-0014",
            "status": "shipped",
            "payment_reference": "pi_0987654321",
            "total_amount": 189.98,
            "shipping_address": {
                "name": "John Doe",
                "address": "123 Main St",
                "city": "New York",
                "state": "NY",
                "postal_code": "10001",
                "country": "US"
            },
            "items_count": 2,
            "created_at": "2025-10-22T10:15:00.000000Z",
            "updated_at": "2025-10-23T09:00:00.000000Z"
        }
    ],
    "pagination": {
        "current_page": 1,
        "per_page": 10,
        "total": 8,
        "last_page": 1
    }
}
```

### 21. Get Single Order

**GET** `/orders/ORD-2025-1023-0015`
_Requires Authentication_

**Response (200):**

```json
{
    "success": true,
    "data": {
        "id": 15,
        "order_number": "ORD-2025-1023-0015",
        "status": "delivered",
        "payment_reference": "pi_1234567890",
        "total_amount": 452.76,
        "subtotal": 409.97,
        "tax_amount": 32.8,
        "shipping_amount": 9.99,
        "shipping_address": {
            "name": "John Doe",
            "address": "123 Main St",
            "city": "New York",
            "state": "NY",
            "postal_code": "10001",
            "country": "US"
        },
        "items": [
            {
                "id": 28,
                "product_id": 1,
                "product_name": "Wireless Bluetooth Headphones",
                "product_sku": "WBH-001",
                "product_image": "https://example.com/headphones-1.jpg",
                "quantity": 2,
                "price": 79.99,
                "total": 159.98
            },
            {
                "id": 29,
                "product_id": 3,
                "product_name": "Smart Watch Series X",
                "product_sku": "SWX-003",
                "product_image": "https://example.com/smartwatch-1.jpg",
                "quantity": 1,
                "price": 249.99,
                "total": 249.99
            }
        ],
        "tracking_number": "1Z999AA1234567890",
        "estimated_delivery": "2025-10-25",
        "status_history": [
            {
                "status": "pending",
                "date": "2025-10-23T15:00:00.000000Z"
            },
            {
                "status": "confirmed",
                "date": "2025-10-23T15:05:00.000000Z"
            },
            {
                "status": "processing",
                "date": "2025-10-23T16:00:00.000000Z"
            },
            {
                "status": "shipped",
                "date": "2025-10-23T18:00:00.000000Z"
            },
            {
                "status": "delivered",
                "date": "2025-10-23T16:30:00.000000Z"
            }
        ],
        "created_at": "2025-10-23T15:00:00.000000Z",
        "updated_at": "2025-10-23T16:30:00.000000Z"
    }
}
```

---

## 👥 Admin - User Management

### 22. Get All Users (Admin)

**GET** `/admin/users?page=1&limit=15&search=john&status=active&role=customer`
_Requires Admin Authentication_

**Response (200):**

```json
{
    "success": true,
    "data": [
        {
            "id": 8,
            "name": "John Doe",
            "email": "john@example.com",
            "role": "customer",
            "is_active": true,
            "email_verified_at": "2025-10-23T14:30:00.000000Z",
            "last_login_at": "2025-10-23T16:15:00.000000Z",
            "orders_count": 3,
            "total_spent": 1247.89,
            "created_at": "2025-10-23T14:30:00.000000Z",
            "updated_at": "2025-10-23T16:15:00.000000Z"
        },
        {
            "id": 9,
            "name": "Jane Smith",
            "email": "jane.smith@example.com",
            "role": "customer",
            "is_active": true,
            "email_verified_at": "2025-10-22T09:45:00.000000Z",
            "last_login_at": "2025-10-23T12:30:00.000000Z",
            "orders_count": 1,
            "total_spent": 299.99,
            "created_at": "2025-10-22T09:45:00.000000Z",
            "updated_at": "2025-10-23T12:30:00.000000Z"
        }
    ],
    "pagination": {
        "current_page": 1,
        "per_page": 15,
        "total": 156,
        "last_page": 11
    }
}
```

### 23. Get User Statistics (Admin)

**GET** `/admin/users/stats`
_Requires Admin Authentication_

**Response (200):**

```json
{
    "success": true,
    "data": {
        "total": 342,
        "active": 321,
        "inactive": 21,
        "admins": 5,
        "customers": 337,
        "newThisMonth": 28,
        "newThisWeek": 12,
        "newToday": 3,
        "usersWithOrders": 156,
        "avgOrdersPerUser": 2.34,
        "topSpenders": [
            {
                "id": 45,
                "name": "Michael Johnson",
                "email": "michael.j@example.com",
                "total_spent": 2899.99,
                "orders_count": 8
            },
            {
                "id": 23,
                "name": "Sarah Wilson",
                "email": "sarah.wilson@example.com",
                "total_spent": 2456.78,
                "orders_count": 12
            },
            {
                "id": 67,
                "name": "David Brown",
                "email": "david.brown@example.com",
                "total_spent": 2234.5,
                "orders_count": 6
            }
        ],
        "recentRegistrations": [
            {
                "id": 340,
                "name": "Alex Turner",
                "email": "alex.turner@example.com",
                "created_at": "2025-10-23T16:45:00.000000Z"
            },
            {
                "id": 339,
                "name": "Emma Davis",
                "email": "emma.davis@example.com",
                "created_at": "2025-10-23T14:20:00.000000Z"
            }
        ]
    }
}
```

### 24. Get Single User (Admin)

**GET** `/admin/users/8`
_Requires Admin Authentication_

**Response (200):**

```json
{
    "success": true,
    "data": {
        "id": 8,
        "name": "John Doe",
        "email": "john@example.com",
        "role": "customer",
        "is_active": true,
        "email_verified_at": "2025-10-23T14:30:00.000000Z",
        "last_login_at": "2025-10-23T16:15:00.000000Z",
        "created_at": "2025-10-23T14:30:00.000000Z",
        "updated_at": "2025-10-23T16:15:00.000000Z",
        "profile": {
            "phone": "+1-555-0123",
            "address": "123 Main St, New York, NY 10001",
            "date_of_birth": "1990-05-15"
        },
        "orders": [
            {
                "id": 15,
                "order_number": "ORD-2025-1023-0015",
                "status": "delivered",
                "total_amount": 452.76,
                "created_at": "2025-10-23T15:00:00.000000Z"
            },
            {
                "id": 12,
                "order_number": "ORD-2025-1020-0012",
                "status": "delivered",
                "total_amount": 795.13,
                "created_at": "2025-10-20T11:30:00.000000Z"
            }
        ],
        "statistics": {
            "orders_count": 3,
            "total_spent": 1247.89,
            "avg_order_value": 415.96,
            "last_order_date": "2025-10-23T15:00:00.000000Z"
        }
    }
}
```

### 25. Update User Status (Admin)

**PUT** `/admin/users/8/status`
_Requires Admin Authentication_

**Request Body:**

```json
{
    "is_active": false,
    "reason": "Account suspended due to policy violation"
}
```

**Response (200):**

```json
{
    "success": true,
    "message": "User status updated successfully",
    "data": {
        "id": 8,
        "name": "John Doe",
        "email": "john@example.com",
        "role": "customer",
        "is_active": false,
        "updated_at": "2025-10-23T17:00:00.000000Z"
    }
}
```

### 26. Update User Role (Admin)

**PUT** `/admin/users/8/role`
_Requires Admin Authentication_

**Request Body:**

```json
{
    "role": "admin"
}
```

**Response (200):**

```json
{
    "success": true,
    "message": "User role updated successfully",
    "data": {
        "id": 8,
        "name": "John Doe",
        "email": "john@example.com",
        "role": "admin",
        "is_active": true,
        "updated_at": "2025-10-23T17:05:00.000000Z"
    }
}
```

### 27. Delete User (Admin)

**DELETE** `/admin/users/8`
_Requires Admin Authentication_

**Response (200):**

```json
{
    "success": true,
    "message": "User deactivated successfully. User data has been anonymized and account marked as deleted."
}
```

---

## 🏷️ Admin - Category Management

### 36. Get All Categories (Admin)

**GET** `/admin/categories?search=electronics&status=active&parent_only=true&page=1&limit=15`
_Requires Admin Authentication_

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
            "products_count": 15,
            "parent": null,
            "children": [
                {
                    "id": 7,
                    "name": "Smartphones",
                    "display_image": "https://via.placeholder.com/200x200/4F46E5/FFFFFF?text=S"
                }
            ],
            "created_at": "2025-10-24T10:00:00.000000Z"
        }
    ],
    "pagination": {
        "current_page": 1,
        "per_page": 15,
        "total": 7,
        "last_page": 1
    }
}
```

### 37. Get Category Statistics (Admin)

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
                "display_image": "https://via.placeholder.com/200x200/4F46E5/FFFFFF?text=E",
                "products_count": 15
            }
        ]
    }
}
```

### 38. Create New Category (Admin)

**POST** `/admin/categories`
_Requires Admin Authentication_

**Content-Type**: `multipart/form-data`

**Request Body:**

```
name: "Smart Home Devices"
description: "Connected devices for your smart home"
parent_id: 1
sort_order: 3
is_active: true
image: [FILE] (optional - max 2MB, jpg/png/gif/svg)
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
        "created_at": "2025-10-24T11:30:00.000000Z"
    }
}
```

### 39. Update Category (Admin)

**PUT** `/admin/categories/1`
_Requires Admin Authentication_

**Request Body:**

```json
{
    "name": "Consumer Electronics",
    "description": "Updated description for consumer electronics",
    "is_active": true
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
        "display_image": "https://via.placeholder.com/200x200/4F46E5/FFFFFF?text=C",
        "updated_at": "2025-10-24T12:00:00.000000Z"
    }
}
```

### 40. Upload Category Image (Admin)

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

### 41. Remove Category Image (Admin)

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

### 42. Delete Category (Admin)

**DELETE** `/admin/categories/1`
_Requires Admin Authentication_

**Response (200):**

```json
{
    "success": true,
    "message": "Category deleted successfully"
}
```

**Error Response (400):**

```json
{
    "success": false,
    "message": "Cannot delete category with existing products"
}
```

---

## 📦 Admin - Orders Management

### 28. Get All Orders (Admin)

**GET** `/admin/orders?page=1&limit=20&status=pending&search=ORD-2025&user_id=8`
_Requires Admin Authentication_

**Response (200):**

```json
{
    "success": true,
    "data": [
        {
            "id": 16,
            "order_number": "ORD-2025-1023-0016",
            "status": "pending",
            "payment_reference": "pi_2345678901",
            "total_amount": 299.99,
            "user": {
                "id": 8,
                "name": "John Doe",
                "email": "john@example.com"
            },
            "items_count": 1,
            "created_at": "2025-10-23T17:15:00.000000Z",
            "updated_at": "2025-10-23T17:15:00.000000Z"
        },
        {
            "id": 17,
            "order_number": "ORD-2025-1023-0017",
            "status": "pending",
            "payment_reference": "pi_3456789012",
            "total_amount": 189.98,
            "user": {
                "id": 12,
                "name": "Alice Johnson",
                "email": "alice@example.com"
            },
            "items_count": 2,
            "created_at": "2025-10-23T17:30:00.000000Z",
            "updated_at": "2025-10-23T17:30:00.000000Z"
        }
    ],
    "pagination": {
        "current_page": 1,
        "per_page": 20,
        "total": 45,
        "last_page": 3
    }
}
```

### 29. Get Order Statistics (Admin)

**GET** `/admin/orders/stats`
_Requires Admin Authentication_

**Response (200):**

```json
{
    "success": true,
    "data": {
        "total": 342,
        "pending": 12,
        "confirmed": 8,
        "processing": 18,
        "shipped": 25,
        "delivered": 274,
        "cancelled": 5,
        "totalValue": 89456.78,
        "todayOrders": 15,
        "todayValue": 3456.89,
        "thisWeekOrders": 78,
        "thisWeekValue": 18945.67,
        "thisMonthOrders": 156,
        "thisMonthValue": 45299.99,
        "avgOrderValue": 261.57,
        "topProducts": [
            {
                "product_id": 1,
                "product_name": "Wireless Bluetooth Headphones",
                "total_sold": 89,
                "total_revenue": 7119.11
            },
            {
                "product_id": 3,
                "product_name": "Smart Watch Series X",
                "total_sold": 45,
                "total_revenue": 11249.55
            }
        ]
    }
}
```

### 30. Get Single Order (Admin)

**GET** `/admin/orders/ORD-2025-1023-0015`
_Requires Admin Authentication_

**Response (200):**

```json
{
    "success": true,
    "data": {
        "id": 15,
        "order_number": "ORD-2025-1023-0015",
        "status": "delivered",
        "payment_reference": "pi_1234567890",
        "total_amount": 452.76,
        "subtotal": 409.97,
        "tax_amount": 32.8,
        "shipping_amount": 9.99,
        "user": {
            "id": 8,
            "name": "John Doe",
            "email": "john@example.com",
            "phone": "+1-555-0123"
        },
        "shipping_address": {
            "name": "John Doe",
            "address": "123 Main St",
            "city": "New York",
            "state": "NY",
            "postal_code": "10001",
            "country": "US"
        },
        "items": [
            {
                "id": 28,
                "product_id": 1,
                "product_name": "Wireless Bluetooth Headphones",
                "product_sku": "WBH-001",
                "product_image": "https://example.com/headphones-1.jpg",
                "quantity": 2,
                "price": 79.99,
                "total": 159.98
            },
            {
                "id": 29,
                "product_id": 3,
                "product_name": "Smart Watch Series X",
                "product_sku": "SWX-003",
                "product_image": "https://example.com/smartwatch-1.jpg",
                "quantity": 1,
                "price": 249.99,
                "total": 249.99
            }
        ],
        "tracking_number": "1Z999AA1234567890",
        "estimated_delivery": "2025-10-25",
        "notes": "Customer requested express delivery",
        "status_history": [
            {
                "status": "pending",
                "date": "2025-10-23T15:00:00.000000Z",
                "note": "Order placed"
            },
            {
                "status": "confirmed",
                "date": "2025-10-23T15:05:00.000000Z",
                "note": "Payment confirmed"
            },
            {
                "status": "processing",
                "date": "2025-10-23T16:00:00.000000Z",
                "note": "Items picked and packed"
            },
            {
                "status": "shipped",
                "date": "2025-10-23T18:00:00.000000Z",
                "note": "Package shipped via UPS"
            },
            {
                "status": "delivered",
                "date": "2025-10-23T16:30:00.000000Z",
                "note": "Package delivered successfully"
            }
        ],
        "created_at": "2025-10-23T15:00:00.000000Z",
        "updated_at": "2025-10-23T16:30:00.000000Z"
    }
}
```

### 31. Update Order Status (Admin)

**PUT** `/admin/orders/ORD-2025-1023-0016/status`
_Requires Admin Authentication_

**Request Body:**

```json
{
    "status": "processing",
    "tracking_number": "1Z999AA1234567891",
    "note": "Order is being processed in the warehouse"
}
```

**Response (200):**

```json
{
    "success": true,
    "message": "Order status updated successfully",
    "data": {
        "id": 16,
        "order_number": "ORD-2025-1023-0016",
        "status": "processing",
        "tracking_number": "1Z999AA1234567891",
        "updated_at": "2025-10-23T18:00:00.000000Z"
    }
}
```

---

## 📊 Admin - Dashboard

### 32. Get Dashboard Statistics

**GET** `/admin/dashboard/stats`
_Requires Admin Authentication_

**Response (200):**

```json
{
    "success": true,
    "data": {
        "totalProducts": 89,
        "totalOrders": 342,
        "totalUsers": 156,
        "totalRevenue": 89456.78,
        "todayOrders": 15,
        "todayRevenue": 3456.89,
        "yesterdayOrders": 12,
        "yesterdayRevenue": 2789.45,
        "thisWeekOrders": 78,
        "thisWeekRevenue": 18945.67,
        "lastWeekOrders": 65,
        "lastWeekRevenue": 15234.89,
        "thisMonthOrders": 156,
        "thisMonthRevenue": 45299.99,
        "lastMonthOrders": 134,
        "lastMonthRevenue": 38967.34,
        "recentOrdersCount": 45,
        "activeUsersCount": 321,
        "newUsersToday": 3,
        "newUsersThisWeek": 12,
        "avgOrderValue": 261.57,
        "conversionRate": 3.2,
        "productStats": {
            "total": 89,
            "inStock": 76,
            "outOfStock": 8,
            "lowStock": 13,
            "featured": 12
        },
        "ordersByStatus": {
            "pending": 12,
            "confirmed": 8,
            "processing": 18,
            "shipped": 25,
            "delivered": 274,
            "cancelled": 5
        },
        "usersByRole": {
            "customers": 337,
            "admins": 5
        },
        "growthRates": {
            "ordersGrowth": 15.3,
            "revenueGrowth": 12.8,
            "usersGrowth": 8.9
        }
    }
}
```

### 33. Get Recent Orders

**GET** `/admin/dashboard/recent-orders?limit=10`
_Requires Admin Authentication_

**Response (200):**

```json
{
    "success": true,
    "data": [
        {
            "id": 17,
            "order_number": "ORD-2025-1023-0017",
            "status": "pending",
            "total_amount": 189.98,
            "user": {
                "id": 12,
                "name": "Alice Johnson",
                "email": "alice@example.com"
            },
            "items_count": 2,
            "created_at": "2025-10-23T17:30:00.000000Z"
        },
        {
            "id": 16,
            "order_number": "ORD-2025-1023-0016",
            "status": "processing",
            "total_amount": 299.99,
            "user": {
                "id": 8,
                "name": "John Doe",
                "email": "john@example.com"
            },
            "items_count": 1,
            "created_at": "2025-10-23T17:15:00.000000Z"
        },
        {
            "id": 15,
            "order_number": "ORD-2025-1023-0015",
            "status": "delivered",
            "total_amount": 452.76,
            "user": {
                "id": 8,
                "name": "John Doe",
                "email": "john@example.com"
            },
            "items_count": 3,
            "created_at": "2025-10-23T15:00:00.000000Z"
        }
    ]
}
```

### 34. Get Product Statistics

**GET** `/admin/dashboard/product-stats`
_Requires Admin Authentication_

**Response (200):**

```json
{
    "success": true,
    "data": {
        "total": 89,
        "inStock": 76,
        "outOfStock": 8,
        "lowStock": 13,
        "featured": 12,
        "topSellingProducts": [
            {
                "product_id": 1,
                "product_name": "Wireless Bluetooth Headphones",
                "product_sku": "WBH-001",
                "total_sold": 89,
                "total_revenue": 7119.11,
                "stock_quantity": 25,
                "image": "https://example.com/headphones-1.jpg"
            },
            {
                "product_id": 3,
                "product_name": "Smart Watch Series X",
                "product_sku": "SWX-003",
                "total_sold": 45,
                "total_revenue": 11249.55,
                "stock_quantity": 8,
                "image": "https://example.com/smartwatch-1.jpg"
            },
            {
                "product_id": 7,
                "product_name": "Wireless Mouse Pro",
                "product_sku": "WMP-007",
                "total_sold": 67,
                "total_revenue": 3349.33,
                "stock_quantity": 34,
                "image": "https://example.com/mouse-1.jpg"
            }
        ],
        "lowStockProducts": [
            {
                "product_id": 3,
                "product_name": "Smart Watch Series X",
                "product_sku": "SWX-003",
                "stock_quantity": 8,
                "reorder_level": 10,
                "image": "https://example.com/smartwatch-1.jpg"
            },
            {
                "product_id": 15,
                "product_name": "Gaming Keyboard RGB",
                "product_sku": "GKR-015",
                "stock_quantity": 5,
                "reorder_level": 15,
                "image": "https://example.com/keyboard-1.jpg"
            }
        ],
        "outOfStockProducts": [
            {
                "product_id": 22,
                "product_name": "Wireless Charger Stand",
                "product_sku": "WCS-022",
                "stock_quantity": 0,
                "last_sale_date": "2025-10-22T14:30:00.000000Z",
                "image": "https://example.com/charger-1.jpg"
            }
        ],
        "categoryDistribution": [
            {
                "category_id": 1,
                "category_name": "Electronics",
                "product_count": 45,
                "percentage": 50.6
            },
            {
                "category_id": 2,
                "category_name": "Fashion",
                "product_count": 28,
                "percentage": 31.5
            },
            {
                "category_id": 3,
                "category_name": "Home & Garden",
                "product_count": 16,
                "percentage": 18.0
            }
        ]
    }
}
```

---

## 🔍 Fallback Endpoints

### 35. Get Users (Fallback)

**GET** `/users`
_Requires Admin Authentication_

**Response (200):**

```json
{
    "success": true,
    "message": "This is a fallback endpoint. Please use /api/admin/users for full functionality.",
    "data": [
        {
            "id": 6,
            "name": "Test Admin",
            "email": "admin@test.com",
            "role": "admin",
            "is_active": true,
            "created_at": "2025-10-23T14:18:26.000000Z"
        },
        {
            "id": 7,
            "name": "Test Customer",
            "email": "customer@test.com",
            "role": "customer",
            "is_active": true,
            "created_at": "2025-10-23T14:18:26.000000Z"
        }
    ]
}
```

---

## ⚠️ Error Responses

### Validation Error (422)

```json
{
    "success": false,
    "message": "The given data was invalid.",
    "errors": {
        "email": ["The email field is required."],
        "password": ["The password must be at least 8 characters."]
    }
}
```

### Authentication Error (401)

```json
{
    "success": false,
    "message": "Unauthenticated.",
    "error": "Token not provided or invalid"
}
```

### Authorization Error (403)

```json
{
    "success": false,
    "message": "This action is unauthorized.",
    "error": "Admin access required"
}
```

### Not Found Error (404)

```json
{
    "success": false,
    "message": "Resource not found.",
    "error": "The requested user could not be found"
}
```

### Server Error (500)

```json
{
    "success": false,
    "message": "Internal server error.",
    "error": "An unexpected error occurred. Please try again later."
}
```

---

## 🔧 Rate Limiting

Most endpoints are rate-limited:

-   **Authentication endpoints**: 5 requests per minute
-   **General API endpoints**: 100 requests per minute
-   **Admin endpoints**: 200 requests per minute

Rate limit headers are included in responses:

```
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 95
X-RateLimit-Reset: 1635123456
```

---

## 📝 Notes

1. **Timestamps**: All timestamps are in UTC ISO 8601 format
2. **Currency**: All monetary values are in cents (USD)
3. **Pagination**: Default page size is 15, maximum is 100
4. **Authentication**: Tokens expire after 24 hours
5. **File Uploads**: Use multipart/form-data for image uploads
6. **Webhooks**: Stripe webhooks are handled at `/webhooks/stripe` (no auth required)

---

**Base URL**: `http://127.0.0.1:8000/api`
**Version**: 1.0
**Last Updated**: October 23, 2025
