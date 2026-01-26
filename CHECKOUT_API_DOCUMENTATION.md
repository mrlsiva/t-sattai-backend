# 🧮 Checkout Calculation API Documentation

## Overview

Dynamic tax and shipping calculation API that replaces hardcoded frontend calculations with server-side logic based on location, cart contents, and shipping methods.

## Base URLs

-   **Test Page**: `http://127.0.0.1:8000/checkout-test.html`
-   **API Base**: `/api/checkout`

## Authentication

All endpoints require Bearer token authentication:

```http
Authorization: Bearer your_access_token_here
```

---

## 📡 API Endpoints

### 1. Calculate Checkout Totals

**POST** `/api/checkout/calculate-totals`

The main endpoint that replaces hardcoded frontend tax and shipping calculations.

#### Request Body

```json
{
    "shipping_address": {
        "country": "US",
        "state": "NY",
        "city": "New York",
        "postal_code": "10001"
    },
    "shipping_method": "standard",
    "cart_items": [
        {
            "product_id": 1,
            "quantity": 2
        }
    ]
}
```

#### Field Details

-   `shipping_address`: **optional** - If not provided, uses user's default shipping address
-   `shipping_method`: **optional** - `standard`, `express`, or `overnight` (defaults to `standard`)
-   `cart_items`: **optional** - If not provided, uses user's current cart

#### Response (200)

```json
{
    "success": true,
    "data": {
        "subtotal": 159.98,
        "tax": {
            "amount": 12.8,
            "rate": 0.08,
            "description": "Sales tax for NY"
        },
        "shipping": {
            "cost": 9.0,
            "method": "standard",
            "description": "Standard Shipping",
            "estimated_days": "3-7"
        },
        "total": 181.78,
        "currency": "USD",
        "breakdown": {
            "cart_items": [
                {
                    "product_id": 1,
                    "name": "Wireless Bluetooth Headphones",
                    "price": 79.99,
                    "quantity": 2,
                    "weight": 8,
                    "line_total": 159.98
                }
            ],
            "items_count": 1,
            "total_weight": 16,
            "shipping_address": {
                "country": "US",
                "state": "NY",
                "city": "New York",
                "postal_code": "10001"
            }
        }
    },
    "message": "Checkout totals calculated successfully"
}
```

### 2. Get Available Shipping Methods

**GET/POST** `/api/checkout/shipping-methods`

Returns available shipping options based on cart weight and destination.

#### Request Body (for POST)

```json
{
    "shipping_address": {
        "country": "US",
        "state": "NY",
        "postal_code": "10001"
    }
}
```

#### Response (200)

```json
{
    "success": true,
    "data": [
        {
            "id": "standard",
            "name": "Standard Shipping",
            "cost": 9.0,
            "estimated_days": "3-7",
            "description": "Standard ground shipping"
        },
        {
            "id": "express",
            "name": "Express Shipping",
            "cost": 23.0,
            "estimated_days": "1-3",
            "description": "Express delivery"
        },
        {
            "id": "overnight",
            "name": "Overnight Shipping",
            "cost": 42.0,
            "estimated_days": "1",
            "description": "Next business day delivery"
        }
    ],
    "message": "Shipping methods retrieved successfully"
}
```

### 3. Get Tax Rates for Location

**GET/POST** `/api/checkout/tax-rates`

Returns tax rate information for a specific location.

#### Request Body (for POST)

```json
{
    "country": "US",
    "state": "NY",
    "city": "New York",
    "postal_code": "10001"
}
```

#### Response (200)

```json
{
    "success": true,
    "data": {
        "rate": 0.08,
        "description": "Sales tax for NY",
        "location": {
            "country": "US",
            "state": "NY",
            "city": "New York",
            "postal_code": "10001"
        }
    },
    "message": "Tax rates retrieved successfully"
}
```

---

## 🧮 Calculation Logic

### Tax Calculation

Taxes are calculated based on the shipping address:

#### US States Tax Rates

-   **No Sales Tax**: Alaska, Delaware, Montana, New Hampshire, Oregon
-   **Low Tax States**: Colorado (2.9%), Alabama (4%), Wyoming (4%)
-   **High Tax States**: California (7.25%), Tennessee (7%), Minnesota (6.875%)
-   **Default**: 8% for unknown US states

#### International Tax Rates

-   **Canada**: 5% GST (simplified)
-   **United Kingdom**: 20% VAT
-   **India**: 18% GST (standard rate)
-   **Other Countries**: 0% (no tax)

### Shipping Calculation

Shipping cost is calculated based on:

1. **Base Cost**: $5.00
2. **Weight Surcharge**: $2.00 per pound over 1 lb
3. **Method Multiplier**:
    - Standard: 1x
    - Express: 2x + $5.00
    - Overnight: 3x + $15.00 (US only)
4. **International Surcharge**:
    - Standard: +$15.00
    - Express: +$25.00

#### Example Calculation

```
Cart Weight: 16oz (1 lb)
Base Cost: $5.00
Weight Surcharge: $0.00 (no overage)
Method: Express (2x + $5.00)
International: No

Total: ($5.00 + $0.00) × 2 + $5.00 = $15.00
```

