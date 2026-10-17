# Default PHP is 8.4. For a different one (the product supports 8.2 and up):
#   docker compose build --build-arg PHP_VERSION=8.2
ARG PHP_VERSION=8.4
FROM php:${PHP_VERSION}-apache-bookworm

# Enable mod_rewrite for URL rewriting
RUN a2enmod rewrite

# Install mysqli extension (required for UCM)
RUN docker-php-ext-install mysqli pdo_mysql

# Install IMAP extension (requires libc-client - available in Bookworm). PHP 8.4 moved it out
# of core to PECL, and the PECL release needs 8.3+, so 8.3 and older build the bundled one.
RUN apt-get update && apt-get install -y libc-client2007e-dev libkrb5-dev libssl-dev && rm -rf /var/lib/apt/lists/* \
	&& if [ "$(php -r 'echo PHP_VERSION_ID;')" -ge 80400 ]; then \
		pecl install imap && docker-php-ext-enable imap; \
	else \
		docker-php-ext-configure imap --with-kerberos --with-imap-ssl && docker-php-ext-install imap; \
	fi

# Debug: PHP errors to stderr (visible in docker-compose logs app)
COPY docker/php-debug.ini /usr/local/etc/php/conf.d/99-debug.ini

# Set Apache document root
ENV APACHE_DOCUMENT_ROOT /var/www/html
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf
