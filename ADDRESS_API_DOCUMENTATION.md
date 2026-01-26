# User Address API Documentation

## Overview
Complete API endpoints for managing user addresses including shipping, billing, and combined address types.

## Base URL
All endpoints require authentication and use the base URL: `/api/user/addresses`

Alternative base URL: `/api/addresses` (for compatibility)

## Authentication
All endpoints require Bearer token authentication:
```
Authorization: Bearer your_access_token_here
```

---

## 1. Get All User Addresses
**GET** `/api/user/addresses`

### Description
Retrieve all addresses for the authenticated user, ordered by default status and creation date.

### Response (200)
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "type": "shipping",
            "first_name": "John",
            "last_name": "Doe",
            "full_name": "John Doe",
            "company": "Test Company Inc.",
            "address_line_1": "123 Main Street",
            "address_line_2": "Apt 4B",
            "city": "New York",
            "state": "NY",
            "postal_code": "10001",
            "country": "United States",
            "phone": "+1-555-0123",
            "is_default": true,
            "formatted_address": "123 Main Street, Apt 4B, New York, NY, 10001, United States",
            "created_at": "2025-10-25T12:00:00.000000Z",
            "updated_at": "2025-10-25T12:00:00.000000Z"
        }
    ],
    "message": "Addresses retrieved successfully"
}
```

---

## 2. Create New Address
**POST** `/api/user/addresses`

### Request Body
```json
{
    "type": "shipping",
    "first_name": "John",
    "last_name": "Doe",
    "company": "Test Company Inc.",
    "address_line_1": "123 Main Street",
    "address_line_2": "Apt 4B",
    "city": "New York",
    "state": "NY",
    "postal_code": "10001",
    "country": "United States",
    "phone": "+1-555-0123",
    "is_default": true
}
```

### Field Requirements
- `type`: **required** - enum: "shipping", "billing", "both"
- `first_name`: **required** - string, max 255 chars
- `last_name`: **required** - string, max 255 chars
- `company`: optional - string, max 255 chars
- `address_line_1`: **required** - string, max 255 chars
- `address_line_2`: optional - string, max 255 chars
- `city`: **required** - string, max 100 chars
- `state`: **required** - string, max 100 chars
- `postal_code`: **required** - string, max 20 chars
- `country`: **required** - string, max 100 chars
- `phone`: optional - string, max 20 chars
- `is_default`: optional - boolean

### Response (201)
```json
{
    "success": true,
    "data": {
        "id": 1,
        "type": "shipping",
        "first_name": "John",
        "last_name": "Doe",
        "full_name": "John Doe",
        "company": "Test Company Inc.",
        "address_line_1": "123 Main Street",
        "address_line_2": "Apt 4B",
        "city": "New York",
        "state": "NY",
        "postal_code": "10001",
        "country": "United States",
        "phone": "+1-555-0123",
        "is_default": true,
        "formatted_address": "123 Main Street, Apt 4B, New York, NY, 10001, United States",
        "created_at": "2025-10-25T12:00:00.000000Z",
        "updated_at": "2025-10-25T12:00:00.000000Z"
    },
    "message": "Address created successfully"
}
```

### Error Response (422)
```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "first_name": ["The first name field is required."],
        "city": ["The city field is required."]
    }
}
```

---

## 3. Get Specific Address
**GET** `/api/user/addresses/{id}`

### Description
Retrieve a specific address by ID for the authenticated user.

### Response (200)
```json
{
    "success": true,
    "data": {
        "id": 1,
        "type": "shipping",
        "first_name": "John",
        "last_name": "Doe",
        "full_name": "John Doe",
        "company": "Test Company Inc.",
        "address_line_1": "123 Main Street",
        "address_line_2": "Apt 4B",
        "city": "New York",
        "state": "NY",
        "postal_code": "10001",
        "country": "United States",
        "phone": "+1-555-0123",
        "is_default": true,
        "formatted_address": "123 Main Street, Apt 4B, New York, NY, 10001, United States",
        "created_at": "2025-10-25T12:00:00.000000Z",
        "updated_at": "2025-10-25T12:00:00.000000Z"
    },
    "message": "Address retrieved successfully"
}
```

### Error Response (404)
```json
{
    "success": false,
    "message": "Address not found",
    "error": "No query results for model [App\\Models\\Address] 1"
}
```

---

## 4. Update Address
**PUT** `/api/user/addresses/{id}`

### Request Body
All fields are optional for updates. Include only the fields you want to change:
```json
{
    "first_name": "Jane",
    "city": "Boston",
    "is_default": true
}
```

### Response (200)
```json
{
    "success": true,
    "data": {
        "id": 1,
        "type": "shipping",
        "first_name": "Jane",
        "last_name": "Doe",
        "full_name": "Jane Doe",
        "company": "Test Company Inc.",
        "address_line_1": "123 Main Street",
        "address_line_2": "Apt 4B",
        "city": "Boston",
        "state": "NY",
        "postal_code": "10001",
        "country": "United States",
        "phone": "+1-555-0123",
        "is_default": true,
        "formatted_address": "123 Main Street, Apt 4B, Boston, NY, 10001, United States",
        "created_at": "2025-10-25T12:00:00.000000Z",
        "updated_at": "2025-10-25T12:05:00.000000Z"
    },
    "message": "Address updated successfully"
}
```

---

## 5. Delete Address
**DELETE** `/api/user/addresses/{id}`

### Description
Delete a specific address for the authenticated user.

### Response (200)
```json
{
    "success": true,
    "message": "Address deleted successfully"
}
```

### Error Response (404)
```json
{
    "success": false,
    "message": "Address not found or failed to delete",
    "error": "No query results for model [App\\Models\\Address] 1"
}
```

---

## 6. Set Address as Default
**PUT** `/api/user/addresses/{id}/default`

### Description
Set a specific address as the default for its type. This will automatically unset other default addresses of the same type.

### Response (200)
```json
{
    "success": true,
    "message": "Address set as default successfully"
}
```

---

## 7. Get Addresses by Type
**GET** `/api/user/addresses/type/{type}`

### Description
Retrieve addresses filtered by type (shipping, billing, or both).

### Parameters
- `type`: Path parameter - "shipping", "billing", or "both"

### Response (200)
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "type": "shipping",
            "first_name": "John",
            "last_name": "Doe",
            "full_name": "John Doe",
            "company": "Test Company Inc.",
            "address_line_1": "123 Main Street",
            "address_line_2": "Apt 4B",
            "city": "New York",
            "state": "NY",
            "postal_code": "10001",
            "country": "United States",
            "phone": "+1-555-0123",
            "is_default": true,
            "formatted_address": "123 Main Street, Apt 4B, New York, NY, 10001, United States",
            "created_at": "2025-10-25T12:00:00.000000Z",
            "updated_at": "2025-10-25T12:00:00.000000Z"
        }
    ],
    "message": "Addresses for type 'shipping' retrieved successfully"
}
```

