FROM php:8.3-cli-alpine

# Install system dependencies
RUN apk add --no-cache \
    curl \
    git \
    libzip-dev \
    libpng-dev \
    sqlite-dev \
    nodejs \
    npm

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_sqlite pdo_mysql zip

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Copy project files
COPY . .

# Set permissions
RUN chmod -R 777 storage bootstrap/cache

# Install dependencies and build frontend assets
RUN composer install --no-interaction --optimize-autoloader --no-dev
RUN npm install && npm run build

# Default environment variables
ENV PORT=10000
ENV APP_ENV=production
ENV APP_DEBUG=false
ENV DEMO=true
ENV DB_CONNECTION=sqlite
ENV DB_DATABASE=database/database.sqlite

EXPOSE 10000

# Start command: ensure sqlite exists, run migrations and seeder, then serve
CMD ["sh", "-c", "php -r \"file_exists('database/database.sqlite') || touch('database/database.sqlite');\" && chmod 777 database/database.sqlite 2>/dev/null || true && php artisan config:clear && php artisan migrate --force --seed && php artisan serve --host=0.0.0.0 --port=${PORT:-10000}"]
