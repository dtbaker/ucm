FROM php:8.4-apache-bookworm

# Enable mod_rewrite for URL rewriting
RUN a2enmod rewrite

# Install mysqli extension (required for UCM)
RUN docker-php-ext-install mysqli pdo_mysql

# Install IMAP extension (PHP 8.4+ uses PECL; requires libc-client - available in Bookworm)
RUN apt-get update && apt-get install -y libc-client2007e-dev libkrb5-dev && rm -rf /var/lib/apt/lists/* \
	&& pecl install imap && docker-php-ext-enable imap

# Debug: PHP errors to stderr (visible in docker-compose logs app)
COPY docker/php-debug.ini /usr/local/etc/php/conf.d/99-debug.ini

# Set Apache document root
ENV APACHE_DOCUMENT_ROOT /var/www/html
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf
