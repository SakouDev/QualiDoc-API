FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    libicu-dev \
    unzip \
    && docker-php-ext-install intl mysqli \
    && rm -rf /var/lib/apt/lists/*

# CodeIgniter lit les surcharges de config depuis $_ENV/$_SERVER, pas
# getenv() — sans ça, les variables passées par "environment:" dans
# docker-compose.yml (ex: database.default.hostname) sont invisibles pour
# l'app alors que getenv() les voit très bien.
RUN echo "variables_order = EGPCS" > /usr/local/etc/php/conf.d/variables-order.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .
RUN composer install --no-dev --optimize-autoloader

EXPOSE 8080
CMD ["php", "spark", "serve", "--host", "0.0.0.0", "--port", "8080"]
