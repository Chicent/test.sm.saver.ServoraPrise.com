# Use the official PHP Apache image
FROM php:8.1-apache

# Enable Apache rewrite module
RUN a2enmod rewrite

# Set DirectoryIndex to prioritize index.php
RUN echo "DirectoryIndex index.php index.html" > /etc/apache2/conf-enabled/docker-php.conf

# Copy project files into the web root
COPY . /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Expose port 80
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]
