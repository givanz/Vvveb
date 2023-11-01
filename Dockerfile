FROM php:8.2.11-apache

ARG DOWNLOAD_URL

ENV DIR_VVVEB='/var/www/html'
ENV DIR_CONFIG=${DIR_VVVEB}'/config'
ENV DIR_PUBLIC=${DIR_VVVEB}'/public'
ENV DIR_PLUGINS=${DIR_VVVEB}'/plugins'
ENV DIR_STORAGE=${DIR_VVVEB}'/storage'
ENV DIR_CACHE=${DIR_STORAGE}'/cache'
ENV DIR_DIGITAL_ASSETS=${DIR_STORAGE}'/digital_assets'
ENV DIR_IMAGE_CACHE=${DIR_PUBLIC}'/image-cache'

#change apache document root to public for better security
RUN sed -ri -e 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!/var/www/html/public!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

RUN apt-get clean && apt-get update && apt-get install unzip

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
  && docker-php-ext-install -j$(nproc) gd \
  && docker-php-ext-install xml dom curl mbstring intl gettext\
  && docker-php-ext-install zip\
  && docker-php-ext-install mysqli


RUN if [ -z "$DOWNLOAD_URL" ]; then \
  curl -Lo /tmp/vvveb.zip https://www.vvveb.com/download.php; \
  else \
  curl -Lo /tmp/vvveb.zip ${DOWNLOAD_URL}; \
  fi

#RUN usermod -u ${PHP_USER_ID} www-data
RUN usermod -u 1000 www-data

RUN unzip /tmp/vvveb.zip -d ${DIR_VVVEB}

RUN rm -rf /tmp/vvveb.zip

COPY php.ini ${PHP_INI_DIR}

RUN a2enmod rewrite
#RUN a2enmod lbmethod_byrequests

WORKDIR ${DIR_VVVEB}

RUN chown -R www-data:www-data ${DIR_VVVEB}
RUN chmod -R 555 ${DIR_VVVEB}
RUN chmod -R 666 ${DIR_STORAGE}
RUN chmod 555 ${DIR_STORAGE}

RUN chmod -R 644 ${DIR_PUBLIC}
RUN chmod 555 ${DIR_PUBLIC}

RUN chmod -R 644 ${DIR_CONFIG}
RUN chmod 555 ${DIR_CONFIG}

RUN chmod -R 666 ${DIR_PLUGINS}
RUN chmod -R 666 ${DIR_CACHE}
RUN chmod -R 666 ${DIR_DIGITAL_ASSETS}
RUN chmod -R 666 ${DIR_IMAGE_CACHE}

EXPOSE 80
#CMD ["apachectl", "-D", "FOREGROUND"]
CMD ["apache2-foreground"]
