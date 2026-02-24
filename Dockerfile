FROM php:8.2-fpm-alpine

# Instalar dependencias
RUN apk add --no-cache \
    nginx \
    mysql-client \
    curl \
    git \
    bash

# Instalar extensión PDO MySQL
RUN docker-php-ext-install pdo pdo_mysql

# Establecer directorio raíz web
WORKDIR /var/www/html

# Copiar TODOS los archivos del proyecto a /var/www/html
COPY . /var/www/html/

# Permisos
RUN chown -R www-data:www-data /var/www/html

# Crear directorio para logs
RUN mkdir -p /var/log/nginx

# Configurar PHP-FPM para escuchar en TCP
RUN mkdir -p /usr/local/etc/php-fpm.d && \
    echo "[global]" > /usr/local/etc/php-fpm.d/zz-docker.conf && \
    echo "listen = 127.0.0.1:9000" >> /usr/local/etc/php-fpm.d/zz-docker.conf && \
    echo "[www]" >> /usr/local/etc/php-fpm.d/zz-docker.conf && \
    echo "user = www-data" >> /usr/local/etc/php-fpm.d/zz-docker.conf && \
    echo "group = www-data" >> /usr/local/etc/php-fpm.d/zz-docker.conf

# Script de inicio
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 80

CMD ["/entrypoint.sh"]