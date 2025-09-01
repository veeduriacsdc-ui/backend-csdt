# Sistema de Autenticación Unificado - CONSEJO SOCIAL DE VEEDURÍA Y DESARROLLO TERRITORIAL

## 📋 Descripción General

El Sistema de Autenticación Unificado permite que clientes, operadores y administradores accedan al sistema usando las mismas credenciales, manteniendo la separación de roles y permisos sin modificar la estructura de la base de datos existente.

## 🏗️ Arquitectura del Sistema

### Modelos Principales

#### 1. Modelo `Sesion`
- **Ubicación**: `app/Models/Sesion.php`
- **Propósito**: Gestiona todas las sesiones de usuario del sistema
- **Características**:
  - Autenticación unificada para todos los tipos de usuario
  - Gestión de roles y permisos
  - Control de expiración de sesiones
  - Logging de actividad

#### 2. Modelo `Cliente`
- **Ubicación**: `app/Models/Cliente.php`
- **Propósito**: Gestiona usuarios del tipo cliente
- **Características**:
  - Registro público
  - Acceso a veedurías y donaciones
  - Rol fijo: `cliente`

#### 3. Modelo `Operador`
- **Ubicación**: `app/Models/Operador.php`
- **Propósito**: Gestiona usuarios del tipo operador (incluye veedores y administradores)
- **Características**:
  - Roles: `operador` (veedor) y `administrador`
  - Niveles de acceso del 1 al 5
  - Permisos granulares
  - Jerarquía de supervisión

### Controladores

#### 1. `SesionControlador`
- **Ubicación**: `app/Http/Controllers/Api/SesionControlador.php`
- **Endpoints**:
  - `POST /api/sesion/iniciar` - Inicio de sesión unificado
  - `POST /api/sesion/registrar` - Registro de usuarios
  - `POST /api/sesion/cerrar` - Cierre de sesión
  - `POST /api/sesion/renovar` - Renovación de sesión
  - `POST /api/sesion/recuperar-contrasena` - Recuperación de contraseña
  - `POST /api/sesion/cambiar-contrasena` - Cambio de contraseña
  - `GET /api/sesion/info` - Información de sesión actual
  - `GET /api/sesion/estadisticas` - Estadísticas de sesiones
  - `POST /api/sesion/crear-admin-inicial` - Crear administrador inicial

#### 2. `GestionUsuariosControlador`
- **Ubicación**: `app/Http/Controllers/Api/GestionUsuariosControlador.php`
- **Endpoints** (solo administradores):
  - `GET /api/gestion-usuarios/` - Lista de usuarios
  - `GET /api/gestion-usuarios/estadisticas` - Estadísticas de usuarios
  - `GET /api/gestion-usuarios/{id}` - Detalles de usuario
  - `PUT /api/gestion-usuarios/{id}/rol` - Cambiar rol de usuario
  - `PUT /api/gestion-usuarios/{id}/estado` - Cambiar estado de usuario
  - `DELETE /api/gestion-usuarios/{id}` - Eliminar usuario
  - `POST /api/gestion-usuarios/{id}/restaurar` - Restaurar usuario

### Middleware

#### `VerificarRol`
- **Ubicación**: `app/Http/Middleware/VerificarRol.php`
- **Propósito**: Verifica autenticación y autorización basada en roles
- **Características**:
  - Verificación de token de autenticación
  - Validación de sesión activa
  - Control de acceso basado en roles
  - Verificación de permisos específicos
  - Logging de intentos de acceso denegado

## 🔐 Sistema de Roles y Permisos

### Jerarquía de Roles

```
Administrador (Nivel 5)
    ↓
Operador/Veedor (Nivel 1-4)
    ↓
Cliente (Nivel 1)
```

### Permisos por Rol

#### Administrador
- ✅ Gestión completa de usuarios
- ✅ Asignación de roles
- ✅ Gestión del sistema
- ✅ Acceso a logs de auditoría
- ✅ Exportación de datos
- ✅ Configuración del sistema

#### Operador/Veedor
- ✅ Gestión de veedurías asignadas
- ✅ Gestión de tareas asignadas
- ✅ Validación de donaciones
- ✅ Subida y gestión de archivos
- ✅ Generación de reportes básicos

#### Cliente
- ✅ Crear veedurías
- ✅ Realizar donaciones
- ✅ Subir archivos personales
- ✅ Ver reportes personales
- ✅ Acceso a CONSEJOIA

### Niveles de Acceso

| Nivel | Descripción | Permisos |
|-------|-------------|----------|
| 1 | Básico | Acceso mínimo al sistema |
| 2 | Estándar | Acceso a funcionalidades básicas |
| 3 | Intermedio | Acceso a funcionalidades avanzadas |
| 4 | Avanzado | Acceso a funcionalidades especializadas |
| 5 | Administrador | Acceso completo al sistema |

## 🚀 Implementación

### 1. Instalación y Configuración

```bash
# Ejecutar migraciones
php artisan migrate

# Crear administrador inicial
php artisan admin:crear

# O usar el seeder
php artisan db:seed --class=AdministradorInicialSeeder
```

