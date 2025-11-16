# === Laravel 12 + PHP 8.3 + Apache + Migrate مرة واحدة ===
FROM php:8.3-apache

# تثبيت المتطلبات
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev zip unzip libzip-dev \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip \
    && a2enmod rewrite \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# تثبيت Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# تثبيت Node.js
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

# نسخ الكود
WORKDIR /var/www/html
COPY . .

# تثبيت dependencies
RUN composer install --optimize-autoloader --no-dev --no-interaction \
    && npm install && npm run build

# إعدادات Apache
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf \
    && echo "DirectoryIndex index.php" >> /etc/apache2/sites-available/000-default.conf

# صلاحيات
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# === CMD: Migrate مرة واحدة فقط + تشغيل Apache ===
CMD ["/bin/bash", "-c", "\
    if [ ! -f .env ]; then cp .env.example .env; fi && \
    php artisan key:generate --force && \
    \
    if [ ! -f /var/www/html/storage/app/.migrations_done ]; then \
        echo 'Running migrations for the first time...' && \
        php artisan migrate --force && \
        touch /var/www/html/storage/app/.migrations_done && \
        echo 'Migrations completed and flagged.'; \
    else \
        echo 'Migrations already done. Skipping...'; \
    fi && \
    \
    php artisan storage:link || true && \
    php artisan config:cache && \
    php artisan route:cache && \
    php artisan view:cache && \
    \
    sed -i \"s/80/$PORT/g\" /etc/apache2/ports.conf && \
    sed -i \"s/:80/:$PORT/g\" /etc/apache2/sites-available/000-default.conf && \
    apache2-foreground \
"]
