# Threads Clone - Laravel Backend API

A production-ready backend API for a Threads.net clone built with Laravel 11, MySQL 8.0, and Redis.

## Features

- **User Authentication**: Registration, login, logout with Laravel Sanctum
- **User Profiles**: Complete user management with followers, following, and posts
- **Posts System**: Create and manage posts with media support
- **Interactions**: Like, repost, and quote functionality
- **Replies**: Nested comments/replies system
- **Notifications**: Real-time notification system
- **Blocks**: User blocking functionality
- **RESTful API**: Clean, well-structured API endpoints
- **Docker Support**: Fully containerized application

## Tech Stack

- **Framework**: Laravel 11
- **Database**: MySQL 8.0
- **Cache/Queue**: Redis
- **Authentication**: Laravel Sanctum (API tokens)
- **PHP**: 8.2
- **Container**: Docker & Docker Compose

## Database Schema

### Users Table
- id, uuid, username (unique), email (unique)
- password (hashed), full_name, bio
- profile_image_url, header_image_url
- followers_count, following_count, posts_count
- is_verified, is_private, is_suspended
- timestamps

### Posts Table
- id, uuid, user_id (FK)
- content (text), media_urls (JSON)
- engagement_count
- timestamps, soft deletes

### Interactions Table
- id, user_id (FK), post_id (FK)
- interaction_type (like, repost, quote)
- timestamps

### Follows Table
- id, follower_id (FK), following_id (FK)
- timestamps

### Replies Table
- id, uuid, user_id (FK), post_id (FK)
- parent_reply_id (FK, nullable)
- content, media_urls (JSON)
- timestamps, soft deletes

### Notifications Table
- id, user_id (FK), actor_id (FK)
- type (like, reply, follow, mention, repost, quote)
- related_post_id (FK, nullable)
- is_read
- timestamps

### Blocks Table
- id, blocker_id (FK), blocked_id (FK)
- timestamps

## Installation

### Prerequisites

- Docker and Docker Compose installed
- Git

### Setup Steps

1. **Clone the repository**
```bash
git clone <repository-url>
cd threads-clone
```

2. **Copy environment file**
```bash
cp .env.example .env
```

3. **Update .env file** (optional)
```
DB_DATABASE=threads_clone
DB_USERNAME=threads_user
DB_PASSWORD=threads_password
```

4. **Start Docker containers**
```bash
docker-compose up -d
```

5. **Install dependencies** (if not using Docker build)
```bash
docker-compose exec app composer install
```

6. **Generate application key**
```bash
docker-compose exec app php artisan key:generate
```

7. **Run migrations**
```bash
docker-compose exec app php artisan migrate
```

8. **Access the application**
- API: http://localhost:8000
- MySQL: localhost:3307
- Redis: localhost:6380

## API Endpoints

### Authentication

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| POST | `/api/auth/register` | Register new user | No |
| POST | `/api/auth/login` | Login user | No |
| POST | `/api/auth/logout` | Logout user | Yes |
| GET | `/api/auth/me` | Get current user | Yes |

### Users

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| GET | `/api/users/{id}` | Get user profile | Yes |
| PATCH | `/api/users/{id}` | Update user profile | Yes |
| GET | `/api/users/{id}/posts` | Get user's posts | Yes |
| GET | `/api/users/{id}/followers` | Get user's followers | Yes |
| GET | `/api/users/{id}/following` | Get users being followed | Yes |

## API Usage Examples

### Register User

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

### Login

```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "login": "johndoe",
    "password": "password123"
  }'
```

### Get User Profile (with token)

```bash
curl -X GET http://localhost:8000/api/users/1 \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### Update Profile

```bash
curl -X PATCH http://localhost:8000/api/users/1 \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "full_name": "John Updated",
    "bio": "Updated bio"
  }'
```

## Development

### Run migrations

```bash
docker-compose exec app php artisan migrate
```

### Create new migration

```bash
docker-compose exec app php artisan make:migration create_table_name
```

### Run seeders

```bash
docker-compose exec app php artisan db:seed
```

### Clear cache

```bash
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan route:clear
```

### Run tests

```bash
docker-compose exec app php artisan test
```

## Docker Commands

### Start containers

```bash
docker-compose up -d
```

### Stop containers

```bash
docker-compose down
```

### View logs

```bash
docker-compose logs -f app
```

### Rebuild containers

```bash
docker-compose down
docker-compose build --no-cache
docker-compose up -d
```

### Access app container

```bash
docker-compose exec app bash
```

## Project Structure

```
threads-clone/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       └── Api/
│   │           ├── AuthController.php
│   │           └── UserController.php
│   └── Models/
│       ├── User.php
│       ├── Post.php
│       ├── Interaction.php
│       ├── Follow.php
│       ├── Reply.php
│       ├── Notification.php
│       └── Block.php
├── database/
│   └── migrations/
│       ├── create_users_table.php
│       ├── create_posts_table.php
│       ├── create_interactions_table.php
│       ├── create_follows_table.php
│       ├── create_replies_table.php
│       ├── create_notifications_table.php
│       └── create_blocks_table.php
├── routes/
│   └── api.php
├── docker/
│   ├── nginx/
│   │   └── default.conf
│   └── php/
│       └── local.ini
├── docker-compose.yml
├── Dockerfile
└── .env.example
```

## Models & Relationships

### User Model
- `hasMany` posts
- `belongsToMany` followers (through follows table)
- `belongsToMany` following (through follows table)
- `hasMany` interactions
- `hasMany` replies
- `hasMany` notifications
- `belongsToMany` blocks (users blocked)

### Post Model
- `belongsTo` user
- `hasMany` interactions
- `hasMany` replies

### Interaction Model
- `belongsTo` user
- `belongsTo` post

### Reply Model
- `belongsTo` user
- `belongsTo` post
- `belongsTo` parentReply (self-referencing)
- `hasMany` childReplies (nested replies)

### Follow Model
- `belongsTo` follower (User)
- `belongsTo` following (User)

### Notification Model
- `belongsTo` user
- `belongsTo` actor (User)
- `belongsTo` relatedPost (Post)

### Block Model
- `belongsTo` blocker (User)
- `belongsTo` blocked (User)

## Security Features

- Password hashing with bcrypt
- API token authentication (Laravel Sanctum)
- CSRF protection
- SQL injection prevention (Eloquent ORM)
- XSS protection
- Rate limiting on auth endpoints
- Input validation on all endpoints

## Performance Optimizations

- Database indexing on foreign keys
- Eager loading to prevent N+1 queries
- Redis caching for sessions and queues
- Database connection pooling
- Optimized Docker images

## Error Handling

The API returns consistent JSON responses:

### Success Response
```json
{
  "message": "Success message",
  "data": {}
}
```

### Error Response
```json
{
  "message": "Error message",
  "errors": {}
}
```

## HTTP Status Codes

- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `422` - Validation Error
- `500` - Server Error

## Contributing

1. Create a feature branch
2. Make your changes
3. Write/update tests
4. Submit a pull request

## License

MIT License

## Support

For issues and questions, please open an issue on the repository.
