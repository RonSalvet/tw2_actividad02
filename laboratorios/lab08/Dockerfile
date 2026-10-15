FROM php:8.4-cli

# Extensión PDO para MySQL (la imagen base de PHP no la trae)
RUN docker-php-ext-install pdo_mysql

# Composer desde su imagen oficial
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

# Genera vendor/autoload.php (PSR-4 + src/funciones.php)
RUN composer dump-autoload

EXPOSE 8000

# El ÚLTIMO argumento (public/index.php) es el ROUTER / front controller.
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public", "public/index.php"]
