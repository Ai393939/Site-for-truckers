FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    git \
    unzip

RUN pecl install mongodb && docker-php-ext-enable mongodb

RUN a2enmod rewrite

WORKDIR /var/www/html