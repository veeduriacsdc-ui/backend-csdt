<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Agregar índices faltantes a la tabla PQRSFD solo si no existen
        if (!Schema::hasIndex('PQRSFD', 'pqrsfd_tipopqrsfd_estado_index')) {
            Schema::table('PQRSFD', function (Blueprint $table) {
                $table->index(['TipoPQRSFD', 'Estado']);
            });
        }
        
        if (!Schema::hasIndex('PQRSFD', 'pqrsfd_idoperadorasignado_estado_index')) {
            Schema::table('PQRSFD', function (Blueprint $table) {
                $table->index(['IdOperadorAsignado', 'Estado']);
            });
        }
        
        if (!Schema::hasIndex('PQRSFD', 'pqrsfd_fecharegistro_index')) {
            Schema::table('PQRSFD', function (Blueprint $table) {
                $table->index(['FechaRegistro']);
            });
        }
        
        if (!Schema::hasIndex('PQRSFD', 'pqrsfd_numeroradicacion_index')) {
            Schema::table('PQRSFD', function (Blueprint $table) {
                $table->index(['NumeroRadicacion']);
            });
        }

        // Agregar índices faltantes a la tabla Donaciones solo si no existen
        if (!Schema::hasIndex('Donaciones', 'donaciones_estado_fechadonacion_index')) {
            Schema::table('Donaciones', function (Blueprint $table) {
                $table->index(['Estado', 'FechaDonacion']);
            });
        }
        
        if (!Schema::hasIndex('Donaciones', 'donaciones_idcliente_estado_index')) {
            Schema::table('Donaciones', function (Blueprint $table) {
                $table->index(['IdCliente', 'Estado']);
            });
        }
        
        if (!Schema::hasIndex('Donaciones', 'donaciones_validadaporoperador_estado_index')) {
            Schema::table('Donaciones', function (Blueprint $table) {
                $table->index(['ValidadaPorOperador', 'Estado']);
            });
        }
        
        if (!Schema::hasIndex('Donaciones', 'donaciones_moneda_estado_index')) {
            Schema::table('Donaciones', function (Blueprint $table) {
                $table->index(['Moneda', 'Estado']);
            });
        }

        // Agregar índices faltantes a la tabla ActividadesCaso solo si no existen
        if (!Schema::hasIndex('ActividadesCaso', 'actividadescaso_estado_prioridad_index')) {
            Schema::table('ActividadesCaso', function (Blueprint $table) {
                $table->index(['Estado', 'Prioridad']);
            });
        }
        
        if (!Schema::hasIndex('ActividadesCaso', 'actividadescaso_fechainicioestimada_fechafinestimada_index')) {
            Schema::table('ActividadesCaso', function (Blueprint $table) {
                $table->index(['FechaInicioEstimada', 'FechaFinEstimada']);
            });
        }

        // Agregar índices faltantes a la tabla Operadores solo si no existen
        if (!Schema::hasIndex('Operadores', 'operadores_estado_rol_index')) {
            Schema::table('Operadores', function (Blueprint $table) {
                $table->index(['Estado', 'Rol']);
            });
        }
        
        if (!Schema::hasIndex('Operadores', 'operadores_profesion_estado_index')) {
            Schema::table('Operadores', function (Blueprint $table) {
                $table->index(['Profesion', 'Estado']);
            });
        }
        
        if (!Schema::hasIndex('Operadores', 'operadores_anosexperiencia_estado_index')) {
            Schema::table('Operadores', function (Blueprint $table) {
                $table->index(['AnosExperiencia', 'Estado']);
            });
        }

        // Agregar índices faltantes a la tabla Clientes solo si no existen
        if (!Schema::hasIndex('Clientes', 'clientes_estado_correoverificado_index')) {
            Schema::table('Clientes', function (Blueprint $table) {
                $table->index(['Estado', 'CorreoVerificado']);
            });
        }
        
        if (!Schema::hasIndex('Clientes', 'clientes_fechanacimiento_index')) {
            Schema::table('Clientes', function (Blueprint $table) {
                $table->index(['FechaNacimiento']);
            });
        }
        
        if (!Schema::hasIndex('Clientes', 'clientes_genero_estado_index')) {
            Schema::table('Clientes', function (Blueprint $table) {
                $table->index(['Genero', 'Estado']);
            });
        }

        // Agregar índices faltantes a la tabla NotificacionesSistema solo si no existen
        if (!Schema::hasIndex('NotificacionesSistema', 'notificacionessistema_tipo_prioridad_index')) {
            Schema::table('NotificacionesSistema', function (Blueprint $table) {
                $table->index(['Tipo', 'Prioridad']);
            });
        }
        
        if (!Schema::hasIndex('NotificacionesSistema', 'notificacionessistema_estado_fechacreacion_index')) {
            Schema::table('NotificacionesSistema', function (Blueprint $table) {
                $table->index(['Estado', 'FechaCreacion']);
            });
        }
        
        if (!Schema::hasIndex('NotificacionesSistema', 'notificacionessistema_destinatario_tipo_index')) {
            Schema::table('NotificacionesSistema', function (Blueprint $table) {
                $table->index(['Destinatario', 'Tipo']);
            });
        }

        // Agregar índices faltantes a la tabla LogsSistema solo si no existen
        if (!Schema::hasIndex('LogsSistema', 'logssistema_nivel_categoria_index')) {
            Schema::table('LogsSistema', function (Blueprint $table) {
                $table->index(['Nivel', 'Categoria']);
            });
        }
        
        if (!Schema::hasIndex('LogsSistema', 'logssistema_usuario_tipousuario_index')) {
            Schema::table('LogsSistema', function (Blueprint $table) {
                $table->index(['Usuario', 'TipoUsuario']);
            });
        }
        
        if (!Schema::hasIndex('LogsSistema', 'logssistema_direccionip_fechacreacion_index')) {
            Schema::table('LogsSistema', function (Blueprint $table) {
                $table->index(['DireccionIP', 'FechaCreacion']);
            });
        }

        // Agregar índices faltantes a la tabla ReportesSistema solo si no existen
        if (!Schema::hasIndex('ReportesSistema', 'reportessistema_tipo_formato_index')) {
            Schema::table('ReportesSistema', function (Blueprint $table) {
                $table->index(['Tipo', 'Formato']);
            });
        }
        
        if (!Schema::hasIndex('ReportesSistema', 'reportessistema_estado_tipo_index')) {
            Schema::table('ReportesSistema', function (Blueprint $table) {
                $table->index(['Estado', 'Tipo']);
            });
        }
        
        if (!Schema::hasIndex('ReportesSistema', 'reportessistema_fechasolicitud_estado_index')) {
            Schema::table('ReportesSistema', function (Blueprint $table) {
                $table->index(['FechaSolicitud', 'Estado']);
            });
        }
    }

    public function down(): void
    {
        // Remover índices de PQRSFD
        Schema::table('PQRSFD', function (Blueprint $table) {
            $table->dropIndex(['TipoPQRSFD', 'Estado']);
            $table->dropIndex(['IdOperadorAsignado', 'Estado']);
            $table->dropIndex(['FechaRegistro']);
            $table->dropIndex(['NumeroRadicacion']);
        });

        // Remover índices de Donaciones
        Schema::table('Donaciones', function (Blueprint $table) {
            $table->dropIndex(['Estado', 'FechaDonacion']);
            $table->dropIndex(['IdCliente', 'Estado']);
            // Se elimina IdPQRSFDAsociado de aquí ya que Donaciones no tiene esa relación
            $table->dropIndex(['ValidadaPorOperador', 'Estado']);
            $table->dropIndex(['Moneda', 'Estado']);
        });

        // Remover índices de ActividadesCaso
        Schema::table('ActividadesCaso', function (Blueprint $table) {
            $table->dropIndex(['Estado', 'Prioridad']);
            $table->dropIndex(['FechaInicioEstimada', 'FechaFinEstimada']);
        });

        // Remover índices de Operadores
        Schema::table('Operadores', function (Blueprint $table) {
            $table->dropIndex(['Estado', 'Rol']);
            $table->dropIndex(['Profesion', 'Estado']);
            $table->dropIndex(['AnosExperiencia', 'Estado']);
        });

        // Remover índices de Clientes
        Schema::table('Clientes', function (Blueprint $table) {
            $table->dropIndex(['Estado', 'CorreoVerificado']);
            $table->dropIndex(['FechaNacimiento']);
            $table->dropIndex(['Genero', 'Estado']);
        });

        // Remover índices de NotificacionesSistema
        Schema::table('NotificacionesSistema', function (Blueprint $table) {
            $table->dropIndex(['Tipo', 'Prioridad']);
            $table->dropIndex(['Estado', 'FechaCreacion']);
            $table->dropIndex(['Destinatario', 'Tipo']);
        });

        // Remover índices de LogsSistema
        Schema::table('LogsSistema', function (Blueprint $table) {
            $table->dropIndex(['Nivel', 'Categoria']);
            $table->dropIndex(['Usuario', 'TipoUsuario']);
            $table->dropIndex(['DireccionIP', 'FechaCreacion']); // Renombrado
        });

        // Remover índices de ReportesSistema
        Schema::table('ReportesSistema', function (Blueprint $table) {
            $table->dropIndex(['Tipo', 'Formato']);
            $table->dropIndex(['Estado', 'Tipo']);
            $table->dropIndex(['FechaSolicitud', 'Estado']);
        });
    }
};
