<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Responder Cotización</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; background: #f7f7f7; }
        .container { max-width:600px; margin:40px auto; background:#fff; border-radius:8px; box-shadow:0 2px 12px #0001; padding:2em; }
        table { width:100%; margin-bottom: 2em; border-collapse:collapse;}
        th, td { padding: 10px; border-bottom: 1px solid #eee; text-align:left;}
        .actions label { margin-right: 10px; }
        .submit-btn { display:block; width:100%; background:#FF7C32; color:#fff; font-weight:bold; border:none; border-radius:6px; padding: 12px 0; cursor:pointer;}
        .submit-btn:disabled { opacity: 0.6; cursor: not-allowed;}
    </style>
</head>
<body>
    <div class="container">
        <h2>Responder Cotización Grupo #{{ $grupo->id }}</h2>
        <p>Cliente: <b>{{ $grupo->client->name ?? '' }}</b></p>
        <p>Referencia: <b>{{ $grupo->reference ?? '-' }}</b></p>
        <form method="POST" action="{{ route('cotizacion.publica.responder', $grupo->id) }}">
            @csrf
            <table>
                <thead>
                    <tr>
                        <th>Ruta</th>
                        <th>Vehículo</th>
                        <th>Valor</th>
                        <th>Decisión</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($grupo->cotizaciones as $cot)
                        <tr>
                            <td>
                                {{ $cot->ciudad_origen }} - {{ $cot->ciudad_destino }}
                            </td>
                            <td>{{ $cot->vehiculo_requerido ?? '-' }}</td>
                            <td>${{ number_format(optional($cot->pricing)->price + optional($cot->pricing)->price * ($cot->porcentaje/100), 0) }}</td>
                            <td class="actions">
                                <label>
                                    <input type="radio" name="decisiones[{{ $cot->id }}]" value="aceptada"
                                        {{ $cot->decision_cliente == 'aceptada' ? 'checked' : '' }}> Aceptar
                                </label>
                                <label>
                                    <input type="radio" name="decisiones[{{ $cot->id }}]" value="rechazada"
                                        {{ $cot->decision_cliente == 'rechazada' ? 'checked' : '' }}> Rechazar
                                </label>
                                <!-- <label>
                                    <input type="radio" name="decisiones[{{ $cot->id }}]" value="pendiente"
                                        {{ $cot->decision_cliente == 'pendiente' ? 'checked' : '' }}> Pendiente
                                </label> -->
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <button class="submit-btn" type="submit">Guardar Respuestas</button>
        </form>
    </div>
</body>
</html>