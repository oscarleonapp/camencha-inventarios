# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

# Sistema de Inventario - Información para Claude

## Descripción del Proyecto
Sistema de inventario completo desarrollado en PHP/MySQL para gestión de productos, ventas, inventarios multi-tienda, vendedores con comisiones, y sistema de reparaciones.

## Estructura del Proyecto

### Tecnologías
- **Backend**: PHP 8+ con PDO
- **Base de Datos**: MySQL/MariaDB
- **Frontend**: Bootstrap 5, jQuery, Font Awesome
- **Arquitectura**: MVC modificado con includes

### Configuración de Base de Datos
- **Nombre BD**: `inventario_sistema` (configurado en config/database.php)
- **Usuario**: `root` (desarrollo local)
- **Host**: `localhost`
- **Puerto**: 3306 (default)
- **Charset**: utf8mb4 (charset completo)
- **Archivo SQL**: `inventario_sistema.sql` (estructura completa disponible)

## Estructura de Archivos Principales

### Archivos de Configuración
- `config/database.php` - Conexión a base de datos
- `includes/auth.php` - Sistema de autenticación y permisos
- `includes/config_functions.php` - Funciones de configuración del sistema
- `includes/session_security.php` - Configuración segura de sesiones (NUEVO)
- `includes/csrf_protection.php` - Protección CSRF (NUEVO)
- `includes/security_headers.php` - Cabeceras de seguridad HTTP (NUEVO)
- `includes/codigo_generator.php` - Generador automático de códigos únicos (NUEVO)
- `includes/excel_importer.php` - Importador masivo Excel/CSV (NUEVO)
- `includes/qr_generator.php` - Generador de códigos QR (NUEVO)
- `includes/exportacion.php` - Sistema de exportación configurable (NUEVO)
- `includes/estilos_dinamicos.php` - Gestión de temas y branding (NUEVO)

### Módulos Principales
- `index.php` - Dashboard principal con estadísticas
- `productos.php` - Gestión de productos (elementos/conjuntos)
- `productos_simple.php` - Vista simplificada para móviles
- `inventarios.php` - Control de inventarios multi-tienda con búsqueda inteligente
- `ventas.php` - Procesamiento de ventas con vendedores
- `reportes_vendedores.php` - Reportes de comisiones (CREADO)
- `reparaciones.php` - Sistema de reparaciones (CREADO)
- `reparaciones_enviar.php` - Envío de productos a reparación (CREADO)
- `reparaciones_recibir.php` - Recepción de productos reparados (CREADO)
- `detalle_venta.php` - Detalle de venta con reembolsos (CREADO)
- `boletas.php` - Gestión de boletas con subida de imágenes (NUEVO)
- `importar_productos.php` - Importación masiva desde Excel/CSV (NUEVO)
- `qr_scan.php` - Escáner QR para inventarios (NUEVO)
- `qr_download.php` - Descarga masiva de códigos QR (NUEVO)
- `personalizacion_visual.php` - Configuración de temas y branding (NUEVO)
- `cotizaciones.php` - Sistema de cotizaciones (NUEVO)
- `compras.php` - Gestión de compras y órdenes (NUEVO)
- `exportar.php` - Exportación de datos configurada (NUEVO)
- `configuracion.php` - Panel de configuración del sistema (NUEVO)
- `lista_tiendas.php` - Lista de tiendas con eliminar confirmación (MEJORADO)
- `editar_tienda.php` - Editar datos de tiendas (MEJORADO)
- `roles.php` - Gestión de roles y permisos
- `login.php` - Autenticación de usuarios

### Includes de Layout
- `includes/layout_header.php` - Cabeceras HTML y configuración
- `includes/navbar.php` - Navegación principal
- `includes/sidebar.php` - Barra lateral (si existe)

## Base de Datos

