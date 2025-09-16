<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Configuracion extends Model
{
    use HasFactory;

    protected $table = 'configuraciones';

    protected $fillable = [
        'clave', 'nombre', 'descripcion', 'valor', 'tipo_valor', 'categoria',
        'subcategoria', 'es_publica', 'requiere_restart', 'opciones_validas',
        'valor_default', 'expresion_regular', 'usuario_modifico_type',
        'usuario_modifico_id', 'esta_activa', 'fecha_ultima_modificacion',
    ];

    protected $casts = [
        'es_publica' => 'boolean',
        'requiere_restart' => 'boolean',
        'esta_activa' => 'boolean',
        'opciones_validas' => 'array',
        'fecha_ultima_modificacion' => 'datetime',
    ];

    // Relaciones
    public function usuario_modifico(): MorphTo
    {
        return $this->morphTo('usuario_modifico', 'usuario_modifico_type', 'usuario_modifico_id');
    }

    // Scopes
    public function scopeActivas($query)
    {
        return $query->where('esta_activa', true);
    }

    public function scopePublicas($query)
    {
        return $query->where('es_publica', true);
    }

    public function scopePorCategoria($query, string $categoria)
    {
        return $query->where('categoria', $categoria);
    }

    public function scopePorSubcategoria($query, string $subcategoria)
    {
        return $query->where('subcategoria', $subcategoria);
    }

    public function scopeQueRequierenRestart($query)
    {
        return $query->where('requiere_restart', true);
    }

    public function scopeRecientes($query, int $dias = 30)
    {
        return $query->where('fecha_ultima_modificacion', '>=', now()->subDays($dias));
    }

    // Accessors
    public function getValorFormateadoAttribute()
    {
        switch ($this->tipo_valor) {
            case 'boolean':
                return $this->valor ? 'Sí' : 'No';
            case 'json':
            case 'array':
                return json_decode($this->valor, true);
            case 'integer':
                return (int) $this->valor;
            case 'float':
                return (float) $this->valor;
            default:
                return $this->valor;
        }
    }

    public function getCategoriaFormateadaAttribute(): string
    {
        $categorias = [
            'general' => 'General',
            'seguridad' => 'Seguridad',
            'rendimiento' => 'Rendimiento',
            'notificaciones' => 'Notificaciones',
            'archivos' => 'Archivos',
            'reportes' => 'Reportes',
            'api' => 'API',
            'sistema' => 'Sistema',
        ];

        return $categorias[$this->categoria] ?? ucfirst($this->categoria);
    }

    public function getTipoValorFormateadoAttribute(): string
    {
        $tipos = [
            'string' => 'Texto',
            'integer' => 'Número Entero',
            'float' => 'Número Decimal',
            'boolean' => 'Verdadero/Falso',
            'json' => 'JSON',
            'array' => 'Arreglo',
            'file' => 'Archivo',
        ];

        return $tipos[$this->tipo_valor] ?? ucfirst($this->tipo_valor);
    }

    public function getPuedeModificarAttribute(): bool
    {
        return $this->esta_activa;
    }

    // Métodos de negocio
    public function actualizarValor($nuevoValor, $usuarioId = null, $usuarioType = null)
    {
        $valorAnterior = $this->valor;

        $this->update([
            'valor' => $nuevoValor,
            'usuario_modifico_id' => $usuarioId,
            'usuario_modifico_type' => $usuarioType,
            'fecha_ultima_modificacion' => now(),
        ]);

        // Crear log de auditoría
        if ($usuarioId && $usuarioType) {
            LogAuditoria::crear([
                'usuario_id' => $usuarioId,
                'usuario_type' => $usuarioType,
                'accion' => 'actualizar_configuracion',
                'entidad' => 'configuracion',
                'entidad_id' => $this->id,
                'datos_anteriores' => ['valor' => $valorAnterior],
                'datos_nuevos' => ['valor' => $nuevoValor],
                'categoria_accion' => 'configuracion',
            ]);
        }

        return true;
    }

    public function activar()
    {
        return $this->update(['esta_activa' => true]);
    }

    public function desactivar()
    {
        return $this->update(['esta_activa' => false]);
    }

    public function validarValor($valor): bool
    {
        // Validar según el tipo
        switch ($this->tipo_valor) {
            case 'boolean':
                return in_array(strtolower($valor), ['true', 'false', '1', '0', 'si', 'no']);
            case 'integer':
                return is_numeric($valor) && intval($valor) == $valor;
            case 'float':
                return is_numeric($valor);
            case 'json':
            case 'array':
                json_decode($valor);
                return json_last_error() === JSON_ERROR_NONE;
        }

        // Validar expresión regular si existe
        if ($this->expresion_regular) {
            return preg_match($this->expresion_regular, $valor);
        }

        // Validar opciones válidas si existen
        if ($this->opciones_validas) {
            return in_array($valor, $this->opciones_validas);
        }

        return true;
    }

    // Métodos estáticos
    public static function obtenerValor(string $clave, $valorPorDefecto = null)
    {
        $configuracion = static::where('clave', $clave)->where('esta_activa', true)->first();

        if (!$configuracion) {
            return $valorPorDefecto;
        }

        return $configuracion->valor_formateado;
    }

    public static function establecerValor(string $clave, $valor, $usuarioId = null, $usuarioType = null)
    {
        $configuracion = static::where('clave', $clave)->first();

        if (!$configuracion) {
            return false;
        }

        if (!$configuracion->validarValor($valor)) {
            return false;
        }

        return $configuracion->actualizarValor($valor, $usuarioId, $usuarioType);
    }

    public static function crearConfiguracion(array $datos)
    {
        $configuracion = static::create($datos);

        // Crear log de auditoría
        if (isset($datos['usuario_modifico_id']) && isset($datos['usuario_modifico_type'])) {
            LogAuditoria::crear([
                'usuario_id' => $datos['usuario_modifico_id'],
                'usuario_type' => $datos['usuario_modifico_type'],
                'accion' => 'crear_configuracion',
                'entidad' => 'configuracion',
                'entidad_id' => $configuracion->id,
                'datos_nuevos' => $datos,
                'categoria_accion' => 'configuracion',
            ]);
        }

        return $configuracion;
    }

    // Validaciones
    public static function rules($id = null): array
    {
        $rules = [
            'clave' => 'required|string|unique:configuraciones,clave,' . $id,
            'nombre' => 'required|string|max:200',
            'descripcion' => 'nullable|string|max:1000',
            'tipo_valor' => 'required|in:string,integer,float,boolean,json,array,file',
            'categoria' => 'required|string|max:100',
            'subcategoria' => 'nullable|string|max:100',
            'es_publica' => 'boolean',
            'requiere_restart' => 'boolean',
            'opciones_validas' => 'nullable|array',
            'expresion_regular' => 'nullable|string|max:500',
            'esta_activa' => 'boolean',
        ];

        // Agregar validación del valor si existe
        if (isset($rules['tipo_valor'])) {
            $rules['valor'] = 'nullable';
        }

        return $rules;
    }

    // Eventos del modelo
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($configuracion) {
            if (empty($configuracion->esta_activa)) {
                $configuracion->esta_activa = true;
            }

            if (empty($configuracion->es_publica)) {
                $configuracion->es_publica = false;
            }

            if (empty($configuracion->requiere_restart)) {
                $configuracion->requiere_restart = false;
            }

            if (empty($configuracion->fecha_ultima_modificacion)) {
                $configuracion->fecha_ultima_modificacion = now();
            }
        });
    }
}