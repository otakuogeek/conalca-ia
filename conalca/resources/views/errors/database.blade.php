<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error de Conexión - {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome-free/css/all.min.css') }}">
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full bg-white rounded-lg shadow-lg p-8 text-center">
        <div class="mb-6">
            <i class="fas fa-database text-6xl text-red-500 mb-4"></i>
            <h1 class="text-2xl font-bold text-gray-800 mb-2">Error de Conexión</h1>
            <p class="text-gray-600 mb-4">
                No se puede conectar a la base de datos en este momento.
            </p>
            <p class="text-sm text-gray-500 mb-6">
                Esto puede deberse a problemas temporales de conectividad.
                Por favor, intenta nuevamente en unos minutos.
            </p>
        </div>

        <div class="space-y-3">
            <button onclick="window.location.reload()"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition duration-200">
                <i class="fas fa-redo mr-2"></i>
                Intentar de Nuevo
            </button>

            <a href="mailto:support@conalca.com"
               class="w-full inline-block bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium py-2 px-4 rounded-lg transition duration-200">
                <i class="fas fa-envelope mr-2"></i>
                Contactar Soporte
            </a>
        </div>

        <div class="mt-6 text-xs text-gray-400">
            Si el problema persiste, contacta al administrador del sistema.
        </div>
    </div>

    <script>
        // Auto-retry after 30 seconds
        setTimeout(function() {
            window.location.reload();
        }, 30000);
    </script>
</body>
</html>
