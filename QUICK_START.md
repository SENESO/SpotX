# Quick Start Guide - Threads Clone

Get up and running in 5 minutes! 🚀

---

## Prerequisites

- Docker & Docker Compose installed
- Git

---

## Setup Steps

### 1. Clone & Configure
```bash
# Navigate to project
cd threads-clone

# Copy environment file
cp .env.example .env

# No changes needed - defaults work with Docker!
```

### 2. Start Docker Services
```bash
# Start all services (MySQL, Redis, Nginx, PHP-FPM)
docker-compose up -d

# Wait ~10 seconds for services to initialize
```

### 3. Install Dependencies
```bash
# Install PHP dependencies
docker-compose exec app composer install

# Generate application key
docker-compose exec app php artisan key:generate
```

### 4. Setup Database
```bash
# Run all migrations (creates 10 tables)
docker-compose exec app php artisan migrate

# Optional: Seed with test users
docker-compose exec app php artisan db:seed
```

### 5. Verify Installation
```bash
# Check if API is responding
curl http://localhost:8000/up

# Should return: {"status":"ok"}
```

---

## 🎉 You're Ready!

The API is now running at: **http://localhost:8000**

---

## Test the API

### Register a New User
```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "username": "johndoe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "full_name": "John Doe",
    "bio": "Test user"
  }'
```

**Response:**
```json
{
  "message": "User registered successfully",
  "user": { ... },
  "token": "1|abcdef..."
}
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

### Get Current User (Protected Route)
```bash
# Save your token from login/register
TOKEN="your_token_here"

curl -X GET http://localhost:8000/api/auth/me \
  -H "Authorization: Bearer $TOKEN"
```

---

## 📚 Available Endpoints

### Public (No Auth Required)
- `POST /api/auth/register` - Register new user
- `POST /api/auth/login` - Login user

### Protected (Token Required)
- `POST /api/auth/logout` - Logout
- `GET /api/auth/me` - Get current user
- `GET /api/users/{id}` - Get user profile
- `PATCH /api/users/{id}` - Update profile
- `GET /api/users/{id}/posts` - Get user's posts
- `GET /api/users/{id}/followers` - Get followers
- `GET /api/users/{id}/following` - Get following

**Full API docs:** See `API_DOCUMENTATION.md`

---

## 🔍 Useful Commands

### Docker Management
```bash
# View logs
docker-compose logs -f app

# Stop services
docker-compose down

# Restart services
docker-compose restart

# Access app container
docker-compose exec app bash
```

### Laravel Commands
```bash
# Run migrations
docker-compose exec app php artisan migrate

# Rollback migrations
docker-compose exec app php artisan migrate:rollback

# Seed database
docker-compose exec app php artisan db:seed

# Clear cache
docker-compose exec app php artisan cache:clear

# List routes
docker-compose exec app php artisan route:list
```

### Testing
```bash
# Run all tests
docker-compose exec app php artisan test

# Run specific test
docker-compose exec app php artisan test --filter=AuthenticationTest
```

---

## 🐛 Troubleshooting

### Port Already in Use
```bash
# Change ports in docker-compose.yml
# MySQL: 3307 → 3308
# Redis: 6380 → 6381  
# Nginx: 8000 → 8080
```

### Permission Issues
```bash
# Fix storage permissions
docker-compose exec app chmod -R 775 storage bootstrap/cache
```

### Database Connection Failed
```bash
# Ensure MySQL is running
docker-compose ps

# Restart MySQL
docker-compose restart mysql

# Wait 10 seconds and try migration again
```

### Clear Everything and Start Fresh
```bash
# Stop and remove all containers
docker-compose down -v

# Remove vendor directory
rm -rf vendor

# Start fresh
docker-compose up -d
docker-compose exec app composer install
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan migrate
```

---

## 📊 Service Access

| Service | Local Access | Container Name |
|---------|--------------|----------------|
| API | http://localhost:8000 | threads_nginx |
| MySQL | localhost:3307 | threads_mysql |
| Redis | localhost:6380 | threads_redis |
| PHP-FPM | Internal only | threads_app |

### MySQL Connection Details
- **Host:** localhost
- **Port:** 3307
- **Database:** threads_clone
- **Username:** threads_user
- **Password:** threads_password

---

## 🧪 Test Users (After Seeding)

```
Email: test@example.com
Password: password123

Email: john@example.com
Password: password123
```

---

## 📦 Using Postman

1. Import `threads-clone.postman_collection.json`
2. Set environment variable:
   - `base_url` = `http://localhost:8000`
3. Register or login to get a token
4. Set environment variable:
   - `token` = `your_token_here`
5. Test all endpoints!

---

## 🎯 What's Next?

Phase 1 is complete! Ready for Phase 2:
- Posts CRUD
- Follow/Unfollow
- Like/Unlike
- Replies
- Notifications
- Search
- Feed generation

---

## 📖 More Documentation

- **Setup Guide:** `README.md`
- **API Reference:** `API_DOCUMENTATION.md`
- **Completion Report:** `PHASE1_FINAL_REPORT.md`
- **Project Overview:** `PROJECT_SUMMARY.md`
- **Verification:** `VERIFICATION_CHECKLIST.md`

---

## 💡 Tips

1. **Always use Docker commands** prefixed with `docker-compose exec app`
2. **Check logs** if something doesn't work: `docker-compose logs -f`
3. **Token expires?** Just login again to get a new one
4. **Rate limited?** Wait 1 minute (auth endpoints: 10 req/min)
5. **Need help?** Check the full documentation in README.md

---

## ✅ Checklist

- [ ] Docker services running (`docker-compose ps`)
- [ ] Dependencies installed (`vendor/` directory exists)
- [ ] App key generated (`.env` has `APP_KEY`)
- [ ] Migrations run (10 tables in database)
- [ ] API responding (`curl http://localhost:8000/up`)
- [ ] Can register a user
- [ ] Can login and get token
- [ ] Can access protected endpoints with token

---

**Happy coding! 🚀**

For detailed information, see the full documentation files.
