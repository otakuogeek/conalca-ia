<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico CSS - Conalca IA</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f8fafc;
        }
        .container {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #ff7f32;
            margin-bottom: 10px;
        }
        .status {
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }
        .success {
            background: #d1fae5;
            border-left: 4px solid #10b981;
        }
        .error {
            background: #fee2e2;
            border-left: 4px solid #ef4444;
        }
        .info {
            background: #dbeafe;
            border-left: 4px solid #3b82f6;
        }
        .warning {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
        }
        code {
            background: #f3f4f6;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 14px;
        }
        .steps {
            background: #f9fafb;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .steps ol {
            margin: 10px 0;
            padding-left: 20px;
        }
        .steps li {
            margin: 10px 0;
        }
        .emoji {
            font-size: 24px;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Diagnóstico del Sistema CSS</h1>
        <p>Esta página verifica qué archivo CSS debería estar cargándose</p>

        <?php
        $manifestPath = __DIR__ . '/../build/manifest.json';
        $cssFound = false;
        $cssFile = '';
        $cssPath = '';
        $cssExists = false;
        $oldCssExists = file_exists(__DIR__ . '/../build/assets/app-7c2b7e87.css');
        
        if (file_exists($manifestPath)) {
            $manifest = json_decode(file_get_contents($manifestPath), true);
            if (isset($manifest['resources/css/app.css'])) {
                $cssFile = $manifest['resources/css/app.css']['file'];
                $cssPath = __DIR__ . '/../build/' . $cssFile;
                $cssFound = true;
                $cssExists = file_exists($cssPath);
            }
        }
        ?>

        <div class="status <?php echo $cssFound && $cssExists ? 'success' : 'error'; ?>">
            <strong><?php echo $cssFound && $cssExists ? '✅' : '❌'; ?> Estado del CSS Actual:</strong><br>
            <?php if ($cssFound): ?>
                Archivo esperado: <code>/build/<?php echo htmlspecialchars($cssFile); ?></code><br>
                Existe: <?php echo $cssExists ? '✅ Sí' : '❌ No'; ?><br>
                <?php if ($cssExists): ?>
                    Tamaño: <?php echo round(filesize($cssPath)/1024, 2); ?> KB
                <?php endif; ?>
            <?php else: ?>
                ❌ No se encontró el manifiesto de Vite
            <?php endif; ?>
        </div>

        <?php if ($oldCssExists): ?>
        <div class="status warning">
            <strong>⚠️ Advertencia:</strong><br>
            El archivo antiguo <code>app-7c2b7e87.css</code> aún existe en el servidor.<br>
            Esto puede causar confusión, pero NO es la causa del error 404 si tu navegador lo sigue pidiendo.
        </div>
        <?php endif; ?>

        <div class="status info">
            <strong>ℹ️ ¿Por qué sigo viendo el error 404?</strong><br>
            El servidor está sirviendo correctamente el CSS nuevo, pero tu <strong>navegador tiene el HTML antiguo en caché</strong>.<br>
            Ese HTML antiguo contiene una referencia al CSS antiguo (<code>app-7c2b7e87.css</code>) que ya no existe.
        </div>

        <div class="steps">
            <h3>🎯 Solución Definitiva</h3>
            <p><strong>Debes limpiar la caché de tu navegador. Aquí tienes 3 opciones:</strong></p>
            
            <h4>Opción 1: Hard Refresh (Más Rápido)</h4>
            <ol>
                <li><strong>Windows/Linux:</strong> Presiona <code>Ctrl + Shift + R</code> o <code>Ctrl + F5</code></li>
                <li><strong>Mac:</strong> Presiona <code>Cmd + Shift + R</code></li>
                <li>Repite 2-3 veces si es necesario</li>
            </ol>

            <h4>Opción 2: Limpiar Caché Completamente</h4>
            <ol>
                <li>Presiona <code>F12</code> para abrir DevTools</li>
                <li>Click derecho en el botón de recargar <span class="emoji">🔄</span></li>
                <li>Selecciona "<strong>Vaciar caché y volver a cargar de manera forzada</strong>"</li>
            </ol>

            <h4>Opción 3: Modo Incógnito (Para Probar)</h4>
            <ol>
                <li><strong>Windows/Linux:</strong> <code>Ctrl + Shift + N</code></li>
                <li><strong>Mac:</strong> <code>Cmd + Shift + N</code></li>
                <li>Abre la página en modo incógnito</li>
            </ol>
        </div>

        <div class="status success">
            <strong>✅ Verificación:</strong><br>
            Después de limpiar la caché, abre la consola del navegador (F12) y busca:<br>
            <ul>
                <li>✅ El error de <code>app-7c2b7e87.css</code> debería desaparecer</li>
                <li>✅ Deberías ver <code>app-8ef293d1.css</code> cargándose correctamente</li>
                <li>✅ El chat debería funcionar normalmente</li>
            </ul>
        </div>

        <div style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #e5e7eb; text-align: center; color: #6b7280;">
            <p>Diagnóstico generado: <?php echo date('Y-m-d H:i:s'); ?></p>
            <p><a href="/quotes" style="color: #ff7f32; text-decoration: none; font-weight: bold;">← Volver a Cotizaciones</a></p>
        </div>
    </div>

    <script>
        // Verificar qué CSS está cargado actualmente
        window.addEventListener('DOMContentLoaded', function() {
            const links = document.querySelectorAll('link[rel="stylesheet"]');
            const cssFiles = Array.from(links).map(link => link.href);
            console.log('📄 Archivos CSS cargados en esta página:', cssFiles);
            
            // Verificar si hay errores 404
            const observer = new PerformanceObserver((list) => {
                list.getEntries().forEach((entry) => {
                    if (entry.name.includes('.css') && entry.responseStatus === 404) {
                        console.error('❌ CSS no encontrado:', entry.name);
                    }
                });
            });
            observer.observe({ entryTypes: ['resource'] });
        });
    </script>
</body>
</html>
