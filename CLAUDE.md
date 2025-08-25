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
- **Archivos SQL disponibles**: 
  - `inventario_sistema-new.sql` - Estructura más reciente y completa
  - Archivos adicionales para características específicas en subdirectorios

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
# Servidor de desarrollo local - XAMPP en Windows/WSL
# URL: http://localhost/inventario-claude/

# XAMPP en WSL - Comandos específicos para MySQL
/mnt/c/xampp/mysql/bin/mysql.exe -u root -p
/mnt/c/xampp/mysql/bin/mysql.exe -u root inventario_sistema -e "SHOW TABLES;"

# Verificar servicios XAMPP
curl http://localhost/dashboard/  # Verificar Apache
curl http://localhost/phpmyadmin/ # Verificar phpMyAdmin

# Verificar estado de servicios en WSL
ps aux | grep mysql    # Verificar MySQL está corriendo
ps aux | grep apache   # Verificar Apache está corriendo

# Configuración PHP recomendada (desarrollo)
error_reporting=E_ALL
display_errors=On
log_errors=On
upload_max_filesize=10M
post_max_size=12M
max_execution_time=60
memory_limit=256M

# Extensiones PHP requeridas
extension=pdo_mysql
extension=gd
extension=fileinfo
extension=zip
extension=mbstring
```

### Base de Datos
```bash
# Crear base de datos
mysql -u root -p -e "CREATE DATABASE inventario_sistema CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Importar estructura completa (recomendado)
mysql -u root -p inventario_sistema < inventario_sistema-new.sql

# IMPORTANTE: Usar el archivo -new.sql que contiene la estructura más actualizada
# Los archivos SQL adicionales están disponibles para características específicas

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
# No hay herramientas de build - archivos estáticos servidos directamente
# Assets ubicados en:
# - assets/css/admin.css - Estilos principales
# - assets/js/admin.js - JavaScript funcionalidades
# - estilos_dinamicos.css.php - CSS dinámico personalizable