### 2. Uso del Sistema

#### Inicio de Sesión
```php
// Ejemplo de inicio de sesión
$response = Http::post('/api/sesion/iniciar', [
    'correo_electronico' => 'usuario@ejemplo.com',
    'contrasena' => 'contraseña123'
]);

$token = $response->json('data.token');
```

#### Verificación de Rol
```php
// En rutas protegidas
Route::middleware(['auth:sanctum', 'verificar.rol:administrador'])->group(function () {
    // Rutas solo para administradores
});

Route::middleware(['auth:sanctum', 'verificar.rol:operador'])->group(function () {
    // Rutas para operadores y administradores
});
```

### 3. Comandos Artisan Disponibles

```bash
# Crear administrador inicial
php artisan admin:crear

# Con opciones personalizadas
php artisan admin:crear --email=admin@ejemplo.com --password=NuevaClave123!

# Ver todos los comandos disponibles
php artisan list
```

## 📊 Logs de Auditoría

### Modelo `LogAuditoria`
- **Propósito**: Registrar todas las acciones del sistema
- **Características**:
  - Tracking de cambios en usuarios
  - Logging de accesos al sistema
  - Registro de acciones administrativas
  - Niveles de severidad configurables

### Tipos de Acciones Registradas
- Creación, actualización y eliminación de usuarios
- Cambios de rol y estado
- Inicios y cierres de sesión
- Acciones administrativas
- Errores del sistema

## 🔒 Seguridad

### Medidas Implementadas
- **Autenticación**: Tokens JWT con Laravel Sanctum
- **Autorización**: Control de acceso basado en roles (RBAC)
- **Validación**: Validación robusta de datos de entrada
- **Logging**: Auditoría completa de todas las acciones
- **Encriptación**: Contraseñas hasheadas con bcrypt
- **Sesiones**: Control de expiración y renovación automática

### Mejores Prácticas
- Cambiar contraseña después del primer acceso
- Usar contraseñas fuertes (mínimo 8 caracteres)
- No compartir credenciales
- Cerrar sesión al terminar
- Revisar logs de auditoría regularmente

## 🧪 Testing

### Pruebas Unitarias
```bash
# Ejecutar todas las pruebas
php artisan test

# Ejecutar pruebas específicas
php artisan test --filter=SesionTest
php artisan test --filter=GestionUsuariosTest
```

### Pruebas de Integración
- Verificación de flujos de autenticación
- Validación de permisos por rol
- Testing de endpoints protegidos
- Verificación de logs de auditoría

## 📝 API Documentation

### Autenticación
Todas las rutas protegidas requieren el header:
```
Authorization: Bearer {token}
```

### Respuestas Estándar
```json
{
    "success": true,
    "message": "Operación exitosa",
    "data": { ... },
    "timestamp": "2024-01-01T00:00:00.000000Z"
}
```

### Códigos de Error
- `401`: No autenticado
- `403`: Acceso denegado
- `404`: Recurso no encontrado
- `422`: Error de validación
- `500`: Error interno del servidor

## 🚨 Troubleshooting

### Problemas Comunes

#### 1. Error de Token Expirado
```bash
# Renovar sesión
POST /api/sesion/renovar
```

#### 2. Acceso Denegado
- Verificar que el usuario tenga el rol requerido
- Verificar que la sesión esté activa
- Revisar logs de auditoría

#### 3. Error de Base de Datos
- Verificar conexión a la base de datos
- Ejecutar migraciones si es necesario
- Verificar permisos de usuario de BD

### Logs y Debugging
```bash
# Ver logs de Laravel
tail -f storage/logs/laravel.log

# Ver logs de auditoría específicos
php artisan tinker
>>> App\Models\LogAuditoria::where('usuario_id', 1)->get();
```

## 🔄 Mantenimiento

### Tareas Programadas
```bash
# Limpiar sesiones expiradas (diario)
php artisan schedule:run

# Limpiar logs antiguos (semanal)
php artisan log:clean
```

### Backup y Restauración
```bash
# Backup de base de datos
php artisan backup:run

# Restaurar desde backup
php artisan backup:restore {backup_id}
```

## 📚 Referencias

- [Laravel Documentation](https://laravel.com/docs)
- [Laravel Sanctum](https://laravel.com/docs/sanctum)
- [Laravel Middleware](https://laravel.com/docs/middleware)
- [PHP Artisan Commands](https://laravel.com/docs/artisan)

## 🤝 Contribución

Para contribuir al sistema:
1. Crear una rama para tu feature
2. Implementar cambios siguiendo las convenciones
3. Agregar pruebas unitarias
4. Actualizar documentación
5. Crear pull request

## 📞 Soporte

Para soporte técnico:
- **Email**: soporte@consejoveeduria.com
- **Documentación**: `/docs/`
- **Issues**: Crear issue en el repositorio
- **Chat**: Canal de Slack del equipo

---

**Versión**: 1.0.0  
**Última Actualización**: Enero 2024  
**Mantenido por**: Equipo de Desarrollo CSVDT
