FROM php:8.2-apache

# Activer mod_rewrite pour Symfony
RUN a2enmod rewrite

# Installation des dépendances système
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Installation de Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Installation de Symfony CLI
RUN curl -sS https://get.symfony.com/cli/installer | bash \
    && mv /root/.symfony*/bin/symfony /usr/local/bin/symfony

# Configuration PHP pour le développement
RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

# Augmenter les limites PHP
RUN echo "memory_limit = 512M" >> "$PHP_INI_DIR/conf.d/custom.ini" \
    && echo "upload_max_filesize = 50M" >> "$PHP_INI_DIR/conf.d/custom.ini" \
    && echo "post_max_size = 50M" >> "$PHP_INI_DIR/conf.d/custom.ini"

# Configuration Apache pour Symfony
COPY docker/apache/vhost.conf /etc/apache2/sites-available/000-default.conf

# Permissions
RUN chown -R www-data:www-data /var/www/html

WORKDIR /var/www/html

EXPOSE 80

CMD ["apache2-foreground"]