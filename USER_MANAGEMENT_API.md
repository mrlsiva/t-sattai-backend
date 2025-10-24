# User Management API Documentation

## Overview

Complete user management system for admin users to manage the application's user base.

## Authentication

All endpoints require admin authentication:

-   Header: `Authorization: Bearer {token}`
-   Middleware: `auth:sanctum` + `admin`

## Endpoints

### 1. Get Users List

```
GET /api/admin/users
```

**Query Parameters:**

-   `page` (integer, optional): Page number (default: 1)
-   `limit` (integer, optional): Items per page (default: 15, max: 100)
-   `search` (string, optional): Search by name or email
-   `status` (string, optional): Filter by status (`active` or `inactive`)
-   `role` (string, optional): Filter by role (`admin` or `user`)

**Response:**

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "phone": "+1234567890",
            "role": "user",
            "status": "active",
            "ordersCount": 5,
            "totalSpent": 299.99,
            "lastLogin": "2025-10-23T10:30:00.000Z",
            "createdAt": "2025-01-15T09:00:00.000Z",
            "updatedAt": "2025-10-23T10:30:00.000Z",
            "dateOfBirth": "1990-05-15",
            "gender": "male",
            "emailVerifiedAt": "2025-01-15T09:05:00.000Z"
        }
    ],
    "pagination": {
        "current_page": 1,
        "per_page": 15,
        "total": 150,
        "last_page": 10
    },
    "message": "Users retrieved successfully"
}
```

### 2. Get User Statistics

```
GET /api/admin/users/stats
```

**Response:**

```json
{
    "success": true,
    "data": {
        "total": 150,
        "active": 145,
        "inactive": 5,
        "admins": 3,
        "regular": 147,
        "newUsers": 12,
        "usersWithOrders": 89,
        "avgOrdersPerUser": 2.34,
        "topSpenders": [
            {
                "id": 15,
                "name": "Jane Smith",
                "email": "jane@example.com",
                "totalSpent": 1299.99,
                "ordersCount": 8
            }
        ]
    },
    "message": "User statistics retrieved successfully"
}
```

### 3. Get Specific User

```
GET /api/admin/users/{id}
```

**Response:**

```json
{
    "success": true,
    "data": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "phone": "+1234567890",
        "role": "user",
        "status": "active",
        "ordersCount": 5,
        "totalSpent": 299.99,
        "lastLogin": "2025-10-23T10:30:00.000Z",
        "createdAt": "2025-01-15T09:00:00.000Z",
        "updatedAt": "2025-10-23T10:30:00.000Z",
        "dateOfBirth": "1990-05-15",
        "gender": "male",
        "emailVerifiedAt": "2025-01-15T09:05:00.000Z",
        "recentOrders": [
            {
                "id": "ORD-ABC123",
                "total": 99.99,
                "status": "delivered",
                "createdAt": "2025-10-20T14:30:00.000Z"
            }
        ]
    },
    "message": "User retrieved successfully"
}
```

### 4. Update User Status

```
PUT /api/admin/users/{id}/status
```

**Body:**

```json
{
    "status": "active" // or "inactive"
}
```

**Response:**

```json
{
    "success": true,
    "data": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "status": "inactive",
        "role": "user"
    },
    "message": "User status updated successfully"
}
```

**Business Rules:**

-   Admins cannot deactivate themselves
-   Deactivating a user revokes all their authentication tokens
-   Action is logged for audit purposes

### 5. Update User Role

```
PUT /api/admin/users/{id}/role
```

**Body:**

```json
{
    "role": "admin" // or "user"
}
```

**Response:**

```json
{
    "success": true,
    "data": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "status": "active",
        "role": "admin"
    },
    "message": "User role updated successfully"
}
```

**Business Rules:**

-   Admins cannot remove their own admin privileges
-   Action is logged for audit purposes

### 6. Delete User

```
DELETE /api/admin/users/{id}
```

**Response:**

```json
{
    "success": true,
    "message": "User deleted successfully"
}
```

**Business Rules:**

-   Admins cannot delete themselves
-   Users with existing orders are deactivated instead of deleted (preserves order history)
-   Users without orders are permanently deleted
-   All user tokens are revoked before deletion/deactivation
-   Action is logged for audit purposes

## Error Responses

### 422 Validation Error

```json
{
    "success": false,
    "message": "Validation error",
    "errors": {
        "status": ["The status field must be either active or inactive."]
    }
}
```

### 403 Forbidden

```json
{
    "success": false,
    "message": "You cannot delete your own account"
}
```

### 404 Not Found

```json
{
    "success": false,
    "message": "User not found"
}
```

## Security Features

1. **Admin Authorization**: All endpoints require admin privileges
2. **Self-Protection**: Admins cannot delete/deactivate themselves
3. **Token Revocation**: Deactivating users revokes their access tokens
4. **Audit Logging**: All admin actions are logged
5. **Data Preservation**: Users with orders are preserved for historical data
6. **Input Validation**: All inputs are validated and sanitized

## Usage Examples

### Search for users by email domain

```
GET /api/admin/users?search=@gmail.com&limit=20
```

### Get all inactive users

```
GET /api/admin/users?status=inactive
```

### Get all admin users

```
GET /api/admin/users?role=admin
```

### Combined filters

```
GET /api/admin/users?status=active&role=user&search=john&page=2&limit=10
```

This API provides comprehensive user management capabilities for your admin dashboard!
