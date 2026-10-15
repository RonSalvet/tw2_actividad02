FROM php:8.4-cli

# Composer desde su imagen oficial
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

# Genera vendor/autoload.php (PSR-4 + src/funciones.php)
RUN composer dump-autoload

EXPOSE 8000

# Lab 7: el ÚLTIMO argumento (public/index.php) es el ROUTER / front controller.
# Sin él, rutas como /producto/MIE-1K o /api/articulos darían 404.
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public", "public/index.php"]
