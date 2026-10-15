FROM php:8.4-cli

# Composer desde su imagen oficial
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

# Genera vendor/autoload.php (PSR-4 + src/funciones.php)
RUN composer dump-autoload

EXPOSE 8000

# 0.0.0.0 para poder abrirlo desde el navegador de Windows
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]
