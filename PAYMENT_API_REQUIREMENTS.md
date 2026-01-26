# 💳 Payment API - Complete Backend Requirements & Debugging Guide

## Overview

This document provides a comprehensive breakdown of the backend payment API requirements, expected data formats, validation rules, and debugging information.

## 🔍 Quick Debug URLs

-   **Payment Debug Page**: `http://127.0.0.1:8000/payment-debug.html`
-   **Debug Requirements API**: `GET /api/payments/debug/requirements`
-   **Debug Payment Intent**: `POST /api/payments/debug/create-intent`

---

## 🛒 Prerequisites

### 1. User Authentication

All payment endpoints require Bearer token authentication:

```http
Authorization: Bearer your_access_token_here
```

### 2. Cart Requirements

The user **must have items in their cart** before creating a payment intent:

-   Cart cannot be empty
-   All cart items must reference valid products
-   All products must have sufficient stock
-   Cart total must match payment amount

### 3. Stripe Configuration

Backend requires these environment variables:

```env
STRIPE_KEY=pk_test_... (or pk_live_...)
STRIPE_SECRET=sk_test_... (or sk_live_...)
STRIPE_WEBHOOK_SECRET=whsec_...
```

---

## 📡 API Endpoints

### 1. Debug Payment Requirements

**GET** `/api/payments/debug/requirements`

Returns comprehensive information about current user state and payment requirements.

**Response:**

```json
{
    "success": true,
    "debug_info": {
        "user": {
            "id": 1,
            "name": "Admin User",
            "email": "admin@example.com"
        },
        "cart": {
            "items_count": 2,
            "is_empty": false,
            "items": [
                {
                    "product_id": 1,
                    "product_name": "Wireless Bluetooth Headphones",
                    "quantity": 2,
                    "unit_price": 79.99,
                    "line_total": 159.98,
                    "stock_available": 50,
                    "has_sufficient_stock": true
                }
            ],
            "totals": {
                "subtotal": 159.98,
                "tax": 12.8,
                "shipping": 10.0,
                "total": 182.78,
                "stripe_amount_cents": 18278
            }
        },
        "configuration": {
            "stripe_configured": true,
            "stripe_key_present": true,
            "stripe_secret_present": true,
            "webhook_secret_present": true
        }
    }
}
```

### 2. Create Payment Intent

**POST** `/api/payments/create-intent`

Creates a Stripe PaymentIntent for the user's cart.

**Request Body:**

```json
{
    "amount": 182.78,
    "currency": "usd"
}
```

**Validation Rules:**

-   `amount`: required, numeric, minimum 0.50
-   `currency`: optional, string, must be "usd", "eur", or "gbp"

**Backend Verification:**

1. Validates cart is not empty
2. Calculates cart total (subtotal + tax + shipping)
3. Verifies request amount matches cart total (±1 cent tolerance)
4. Checks product stock availability
5. Creates Stripe PaymentIntent

**Success Response (200):**

```json
{
    "success": true,
    "data": {
        "client_secret": "pi_1234567890_secret_abcdef123456",
        "payment_intent_id": "pi_1234567890",
        "amount": 182.78,
        "currency": "usd",
        "status": "requires_payment_method"
    }
}
```

**Error Responses:**

**Empty Cart (400):**

```json
{
    "success": false,
    "message": "Cart is empty"
}
```

**Amount Mismatch (400):**

```json
{
    "success": false,
    "message": "Amount mismatch between cart and request",
    "debug": {
        "calculated_amount": 182.78,
        "request_amount": 180.0,
        "difference": 2.78
    }
}
```

**Insufficient Stock (400):**

```json
{
    "success": false,
    "message": "Insufficient stock for some items",
    "debug": {
        "stock_issues": [
            {
                "product_id": 1,
                "product_name": "Wireless Bluetooth Headphones",
                "requested": 5,
                "available": 3
            }
        ]
    }
}
```

### 3. Confirm Payment

**POST** `/api/payments/confirm`

Confirms payment and creates an order from the cart.

**Request Body:**

```json
{
    "payment_intent_id": "pi_1234567890",
    "shipping_address": {
        "name": "John Doe",
        "line1": "123 Main Street",
        "line2": "Apt 4B",
        "city": "New York",
        "state": "NY",
        "postal_code": "10001",
        "country": "US"
    }
}
```

**Validation Rules:**

-   `payment_intent_id`: required, string
-   `shipping_address`: required, object
-   `shipping_address.name`: required, string
-   `shipping_address.line1`: required, string
-   `shipping_address.line2`: nullable, string
-   `shipping_address.city`: required, string
-   `shipping_address.state`: nullable, string
-   `shipping_address.postal_code`: required, string
-   `shipping_address.country`: required, string, exactly 2 characters

**Backend Process:**

1. Retrieves PaymentIntent from Stripe
2. Verifies payment status is "succeeded"
3. Checks payment belongs to authenticated user
4. Prevents duplicate orders for same payment intent
5. Creates order with calculated totals
6. Creates order items from cart
7. Updates product stock levels
8. Clears user's cart

**Success Response (200):**

```json
{
    "success": true,
    "message": "Order created successfully",
    "data": {
        "order": {
            "id": 15,
            "order_number": "ORD-67890ABC",
            "status": "processing",
            "total_amount": 182.78,
            "payment_status": "paid",
            "created_at": "2025-10-25T12:00:00.000000Z"
        },
        "order_number": "ORD-67890ABC"
    }
}
```

