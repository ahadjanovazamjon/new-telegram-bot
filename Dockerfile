FROM php:8.2-apache

# Install SQLite & PDO dependencies
RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    pkg-config \
    && docker-php-ext-install pdo pdo_sqlite

# Copy application files
COPY . /var/www/html/

# Expose port
ENV PORT=80
EXPOSE 80

# Enable Apache mod_rewrite
RUN a2enmod rewrite
