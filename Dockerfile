FROM php:8.2-fpm-alpine

# Instalar dependencias
RUN apk add --no-cache \
    nginx \
    mysql-client \
    curl \
    git

# Instalar extensión PDO MySQL
RUN docker-php-ext-install pdo pdo_mysql

# Copiar archivos de la aplicación
COPY app /var/www/html/app
COPY app/index.html /var/www/html/index.html
COPY nginx.conf /etc/nginx/http.d/default.conf

# Crear directorio para logs de Nginx
RUN mkdir -p /var/log/nginx && \
    chown -R www-data:www-data /var/www/html

# Configurar PHP-FPM para escuchar en TCP (no socket)
RUN echo "listen = 127.0.0.1:9000" > /usr/local/etc/php-fpm.d/zz-docker.conf && \
    echo "listen.allowed_clients = 127.0.0.1" >> /usr/local/etc/php-fpm.d/zz-docker.conf

# Script de inicio que corre Nginx y PHP-FPM
RUN echo '#!/bin/sh\nphp-fpm &\nnginx -g "daemon off;"' > /entrypoint.sh && \
    chmod +x /entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]