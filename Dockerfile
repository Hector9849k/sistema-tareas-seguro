FROM php:8.2-fpm

# Instalar extensiones necesarias para MySQL
RUN docker-php-ext-install pdo pdo_mysql

FROM php:8.2-fpm

# Instalar extensiones necesarias para MySQL
RUN docker-php-ext-install pdo pdo_mysql

# Instalar Nginx
RUN apt-get update && apt-get install -y nginx && rm -rf /var/lib/apt/lists/*

# Crear directorio para socket
RUN mkdir -p /var/run/php-fpm && chown -R www-data:www-data /var/run/php-fpm

# Configurar PHP-FPM para usar socket Unix
RUN sed -i 's|listen = 127.0.0.1:9000|listen = /var/run/php-fpm/php-fpm.sock|' /usr/local/etc/php-fpm.d/www.conf && \
    sed -i 's|;listen.owner = nobody|listen.owner = www-data|' /usr/local/etc/php-fpm.d/www.conf && \
    sed -i 's|;listen.group = nobody|listen.group = www-data|' /usr/local/etc/php-fpm.d/www.conf && \
    sed -i 's|;listen.mode = 0660|listen.mode = 0660|' /usr/local/etc/php-fpm.d/www.conf

# Copiar aplicación
COPY ./app /var/www/html/

# Permisos correctos
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html

# Copiar configuración de Nginx
COPY nginx.conf /etc/nginx/conf.d/default.conf

# Script para iniciar ambos servicios
RUN echo '#!/bin/bash\n\
mkdir -p /var/run/php-fpm\n\
php-fpm -D\n\
nginx -g "daemon off;"\n\
' > /start.sh && chmod +x /start.sh

EXPOSE 80

CMD ["/start.sh"]