#!/bin/bash

# Wrapper para ejecutar artisan con .env.development

# Copiar .env.development a .env temporalmente para este proceso
cp .env.development .env.dev.tmp

# Ejecutar artisan serve con el archivo temporal
php artisan serve --port=${1:-8000} --env=../../../.env.dev.tmp

# Limpiar archivo temporal al salir
rm -f .env.dev.tmp