---

## 🔄 Frontend Integration

### Replacing Hardcoded Calculations

#### ❌ Old Frontend Code (Hardcoded)

```javascript
// Remove this hardcoded logic:
const tax = total * 0.08; // 8% tax - hardcoded
const shipping = 10.0; // Fixed shipping - hardcoded
const finalTotal = total + tax + shipping;
```

#### ✅ New Frontend Code (Dynamic)

```javascript
// Replace with dynamic API call:
const calculateCheckoutTotals = async (
    shippingAddress,
    shippingMethod = "standard"
) => {
    try {
        const response = await fetch("/api/checkout/calculate-totals", {
            method: "POST",
            headers: {
                Authorization: `Bearer ${token}`,
                "Content-Type": "application/json",
            },
            body: JSON.stringify({
                shipping_address: shippingAddress,
                shipping_method: shippingMethod,
            }),
        });

        const data = await response.json();

        if (data.success) {
            return {
                subtotal: data.data.subtotal,
                tax: data.data.tax.amount,
                shipping: data.data.shipping.cost,
                total: data.data.total,
                taxDescription: data.data.tax.description,
                shippingDescription: data.data.shipping.description,
            };
        }

        throw new Error(data.message);
    } catch (error) {
        console.error("Error calculating totals:", error);
        throw error;
    }
};

// Usage in checkout flow:
const totals = await calculateCheckoutTotals(
    {
        country: "US",
        state: "NY",
        city: "New York",
        postal_code: "10001",
    },
    "standard"
);

console.log(`Subtotal: $${totals.subtotal}`);
console.log(`Tax: $${totals.tax} (${totals.taxDescription})`);
console.log(`Shipping: $${totals.shipping} (${totals.shippingDescription})`);
console.log(`Total: $${totals.total}`);
```

### Real-time Updates

```javascript
// Update totals when address or shipping method changes
const updateCheckoutTotals = async () => {
    const shippingAddress = getShippingAddressFromForm();
    const shippingMethod = getSelectedShippingMethod();

    try {
        const totals = await calculateCheckoutTotals(
            shippingAddress,
            shippingMethod
        );
        updateTotalsDisplay(totals);
    } catch (error) {
        showError("Error calculating totals: " + error.message);
    }
};

// Bind to form changes
document
    .getElementById("shipping-address-form")
    .addEventListener("change", updateCheckoutTotals);
document
    .getElementById("shipping-method-select")
    .addEventListener("change", updateCheckoutTotals);
```

---

## 🧪 Testing the API

### Using the Test Page

1. **Open**: `http://127.0.0.1:8000/checkout-test.html`
2. **Login** with your credentials
3. **Add items to cart**
4. **Configure shipping address**
5. **Test calculations** with different locations and methods

### Manual API Testing

#### Calculate Totals

```bash
curl -X POST "http://127.0.0.1:8000/api/checkout/calculate-totals" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "shipping_address": {
      "country": "US",
      "state": "CA",
      "city": "Los Angeles",
      "postal_code": "90210"
    },
    "shipping_method": "express"
  }'
```

#### Get Shipping Methods

```bash
curl -X POST "http://127.0.0.1:8000/api/checkout/shipping-methods" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "shipping_address": {
      "country": "CA",
      "postal_code": "M5V 3A8"
    }
  }'
```

#### Get Tax Rates

```bash
curl -X POST "http://127.0.0.1:8000/api/checkout/tax-rates" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "country": "US",
    "state": "TX",
    "city": "Austin",
    "postal_code": "73301"
  }'
```

---

## 🚀 Benefits of Dynamic Calculation

### 1. **Accuracy**

-   Real-time tax rates based on actual location
-   Shipping costs based on actual weight and distance
-   No hardcoded values that become outdated

### 2. **Flexibility**

-   Easy to update tax rates without frontend changes
-   Support for international shipping and taxes
-   Multiple shipping methods with dynamic pricing

### 3. **Compliance**

-   Proper tax calculation for different jurisdictions
-   Audit trail of calculation logic
-   Centralized business rules

### 4. **User Experience**

-   Accurate estimates before payment
-   No surprises at checkout
-   Real-time updates as user changes options

---

## 💡 Configuration & Customization

### Tax Rate Configuration

Tax rates are currently hardcoded in the controller but can be moved to:

-   Database table with tax rates by location
-   External tax service integration (Avalara, TaxJar)
-   Configuration files for easy updates

### Shipping Rate Configuration

Shipping calculations can be enhanced with:

-   Integration with shipping APIs (FedEx, UPS, USPS)
-   Database-driven shipping zones and rates
-   Support for free shipping thresholds
-   Product-specific shipping rules

### Future Enhancements

-   Coupon/discount integration
-   Bulk shipping discounts
-   Location-based product availability
-   Real-time shipping API integration
-   Tax exemption handling

This API provides a solid foundation for accurate, dynamic checkout calculations that can grow with your business needs!
