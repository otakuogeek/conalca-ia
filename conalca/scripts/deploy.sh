
#!/bin/bash
set -e

echo "Otorgando propiedad a ubuntu y permisos antes del git reset..."
sudo chown -R ubuntu:ubuntu /var/www/ai-transport
sudo chmod -R 775 /var/www/ai-transport

echo "🚚 Iniciando despliegue..."

# echo "📁 Sincronizando archivos con rsync..."
# rsync -av --delete /ruta/del/codigo/ /var/www/ai-transport/

echo "📂 Entrando al directorio del proyecto..."
cd /var/www/ai-transport

echo "🔄 Reiniciando estado del repositorio local..."
git reset --hard

echo "⬇️ Haciendo pull desde la rama master..."
git pull origin master

echo "📦 Instalando dependencias de Composer..."
composer install --no-dev --optimize-autoloader

echo "🧬 Ejecutando migraciones..."
php artisan migrate --force

echo "🔐 Ajustando permisos del proyecto..."
sudo chmod -R 775 /var/www/ai-transport
sudo chown -R ubuntu:www-data /var/www/ai-transport

echo "🛠 Asegurando que bootstrap/cache existe y tiene permisos correctos..."
mkdir -p /var/www/ai-transport/bootstrap/cache
chmod -R 775 /var/www/ai-transport/bootstrap/cache
chown -R ubuntu:www-data /var/www/ai-transport/bootstrap/cache

echo "♻️ Limpiando caché de configuración y aplicación..."
php artisan config:clear
php artisan cache:clear

echo "📦 Instalando dependencias de NPM..."
npm install

echo "🛠 Compilando assets con NPM..."
npm run build

echo "🧠 Verificando si supervisor está disponible..."
if command -v supervisorctl &> /dev/null
then
    echo "🔁 Reiniciando workers de Laravel (queue y scheduler)..."
    sudo supervisorctl restart laravel-worker:*
    sudo supervisorctl restart laravel-scheduler
else
    echo "⚠️ supervisorctl no encontrado, omitiendo reinicio de workers."
fi

echo "🌐 Reiniciando Apache..."
sudo systemctl restart apache2

echo "✅ Despliegue completado con éxito."
