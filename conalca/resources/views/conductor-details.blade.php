<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Detalles del Conductor - {{ $driverId }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div id="driver-details-root" data-driver-id="{{ $driverId }}"></div>
    
    @vite(['resources/js/pages/driver-details.jsx'])
</body>
</html>
