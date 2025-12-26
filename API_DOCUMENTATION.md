# Threads Clone API Documentation

Base URL: `http://localhost:8000/api`

## Authentication

All authenticated endpoints require a Bearer token in the Authorization header:
```
Authorization: Bearer YOUR_TOKEN_HERE
```

### Rate Limiting
Authentication endpoints (register, login) are rate limited to 10 requests per minute.

---

## Endpoints

### 1. Register User

**Endpoint:** `POST /auth/register`

**Authentication:** Not required

**Request Body:**
```json
{
  "username": "string (required, unique, alphanumeric + underscore)",
  "email": "string (required, unique, valid email)",
  "password": "string (required, min: 8 characters)",
  "password_confirmation": "string (required, must match password)",
  "full_name": "string (required)",
  "bio": "string (optional, max: 500 characters)"
}
```

**Success Response (201):**
```json
{
  "message": "User registered successfully",
  "user": {
    "id": 1,
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "username": "johndoe",
    "email": "john@example.com",
    "full_name": "John Doe",
    "bio": "Software developer",
    "profile_image_url": null,
    "header_image_url": null,
    "followers_count": 0,
    "following_count": 0,
    "posts_count": 0,
    "is_verified": false,
    "is_private": false
  },
  "token": "1|abcdefghijklmnopqrstuvwxyz"
}
```

**Error Response (422):**
```json
{
  "message": "Validation failed",
  "errors": {
    "username": ["The username has already been taken."],
    "email": ["The email field must be a valid email address."]
  }
}
```

---

### 2. Login User

**Endpoint:** `POST /auth/login`

**Authentication:** Not required

**Request Body:**
```json
{
  "login": "string (required, username or email)",
  "password": "string (required)"
}
```

**Success Response (200):**
```json
{
  "message": "Login successful",
  "user": {
    "id": 1,
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "username": "johndoe",
    "email": "john@example.com",
    "full_name": "John Doe",
    "bio": "Software developer",
    "profile_image_url": null,
    "header_image_url": null,
    "followers_count": 0,
    "following_count": 0,
    "posts_count": 0,
    "is_verified": false,
    "is_private": false
  },
  "token": "2|zyxwvutsrqponmlkjihgfedcba"
}
```

**Error Response (422):**
```json
{
  "message": "Validation failed",
  "errors": {
    "login": ["The provided credentials are incorrect."]
  }
}
```

**Error Response (403) - Suspended Account:**
```json
{
  "message": "Your account has been suspended."
}
```

---

### 3. Get Current User

**Endpoint:** `GET /auth/me`

**Authentication:** Required

**Success Response (200):**
```json
{
  "user": {
    "id": 1,
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "username": "johndoe",
    "email": "john@example.com",
    "full_name": "John Doe",
    "bio": "Software developer",
    "profile_image_url": null,
    "header_image_url": null,
    "followers_count": 0,
    "following_count": 0,
    "posts_count": 0,
    "is_verified": false,
    "is_private": false
  }
}
```

---

### 4. Logout User

**Endpoint:** `POST /auth/logout`

**Authentication:** Required

**Success Response (200):**
```json
{
  "message": "Logged out successfully"
}
```

---

### 5. Get User Profile

**Endpoint:** `GET /users/{id}`

**Authentication:** Required

**URL Parameters:**
- `id` (integer) - User ID

**Success Response (200):**
```json
{
  "user": {
    "id": 1,
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "username": "johndoe",
    "full_name": "John Doe",
    "bio": "Software developer",
    "profile_image_url": null,
    "header_image_url": null,
    "followers_count": 150,
    "following_count": 200,
    "posts_count": 45,
    "is_verified": false,
    "is_private": false,
    "created_at": "2024-01-01T00:00:00.000000Z"
  }
}
```

**Error Response (404):**
```json
{
  "message": "Resource not found"
}
```

---

### 6. Update User Profile

**Endpoint:** `PATCH /users/{id}`

**Authentication:** Required (can only update own profile)

**URL Parameters:**
- `id` (integer) - User ID

**Request Body:**
```json
{
  "full_name": "string (optional)",
  "bio": "string (optional, max: 500)",
  "profile_image_url": "string (optional)",
  "header_image_url": "string (optional)",
  "is_private": "boolean (optional)"
}
```

**Success Response (200):**
```json
{
  "message": "Profile updated successfully",
  "user": {
    "id": 1,
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "username": "johndoe",
    "full_name": "John Updated",
    "bio": "Updated bio",
    "profile_image_url": null,
    "header_image_url": null,
    "followers_count": 150,
    "following_count": 200,
    "posts_count": 45,
    "is_verified": false,
    "is_private": false
  }
}
```

**Error Response (403):**
```json
{
  "message": "Unauthorized"
}
```

---

### 7. Get User's Posts

**Endpoint:** `GET /users/{id}/posts`

**Authentication:** Required

**URL Parameters:**
- `id` (integer) - User ID

**Query Parameters:**
- `page` (integer, optional) - Page number (default: 1)
- `per_page` (integer, optional) - Items per page (default: 20)