### Tablas Principales
- `productos` - Catálogo de productos (elementos/conjuntos)
- `inventarios` - Stock por tienda y producto
- `ventas` - Registro de ventas con vendedor asignado
- `detalle_ventas` - Items de cada venta
- `usuarios` - Usuarios del sistema
- `tiendas` - Sucursales/ubicaciones
- `vendedores` - Vendedores con comisiones (AGREGADA)
- `comisiones_vendedores` - Cálculo de comisiones (AGREGADA)
- `reparaciones` - Productos enviados a reparación (AGREGADA)
- `boletas` - Boletas con imágenes subidas (NUEVA)

### Tablas de Configuración
- `configuraciones` - Configuración del sistema (AGREGADA)
- `etiquetas_personalizadas` - Textos personalizables (AGREGADA)
- `temas_sistema` - Temas visuales (AGREGADA)
- `roles` - Roles de usuarios
- `permisos` - Permisos del sistema
- `rol_permisos` - Relación roles-permisos

### Campos Importantes Agregados
- `ventas.vendedor_id` - FK a vendedores
- `inventarios.cantidad_reparacion` - Stock en reparación
- `roles.es_sistema` - Roles del sistema
- `permisos.orden` - Orden de visualización

## Sistema de Permisos

### Roles Principales
- `admin` - Acceso completo
- `encargado` - Permisos limitados por tienda

### Permisos por Módulo
- `dashboard` - Ver dashboard
- `productos_*` - CRUD productos
- `inventarios_*` - Gestión inventarios
- `ventas_*` - Procesamiento ventas
- `reparaciones_*` - Sistema reparaciones
- `boletas_*` - Gestión de boletas (NUEVO)
- `tiendas_*` - Gestión de tiendas
- `usuarios_*` - Gestión usuarios
- `config_*` - Configuración sistema

## Funcionalidades Implementadas

### Sistema de Vendedores (NUEVO)
- Registro de vendedores con porcentaje de comisión
- Asignación de vendedor en cada venta
- Cálculo automático de comisiones
- Reportes de performance por vendedor
- Dashboard con top 10 vendedores

### Sistema de Reparaciones (NUEVO)
- Estados: enviado, en_reparacion, completado, perdido
- Integración con inventario (cantidad_reparacion)
- Control de costos de reparación
- Historial completo de reparaciones
- Usuarios de envío y retorno

### Sistema de Reembolsos (NUEVO)
- Reintegración automática al inventario
- Razones de reembolso categorizadas
- Control de estados de venta

### Sistema de Boletas (NUEVO)
- Subida segura de imágenes de boletas
- Campos obligatorios: número, fecha, proveedor, descripción, imagen
- Validación de archivos (JPG, PNG, GIF, máx 5MB)
- Vista previa automática antes de subir
- Búsqueda avanzada por número, proveedor, fechas
- Visualización modal de imágenes en pantalla completa
- Eliminación segura con confirmación doble
- Estadísticas de uso y espacio ocupado
- Control de permisos por roles

### Sistema de Generación Automática de Códigos (NUEVO)
- Generación automática de códigos únicos para productos
- Prevención de conflictos mediante validación en BD
- Formato consistente con patrón configurable (YYYY-TIPO-NNN)
- Trazabilidad temporal con año incluido
- Tipos diferenciados para elementos (EL) y conjuntos (CJ)
- Migración de códigos existentes al nuevo formato
- Estadísticas de uso por tipo de entidad

### Sistema de Importación Masiva (NUEVO)
- Importación desde Excel/CSV con validación automática
- Soporte para elementos y conjuntos con componentes
- Procesamiento por lotes con transacciones seguras
- Reportes detallados de importación con errores
- Plantilla descargable con formato correcto
- Interfaz intuitiva con drag & drop
- Integración con generador automático de códigos

### Sistema de Códigos QR (NUEVO)
- Generación automática de QR para productos
- Escáner móvil para actualización rápida de inventarios
- Descarga masiva de códigos QR en PDF
- Integración con inventarios para búsqueda rápida
- Validación automática de productos escaneados

