FROM php:8.2-apache

# Install required packages
RUN apt-get update && \
    apt-get install -y --no-install-recommends unzip cron libpng-dev libjpeg62-turbo-dev libfreetype6-dev && \
    docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install gd pdo pdo_mysql && \
    apt-get clean && rm -rf /var/lib/apt/lists/*

# Working directory
WORKDIR /var/www/html

# Apache fix FQDN warning
RUN echo "ServerName localhost" > /etc/apache2/conf-available/servername.conf && \
    a2enconf servername

# Copy application
COPY html/ /var/www/html/
COPY composer.json composer.lock* /var/www/html/

# Install Composer dependencies (inside image)
RUN curl -sS https://getcomposer.org/installer | php && \
    php composer.phar install --no-dev --optimize-autoloader && \
    rm composer.phar

# File permissions
RUN chmod +x /var/www/html/rotate.sh && \
    chown -R www-data:www-data /var/www/html

# Default cron file (will be overwritten by entrypoint)
RUN touch /etc/cron.d/rotate-psk && chmod 0644 /etc/cron.d/rotate-psk

# Add entrypoint
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]
