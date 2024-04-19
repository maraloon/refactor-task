FROM php:8.0.11-fpm-alpine

# Set working directory
WORKDIR /var/www

# Install dependencies
RUN apk -v --update add \
    zip \
    mc \
    unzip \
    git \
    curl \
    sqlite

# Clear cache
RUN rm -rf /var/lib/apt/lists/*

# Install extensions
RUN apk add zlib-dev libzip-dev freetype-dev libxml2-dev \
    && docker-php-ext-install pdo zip


# Install composer
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN curl -sS https://getcomposer.org/installer | php -- \
        --filename=composer \
        --install-dir=/usr/local/bin && \
        echo "alias composer='composer'" >> /root/.bashrc && \
        composer

ARG UID
ARG GID
ENV UID=${UID}
ENV GID=${GID}

RUN addgroup -g ${GID} --system outer
RUN adduser -G outer --system -D -s /bin/sh -u ${UID} outer
RUN sed -i "s/user = www-data/user = outer/g" /usr/local/etc/php-fpm.d/www.conf
RUN sed -i "s/group = www-data/group = outer/g" /usr/local/etc/php-fpm.d/www.conf

CMD ["php", "artisan", "serve", "--host", "0.0.0.0"]
