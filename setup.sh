#!/bin/bash

echo "🚀 Setting up Threads Clone Laravel Backend..."

# Check if .env exists
if [ ! -f .env ]; then
    echo "📝 Creating .env file..."
    cp .env.example .env
fi

# Install dependencies
echo "📦 Installing Composer dependencies..."
composer install

# Generate app key
echo "🔑 Generating application key..."
php artisan key:generate

# Set permissions
echo "🔐 Setting permissions..."
chmod -R 775 storage bootstrap/cache
chmod +x setup.sh

echo "✅ Setup complete!"
echo ""
echo "Next steps:"
echo "1. Start Docker containers: docker-compose up -d"
echo "2. Run migrations: docker-compose exec app php artisan migrate"
echo "3. (Optional) Seed database: docker-compose exec app php artisan db:seed"
echo "4. Access API at: http://localhost:8000"
