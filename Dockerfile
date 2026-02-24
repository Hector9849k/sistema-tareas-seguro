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

# Crear directorio raíz web
RUN mkdir -p /var/www/html

# Copiar SOLO la carpeta app al directorio raíz de Nginx
COPY app /var/www/html

# Copiar nginx.conf
COPY nginx.conf /etc/nginx/http.d/default.conf

# Permisos
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html

# Crear directorio para logs
RUN mkdir -p /var/log/nginx

# Configurar PHP-FPM
RUN echo "[www]" > /usr/local/etc/php-fpm.d/www.conf && \
    echo "user = www-data" >> /usr/local/etc/php-fpm.d/www.conf && \
    echo "group = www-data" >> /usr/local/etc/php-fpm.d/www.conf && \
    echo "listen = 127.0.0.1:9000" >> /usr/local/etc/php-fpm.d/www.conf && \
    echo "pm = dynamic" >> /usr/local/etc/php-fpm.d/www.conf && \
    echo "pm.max_children = 5" >> /usr/local/etc/php-fpm.d/www.conf && \
    echo "pm.start_servers = 2" >> /usr/local/etc/php-fpm.d/www.conf && \
    echo "pm.min_spare_servers = 1" >> /usr/local/etc/php-fpm.d/www.conf && \
    echo "pm.max_spare_servers = 3" >> /usr/local/etc/php-fpm.d/www.conf

# Script de inicio
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 80

CMD ["/entrypoint.sh"]