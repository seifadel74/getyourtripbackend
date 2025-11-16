# === Laravel 12 + PHP 8.3 + Apache + MySQL Client ===
FROM php:8.3-apache

# === 1. تثبيت المتطلبات ===
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev zip unzip libzip-dev \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip \
    && a2enmod rewrite \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# === 2. تثبيت Composer ===
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# === 3. تثبيت Node.js 20 ===
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

# === 4. نسخ الكود + تثبيت dependencies ===
WORKDIR /var/www/html
COPY . .
RUN composer install --optimize-autoloader --no-dev --no-interaction \
    && npm install && npm run build

# === 5. إعدادات Apache للـ public ===
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf \
    && echo "DirectoryIndex index.php" >> /etc/apache2/sites-available/000-default.conf

# === 6. صلاحيات الملفات ===
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# === 7. تشغيل Laravel Setup + Apache (كل حاجة داخل الـ CMD) ===
CMD ["/bin/bash", "-c", "\
    if [ ! -f .env ]; then cp .env.example .env; fi && \
    php artisan key:generate --force && \
    php artisan migrate --force && \
    php artisan storage:link || true && \
    php artisan config:cache && \
    php artisan route:cache && \
    php artisan view:cache && \
    sed -i \"s/80/$PORT/g\" /etc/apache2/ports.conf && \
    sed -i \"s/:80/:$PORT/g\" /etc/apache2/sites-available/000-default.conf && \
    apache2-foreground \
"]