### Sistema de Cotizaciones (NUEVO)
- Creación de cotizaciones con productos del inventario
- Conversión directa a ventas
- Duplicación de cotizaciones existentes
- Impresión en formato profesional
- Estados: borrador, enviada, aceptada, rechazada

### Sistema de Personalización Visual (NUEVO)
- 5 temas predefinidos configurables
- Personalización de colores corporativos
- Carga de logo y branding empresarial
- Configuración de información de empresa
- Vista previa en tiempo real de cambios
- Almacenamiento seguro de archivos de branding

### Sistema de Exportación Configurable (NUEVO)
- Exportación de datos a Excel con formato personalizado
- Filtros avanzados por fechas, tiendas, vendedores
- Configuración de columnas a exportar
- Formato empresarial con logo y encabezados
- Descarga automática de archivos generados

### Mejoras UI/UX Implementadas (RECIENTES)
- **Inventarios**: Búsqueda inteligente de productos con autocompletado
- **Tiendas**: Confirmación doble para eliminación con prompt "ELIMINAR"
- **Navbar**: Eliminado submenú "Ver Componentes" de Productos
- **Formularios**: Diseño compacto horizontal para mejor aprovechamiento
- **QR Scanner**: Interfaz móvil optimizada para escaneo
- **Toast Notifications**: Sistema de notificaciones no intrusivas
- **Drag & Drop**: Interfaces modernas para subida de archivos
- **Modal Windows**: Ventanas emergentes responsivas
- **Vista Móvil**: productos_simple.php optimizada para dispositivos móviles

### Mejoras de Seguridad (IMPLEMENTADAS)
- Protección CSRF en formularios
- Sesiones seguras con regeneración de ID
- Validación y sanitización de entrada
- Output encoding contra XSS
- Cabeceras de seguridad HTTP
- Logging seguro de errores
- Protección de uploads con .htaccess
- Validación de tipos MIME para imágenes

## Comandos de Desarrollo

### Configuración del Entorno
```bash
# Servidor de desarrollo local (XAMPP/WAMP recomendado)
# URL: http://localhost/inventario-claude/

# Configuración PHP recomendada (desarrollo)
error_reporting=E_ALL
display_errors=On
log_errors=On

# Extensiones PHP requeridas
extension=pdo_mysql
extension=gd
extension=fileinfo
extension=zip
```

### Base de Datos
```bash
# Crear base de datos
mysql -u root -p -e "CREATE DATABASE inventario_sistema CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Importar estructura completa (recomendado)
mysql -u root -p inventario_sistema < inventario_sistema.sql

# Aplicar schema adicional (si es necesario)
mysql -u root -p inventario_sistema < schema_minimo_dashboard.sql

# Verificar conexión y estructura
mysql -u root -p inventario_sistema -e "SHOW TABLES;"
mysql -u root -p inventario_sistema -e "SELECT COUNT(*) FROM usuarios WHERE rol = 'admin';"
mysql -u root -p inventario_sistema -e "DESCRIBE productos;"

# Verificar funcionalidades nuevas
mysql -u root -p inventario_sistema -e "SHOW COLUMNS FROM productos LIKE '%qr%';"
mysql -u root -p inventario_sistema -e "SELECT COUNT(*) FROM configuraciones;"
```

### Desarrollo y Depuración
```bash
# No hay herramientas de build - archivos estáticos
# Assets ubicados en:
# - assets/css/admin.css
# - assets/js/admin.js

# Verificar permisos de archivos (Linux/Mac)
chmod 755 includes/
chmod 755 uploads/
chmod 755 uploads/boletas/
chmod 755 uploads/branding/
chmod 755 uploads/temp/
chmod 644 config/database.php

# Configuración PHP recomendada para uploads y QR
upload_max_filesize = 10M
post_max_size = 12M
max_execution_time = 60
memory_limit = 256M

# Logs de PHP (ubicación común)
tail -f /var/log/apache2/error.log
tail -f /opt/lampp/logs/error_log

# Testing de funcionalidades nuevas
php test_codigo_generator.php  # Probar generador códigos
php test_productos_simple.php  # Probar vista móvil
php debug_qr_schema.php        # Debug schema QR
```

