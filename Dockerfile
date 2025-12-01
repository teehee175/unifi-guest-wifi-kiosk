FROM php:8.2-apache

# Install required packages
RUN apt-get update && \
    apt-get install -y --no-install-recommends unzip cron libpng-dev libjpeg62-turbo-dev libfreetype6-dev && \
    docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install gd pdo pdo_mysql && \
    apt-get clean && rm -rf /var/lib/apt/lists/*

# Set working directory
WORKDIR /var/www/html

# Apache: Fix FQDN warning
RUN echo "ServerName localhost" > /etc/apache2/conf-available/servername.conf && \
    a2enconf servername

# Copy application code
COPY html/ /var/www/html/
COPY composer.json composer.lock* /var/www/html/
COPY .env /var/www/html/.env

# Install Composer dependencies
RUN curl -sS https://getcomposer.org/installer | php && \
    php composer.phar install --no-dev --optimize-autoloader && \
    rm composer.phar

# Permissions
RUN chmod +x /var/www/html/rotate.sh && \
    chown -R www-data:www-data /var/www/html

# Add cron job (run at 2 AM)
RUN echo "0 2 * * * root /var/www/html/rotate.sh >> /var/log/cron.log 2>&1" \
    > /etc/cron.d/rotate-psk && \
    chmod 0644 /etc/cron.d/rotate-psk && \
    crontab /etc/cron.d/rotate-psk

EXPOSE 80

# Start cron quietly, then start Apache in foreground
CMD service cron start && apache2-foreground