# Reset del Sistema - Documentación

## 🚨 FUNCIÓN DE LIMPIEZA DE DATOS DEMO

### Descripción
Esta funcionalidad permite a los administradores eliminar todos los datos de demostración del sistema para entregarlo completamente limpio al cliente final.

## 🔐 Requisitos de Acceso
- **Rol requerido**: Administrador (`admin`)
- **Permiso específico**: `sistema_eliminar`
- **Acceso**: Solo a través de Configuración → Sistema

## 📋 ¿Qué se Elimina?

### ✗ Datos que SE ELIMINARÁN (PERMANENTE):
- ✗ Todos los productos demo
- ✗ Todos los inventarios registrados  
- ✗ Todas las ventas registradas
- ✗ Todos los vendedores demo
- ✗ Todas las boletas subidas
- ✗ Todas las reparaciones
- ✗ Todos los movimientos de inventario
- ✗ Usuarios demo (excepto administrador actual)
- ✗ Tiendas demo (excepto una tienda principal)

### ✓ Datos que SE PRESERVAN:
- ✓ Usuario administrador actual
- ✓ Configuración del sistema (moneda, empresa, etc.)
- ✓ Roles y permisos del sistema
- ✓ Estructura completa de la base de datos
- ✓ Configuración de moneda (Quetzal Guatemalteco)
- ✓ Temas del sistema
- ✓ Una tienda principal limpia

## 🛡️ Medidas de Seguridad

### Verificaciones Múltiples:
1. **Verificación de rol**: Solo administradores pueden acceder
2. **Confirmación textual**: Debe escribir exactamente "CONFIRMAR LIMPIEZA"
3. **Checkboxes de confirmación**: Debe marcar que entiende las consecuencias
4. **Confirmación final**: Dialog de JavaScript con última oportunidad
5. **Transacciones de BD**: Rollback automático si hay errores

### Logging:
- Todas las ejecuciones se registran en el log del sistema
- Incluye ID de usuario y email del administrador que ejecutó la acción

## 📁 Archivos Relacionados

### Archivos Principales:
- `admin_reset_sistema.php` - Interfaz principal de reset
- `limpiar_datos_demo.sql` - Script SQL de limpieza
- `agregar_permiso_reset_sistema.sql` - Configuración de permisos

### Configuración en Base de Datos:
```sql
-- Permiso requerido
INSERT INTO permisos (nombre, descripcion, modulo) VALUES
('sistema_reset', 'Limpiar datos demo del sistema', 'sistema');

-- Asignado solo a rol admin
INSERT INTO rol_permisos (rol_id, permiso_id, puede_eliminar) VALUES
(1, [permiso_id], 1);
```

## 🚀 Instrucciones de Uso

### Para el Desarrollador:
1. **Configurar permisos** (solo primera vez):
   ```bash
   mysql -u root -p inventario_sistema < agregar_permiso_reset_sistema.sql
   ```

### Para el Administrador:
1. **Acceder al sistema** como administrador
2. **Ir a**: Configuración → Sistema
3. **Hacer clic en**: "Acceder a Reset del Sistema"
4. **Leer las advertencias** cuidadosamente
5. **Escribir**: "CONFIRMAR LIMPIEZA" (exacto)
6. **Marcar** las casillas de confirmación
7. **Hacer clic**: "EJECUTAR LIMPIEZA"
8. **Confirmar** en el diálogo final

## ⚠️ Recomendaciones Importantes

### Antes de Ejecutar:
- ✅ **Hacer backup** de la base de datos si es necesario
- ✅ **Verificar** que es el momento correcto
- ✅ **Confirmar** que no hay datos importantes
- ✅ **Avisar** a otros usuarios del sistema

### Después de Ejecutar:
- ✅ **Verificar** que el sistema funciona correctamente
- ✅ **Configurar** la información de la empresa
- ✅ **Crear** las tiendas necesarias
- ✅ **Crear** usuarios reales del sistema

## 🎯 Casos de Uso Típicos

### 1. Entrega a Cliente:
- Sistema desarrollado con datos demo
- Cliente listo para usar el sistema
- Necesita datos completamente limpios

### 2. Reinstalación:
- Sistema con datos de prueba
- Necesita volver a empezar limpio
- Mantener configuración actual

### 3. Migración:
- Cambio de servidor o instalación
- Mantener estructura pero limpiar datos
- Preservar configuraciones específicas

## 🔧 Aspectos Técnicos

### Transacciones:
- Todo el proceso se ejecuta en una sola transacción
- Si hay error, se revierte automáticamente
- Garantiza integridad de datos

### Foreign Keys:
- Se deshabilitan temporalmente para la limpieza
- Se rehabilitan al final del proceso
- Mantiene integridad referencial

### Auto Increment:
- Los contadores se reinician a 1
- Los nuevos registros empezarán desde ID 1
- Optimiza el uso de IDs

### Performance:
- Proceso optimizado con DELETE directo
- Sin verificaciones registro por registro
- Ejecución rápida incluso con muchos datos

## 📞 Soporte

### Si algo sale mal:
1. **Revisar logs** del servidor web
2. **Verificar** que la base de datos esté íntegra
3. **Restaurar backup** si es necesario
4. **Contactar** al desarrollador del sistema

### Archivos de log:
- PHP: `/var/log/apache2/error.log` o `/opt/lampp/logs/error_log`
- Sistema: Se registra automáticamente vía `error_log()`

---

**⚠️ IMPORTANTE**: Esta función es destructiva y permanente. Una vez ejecutada, los datos eliminados NO se pueden recuperar sin un backup previo.