### URL del Proyecto
- **Local**: `http://localhost/inventario-claude/`
- **Login Demo**: admin@inventario.com / password

## Problemas Resueltos

### Errores Críticos Corregidos
1. **Sintaxis HTML** en navbar.php - Etiqueta `<link>` mal cerrada
2. **SQL Injection** en reparaciones.php - Variable interpolada en query
3. **Bypass Autenticación** en toggle_edit_mode.php - Sin verificación login
4. **Campos inexistentes**: Múltiples errores de columnas no encontradas en reportes_vendedores.php
5. **Stock mínimo**: Error por columna faltante en inventarios.php (solucionado con valor fijo)

### Correcciones de Esquema BD (Recientes)
- **inventarios.php**: `cantidad_disponible` calculado como `cantidad - cantidad_reparacion`
- **reportes_vendedores.php**: Removidos campos `apellido`, `tipo_vendedor`, `tienda_principal_id`
- **ventas**: Campo correcto es `fecha` no `fecha_venta`
- **comisiones_vendedores**: Sin campo `mes_comision` en estructura real

### Mejoras de Seguridad
- Validación de entrada en login.php
- Manejo seguro de errores de BD
- Implementación completa CSRF
- Configuración segura de sesiones
- Output encoding en vistas
- Cabeceras de seguridad HTTP
- Protección de uploads con .htaccess

### Correcciones de BD
- Columna `es_sistema` agregada a `roles`
- Columna `orden` agregada a `permisos`
- Todas las tablas de configuración creadas
- Tabla `boletas` creada con estructura completa
- Permisos `boletas_*` agregados al sistema

## Notas para Claude

### Al iniciar trabajo:
1. Siempre verificar que las tablas de configuración existan
2. Usar las funciones de seguridad implementadas
3. Validar entrada y escapar salida
4. Seguir patrón MVC existente

### Archivos críticos para leer primero:
- `includes/auth.php` - Sistema de autenticación
- `config/database.php` - Conexión BD
- `includes/config_functions.php` - Configuración sistema

### Convenciones del código:
- Prepared statements PDO siempre
- Nombres en español para BD
- Bootstrap 5 para UI
- Font Awesome para iconos
- Transacciones para operaciones complejas

### Endpoints AJAX importantes:
- `includes/toggle_edit_mode.php` - Alternar modo edición
- `includes/update_label.php` - Actualizar etiquetas personalizables
- `ajax/qr_actions.php` - Acciones del escáner QR
- `includes/update_inventario.php` - Actualización de inventarios
- `includes/get_log_detail.php` - Detalles de logs
- `includes/limpiar_logs.php` - Limpieza de logs
- Formularios requieren token CSRF válido

### Arquitectura de includes:
- `includes/layout_header.php` - Configuración global y head HTML
- `includes/navbar.php` - Navegación con permisos dinámicos
- `includes/auth.php` - Verificación de autenticación en cada página
- `includes/*_functions.php` - Funciones específicas por módulo

### Testing y Calidad de Código:
- **Usuario demo**: admin@inventario.com / password
- **URL local**: `http://localhost/inventario-claude/`
- **Sin framework de testing**: Sistema sin PHPUnit - testing manual
- **Sin herramientas de linting**: Sin PHP CodeSniffer configurado
- **Checklist manual**:
  - Verificar permisos por rol
  - Validar formularios CSRF
  - Probar transacciones de BD
  - Verificar logs de errores PHP

