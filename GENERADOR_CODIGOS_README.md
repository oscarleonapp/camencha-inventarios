# Sistema de Generación Automática de Códigos

## Descripción

El sistema de generación automática de códigos proporciona una solución centralizada para crear códigos únicos para todas las entidades del sistema de inventario. Este sistema elimina la necesidad de especificar códigos manualmente y previene conflictos en la base de datos.

## Características Principales

### ✅ Implementado
- **Generación automática** de códigos únicos para productos
- **Prevención de conflictos** mediante validación en base de datos
- **Formato consistente** con patrón configurable
- **Trazabilidad temporal** con año incluido
- **Tipos diferenciados** para elementos y conjuntos
- **Validación de formato** para códigos existentes
- **Estadísticas de uso** por tipo de entidad
- **Migración de códigos** existentes al nuevo formato

### 🔄 En Progreso
- Aplicación a otros módulos del sistema (ventas, usuarios, etc.)

### 📋 Pendiente
- Configuración de patrones por usuario/empresa
- Interfaz de administración de patrones

## Arquitectura

### Clase Principal: `CodigoGenerator`

**Ubicación:** `includes/codigo_generator.php`

```php
class CodigoGenerator {
    // Patrones de códigos por tipo de entidad
    private $patrones = [
        'producto' => ['prefijo' => 'PROD', 'longitud' => 8],
        'conjunto' => ['prefijo' => 'CNJ', 'longitud' => 8],
        'venta' => ['prefijo' => 'VTA', 'longitud' => 10],
        // ... más tipos
    ];
}
```

### Métodos Principales

#### `generarCodigo($tipo_entidad, $parametros = [])`
Genera un código único para el tipo de entidad especificado.

**Parámetros:**
- `$tipo_entidad`: Tipo de entidad ('producto', 'conjunto', 'venta', etc.)
- `$parametros`: Array opcional con configuración personalizada
  - `prefijo`: Prefijo personalizado
  - `longitud`: Longitud del número secuencial
  - `incluir_fecha`: Si incluir el año en el código

**Retorna:** String con el código generado

**Ejemplo:**
```php
$codigo = $codigoGenerator->generarCodigo('producto');
// Resultado: "PROD-2025-00000001"
```

#### `generarCodigosLote($tipo_entidad, $cantidad, $parametros = [])`
Genera múltiples códigos de una vez.

#### `validarFormatoCodigo($codigo, $tipo_entidad)`
Valida si un código cumple con el formato esperado.

#### `obtenerEstadisticas($tipo_entidad = null)`
Obtiene estadísticas de códigos generados.

#### `migrarCodigosExistentes($tipo_entidad, $dry_run = true)`
Migra códigos existentes al nuevo formato.

## Patrones de Códigos

### Formato General
```
PREFIJO-YYYY-NNNNNNNN
```

- **PREFIJO**: Identificador del tipo de entidad (3-4 caracteres)
- **YYYY**: Año actual (4 dígitos)
- **NNNNNNNN**: Número secuencial con padding de ceros

### Patrones Configurados

| Tipo | Prefijo | Longitud | Ejemplo |
|------|---------|----------|---------|
| Producto | PROD | 8 | PROD-2025-00000001 |
| Conjunto | CNJ | 8 | CNJ-2025-00000001 |
| Venta | VTA | 10 | VTA-2025-0000000001 |
| Usuario | USR | 6 | USR-2025-000001 |
| Tienda | TDA | 4 | TDA-2025-0001 |
| Vendedor | VND | 6 | VND-2025-000001 |
| Reparación | REP | 8 | REP-2025-00000001 |
| Boleta | BOL | 8 | BOL-2025-00000001 |

## Integración en Productos

### Cambios Implementados

1. **productos.php**: Integración completa del generador
   - Código automático en creación de productos
   - Eliminación del campo manual de código
   - Mensaje de confirmación con código generado

2. **Formulario de Creación**:
   ```php
   // Generar código automáticamente basado en el tipo
   $tipo = $_POST['tipo'];
   $tipo_codigo = ($tipo === 'conjunto') ? 'conjunto' : 'producto';
   $codigo = $codigoGenerator->generarCodigo($tipo_codigo);
   ```

3. **Interfaz de Usuario**:
   - Alerta informativa sobre generación automática
   - Eliminación del campo de entrada manual
   - Código incluido en mensaje de éxito

## Scripts de Utilidad

### `test_codigo_generator.php`
Script de prueba para verificar el funcionamiento del generador.

**Funciones:**
- Generación de códigos individuales
- Generación en lote
- Validación de formatos
- Estadísticas de uso
- Pruebas con parámetros personalizados

**URL:** `http://localhost/inventario-claude/test_codigo_generator.php`

