<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alertas del Sistema CSDT</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #1e40af; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background-color: #f8fafc; padding: 20px; border: 1px solid #e2e8f0; }
        .alert { margin: 10px 0; padding: 15px; border-radius: 6px; border-left: 4px solid; }
        .alert.critico { background-color: #fef2f2; border-left-color: #dc2626; }
        .alert.advertencia { background-color: #fefce8; border-left-color: #d97706; }
        .footer { background-color: #1e40af; color: white; padding: 15px; text-align: center; border-radius: 0 0 8px 8px; font-size: 12px; }
        .stats { display: flex; justify-content: space-around; margin: 20px 0; }
        .stat { text-align: center; padding: 10px; background-color: white; border-radius: 6px; border: 1px solid #e2e8f0; }
        .stat-number { font-size: 24px; font-weight: bold; color: #1e40af; }
        .stat-label { font-size: 12px; color: #64748b; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚨 Alertas del Sistema CSDT</h1>
            <p>CONSEJO SOCIAL DE VEEDURÍA Y DESARROLLO TERRITORIAL</p>
        </div>

        <div class="content">
            <p>Estimado administrador,</p>
            <p>Se han detectado las siguientes alertas en el sistema CSDT:</p>

            <!-- Estadísticas -->
            <div class="stats">
                <div class="stat">
                    <div class="stat-number">{{ $totalCriticas }}</div>
                    <div class="stat-label">Alertas Críticas</div>
                </div>
                <div class="stat">
                    <div class="stat-number">{{ $totalAdvertencias }}</div>
                    <div class="stat-label">Advertencias</div>
                </div>
                <div class="stat">
                    <div class="stat-number">{{ $totalCriticas + $totalAdvertencias }}</div>
                    <div class="stat-label">Total Alertas</div>
                </div>
            </div>

            <!-- Alertas Críticas -->
            @if(count($alertas['criticas']) > 0)
                <h3 style="color: #dc2626;">🔴 Alertas Críticas</h3>
                @foreach($alertas['criticas'] as $alerta)
                    <div class="alert critico">
                        <h4>{{ $alerta['titulo'] }}</h4>
                        <p>{{ $alerta['mensaje'] }}</p>
                        <p><strong>Acción requerida:</strong> {{ $alerta['accion'] }}</p>
                    </div>
                @endforeach
            @endif

            <!-- Alertas de Advertencia -->
            @if(count($alertas['advertencias']) > 0)
                <h3 style="color: #d97706;">🟡 Advertencias</h3>
                @foreach($alertas['advertencias'] as $alerta)
                    <div class="alert advertencia">
                        <h4>{{ $alerta['titulo'] }}</h4>
                        <p>{{ $alerta['mensaje'] }}</p>
                        <p><strong>Acción sugerida:</strong> {{ $alerta['accion'] }}</p>
                    </div>
                @endforeach
            @endif

            <p>Esta es una notificación automática del sistema CSDT. Por favor, revise estas alertas y tome las acciones necesarias.</p>

            <p>Fecha de generación: {{ $fecha }}</p>
        </div>

        <div class="footer">
            <p><strong>CONSEJO SOCIAL DE VEEDURÍA Y DESARROLLO TERRITORIAL</strong></p>
            <p>Sistema de Gestión Territorial • Versión 1.0</p>
            <p>Esta es una notificación automática, por favor no responda este email.</p>
        </div>
    </div>
</body>
</html>