### Errores Comunes y Soluciones:
```bash
# Error de conexión BD
# Verificar config/database.php línea 4: db_name = 'inventario_sistema'

# Errores de permisos
# Verificar usuario tiene rol asignado en tabla usuarios.rol_id

# Problemas de sesión
# Limpiar sessions: session_destroy() en includes/session_security.php

# Errores CSRF
# Token regenerado en cada formulario via includes/csrf_protection.php

# Error "Column not found" - MUY COMÚN
# Verificar estructura real de BD antes de asumir nombres de columnas:
mysql -u root -p inventario_sistema -e "DESCRIBE ventas;"
mysql -u root -p inventario_sistema -e "DESCRIBE vendedores;"
mysql -u root -p inventario_sistema -e "DESCRIBE inventarios;"

# Errores de subida de archivos
chmod 755 uploads/boletas/
# Verificar upload_max_filesize en php.ini

# Logs de errores PHP
tail -f /var/log/apache2/error.log
tail -f /opt/lampp/logs/error_log
```

### Estructura BD Confirmada (Importante):
```sql
-- VENTAS: campo correcto es 'fecha' (no fecha_venta)
-- VENDEDORES: solo tiene id, nombre, email, telefono, comision_porcentaje, activo
-- INVENTARIOS: tiene cantidad, cantidad_reparacion (cantidad_disponible se calcula)
-- PRODUCTOS: no tiene stock_minimo
-- COMISIONES_VENDEDORES: no tiene mes_comision
```

## Estado Actual
✅ Sistema completamente funcional
✅ Seguridad implementada y auditada  
✅ Base de datos estable y documentada
✅ Todos los módulos operativos
✅ Sistema de boletas implementado
✅ Búsquedas inteligentes en inventarios
✅ Confirmaciones de eliminación mejoradas
✅ Errores de columnas BD corregidos
✅ Estructura BD real documentada

## Archivos Importantes para Próximas Sesiones

### Scripts SQL Disponibles
- `inventario_sistema_completo.sql` - Base de datos completa
- `crear_tabla_boletas.sql` - Tabla de boletas
- `agregar_permisos_boletas.sql` - Permisos para boletas
- `BOLETAS_README.md` - Documentación detallada del sistema de boletas

### Estructura de Archivos Clave
```
/inventario-claude/
├── boletas.php                    # Gestión de boletas (NUEVO)
├── uploads/boletas/               # Imágenes de boletas
├── reparaciones_enviar.php        # Envío a reparación
├── reparaciones_recibir.php       # Recepción de reparación
├── lista_tiendas.php             # Lista con eliminación mejorada
└── inventarios.php               # Con búsqueda inteligente
```

### Configuración Requerida
```bash
# Permisos de archivos
chmod 755 uploads/boletas/

# Configuración PHP para uploads
upload_max_filesize = 10M
post_max_size = 12M

# Instalar tabla de boletas
mysql -u root -p inventario_sistema < crear_tabla_boletas.sql
mysql -u root -p inventario_sistema < agregar_permisos_boletas.sql
```

**Último análisis**: Agosto 2025 - Sistema listo para producción con:
- Sistema de boletas completamente funcional
- Mejoras UI/UX implementadas 
- Errores de esquema BD corregidos
- Documentación actualizada y completa
- **NUEVAS CARACTERÍSTICAS AGREGADAS**:
  - ✅ Sistema de generación automática de códigos
  - ✅ Importación masiva desde Excel/CSV
  - ✅ Códigos QR con escáner móvil
  - ✅ Sistema de cotizaciones completo
  - ✅ Personalización visual y branding
  - ✅ Exportación configurable de datos
  - ✅ Panel de configuración del sistema
  - ✅ Logs del sistema con interfaz web
  - ✅ Vista móvil optimizada (productos_simple.php)
  - ✅ Notificaciones toast no intrusivas
  - ✅ Sistema de reset completo del sistema