### `migrar_codigos.php`
Script para migrar códigos existentes al nuevo formato.

**Funciones:**
- Vista previa de migración (dry run)
- Migración real con confirmación
- Estadísticas antes y después
- Logging de cambios

**URL:** `http://localhost/inventario-claude/migrar_codigos.php`

## Configuración de Base de Datos

### Tablas Afectadas

#### Tabla: `productos`
- Campo `codigo`: Ahora se genera automáticamente
- Índice en `codigo` para búsquedas rápidas

#### Mapeo de Tipos a Tablas
```php
private function obtenerTablaParaTipo($tipo_entidad) {
    $tablas = [
        'producto' => 'productos',
        'conjunto' => 'productos',
        'venta' => 'ventas', 
        'usuario' => 'usuarios',
        'tienda' => 'tiendas',
        'vendedor' => 'vendedores',
        'reparacion' => 'reparaciones',
        'boleta' => 'boletas'
    ];
    return $tablas[$tipo_entidad] ?? 'productos';
}
```

## Uso en Desarrollo

### Inicialización
```php
require_once 'includes/codigo_generator.php';

$database = new Database();
$codigoGenerator = new CodigoGenerator($database->getConnection());
```

### Generación Simple
```php
// Generar código para producto
$codigo = $codigoGenerator->generarCodigo('producto');

// Generar código para conjunto
$codigo_conjunto = $codigoGenerator->generarCodigo('conjunto');
```

### Generación Personalizada
```php
$codigo_custom = $codigoGenerator->generarCodigo('producto', [
    'prefijo' => 'SPEC',
    'longitud' => 6,
    'incluir_fecha' => false
]);
// Resultado: "SPEC-000001"
```

### Validación
```php
$es_valido = $codigoGenerator->validarFormatoCodigo('PROD-2025-00000001', 'producto');
if (!$es_valido) {
    throw new Exception('Código inválido');
}
```

## Manejo de Errores

### Errores Comunes
1. **Tipo no soportado**: Cuando se intenta generar código para un tipo no configurado
2. **Límite de intentos**: Cuando no se puede generar código único después de 100 intentos
3. **Error de base de datos**: Problemas de conexión o permisos

### Logging
```php
getLogger()->info('codigo_generado', 'productos', 
    "Código generado: $codigo", [
        'tipo' => $tipo_entidad,
        'codigo' => $codigo
    ]
);
```

## Beneficios del Sistema

### Para Desarrolladores
- **Código limpio**: No más validaciones manuales de unicidad
- **Consistencia**: Formato estándar en todo el sistema
- **Flexibilidad**: Patrones configurables por tipo
- **Escalabilidad**: Fácil adición de nuevos tipos

### Para Usuarios
- **Simplicidad**: No necesidad de inventar códigos
- **Prevención de errores**: No más códigos duplicados
- **Trazabilidad**: Códigos que incluyen información temporal
- **Profesionalismo**: Formato consistente y ordenado

### Para el Sistema
- **Rendimiento**: Índices optimizados para búsquedas
- **Integridad**: Prevención de conflictos automática
- **Mantenimiento**: Centralización de lógica de códigos
- **Auditoría**: Trazabilidad completa de generación

## Próximos Pasos

### Corto Plazo
1. **Aplicar a ventas**: Integrar generador en sistema de ventas
2. **Aplicar a usuarios**: Códigos automáticos para nuevos usuarios
3. **Aplicar a tiendas**: Códigos para nuevas sucursales

### Mediano Plazo
1. **Interfaz de administración**: Panel para configurar patrones
2. **Reportes de códigos**: Dashboard de estadísticas de uso
3. **API REST**: Endpoints para generación externa

### Largo Plazo
1. **Configuración por empresa**: Patrones personalizables
2. **Códigos de barras**: Integración con generación de códigos de barras
3. **Sincronización externa**: Integración con sistemas ERP

## Troubleshooting

### Problema: "Tipo de entidad no soportado"
**Solución:** Agregar el tipo al array `$patrones` en `CodigoGenerator`

### Problema: "No se pudo generar código único"
**Solución:** Verificar que no hay duplicados en BD o aumentar longitud del código

### Problema: Códigos no secuenciales
**Solución:** Normal cuando hay múltiples usuarios, el sistema garantiza unicidad no secuencialidad exacta

## Contacto y Soporte

Para dudas sobre implementación o problemas con el generador:
1. Revisar logs en `includes/logger.php`
2. Ejecutar script de pruebas `test_codigo_generator.php`
3. Verificar permisos de base de datos
4. Consultar documentación en `CLAUDE.md`

---

**Versión:** 1.0  
**Fecha:** Agosto 2025  
**Estado:** ✅ Implementado y Funcional