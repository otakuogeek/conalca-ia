<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cotización Respondida</title>
    <style>
        body { font-family: Arial; background: #fafafa; display:flex; align-items:center; min-height:100vh; }
        .card { margin: auto; max-width:400px; background:white; box-shadow:0 3px 20px #0002; border-radius:10px; padding:2rem; text-align:center;}
    </style>
</head>
<body>
    <div class="card">
        @if($grupo->status === 'aceptada')
            <h2 style="color:green;">¡Gracias por aceptar al menos una cotización!</h2>
            <p>En breve nuestro equipo se pondrá en contacto contigo.</p>
        @else
            <h2 style="color:#e35b44">¡Ninguna cotización aceptada!</h2>
            <p>Informaremos al asesor comercial para re-negociar o aclarar tus inquietudes.</p>
        @endif
    </div>
</body>
</html>