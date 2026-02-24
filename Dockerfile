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

# Copiar archivos de la aplicación
COPY app /var/www/html/app
COPY app/index.html /var/www/html/index.html
COPY nginx.conf /etc/nginx/http.d/default.conf

# Crear directorio para logs de Nginx
RUN mkdir -p /var/log/nginx && \
    chown -R www-data:www-data /var/www/html

# Configurar PHP-FPM para escuchar en TCP
RUN mkdir -p /usr/local/etc/php-fpm.d && \
    echo "[global]" > /usr/local/etc/php-fpm.d/zz-docker.conf && \
    echo "listen = 127.0.0.1:9000" >> /usr/local/etc/php-fpm.d/zz-docker.conf && \
    echo "[www]" >> /usr/local/etc/php-fpm.d/zz-docker.conf && \
    echo "user = www-data" >> /usr/local/etc/php-fpm.d/zz-docker.conf && \
    echo "group = www-data" >> /usr/local/etc/php-fpm.d/zz-docker.conf

# Script de inicio - usar sh directamente
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 80

CMD ["/entrypoint.sh"]