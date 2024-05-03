FROM php:8.3-fpm

RUN apt-get clean && apt-get update

RUN apt-get install -y \
  libfreetype6-dev \
  libjpeg62-turbo-dev \
  libxml2-dev \
  libwebp-dev \
  libpng-dev \
  libzip-dev \
  libonig-dev \
  libcurl4-openssl-dev \
  && docker-php-ext-configure gd  --with-webp --with-jpeg\
  && docker-php-ext-install -j$(nproc) gd\
  && docker-php-ext-install xml dom curl mbstring intl gettext\
  && docker-php-ext-install zip\
  && pecl bundle -d /usr/src/php/ext apcu\
  && docker-php-ext-install /usr/src/php/ext/apcu\
# && docker-php-ext-install sqlite3\
  && docker-php-ext-install mysqli

COPY php.ini ${PHP_INI_DIR}
