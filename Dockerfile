FROM php:8.3-cli

RUN apt-get update && apt-get install -y --no-install-recommends \
    gnupg g++ procps openssl git zip unzip locales \
    zlib1g-dev libzip-dev libfreetype6-dev libpng-dev libwebp-dev libxpm-dev \
    libpq-dev libjpeg-dev libjpeg62-turbo-dev libicu-dev libgd-dev libonig-dev libxslt1-dev \
    acl vim wget npm nodejs apt-transport-https lsb-release ca-certificates \
    && echo 'alias sf="php bin/console"' >> ~/.bashrc

RUN echo "en_US.UTF-8 UTF-8" > /etc/locale.gen  \
    &&  echo "fr_FR.UTF-8 UTF-8" >> /etc/locale.gen \
    &&  locale-gen

RUN curl -sS https://getcomposer.org/installer | php -- \
    &&  mv composer.phar /usr/local/bin/composer

RUN curl -sS https://get.symfony.com/cli/installer | bash \
    &&  mv /root/.symfony5/bin/symfony /usr/local/bin

RUN docker-php-ext-configure intl \
    && docker-php-ext-configure gd --enable-gd --with-freetype=/usr/include/ --with-jpeg=/usr/include/ --with-webp=/usr/include/ \
    && docker-php-ext-install \
       pdo pdo_mysql opcache intl zip calendar dom mbstring exif gd xsl mysqli

RUN pecl install apcu && docker-php-ext-enable apcu

RUN npm install --global yarn

CMD tail -f /dev/null

WORKDIR /var/www/html/

# Expose port 8000 and launch server.
EXPOSE 8000
CMD symfony server:start
