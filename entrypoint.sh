#!/bin/bash
set -e

echo "=== Iniciando servicios ==="

# Iniciar PHP-FPM en segundo plano
echo "Iniciando PHP-FPM..."
php-fpm -D

# Esperar a que PHP-FPM esté listo
sleep 2

# Verificar que PHP-FPM esté corriendo
if ! pgrep -x "php-fpm" > /dev/null; then
    echo "ERROR: PHP-FPM no se inició correctamente"
    exit 1
fi

echo "PHP-FPM iniciado correctamente"

# Iniciar Nginx en primer plano
echo "Iniciando Nginx..."
nginx -g "daemon off;"