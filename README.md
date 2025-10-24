# E-Commerce Backend API

A Laravel-based REST API for the e-commerce application with complete functionality for managing products, orders, users, and more.

## Features

### Authentication & Authorization

-   User registration and login
-   JWT-based authentication with Laravel Sanctum
-   Role-based access control (Admin/User)
-   Password reset functionality

### Product Management

-   Product CRUD operations
-   Category management
-   Product variants and attributes
-   Image management
-   Inventory tracking
-   Search and filtering capabilities

### Order Management

-   Shopping cart functionality
-   Order creation and processing
-   Order status tracking
-   Order history

### User Features

-   User profile management
-   Address management
-   Wishlist functionality
-   Product reviews and ratings

### Payment Integration

-   PhonePe payment gateway
-   Razorpay payment gateway
-   Multiple payment methods support
-   Secure transaction handling

### Admin Features

-   Complete admin dashboard
-   User management
-   Product management
-   Order management
-   Analytics and reporting

## API Endpoints

### Authentication

```
POST   /api/auth/register      - User registration
POST   /api/auth/login         - User login
POST   /api/auth/logout        - User logout
GET    /api/auth/profile       - Get user profile
PUT    /api/auth/profile       - Update user profile
POST   /api/auth/forgot-password  - Password reset request
POST   /api/auth/reset-password   - Reset password
```

### Products

```
GET    /api/products           - Get all products (with filters)
GET    /api/products/{id}      - Get product details
GET    /api/products/featured  - Get featured products
GET    /api/products/search    - Search products
POST   /api/products           - Create product (Admin)
PUT    /api/products/{id}      - Update product (Admin)
DELETE /api/products/{id}      - Delete product (Admin)
```

### Categories

```
GET    /api/categories         - Get all categories
GET    /api/categories/{id}    - Get category details
GET    /api/categories/{id}/products - Get products by category
POST   /api/categories         - Create category (Admin)
PUT    /api/categories/{id}    - Update category (Admin)
DELETE /api/categories/{id}    - Delete category (Admin)
```

### Shopping Cart

```
GET    /api/cart               - Get user's cart
POST   /api/cart/add           - Add item to cart
PUT    /api/cart/items/{id}    - Update cart item
DELETE /api/cart/items/{id}    - Remove item from cart
DELETE /api/cart               - Clear cart
POST   /api/cart/coupon        - Apply coupon
DELETE /api/cart/coupon        - Remove coupon
```

### Orders

```
GET    /api/orders             - Get user's orders
GET    /api/orders/{id}        - Get order details
POST   /api/orders             - Create new order
PUT    /api/orders/{id}/cancel - Cancel order
GET    /api/orders/{id}/track  - Track order
```

### Wishlist

```
GET    /api/wishlist           - Get user's wishlist
POST   /api/wishlist/add       - Add item to wishlist
DELETE /api/wishlist/remove/{id} - Remove item from wishlist
DELETE /api/wishlist            - Clear wishlist
```

### Reviews

```
GET    /api/products/{id}/reviews - Get product reviews
POST   /api/products/{id}/reviews - Add review
PUT    /api/reviews/{id}          - Update review
DELETE /api/reviews/{id}          - Delete review
POST   /api/reviews/{id}/helpful  - Mark review as helpful
```

### Admin Routes

```
GET    /api/admin/dashboard     - Admin dashboard stats
GET    /api/admin/users         - Manage users
GET    /api/admin/orders        - Manage orders
GET    /api/admin/products      - Manage products
GET    /api/admin/analytics     - Get analytics data
```

## Installation

1. Clone the repository
2. Install dependencies: `composer install`
3. Copy `.env.example` to `.env` and configure database
4. Generate application key: `php artisan key:generate`
5. Run migrations: `php artisan migrate`
6. Seed the database: `php artisan db:seed`
7. Start the server: `php artisan serve`

## License

This project is licensed under the MIT License.

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

-   [Simple, fast routing engine](https://laravel.com/docs/routing).
-   [Powerful dependency injection container](https://laravel.com/docs/container).
-   Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
-   Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
-   Database agnostic [schema migrations](https://laravel.com/docs/migrations).
-   [Robust background job processing](https://laravel.com/docs/queues).
-   [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

-   **[Vehikl](https://vehikl.com)**
-   **[Tighten Co.](https://tighten.co)**
-   **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
-   **[64 Robots](https://64robots.com)**
-   **[Curotec](https://www.curotec.com/services/technologies/laravel)**
-   **[DevSquad](https://devsquad.com/hire-laravel-developers)**
-   **[Redberry](https://redberry.international/laravel-development)**
-   **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
