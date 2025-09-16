<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificado de Donación - CSDT</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f8fafc;
        }
        .certificado {
            background: white;
            border: 3px solid #059669;
            border-radius: 15px;
            padding: 40px;
            max-width: 800px;
            margin: 0 auto;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 40px;
            border-bottom: 2px solid #059669;
            padding-bottom: 20px;
        }
        .logo {
            font-size: 36px;
            font-weight: bold;
            color: #059669;
            margin-bottom: 10px;
        }
        .titulo {
            font-size: 28px;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 10px;
        }
        .subtitulo {
            font-size: 16px;
            color: #6b7280;
        }
        .contenido {
            margin-bottom: 30px;
        }
        .campo {
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            background-color: #f8fafc;
            border-radius: 8px;
        }
        .campo-label {
            font-weight: bold;
            color: #374151;
            flex: 1;
        }
        .campo-valor {
            color: #1f2937;
            flex: 2;
            text-align: right;
        }
        .monto-destacado {
            background: linear-gradient(135deg, #059669 0%, #10b981 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            margin: 20px 0;
        }
        .monto-numero {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .monto-texto {
            font-size: 14px;
            opacity: 0.9;
        }
        .observaciones {
            background-color: #e0f2fe;
            border-left: 4px solid #0ea5e9;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            color: #6b7280;
            font-size: 12px;
        }
        .firma {
            margin-top: 30px;
            text-align: center;
        }
        .firma-linea {
            border-bottom: 1px solid #374151;
            width: 300px;
            margin: 0 auto 10px;
            height: 40px;
        }
        .firma-texto {
            font-size: 14px;
            color: #6b7280;
        }
        .qr-code {
            text-align: center;
            margin: 20px 0;
        }
        .qr-placeholder {
            width: 150px;
            height: 150px;
            border: 2px dashed #d1d5db;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            color: #6b7280;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="certificado">
        <div class="header">
            <div class="logo">CSDT</div>
            <div class="titulo">CERTIFICADO DE DONACIÓN</div>
            <div class="subtitulo">Consejo Social de Veeduría y Desarrollo Territorial</div>
        </div>

        <div class="contenido">
            <p style="text-align: center; font-size: 16px; color: #374151; margin-bottom: 30px;">
                Por medio del presente certificado, se acredita que:
            </p>

            <div class="campo">
                <span class="campo-label">Número de Referencia:</span>
                <span class="campo-valor"><strong>{{ $donacion->numero_referencia }}</strong></span>
            </div>

            <div class="campo">
                <span class="campo-label">Donante:</span>
                <span class="campo-valor">{{ $donacion->nombre_donante }}</span>
            </div>

            <div class="campo">
                <span class="campo-label">Email:</span>
                <span class="campo-valor">{{ $donacion->email_donante }}</span>
            </div>

            <div class="campo">
                <span class="campo-label">Teléfono:</span>
                <span class="campo-valor">{{ $donacion->telefono_donante ?? 'No especificado' }}</span>
            </div>

            <div class="campo">
                <span class="campo-label">Tipo de Donación:</span>
                <span class="campo-valor">{{ ucfirst(str_replace('_', ' ', $donacion->tipo_donacion)) }}</span>
            </div>

            <div class="campo">
                <span class="campo-label">Método de Pago:</span>
                <span class="campo-valor">{{ ucfirst($donacion->metodo_pago) }}</span>
            </div>

            @if($donacion->referencia_donacion)
            <div class="campo">
                <span class="campo-label">Referencia de Donación:</span>
                <span class="campo-valor">{{ $donacion->referencia_donacion }}</span>
            </div>
            @endif

            <div class="monto-destacado">
                <div class="monto-numero">{{ $donacion->monto_formateado }}</div>
                <div class="monto-texto">MONTO DE LA DONACIÓN</div>
            </div>

            <div class="campo">
                <span class="campo-label">Fecha de Donación:</span>
                <span class="campo-valor">{{ $donacion->created_at->format('d/m/Y H:i') }}</span>
            </div>

            <div class="campo">
                <span class="campo-label">Fecha de Validación:</span>
                <span class="campo-valor">{{ $donacion->fecha_validacion ? $donacion->fecha_validacion->format('d/m/Y H:i') : 'No validado' }}</span>
            </div>

            <div class="campo">
                <span class="campo-label">Fecha de Certificación:</span>
                <span class="campo-valor">{{ $donacion->fecha_certificacion->format('d/m/Y H:i') }}</span>
            </div>

            <div class="campo">
                <span class="campo-label">Validado por:</span>
                <span class="campo-valor">{{ $donacion->validador ? $donacion->validador->nombre : 'No especificado' }}</span>
            </div>

            <div class="campo">
                <span class="campo-label">Certificado por:</span>
                <span class="campo-valor">{{ $donacion->certificador ? $donacion->certificador->nombre : 'No especificado' }}</span>
            </div>

            @if($donacion->mensaje)
            <div class="observaciones">
                <strong>Mensaje del Donante:</strong><br>
                {{ $donacion->mensaje }}
            </div>
            @endif

            @if($observaciones)
            <div class="observaciones">
                <strong>Observaciones de Certificación:</strong><br>
                {{ $observaciones }}
            </div>
            @endif

            @if($donacion->observaciones_admin)
            <div class="observaciones">
                <strong>Observaciones de Validación:</strong><br>
                {{ $donacion->observaciones_admin }}
            </div>
            @endif

            <div class="qr-code">
                <div class="qr-placeholder">
                    QR Code<br>
                    {{ $donacion->numero_referencia }}
                </div>
            </div>
        </div>

        <div class="firma">
            <div class="firma-linea"></div>
            <div class="firma-texto">Firma Digital del Administrador</div>
        </div>

        <div class="footer">
            <p><strong>CONSEJO SOCIAL DE VEEDURÍA Y DESARROLLO TERRITORIAL</strong></p>
            <p>Este certificado es válido y puede ser verificado en nuestro sistema</p>
            <p>Fecha de emisión: {{ now()->format('d/m/Y H:i:s') }}</p>
            <p>Documento generado automáticamente por el sistema CSDT</p>
        </div>
    </div>
</body>
</html>
