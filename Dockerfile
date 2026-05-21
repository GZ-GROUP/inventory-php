FROM php:8.3-apache

RUN docker-php-ext-install mysqli pdo pdo_mysql

COPY ./src /var/www/html
COPY ./apache.conf /etc/apache2/sites-available/000-default.conf

RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
