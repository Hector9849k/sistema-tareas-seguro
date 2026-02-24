FROM php:8.2-fpm-alpine

# Instalar dependencias
RUN apk add --no-cache \
    nginx \
    mysql-client \
    curl \
    bash

# Instalar extensión PDO MySQL
RUN docker-php-ext-install pdo pdo_mysql

# Crear directorio raíz web
RUN mkdir -p /var/www/html

# Copiar carpeta app al root
COPY app /var/www/html

# Copiar configuración nginx
COPY nginx.conf /etc/nginx/http.d/default.conf

# Permisos
RUN chown -R www-data:www-data /var/www/html

# 🔥 IMPORTANTE:
# NO sobrescribimos www.conf
# Solo modificamos el listen usando sed
RUN sed -i 's|^listen = .*|listen = 127.0.0.1:9000|' /usr/local/etc/php-fpm.d/www.conf

# Script de inicio
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 80

CMD ["/entrypoint.sh"]