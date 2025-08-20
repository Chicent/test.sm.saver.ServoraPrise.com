# Use official PHP 8.1 + Apache
FROM php:8.1-apache

# Enable Apache modules
RUN a2enmod rewrite

# Install common PHP extensions you may need
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Configure Apache to parse PHP
RUN echo "DirectoryIndex index.php index.html" > /etc/apache2/conf-enabled/docker-php.conf \
    && echo "<FilesMatch \.php$>\n    SetHandler application/x-httpd-php\n</FilesMatch>" > /etc/apache2/conf-enabled/php-handler.conf

# Copy project files
COPY . /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Expose port
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]
