<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Configuracion extends Model
{
    use HasFactory;

    protected $table = 'configuraciones';
    
    protected $fillable = [
        'cla', 'val', 'des', 'cat', 'tip', 'est'
    ];

    // Scopes
    public function scopeActivas($query)
    {
        return $query->where('est', 'act');
    }

    public function scopePorCategoria($query, $categoria)
    {
        return $query->where('cat', $categoria);
    }

    public function scopePorTipo($query, $tipo)
    {
        return $query->where('tip', $tipo);
    }

    // Métodos de negocio
    public function getEstadoColorAttribute()
    {
        return match ($this->est) {
            'act' => 'success',
            'ina' => 'danger',
            default => 'secondary'
        };
    }

    public function getEstadoTextoAttribute()
    {
        return match ($this->est) {
            'act' => 'Activo',
            'ina' => 'Inactivo',
            default => 'Desconocido'
        };
    }

    public function getTipoTextoAttribute()
    {
        return match ($this->tip) {
            'str' => 'Texto',
            'int' => 'Número Entero',
            'bool' => 'Verdadero/Falso',
            'json' => 'JSON',
            'dec' => 'Número Decimal',
            default => 'Desconocido'
        };
    }

    public function activar()
    {
        $this->update(['est' => 'act']);
    }

    public function desactivar()
    {
        $this->update(['est' => 'ina']);
    }

    public function getValorFormateadoAttribute()
    {
        switch ($this->tip) {
            case 'bool':
                return filter_var($this->val, FILTER_VALIDATE_BOOLEAN);
            case 'int':
                return (int) $this->val;
            case 'dec':
                return (float) $this->val;
            case 'json':
                return json_decode($this->val, true);
            default:
                return $this->val;
        }
    }

    public function setValorFormateado($valor)
    {
        switch ($this->tip) {
            case 'bool':
                $this->val = $valor ? '1' : '0';
                break;
            case 'int':
                $this->val = (string) (int) $valor;
                break;
            case 'dec':
                $this->val = (string) (float) $valor;
                break;
            case 'json':
                $this->val = json_encode($valor);
                break;
            default:
                $this->val = (string) $valor;
        }
        $this->save();
    }

    // Métodos estáticos para obtener configuraciones
    public static function obtener($clave, $valorPorDefecto = null)
    {
        $config = static::where('cla', $clave)->where('est', 'act')->first();
        
        if (!$config) {
            return $valorPorDefecto;
        }
        
        return $config->valor_formateado;
    }

    public static function establecer($clave, $valor, $descripcion = null, $categoria = null, $tipo = 'str')
    {
        $config = static::where('cla', $clave)->first();
        
        if (!$config) {
            $config = new static();
            $config->cla = $clave;
            $config->des = $descripcion;
            $config->cat = $categoria;
            $config->tip = $tipo;
            $config->est = 'act';
        }
        
        $config->setValorFormateado($valor);
        return $config;
    }

    // Validaciones
    public static function reglas($id = null)
    {
        $uniqueClave = 'unique:configuraciones,cla';
        if ($id) {
            $uniqueClave .= ','.$id.',id';
        }

        return [
            'cla' => 'required|string|max:100|'.$uniqueClave,
            'val' => 'required|string',
            'des' => 'nullable|string|max:255',
            'cat' => 'nullable|string|max:100',
            'tip' => 'required|in:str,int,bool,json,dec',
            'est' => 'sometimes|in:act,ina',
        ];
    }

    public static function mensajes()
    {
        return [
            'cla.required' => 'La clave es obligatoria.',
            'cla.max' => 'La clave no puede exceder 100 caracteres.',
            'cla.unique' => 'Esta clave ya existe.',
            'val.required' => 'El valor es obligatorio.',
            'des.max' => 'La descripción no puede exceder 255 caracteres.',
            'cat.max' => 'La categoría no puede exceder 100 caracteres.',
            'tip.required' => 'El tipo es obligatorio.',
            'tip.in' => 'El tipo debe ser válido.',
            'est.in' => 'El estado debe ser activo o inactivo.',
        ];
    }
}