FROM php:8.2-cli

RUN apt-get update -y && apt-get install -y libmcrypt-dev && apt-get -y install libpq-dev
RUN docker-php-ext-install pdo pdo_pgsql pgsql
RUN apt install -y git
RUN apt install -y p7zip-full 
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

COPY composer.json composer.json
COPY . /app
WORKDIR /app

RUN composer -n install

RUN sed -i 's/;extension=pgsql/extension=pgsql/g' ~/../usr/local/etc/php/php.ini-development

EXPOSE 8000
CMD php artisan serve --host=0.0.0.0 --port=8000