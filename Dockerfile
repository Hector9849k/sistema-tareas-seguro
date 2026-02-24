FROM php:8.2-fpm as php

# Instalar extensiones necesarias
RUN docker-php-ext-install pdo pdo_mysql

COPY ./app /var/www/html/
RUN chown -R www-data:www-data /var/www/html

# Nginx
FROM nginx:alpine

# Copiar configuración de Nginx
COPY nginx.conf /etc/nginx/conf.d/default.conf

# Copiar app desde la etapa PHP
COPY --from=php /var/www/html /var/www/html

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]