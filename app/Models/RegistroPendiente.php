<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class RegistroPendiente extends Model
{
    use HasFactory;

    protected $table = 'registros_pendientes';

    protected $fillable = [
        'nombre',
        'email',
        'telefono',
        'documento_identidad',
        'tipo_documento',
        'rol_solicitado',
        'motivacion',
        'experiencia',
        'estado',
        'observaciones_admin',
        'aprobado_por',
        'fecha_aprobacion',
        'token_verificacion',
        'email_verificado_at'
    ];

    protected $casts = [
        'email_verificado_at' => 'datetime',
        'fecha_aprobacion' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Relación con el usuario que aprobó el registro
     */
    public function aprobadoPor(): BelongsTo
    {
        return $this->belongsTo(UsuarioSistema::class, 'aprobado_por');
    }

    /**
     * Generar token de verificación único
     */
    public static function generarTokenVerificacion(): string
    {
        do {
            $token = Str::random(60);
        } while (self::where('token_verificacion', $token)->exists());

        return $token;
    }

    /**
     * Verificar si el registro requiere aprobación
     */
    public function requiereAprobacion(): bool
    {
        return in_array($this->rol_solicitado, ['operador', 'administrador']);
    }

    /**
     * Verificar si el email ya está verificado
     */
    public function emailVerificado(): bool
    {
        return !is_null($this->email_verificado_at);
    }

    /**
     * Marcar email como verificado
     */
    public function marcarEmailVerificado(): void
    {
        $this->update(['email_verificado_at' => now()]);
    }

    /**
     * Aprobar registro
     */
    public function aprobar(int $aprobadoPor, ?string $observaciones = null): void
    {
        $this->update([
            'estado' => 'aprobado',
            'aprobado_por' => $aprobadoPor,
            'fecha_aprobacion' => now(),
            'observaciones_admin' => $observaciones
        ]);
    }

    /**
     * Rechazar registro
     */
    public function rechazar(int $aprobadoPor, ?string $observaciones = null): void
    {
        $this->update([
            'estado' => 'rechazado',
            'aprobado_por' => $aprobadoPor,
            'fecha_aprobacion' => now(),
            'observaciones_admin' => $observaciones
        ]);
    }

    /**
     * Scope para registros pendientes
     */
    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }

    /**
     * Scope para registros por rol
     */
    public function scopePorRol($query, string $rol)
    {
        return $query->where('rol_solicitado', $rol);
    }

    /**
     * Scope para registros verificados
     */
    public function scopeVerificados($query)
    {
        return $query->whereNotNull('email_verificado_at');
    }
}
