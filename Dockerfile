# Use the official PHP with Apache image
FROM php:8.2-apache

# Install necessary system dependencies
RUN apt-get update && apt-get install -y \
    iputils-ping \
    git \
    unzip \
    zip \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Enable mod_rewrite for Apache
RUN a2enmod rewrite

# Set the working directory inside the container
WORKDIR /var/www/html

# Copy the PHP application to the container
COPY . .

# Install Composer and PHP dependencies
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer && \
    composer install --no-dev --optimize-autoloader

# Expose port 8000 for the Apache server
EXPOSE 8000

RUN echo "Listen 0.0.0.0:8000" >> /etc/apache2/ports.conf

# Start Apache in the foreground
CMD ["apache2-foreground"]

RUN echo "<VirtualHost *:8000>\n\
    DocumentRoot /var/www/html/public\n\
    <Directory /var/www/html/public>\n\
        Options Indexes FollowSymLinks\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
</VirtualHost>" > /etc/apache2/sites-available/000-default.conf
