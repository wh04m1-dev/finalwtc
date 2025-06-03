# Build the image
docker-compose build

# Start containers
docker-compose up -d

# Run migrations (first time)
docker-compose exec app php artisan migrate --force

# Generate application key
docker-compose exec app php artisan key:generate
