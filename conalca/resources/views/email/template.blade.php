<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Cotización Conalca</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        .email-container {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
        }
        
        .gradient-bg {
            background: linear-gradient(135deg, #ff8c42 0%, #d85e13 100%);
        }
        
        .shadow-card {
            box-shadow: 0 10px 25px rgba(216, 94, 19, 0.15);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #04D000 0%, #00A000 100%);
            transition: all 0.3s ease;
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            transition: all 0.3s ease;
        }
        
        @media (max-width: 600px) {
            .responsive-padding { padding: 16px !important; }
            .responsive-text { font-size: 14px !important; }
            .responsive-buttons { flex-direction: column !important; gap: 8px !important; }
        }
    </style>
</head>
<body style="margin: 0; padding: 20px; background: linear-gradient(135deg, #fff5f0 0%, #ffe8d6 100%); min-height: 100vh;" class="email-container">
    <div style="margin: 0 auto; max-width: 700px; background: #ffffff; border-radius: 16px; overflow: hidden;" class="shadow-card">
        {{-- Header con gradiente --}}
        <div style="padding: 32px 32px 24px 32px; background: linear-gradient(135deg, #ff8c42 0%, #d85e13 100%); color: white; text-align: center;" class="gradient-bg responsive-padding">
           <img src="{{ $logoUrl ?? asset('img/logo-conalca.png') }}" alt="CONALCA" style="height: 100px; margin-bottom: 16px; filter: brightness(0) invert(1);">

            <h1 style="margin: 0; font-size: 24px; font-weight: 700; letter-spacing: -0.02em; color: #000000;">
                Cotización de Transporte
            </h1>
            <p style="margin: 8px 0 0 0; font-size: 16px; opacity: 0.9; font-weight: 400; color: #000000;">
                Propuesta comercial personalizada
            </p>
        </div>

        <div style="padding: 32px;" class="responsive-padding">

            {{-- Título de la cotización --}}
            <div style="text-align: center; margin-bottom: 32px;">
                <h2 style="margin: 0 0 8px 0; font-size: 20px; font-weight: 600; color: #d85e13;">
                    {{ $data['title'] ?? 'Cotización de Servicios' }}
                </h2>
                <div style="width: 60px; height: 3px; background: linear-gradient(90deg, #ff8c42, #d85e13); margin: 0 auto; border-radius: 2px;"></div>
            </div>

            {{-- Información del cliente con diseño mejorado --}}
            <div style="margin-bottom: 32px; padding: 24px; background: linear-gradient(135deg, #fff8f4 0%, #ffeee6 100%); border-radius: 12px; border-left: 4px solid #d85e13;">
                <h3 style="margin: 0 0 16px 0; font-size: 16px; font-weight: 600; color: #d85e13; display: flex; align-items: center;">
                    <svg style="width: 20px; height: 20px; margin-right: 8px;" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"/>
                    </svg>
                    Información del Cliente
                </h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; font-size: 14px; color: #444;">
                    <div><strong style="color: #d85e13;">Cliente:</strong> {{ $data['client_name'] ?? 'No especificado' }}</div>
                    <div><strong style="color: #d85e13;">NIT:</strong> {{ $data['client_document'] ?? 'No especificado' }}</div>
                    <div><strong style="color: #d85e13;">Ubicación:</strong> {{ $data['client_location'] ?? 'No especificada' }}</div>
                    <div><strong style="color: #d85e13;">Teléfonos:</strong> {{ $data['client_phone_numbers'] ?? 'No especificados' }}</div>
                </div>
            </div>

            {{-- Saludo personalizado --}}
            <div style="margin-bottom: 24px; padding: 20px; background: #f8f9fa; border-radius: 12px; border: 1px solid #e9ecef;">
                <p style="margin: 0; font-size: 16px; color: #333; line-height: 1.6;">
                    De parte de <strong style="color: #d85e13;">{{ $data['asesor_name'] ?? 'Nuestro equipo' }}</strong> de Conalca (NIT: 900416879),
                    le extendemos un cordial saludo.
                </p>
                <div style="color: #6c757d; font-size: 13px; margin-top: 12px; display: flex; align-items: center;">
                    <svg style="width: 16px; height: 16px; margin-right: 6px;" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zM4 7h12v9H4V7z"/>
                    </svg>
                    {{ \Carbon\Carbon::parse($data['created_at'] ?? now())->format('d \d\e F, Y \a \l\a\s H:i') }}
                </div>
            </div>

            {{-- Mensaje principal --}}
            @if(!empty($data['text']))
            <div style="margin-bottom: 32px; padding: 20px; background: #ffffff; border-radius: 12px; border: 1px solid #e3f2fd;">
                <p style="margin: 0; color: #333; font-size: 15px; line-height: 1.7;">
                    {{ $data['text'] }}
                </p>
            </div>
            @endif

            {{-- Tabla de rutas mejorada --}}
            <div style="margin-bottom: 32px;">
                <h3 style="margin: 0 0 20px 0; font-size: 18px; font-weight: 600; color: #d85e13; display: flex; align-items: center;">
                    <svg style="width: 20px; height: 20px; margin-right: 8px;" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"/>
                    </svg>
                    Detalle de Rutas y Servicios
                </h3>
                
                <div style="background: #ffffff; border-radius: 12px; overflow: hidden; border: 1px solid #e9ecef; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                    <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                        <thead>
                            <tr style="background: linear-gradient(135deg, #d85e13 0%, #ff8c42 100%); color: white;">
                                <th style="padding: 16px 12px; text-align: center; font-weight: 600; border-right: 1px solid rgba(255,255,255,0.2);">Ruta</th>
                                <th style="padding: 16px 12px; text-align: center; font-weight: 600; border-right: 1px solid rgba(255,255,255,0.2);">Origen</th>
                                <th style="padding: 16px 12px; text-align: center; font-weight: 600; border-right: 1px solid rgba(255,255,255,0.2);">Destino</th>
                                <th style="padding: 16px 12px; text-align: center; font-weight: 600; border-right: 1px solid rgba(255,255,255,0.2);">Vehículo</th>
                                <th style="padding: 16px 12px; text-align: center; font-weight: 600;">Valor</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($data['routes'] as $i => $route)
                                <tr style="background-color: {{ $i % 2 == 0 ? '#fff8f4' : '#ffffff' }}; transition: all 0.2s ease;">
                                    <td style="padding: 16px 12px; text-align: center; border-bottom: 1px solid #f1f3f4; font-weight: 600; color: #d85e13;">
                                        #{{ $i + 1 }}
                                    </td>
                                    <td style="padding: 16px 12px; text-align: center; border-bottom: 1px solid #f1f3f4; color: #333;">
                                        {{ $route['ciudad_origen'] ?? '-' }}
                                    </td>
                                    <td style="padding: 16px 12px; text-align: center; border-bottom: 1px solid #f1f3f4; color: #333;">
                                        {{ $route['ciudad_destino'] ?? '-' }}
                                    </td>
                                    <td style="padding: 16px 12px; text-align: center; border-bottom: 1px solid #f1f3f4; color: #666; font-size: 13px;">
                                        {{ $route['vehiculo_requerido'] ?? '-' }}
                                    </td>
                                    <td style="padding: 16px 12px; text-align: center; border-bottom: 1px solid #f1f3f4; font-weight: 600; color: #d85e13; font-size: 15px;">
                                        ${{ number_format($route['valor_final'] ?? 0, 0, ',', '.') }}

                                        @php
                                            /* datos acompañante de ESTA ruta */
                                            $tipo   = $route['tipaco_codigo'] ?? '';
                                            $cant   = (int)($route['itesoltra_vehiculoacompanamiento'] ?? 0);
                                            $valorU = (float)($route['itesoltra_acompanamientovalor']  ?? 0);
                                            $totalA = $cant > 0 ? $cant * $valorU : 0;
                                        @endphp

                                        @if($tipo && $totalA > 0)
                                            <table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-top:4px;font-size:10px;border-collapse:collapse;">
                                                <tr style="background:#fff7ed;">
                                                    <th style="border:1px solid #e5e7eb;text-align:center;">N°</th>
                                                    <th style="border:1px solid #e5e7eb;text-align:center;">Acompañamiento</th>
                                                    <th style="border:1px solid #e5e7eb;text-align:center;">Valor</th>
                                                </tr>
                                                <tr>
                                                    <td style="border:1px solid #e5e7eb;text-align:center;">1</td>
                                                    <td style="border:1px solid #e5e7eb;text-align:center;">
                                                        {{ ucfirst(strtolower($tipo)) }}
                                                    </td>
                                                    <td style="border:1px solid #e5e7eb;text-align:center;">
                                                        ${{ number_format($totalA,0,',','.') }}
                                                    </td>
                                                </tr>
                                            </table>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            
                            {{-- Fila del total mejorada --}}
                            <tr style="background: linear-gradient(135deg, #fff2e6 0%, #ffe8d6 100%); border-top: 2px solid #d85e13;">
                                <td colspan="4" style="padding: 20px 16px; text-align: right; font-weight: 700; font-size: 16px; color: #d85e13; letter-spacing: 0.5px;">
                                    TOTAL COTIZACIÓN
                                </td>
                                <td style="padding: 20px 16px; text-align: center; font-weight: 700; color: #d85e13; font-size: 18px; letter-spacing: 0.5px;">
                                    @php
                                        $total = collect($data['routes'])->sum(function ($r) {
                                            return floatval($r['valor_final'] ?? 0);   // valor_final ya incluye acompañamiento
                                        });
                                    @endphp
                                    ${{ number_format($total, 0, ',', '.') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Call to action mejorado --}}
            <div style="margin-bottom: 32px; padding: 28px; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-radius: 16px; text-align: center; border: 1px solid #dee2e6;">
                <h3 style="margin: 0 0 16px 0; font-size: 18px; font-weight: 600; color: #333;">
                    ¿Qué decides con nuestra propuesta?
                </h3>
                <p style="margin: 0 0 24px 0; color: #666; font-size: 15px; line-height: 1.6;">
                    Puedes aceptar nuestra cotización para proceder con el servicio o rechazarla para generar una nueva negociación.
                </p>

                {{-- Botones de acción mejorados --}}
              <a href="{{ url('cotizacion/grupo/'.$data['group_cotization_id'].'/responder') }}"
                    style="display:inline-block;width: 14rem; height: 2.125rem; line-height:2.125rem; border-radius: 8px; background-color: #007bff; color: white; text-align: center; font-weight: 500; text-decoration: none;">
                    Ver y aceptar / rechazar cotización
                </a>
            </div>

            <hr style="border: none; border-top: 2px solid #f1f3f4; margin: 40px 0 32px 0;">

            {{-- Mensaje de despedida --}}
            <div style="margin-bottom: 24px; padding: 20px; background: linear-gradient(135deg, #fff8f4 0%, #ffeee6 100%); border-radius: 12px; border-left: 4px solid #ff8c42;">
                <p style="margin: 0; color: #333; font-size: 15px; line-height: 1.6; font-style: italic;">
                    {{ $data['greeting'] ?? 'Quedamos atentos a cualquier inquietud. ¡Gracias por confiar en nosotros!' }}
                </p>
            </div>
            
            {{-- Firma del asesor --}}
            @if(!empty($data['advisor_signature']))
                <div style="text-align: center; margin-bottom: 24px;">
                    <div style="display: inline-block; padding: 16px; background: #ffffff; border-radius: 12px; border: 1px solid #e9ecef; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                        <img src="{{ asset($data['advisor_signature']) }}" alt="Firma Asesor" style="height: 48px; width: auto; object-fit: contain;">
                    </div>
                </div>
            @endif

            {{-- Información del asesor mejorada --}}
            <div style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); padding: 24px; border-radius: 12px; text-align: center; border: 1px solid #dee2e6;">
                <div style="margin-bottom: 16px;">
                    <h4 style="margin: 0 0 8px 0; font-size: 18px; font-weight: 600; color: #d85e13;">
                        {{ $data['asesor_name'] ?? 'Asesor Comercial' }}
                    </h4>
                    <div style="width: 40px; height: 2px; background: #ff8c42; margin: 0 auto; border-radius: 1px;"></div>
                </div>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; color: #555; font-size: 14px;">
                    @if(!empty($data['asesor_phone']))
                    <div style="display: flex; align-items: center; justify-content: center;">
                        <svg style="width: 16px; height: 16px; margin-right: 8px; color: #d85e13;" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"/>
                        </svg>
                        <span>{{ $data['asesor_phone'] }}</span>
                    </div>
                    @endif
                    
                    @if(!empty($data['asesor_email']))
                    <div style="display: flex; align-items: center; justify-content: center;">
                        <svg style="width: 16px; height: 16px; margin-right: 8px; color: #d85e13;" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                            <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                        </svg>
                        <span>{{ $data['asesor_email'] }}</span>
                    </div>
                    @endif
                </div>
            </div>
            
            {{-- Footer con información legal --}}
            <div style="margin-top: 32px; padding: 20px; background: #f8f9fa; border-radius: 12px; text-align: center; border: 1px solid #e9ecef;">
                <p style="color: #6c757d; font-size: 12px; margin: 0; line-height: 1.5;">
                    Este correo fue enviado a <strong>{{ $data['asesor_email'] ?? 'tu dirección de correo' }}</strong><br>
                    <span style="color: #adb5bd;">Conalca - Soluciones de Transporte | NIT: 900416879</span><br>
                    <a href="#" style="color: #d85e13; text-decoration: none;">Administrar preferencias</a> | 
                    <a href="#" style="color: #d85e13; text-decoration: none;">Política de Privacidad</a> | 
                    <a href="#" style="color: #d85e13; text-decoration: none;">Términos y Condiciones</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>