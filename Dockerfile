# Use the official PHP image
FROM php:8.2-apache

# Install necessary dependencies
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Enable mod_rewrite for Apache
RUN a2enmod rewrite

# Set the working directory inside the container
WORKDIR /var/www/html

# Copy the PHP application to the container
COPY . .

# Install dependencies via Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer && \
    composer install

# Expose port 8000 for the application
EXPOSE 8000
