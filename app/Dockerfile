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

# Start command: ensure sqlite exists and seeded, then serve
CMD ["sh", "-c", "php -r \"if (!file_exists('database/database.sqlite') || filesize('database/database.sqlite') === 0) { touch('database/database.sqlite'); chmod('database/database.sqlite', 0777); passthru('php artisan seed:demo'); } else { passthru('php artisan migrate --force'); }\" && php artisan config:clear && php artisan serve --host=0.0.0.0 --port=${PORT:-10000}"]
