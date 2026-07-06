FROM php:8.3-fpm

RUN apt-get update && apt-get install -y --no-install-recommends \
      curl ca-certificates git unzip less default-mysql-client \
      libzip-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev libicu-dev \
  && docker-php-ext-configure gd --with-jpeg --with-freetype \
  && docker-php-ext-install -j"$(nproc)" pdo_mysql mysqli gd zip exif intl opcache \
  && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN curl -fsSL https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar -o /usr/local/bin/wp \
  && chmod +x /usr/local/bin/wp

COPY etc/php/php.ini /usr/local/etc/php/conf.d/zz-app.ini

WORKDIR /var/app/public_html
