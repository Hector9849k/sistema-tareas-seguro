#!/bin/bash
set -e

# Iniciar PHP-FPM en background
php-fpm &

# Iniciar Nginx en foreground (para que Docker pueda monitorear)
nginx -g "daemon off;"