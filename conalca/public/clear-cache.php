<!DOCTYPE html>
<html>
<head>
    <title>Limpiar Cache del Navegador</title>
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
    <meta http-equiv="Pragma" content="no-cache" />
    <meta http-equiv="Expires" content="0" />
</head>
<body>
    <h1>Limpiando cache...</h1>
    <script>
        // Limpiar cache y recargar
        if ('caches' in window) {
            caches.keys().then(function(names) {
                names.forEach(function(name) {
                    caches.delete(name);
                });
            });
        }
        
        // Forzar recarga sin cache
        setTimeout(function() {
            window.location.href = '/quotes?' + new Date().getTime();
        }, 1000);
    </script>
    <p>Redirigiendo a /quotes en 1 segundo...</p>
</body>
</html>
