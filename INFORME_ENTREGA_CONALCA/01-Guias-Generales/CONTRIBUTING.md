# 🤝 Guía de Contribución

## 📋 Tabla de Contenidos
- [Configuración del Entorno](#configuración-del-entorno)
- [Flujo de Trabajo Git](#flujo-de-trabajo-git)
- [Estándares de Código](#estándares-de-código)
- [Testing](#testing)
- [Deployment](#deployment)

## 🚀 Configuración del Entorno

### Requisitos Previos
- PHP 8.3+
- Composer 2.x
- Node.js 18+
- MySQL 8.0+
- Nginx

### Instalación Local

1. **Clonar el repositorio:**
```bash
git clone https://github.com/TU_USUARIO/conalca-ia-system.git
cd conalca-ia-system/conalca
```

2. **Instalar dependencias PHP:**
```bash
composer install
```

3. **Instalar dependencias Node:**
```bash
npm install
```

4. **Configurar variables de entorno:**
```bash
cp .env.example .env
php artisan key:generate
```

5. **Configurar base de datos:**
- Edita `.env` con tus credenciales
- Ejecuta migraciones:
```bash
php artisan migrate
```

6. **Compilar assets:**
```bash
npm run build
```

7. **Iniciar servicios:**
```bash
# Laravel
php artisan serve

# Queue worker (en otra terminal)
php artisan queue:work

# Vite dev server (en otra terminal)
npm run dev
```

## 🔄 Flujo de Trabajo Git

### Branching Strategy

- `main` - Código en producción
- `develop` - Desarrollo activo
- `feature/*` - Nuevas características
- `hotfix/*` - Correcciones urgentes

### Crear Feature Branch

```bash
git checkout develop
git pull origin develop
git checkout -b feature/nombre-descriptivo
```

### Commits

Usamos **Conventional Commits**:

```
tipo(scope): descripción corta

Descripción larga opcional

Fixes #123
```

**Tipos:**
- `feat`: Nueva característica
- `fix`: Corrección de bug
- `docs`: Documentación
- `style`: Formateo (sin cambios de código)
- `refactor`: Refactorización
- `test`: Tests
- `chore`: Tareas de mantenimiento

**Ejemplos:**
```bash
git commit -m "feat(chat): añadir auto-llenado de formularios"
git commit -m "fix(silogtran): corregir normalización de centros de costos"
git commit -m "docs(readme): actualizar instrucciones de instalación"
```

### Pull Request

1. Push tu branch:
```bash
git push origin feature/nombre-descriptivo
```

2. Crea PR en GitHub hacia `develop`
3. Espera code review
4. Merge después de aprobación

## 💻 Estándares de Código

### PHP (Laravel)

```php
<?php

namespace App\Services;

use App\Models\User;

/**
 * Servicio para gestión de usuarios
 */
class UserService
{
    /**
     * Crear nuevo usuario
     *
     * @param array $data
     * @return User
     */
    public function create(array $data): User
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
        ]);
    }
}
```

**Reglas:**
- PSR-12 coding standard
- Type hints obligatorios
- DocBlocks para métodos públicos
- Nombres descriptivos en español para lógica de negocio

### JavaScript/React

```javascript
import React, { useState } from 'react';

/**
 * Componente para formulario de contacto
 */
export default function ContactForm({ onSubmit }) {
    const [formData, setFormData] = useState({
        name: '',
        email: ''
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        onSubmit(formData);
    };

    return (
        <form onSubmit={handleSubmit}>
            {/* Form fields */}
        </form>
    );
}
```

**Reglas:**
- ESLint configuración estándar
- Componentes funcionales con Hooks
- PropTypes o TypeScript (futuro)
- Nombres en inglés para componentes

### CSS (Tailwind)

```jsx
<button className="w-full bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-4 rounded shadow-md">
    Guardar
</button>
```

**Reglas:**
- Usar Tailwind CSS primero
- CSS custom solo cuando sea necesario
- Mobile-first approach

## 🧪 Testing

### Tests Unitarios PHP

```bash
php artisan test
```

### Tests Frontend

```bash
npm run test
```

### Coverage

```bash
php artisan test --coverage
```

## 🚀 Deployment

### Pre-deployment Checklist

- [ ] Tests pasando
- [ ] Assets compilados (`npm run build`)
- [ ] Variables de entorno configuradas
- [ ] Migraciones revisadas
- [ ] Logs verificados

### Deploy a Producción

```bash
# 1. Pull latest code
git pull origin main

# 2. Install/update dependencies
composer install --no-dev --optimize-autoloader
npm ci

# 3. Build assets
npm run build

# 4. Run migrations
php artisan migrate --force

# 5. Clear caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Restart services
sudo systemctl restart laravel-conalca
sudo systemctl restart nginx
```

## 🐛 Debugging

### Logs

```bash
# Laravel logs
tail -f storage/logs/laravel.log

# Nginx error logs
sudo tail -f /var/log/nginx/error.log

# PHP-FPM logs
sudo tail -f /var/log/php8.3-fpm.log
```

### Common Issues

**1. Error 500 - Assets no encontrados**
```bash
npm run build
php artisan view:clear
```

**2. Error de permisos en storage**
```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

**3. Cache antiguo**
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

## 📚 Recursos

- [Laravel Documentation](https://laravel.com/docs)
- [React Documentation](https://react.dev)
- [Tailwind CSS](https://tailwindcss.com)
- [OpenAI API](https://platform.openai.com/docs)

## ❓ Preguntas

Si tienes dudas, abre un issue en GitHub con la etiqueta `question`.

---

¡Gracias por contribuir al proyecto Conalca IA! 🚀
