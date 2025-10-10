<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Respuesta Cotización</title>
    <style>
        body { font-family: Arial; text-align: center; background: #f3f3f3; margin:0; padding:0;}
        .card {
            margin: 7% auto; max-width: 440px; background: white; border-radius: 12px;
            box-shadow: 0 2px 18px #0002; padding: 2.5rem 2rem 2rem 2rem;
        }
        .icon {
            font-size:2.8em; margin: 0 0 1.2em 0;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">
            @if($esAceptada)
                <span style="color:#36b730;">&#10003;</span>
            @else
                <span style="color:#e35b44;">&#10007;</span>
            @endif
        </div>
        <h1 style="font-size:1.8rem;">
            {{ $esAceptada ? 'Cotización aceptada' : 'Cotización rechazada' }}
        </h1>
        <p style="color:#333; font-size:1.1em; margin:1.5em 0;">{{ $mensaje }}</p>
        <br>
    </div>
</body>
</html>