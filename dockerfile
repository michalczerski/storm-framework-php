FROM mongo:7 as mongo
ENV MONGO_INITDB_DATABASE blog
COPY user.seed.js /docker-entrypoint-initdb.d/

FROM php:cli-alpine3.18 as storm
ENV APP_ENV=development
RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"
WORKDIR /usr/dev
#NPM
RUN apk add nodejs npm
RUN npm install -D tailwindcss
#MONGO-PHP
RUN apk add autoconf
RUN apk add build-base
RUN pecl install mongodb
RUN docker-php-ext-enable mongodb
#COMPOSE
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

FROM storm as blog
WORKDIR /usr/dev
COPY storm.php /usr/dev/storm.php
COPY tailwind.config.js /usr/dev/tailwind.config.js
COPY src/ /usr/dev/src
COPY apache/ /usr/dev/apache

RUN echo 'php -S 0.0.0.0:80 -t /usr/dev/apache &' >> init.sh
RUN echo 'npx tailwindcss -i ./apache/public/style.css -o ./apache/public/main.css -w -m' >> init.sh

CMD sh -c "sh init.sh"