# Verificar permisos de archivos (Linux/WSL)
chmod 755 includes/ uploads/ ajax/
chmod 755 uploads/boletas/ uploads/branding/ uploads/temp/ uploads/reparaciones/
chmod 644 config/database.php *.php
chmod 644 includes/*.php

# Configuración PHP recomendada para uploads, QR y Excel
upload_max_filesize = 10M
post_max_size = 12M
max_execution_time = 60
memory_limit = 256M
max_input_vars = 1000

# Logs de PHP (ubicación común WSL/XAMPP)
tail -f /var/log/apache2/error.log
tail -f /opt/lampp/logs/error_log
# En XAMPP Windows:
# C:\xampp\apache\logs\error.log

# Testing de funcionalidades (ejecutar desde navegador)
# http://localhost/inventario-claude/test_simple.php
# http://localhost/inventario-claude/test_codigo_generator.php
# http://localhost/inventario-claude/test_permisos.php
# http://localhost/inventario-claude/debug_qr_schema.php

# Verificar estructura de archivos críticos
ls -la config/ includes/ uploads/
ls -la *.sql
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
2. Usar las funciones de seguridad implementadas (CSRF, sesiones, validación)
3. Validar entrada y escapar salida en TODAS las operaciones
4. Seguir patrón MVC modificado existente
5. **CRÍTICO**: Verificar estructura real de BD antes de asumir nombres de columnas
6. Probar cambios con archivos test_*.php cuando corresponda

### Archivos críticos para leer primero:
- `includes/auth.php` - Sistema de autenticación y permisos (OBLIGATORIO)
- `config/database.php` - Conexión BD
- `includes/config_functions.php` - Configuración sistema
- `includes/layout_header.php` - Layout HTML y estilos dinámicos
- `includes/csrf_protection.php` - Protección CSRF
- `includes/session_security.php` - Configuración segura sesiones

### Convenciones del código:
- **PDO Prepared statements**: OBLIGATORIO siempre (100% protección SQL injection)
- **Nombres en español**: Tablas, campos, variables en BD
- **Bootstrap 5**: UI responsiva con clases modernas
- **Font Awesome 6**: Iconos consistentes
- **Transacciones**: Para operaciones complejas (ventas, traslados, etc.)
- **UTF-8**: Charset obligatorio en BD y PHP
- **CSRF tokens**: En todos los formularios de escritura

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
- **Archivos de testing disponibles**:
  - `test_simple.php` - Test básico de funciones
  - `test_codigo_generator.php` - Test generador de códigos
  - `test_functions.php` - Test funciones sistema
  - `test_permisos.php` - Test sistema permisos
  - `test_productos_simple.php` - Test vista móvil
  - `test_utf8.php` - Test codificación UTF-8
- **Checklist manual**:
  - Verificar permisos por rol usando test_permisos.php
  - Validar formularios CSRF
  - Probar transacciones de BD
  - Verificar logs de errores PHP
  - Ejecutar archivos test_*.php para validar funciones específicas

### Errores Comunes y Soluciones:
```bash
# Error de conexión BD
# Verificar config/database.php línea 4: db_name = 'inventario_sistema'

# Problemas específicos XAMPP/WSL
# Verificar MySQL corriendo:
/mnt/c/xampp/mysql/bin/mysql.exe -u root -e "SELECT 1;"

# Verificar Apache corriendo:
curl -I http://localhost/

# Reiniciar servicios XAMPP (desde Windows):
# C:\xampp\xampp-control.exe

# Error de permisos WSL
# Verificar montaje correcto:
ls -la /mnt/c/Users/oscar/Documents/xamp/htdocs/inventario-claude/

# Errores de permisos de usuario
# Verificar usuario tiene rol asignado en tabla usuarios.rol_id

# Problemas de sesión
# Limpiar sessions: session_destroy() en includes/session_security.php

# Errores CSRF
# Token regenerado en cada formulario via includes/csrf_protection.php

# Error "Column not found" - MUY COMÚN
# Verificar estructura real de BD antes de asumir nombres de columnas:
/mnt/c/xampp/mysql/bin/mysql.exe -u root inventario_sistema -e "DESCRIBE ventas;"
/mnt/c/xampp/mysql/bin/mysql.exe -u root inventario_sistema -e "DESCRIBE vendedores;"
/mnt/c/xampp/mysql/bin/mysql.exe -u root inventario_sistema -e "DESCRIBE inventarios;"

# Errores de subida de archivos
chmod 755 uploads/boletas/
# Verificar upload_max_filesize en php.ini

# Logs de errores PHP (WSL)
tail -f /var/log/apache2/error.log
# O en XAMPP Windows:
# C:\xampp\apache\logs\error.log
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
# Permisos de archivos (Linux/WSL)
chmod 755 uploads/boletas/
chmod 755 uploads/branding/
chmod 755 uploads/temp/

# Verificar que MySQL está ejecutándose en XAMPP
/mnt/c/xampp/mysql/bin/mysql.exe -u root -e "SELECT 1;"

# Configuración PHP para uploads
upload_max_filesize = 10M
post_max_size = 12M

# Base de datos completa (ya incluye tablas de boletas y nuevas características)
mysql -u root -p inventario_sistema < inventario_sistema-new.sql
```

**Último análisis**: Agosto 2025 - Sistema completamente funcional en entorno XAMPP/WSL:
- ✅ Base de datos `inventario_sistema` con estructura completa y optimizada
- ✅ Configuración MySQL para XAMPP Windows verificada
- ✅ Sistema de boletas completamente funcional con upload de imágenes
- ✅ Mejoras UI/UX implementadas con interfaz moderna responsiva
- ✅ Errores de esquema BD identificados y corregidos sistemáticamente
- ✅ Documentación técnica actualizada y completa
- ✅ Comandos WSL/XAMPP documentados con casos de uso específicos
- ✅ Archivos de testing disponibles para validación de funcionalidades

### **CARACTERÍSTICAS AVANZADAS IMPLEMENTADAS**:
  - ✅ Sistema de generación automática de códigos únicos
  - ✅ Importación masiva desde Excel/CSV con validación
  - ✅ Códigos QR con escáner móvil para inventarios
  - ✅ Sistema de cotizaciones completo con conversión a ventas
  - ✅ Personalización visual y branding empresarial dinámico
  - ✅ Exportación configurable de datos en múltiples formatos
  - ✅ Panel de configuración del sistema centralizado
  - ✅ Logs del sistema con interfaz web de consulta
  - ✅ Vista móvil optimizada (productos_simple.php)
  - ✅ Notificaciones toast no intrusivas con estilos modernos
  - ✅ Sistema de reset completo del sistema con respaldos
  - ✅ Sistema de comisiones de vendedores automático
  - ✅ Workflow completo de reparaciones integrado con inventario
  - ✅ Sistema de reembolsos con reintegración automática

### **ENTORNO DE DESARROLLO**:
- **OS**: Windows con WSL2 (Linux subsystem)
- **Servidor**: XAMPP (Apache 2.4+ + MySQL 8.0+)
- **Base de datos activa**: `inventario_sistema` (charset: utf8mb4)
- **URL local**: `http://localhost/inventario-claude/`
- **MySQL path WSL**: `/mnt/c/xampp/mysql/bin/mysql.exe`
- **PHP Version**: 8.0+ con extensiones requeridas
- **Frontend**: Bootstrap 5.3 + jQuery 3.6 + Font Awesome 6.0