---

## API Testing Examples

### Frontend JavaScript Examples

#### 1. Get All Addresses
```javascript
const getAddresses = async () => {
    try {
        const response = await fetch('/api/user/addresses', {
            method: 'GET',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`,
                'Accept': 'application/json',
            }
        });
        
        const data = await response.json();
        if (data.success) {
            return data.data;
        }
        throw new Error(data.message);
    } catch (error) {
        console.error('Error fetching addresses:', error);
        throw error;
    }
};
```

#### 2. Create New Address
```javascript
const createAddress = async (addressData) => {
    try {
        const response = await fetch('/api/user/addresses', {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify(addressData)
        });
        
        const data = await response.json();
        if (data.success) {
            return data.data;
        }
        throw new Error(data.message);
    } catch (error) {
        console.error('Error creating address:', error);
        throw error;
    }
};
```

#### 3. Update Address
```javascript
const updateAddress = async (addressId, updateData) => {
    try {
        const response = await fetch(`/api/user/addresses/${addressId}`, {
            method: 'PUT',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify(updateData)
        });
        
        const data = await response.json();
        if (data.success) {
            return data.data;
        }
        throw new Error(data.message);
    } catch (error) {
        console.error('Error updating address:', error);
        throw error;
    }
};
```

### cURL Examples

#### 1. Get All Addresses
```bash
curl -X GET "http://127.0.0.1:8000/api/user/addresses" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

#### 2. Create New Address
```bash
curl -X POST "http://127.0.0.1:8000/api/user/addresses" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "type": "shipping",
    "first_name": "John",
    "last_name": "Doe",
    "address_line_1": "123 Main Street",
    "city": "New York",
    "state": "NY",
    "postal_code": "10001",
    "country": "United States",
    "is_default": true
  }'
```

#### 3. Update Address
```bash
curl -X PUT "http://127.0.0.1:8000/api/user/addresses/1" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "city": "Boston",
    "state": "MA"
  }'
```

#### 4. Delete Address
```bash
curl -X DELETE "http://127.0.0.1:8000/api/user/addresses/1" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

---

## Database Schema

The addresses table has the following structure:

```sql
CREATE TABLE addresses (
    id bigint unsigned NOT NULL AUTO_INCREMENT,
    user_id bigint unsigned NOT NULL,
    type enum('shipping','billing','both') DEFAULT 'both',
    first_name varchar(255) NOT NULL,
    last_name varchar(255) NOT NULL,
    company varchar(255) DEFAULT NULL,
    address_line_1 varchar(255) NOT NULL,
    address_line_2 varchar(255) DEFAULT NULL,
    city varchar(255) NOT NULL,
    state varchar(255) NOT NULL,
    postal_code varchar(20) NOT NULL,
    country varchar(255) NOT NULL,
    phone varchar(20) DEFAULT NULL,
    is_default tinyint(1) NOT NULL DEFAULT '0',
    created_at timestamp NULL DEFAULT NULL,
    updated_at timestamp NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY addresses_user_id_foreign (user_id),
    KEY addresses_user_id_type_index (user_id,type),
    KEY addresses_user_id_is_default_index (user_id,is_default),
    CONSTRAINT addresses_user_id_foreign FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
);
```

## Model Relationships

The Address model has the following relationships:
- `belongsTo(User::class)` - Each address belongs to a user
- User model has `hasMany(Address::class)` - Each user can have multiple addresses

## Error Handling

All endpoints return standardized error responses:
- `422` - Validation errors with detailed field errors
- `404` - Resource not found
- `401` - Unauthorized (invalid or missing token)
- `500` - Server errors

## Notes

1. **Default Address Logic**: Setting an address as default automatically unsets other default addresses of the same type for the user.

2. **Address Types**: 
   - `shipping` - For delivery addresses
   - `billing` - For payment addresses  
   - `both` - Can be used for both shipping and billing

3. **Security**: All endpoints are protected by authentication middleware and users can only access their own addresses.

4. **Data Validation**: Comprehensive validation ensures data integrity and proper formatting.

5. **Formatted Address**: The API automatically provides a `formatted_address` field that combines all address components into a readable string.