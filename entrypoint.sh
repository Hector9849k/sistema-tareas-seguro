#!/bin/sh
set -e

echo "=== Iniciando servicios ==="

echo "Iniciando PHP-FPM..."
php-fpm -D

sleep 2

if ! pgrep php-fpm > /dev/null; then
    echo "ERROR: PHP-FPM no se inició correctamente"
    exit 1
fi

echo "PHP-FPM iniciado correctamente"

echo "Iniciando Nginx..."
nginx -g "daemon off;"