**Error Responses:**

**Payment Not Succeeded (400):**

```json
{
    "success": false,
    "message": "Payment not completed. Status: requires_payment_method"
}
```

**Unauthorized Payment (403):**

```json
{
    "success": false,
    "message": "Unauthorized"
}
```

**Duplicate Order (200):**

```json
{
    "success": true,
    "message": "Order already exists",
    "data": {
        "order": { ... },
        "order_number": "ORD-EXISTING123"
    }
}
```

---

## 🧮 Business Logic

### Cart Total Calculation

```
Subtotal = Sum of (product_price × quantity) for all cart items
Tax = Subtotal × 0.08 (8% tax rate)
Shipping = $10.00 (fixed rate)
Total = Subtotal + Tax + Shipping
```

### Payment Intent Amount

-   Frontend sends total amount in dollars (e.g., 182.78)
-   Backend converts to cents for Stripe (e.g., 18278)
-   Tolerance of ±1 cent for rounding differences

### Order Creation Process

1. **Verification Phase**

    - Check payment intent status
    - Verify user authorization
    - Prevent duplicate orders

2. **Order Creation**

    - Generate unique order number
    - Set status to "processing"
    - Store payment reference

3. **Order Items Creation**

    - Create line items from cart
    - Store product snapshot (name, price, SKU)
    - Calculate line totals

4. **Inventory Management**

    - Decrement product stock
    - Handle stock validation

5. **Cleanup**
    - Clear user's cart
    - Log successful completion

---

## 🔧 Testing & Debugging

### Using the Debug Tools

1. **Payment Debug Page**: Open `http://127.0.0.1:8000/payment-debug.html`

    - Interactive testing interface
    - Step-by-step payment flow
    - Real-time API responses

2. **Debug API Endpoints**:

    ```bash
    # Check current requirements
    curl -X GET "http://127.0.0.1:8000/api/payments/debug/requirements" \
      -H "Authorization: Bearer YOUR_TOKEN"

    # Debug payment intent creation
    curl -X POST "http://127.0.0.1:8000/api/payments/debug/create-intent" \
      -H "Authorization: Bearer YOUR_TOKEN" \
      -H "Content-Type: application/json" \
      -d '{"amount": 182.78, "currency": "usd"}'
    ```

### Common Issues & Solutions

**Issue**: "Cart is empty"

-   **Solution**: Add items to cart before creating payment intent
-   **Debug**: Check `GET /api/cart` response

**Issue**: "Amount mismatch"

-   **Solution**: Ensure frontend calculates same total as backend
-   **Debug**: Use `/api/payments/debug/requirements` to see expected amount

**Issue**: "Insufficient stock"

-   **Solution**: Reduce cart quantities or restock products
-   **Debug**: Check product stock levels in debug response

**Issue**: "Payment not completed"

-   **Solution**: This means Stripe payment didn't succeed (frontend issue)
-   **Debug**: Check Stripe payment flow in frontend

**Issue**: "Unauthorized"

-   **Solution**: Ensure Bearer token is valid and payment belongs to user
-   **Debug**: Check token with `/api/auth/profile`

### Sample Test Flow

1. **Login and get token**
2. **Add items to cart**: `POST /api/cart`
3. **Check requirements**: `GET /api/payments/debug/requirements`
4. **Create payment intent**: `POST /api/payments/create-intent`
5. **[Frontend] Process payment with Stripe**
6. **Confirm payment**: `POST /api/payments/confirm`

---

## 🌐 Frontend Integration

### Expected Frontend Flow

```javascript
// 1. Get cart total and create payment intent
const cartTotal = await getCartTotal();
const paymentIntent = await createPaymentIntent(cartTotal);

// 2. Process payment with Stripe Elements
const result = await stripe.confirmCardPayment(paymentIntent.client_secret, {
    payment_method: {
        card: cardElement,
        billing_details: { name: "Customer Name" },
    },
});

// 3. If payment succeeds, confirm with backend
if (result.paymentIntent.status === "succeeded") {
    const order = await confirmPayment(
        result.paymentIntent.id,
        shippingAddress
    );
}
```

### Required Frontend Libraries

-   Stripe.js or Stripe Elements
-   Proper error handling for payment failures
-   Loading states during payment processing

---

## 📝 Environment Setup

### Required .env Variables

```env
# Stripe Configuration
STRIPE_KEY=pk_test_your_publishable_key_here
STRIPE_SECRET=sk_test_your_secret_key_here
STRIPE_WEBHOOK_SECRET=whsec_your_webhook_secret_here

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### Laravel Configuration

Ensure these files are properly configured:

-   `config/services.php` - Stripe keys
-   `config/database.php` - Database connection
-   `config/cors.php` - CORS settings for your domain

---

## 🚀 Production Considerations

### Security

-   Use live Stripe keys in production
-   Implement rate limiting on payment endpoints
-   Log all payment attempts for audit purposes
-   Validate webhook signatures properly

### Error Handling

-   Graceful handling of Stripe API failures
-   Proper transaction rollbacks on errors
-   User-friendly error messages
-   Comprehensive logging for debugging

### Performance

-   Database indexes on order queries
-   Efficient cart validation queries
-   Proper caching where appropriate

This documentation should give you complete visibility into what the backend expects and how to properly integrate with the payment API!
