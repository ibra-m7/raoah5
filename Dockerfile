# FROM node:22-alpine AS frontend
# WORKDIR /app
# COPY package*.json ./
# RUN npm ci
# COPY resources ./resources
# COPY vite.config.js .
# RUN npm run build

# FROM composer:2 AS dependencies
# WORKDIR /app
# COPY composer.json composer.lock ./
# RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts

# FROM php:8.2-apache
# WORKDIR /var/www/html

# RUN apt-get update \
#     && apt-get install -y --no-install-recommends libpq-dev libzip-dev unzip \
#     && docker-php-ext-install pdo_pgsql zip opcache \
#     && a2enmod rewrite headers \
#     && rm -rf /var/lib/apt/lists/*

# COPY --from=dependencies /app/vendor ./vendor
# COPY . .
# COPY --from=frontend /app/public/build ./public/build

# RUN sed -i 's#DocumentRoot /var/www/html#DocumentRoot /var/www/html/public#' /etc/apache2/sites-available/000-default.conf \
#     && sed -i '/<\/VirtualHost>/i\    <Directory /var/www/html/public>\n        AllowOverride All\n        Require all granted\n    </Directory>' /etc/apache2/sites-available/000-default.conf \
#     && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
#     && chown -R www-data:www-data storage bootstrap/cache

# COPY docker/entrypoint.sh /usr/local/bin/laravel-entrypoint
# RUN chmod +x /usr/local/bin/laravel-entrypoint

# EXPOSE 8080
# ENTRYPOINT ["laravel-entrypoint"]


FROM php:8.2-apache

# ===============================
# 1️⃣ System dependencies
# ===============================
RUN apt-get update && apt-get install -y \
    git unzip libzip-dev libpng-dev libonig-dev libxml2-dev \
    libpq-dev \
    && docker-php-ext-install \
    pdo \
    pdo_pgsql \
    zip

# ===============================
# 2️⃣ Enable Apache rewrite
# ===============================
RUN a2enmod rewrite

# ===============================
# 3️⃣ Set working directory
# ===============================
WORKDIR /var/www/html

# ===============================
# 4️⃣ Install Composer
# ===============================
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# ===============================
# 5️⃣ Copy composer files first (Docker cache optimization)
# ===============================
COPY composer.json composer.lock ./

RUN composer install --no-dev --optimize-autoloader --no-scripts

# ===============================
# 6️⃣ Copy project files
# ===============================
COPY . .

# ===============================
# 7️⃣ Laravel permissions
# ===============================
RUN chown -R www-data:www-data storage bootstrap/cache

# ===============================
# 8️⃣ Apache document root → /public
# ===============================
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/*.conf \
    /etc/apache2/apache2.conf \
    /etc/apache2/conf-available/*.conf

# ===============================
# 9️⃣ Expose port
# ===============================
EXPOSE 80

# ===============================
# 🔟 Run migrations & start Apache
# ===============================
CMD php artisan migrate --force && \
php artisan db:seed --force || true && \
    php artisan config:clear && \
    php artisan config:cache && \
    apache2-foreground