**Success Response (200):**
```json
{
  "posts": [
    {
      "id": 1,
      "uuid": "650e8400-e29b-41d4-a716-446655440001",
      "user_id": 1,
      "content": "This is my first post!",
      "media_urls": ["https://example.com/image.jpg"],
      "engagement_count": 25,
      "created_at": "2024-01-01T12:00:00.000000Z",
      "updated_at": "2024-01-01T12:00:00.000000Z",
      "user": {
        "id": 1,
        "username": "johndoe",
        "full_name": "John Doe"
      }
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

---

### 8. Get User's Followers

**Endpoint:** `GET /users/{id}/followers`

**Authentication:** Required

**URL Parameters:**
- `id` (integer) - User ID

**Query Parameters:**
- `page` (integer, optional) - Page number (default: 1)

**Success Response (200):**
```json
{
  "followers": [
    {
      "id": 2,
      "uuid": "750e8400-e29b-41d4-a716-446655440002",
      "username": "janedoe",
      "full_name": "Jane Doe",
      "profile_image_url": null,
      "is_verified": false
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 20,
    "total": 150,
    "last_page": 8
  }
}
```

---

### 9. Get User's Following

**Endpoint:** `GET /users/{id}/following`

**Authentication:** Required

**URL Parameters:**
- `id` (integer) - User ID

**Query Parameters:**
- `page` (integer, optional) - Page number (default: 1)

**Success Response (200):**
```json
{
  "following": [
    {
      "id": 3,
      "uuid": "850e8400-e29b-41d4-a716-446655440003",
      "username": "bobsmith",
      "full_name": "Bob Smith",
      "profile_image_url": null,
      "is_verified": false
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 20,
    "total": 200,
    "last_page": 10
  }
}
```

---

## Error Responses

### 401 Unauthorized
```json
{
  "message": "Unauthenticated"
}
```

### 403 Forbidden
```json
{
  "message": "Unauthorized"
}
```

### 404 Not Found
```json
{
  "message": "Resource not found"
}
```

### 422 Validation Error
```json
{
  "message": "Validation failed",
  "errors": {
    "field_name": ["Error message"]
  }
}
```

### 429 Too Many Requests
```json
{
  "message": "Too Many Attempts."
}
```

### 500 Internal Server Error
```json
{
  "message": "Server Error"
}
```

---

## Testing with cURL

### Register a new user:
```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "username": "johndoe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "full_name": "John Doe",
    "bio": "Software developer"
  }'
```

### Login:
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "login": "johndoe",
    "password": "password123"
  }'
```

### Get user profile (authenticated):
```bash
curl -X GET http://localhost:8000/api/users/1 \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### Update profile (authenticated):
```bash
curl -X PATCH http://localhost:8000/api/users/1 \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "full_name": "John Updated",
    "bio": "Updated bio"
  }'
```

---

## Saved Posts

### Save Post
**Endpoint:** `POST /posts/{id}/save`

**Authentication:** Required

**Success Response (201):**
```json
{
  "message": "Post saved successfully"
}
```

**Error Responses:**
- `400` - Cannot save own post
- `400` - Post already saved

---

### Unsave Post
**Endpoint:** `DELETE /posts/{id}/save`

**Authentication:** Required

**Success Response (200):**
```json
{
  "message": "Post unsaved successfully"
}
```

**Error Responses:**
- `404` - Saved post not found

---

### Check if Post is Saved
**Endpoint:** `GET /posts/{id}/saved`

**Authentication:** Required

**Success Response (200):**
```json
{
  "saved": true
}
```

---

### Get Saved Posts
**Endpoint:** `GET /users/{id}/saved-posts`

**Authentication:** Required

**Query Parameters:**
- `page` - Page number (default: 1)
- `limit` - Items per page (default: 20, max: 100)

**Success Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "uuid": "550e8400-e29b-41d4-a716-446655440000",
      "content": "Amazing sunset today! 🌅",
      "media_urls": ["https://example.com/sunset.jpg"],
      "visibility": "public",
      "user_id": 2,
      "created_at": "2023-12-26T10:00:00Z",
      "updated_at": "2023-12-26T10:00:00Z",
      "deleted_at": null,
      "author": {
        "id": 2,
        "uuid": "...",
        "username": "jane_doe",
        "full_name": "Jane Doe",
        "profile_image_url": "https://..."
      },
      "likes_count": 45,
      "reposts_count": 8,
      "quotes_count": 3,
      "replies_count": 12
    }
  ],
  "pagination": {
    "current_page": 1,
    "next_page_url": "http://localhost:8000/api/users/1/saved-posts?page=2",
    "prev_page_url": null
  }
}
```

**Notes:** Posts are ordered by save date (newest saved first)

---

## Postman Collection

Import the `threads-clone.postman_collection.json` file into Postman for easy API testing.

1. Open Postman
2. Click Import
3. Select the JSON file
4. Set the `base_url` variable to `http://localhost:8000`
5. After login/register, set the `token` variable with the returned token
