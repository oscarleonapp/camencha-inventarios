# 📋 DOCUMENTACIÓN COMPLETA - Sistema de Inventarios v2.0

## 🗂️ ÍNDICE
1. [Información General](#información-general)
2. [Estructura del Proyecto](#estructura-del-proyecto)
3. [Base de Datos](#base-de-datos)
4. [Sistema de Permisos](#sistema-de-permisos)
5. [Arquitectura del Sistema](#arquitectura-del-sistema)
6. [Funcionalidades Implementadas](#funcionalidades-implementadas)
7. [Sistema de Personalización](#sistema-de-personalización)
8. [Configuración y Deployment](#configuración-y-deployment)
9. [Códigos Importantes](#códigos-importantes)
10. [Resolución de Problemas](#resolución-de-problemas)

---

## 📊 INFORMACIÓN GENERAL

### Versión y Estado
- **Versión**: 2.0.0 (Actualización Mayor)
- **Estado**: Completamente Funcional ✅
- **Última Actualización**: Implementación completa con sidebar lateral y sistema de personalización
- **Stack Tecnológico**: PHP 8, MySQL 8, Bootstrap 5, JavaScript ES6

### Características Principales
- ✅ Sistema de inventarios completo con elementos y conjuntos
- ✅ Gestión granular de roles y permisos (4 niveles de acceso)
- ✅ Sidebar lateral responsivo con submenús
- ✅ Sistema de personalización completo (monedas, temas, colores)
- ✅ Modo edición para cambiar textos sin tocar código
- ✅ Dashboard con estadísticas en tiempo real
- ✅ Transferencias entre tiendas
- ✅ Sistema de ventas con validación de stock

### Datos de Acceso
- **URL Local**: `http://localhost/inventario-claude`
- **Usuario Admin**: `admin@inventario.com`
- **Contraseña**: `password`
- **Base de Datos**: `inventario-camencha`

---

## 🏗️ ESTRUCTURA DEL PROYECTO

### Directorios Principales
```
inventario-claude/
├── assets/                     # Recursos estáticos
│   ├── css/
│   │   └── admin.css          # Estilos principales del sistema
│   └── js/
│       └── admin.js           # JavaScript principal
├── config/
│   └── database.php           # Configuración de BD
├── includes/                   # Archivos de inclusión
│   ├── auth.php               # Sistema de autenticación y permisos
│   ├── config_functions.php   # Funciones de configuración
│   ├── layout_header.php      # Header con sidebar nuevo
│   ├── layout_footer.php      # Footer del nuevo layout
│   ├── navbar.php             # Navbar antigua (mantener por compatibilidad)
│   ├── toggle_edit_mode.php   # API para modo edición
│   ├── update_label.php       # API para actualizar etiquetas
│   ├── export_config.php      # API para exportar configuración
│   └── import_config.php      # API para importar configuración
├── *.php                      # Páginas principales del sistema
├── *.sql                      # Scripts de base de datos
└── DOCUMENTACION_COMPLETA.md  # Este archivo
```

### Páginas Principales
1. **index.php** - Dashboard principal con estadísticas
2. **login.php** - Página de inicio de sesión
3. **productos.php** - Gestión de productos (elementos y conjuntos)
4. **ver_componentes.php** - Visualización de componentes de conjuntos
5. **inventarios.php** - Control de stock y transferencias
6. **ventas.php** - Sistema de ventas
7. **tiendas.php** - Gestión de tiendas y encargados
8. **usuarios.php** - Administración de usuarios
9. **roles.php** - Gestión de roles y permisos
10. **configuracion.php** - Panel de personalización del sistema
11. **sin_permisos.php** - Página de acceso denegado

---

## 🗄️ BASE DE DATOS

### Archivos SQL de Instalación (ORDEN IMPORTANTE)
1. **database.sql** - Estructura inicial y datos básicos
2. **permisos_fixed.sql** - Sistema de permisos y roles
3. **configuracion_update.sql** - Sistema de configuración y personalización

### Tablas Principales

#### **Gestión de Usuarios y Permisos**
- `usuarios` - Usuarios del sistema
- `roles` - Roles personalizados
- `permisos` - Catálogo de permisos del sistema
- `rol_permisos` - Asignación de permisos a roles

#### **Gestión de Inventarios**
- `tiendas` - Tiendas del sistema
- `productos` - Productos (elementos y conjuntos)
- `producto_componentes` - Componentes de los conjuntos
- `inventarios` - Stock por tienda y producto
- `movimientos_inventario` - Historial de movimientos

#### **Sistema de Ventas**
- `ventas` - Registro de ventas
- `detalle_ventas` - Detalles de cada venta

#### **Sistema de Personalización**
- `configuraciones` - Configuraciones del sistema
- `etiquetas_personalizadas` - Etiquetas editables
- `temas_sistema` - Temas y colores predefinidos

### Relaciones Clave
```sql
usuarios -> roles (rol_id)
roles -> rol_permisos -> permisos
tiendas -> usuarios (encargado_id)
productos -> producto_componentes (para conjuntos)
inventarios -> (tiendas + productos)
ventas -> (tiendas + usuarios + detalle_ventas)
movimientos_inventario -> (tiendas + productos + usuarios)
```

---

## 🔐 SISTEMA DE PERMISOS

### Niveles de Acceso
1. **Crear** (`puede_crear`) - Puede crear nuevos registros
2. **Leer** (`puede_leer`) - Puede ver información
3. **Actualizar** (`puede_actualizar`) - Puede modificar registros
4. **Eliminar** (`puede_eliminar`) - Puede eliminar registros

### Módulos del Sistema
- **dashboard** - Panel principal
- **tiendas** - Gestión de tiendas
- **usuarios** - Administración de usuarios  
- **productos** - Gestión de productos
- **inventarios** - Control de inventario
- **ventas** - Sistema de ventas
- **reportes** - Reportes del sistema
- **configuracion** - Configuración del sistema

### Roles Predefinidos

#### 👑 **Administrador** (ID: 1)
- **Acceso**: Total al sistema
- **Permisos**: Todos los módulos con todos los niveles
- **Uso**: Super usuario del sistema

#### 👔 **Gerente** (ID: 2)  
- **Acceso**: Gestión completa excepto configuración crítica
- **Restricciones**: No puede eliminar, no acceso a config_roles/sistema
- **Uso**: Gestión operativa completa

#### 🏪 **Encargado de Tienda** (ID: 3)
- **Acceso**: Operaciones de tienda
- **Restricciones**: No gestión de tiendas/usuarios/configuración
- **Uso**: Gestión diaria de inventario y ventas

#### 💰 **Vendedor** (ID: 4)
- **Acceso**: Solo ventas y consultas básicas  
- **Permisos**: Dashboard, productos (leer), inventarios (leer), ventas (crear)
- **Uso**: Personal de ventas

#### 👁️ **Solo Lectura** (ID: 5)
- **Acceso**: Solo visualización
- **Permisos**: Todos los módulos con solo lectura
- **Uso**: Supervisión y consultas

### Funciones de Validación
```php
// En includes/auth.php
tienePermiso($permiso, $accion = 'leer')          // Verificar si tiene permiso
verificarPermiso($permiso, $accion = 'leer')      // Verificar o redirigir
esAdmin()                                          // Verificar si es admin
cargarPermisos()                                   // Cargar permisos del usuario
obtenerMenuModulos()                               // Obtener menú según permisos
```

---

## 🏛️ ARQUITECTURA DEL SISTEMA

### Patrón de Diseño
- **Arquitectura**: MVC simplificado con PHP nativo
- **Separación**: Lógica de negocio, presentación y datos separados
- **Seguridad**: Validación de permisos en cada página
- **Configuración**: Sistema centralizado de configuración

### Flujo de Autenticación
```
1. Usuario accede a página
2. includes/auth.php verifica sesión activa
3. Si no tiene sesión → redirige a login.php
4. Si tiene sesión → verifica permisos para la página
5. Si no tiene permisos → redirige a sin_permisos.php  
6. Si tiene permisos → carga la página
```

### Sistema de Layout
- **Nuevo Layout**: `includes/layout_header.php` + `includes/layout_footer.php`
- **Layout Anterior**: `includes/navbar.php` (mantenido por compatibilidad)
- **CSS Principal**: `assets/css/admin.css`
- **JavaScript**: `assets/js/admin.js`

### Gestión de Estado
- **Sesiones PHP**: Información del usuario y permisos
- **Cookies**: Estado del sidebar, tema seleccionado
- **LocalStorage**: Colores personalizados
- **Cache**: Configuraciones y etiquetas en variables estáticas

---

## ⚙️ FUNCIONALIDADES IMPLEMENTADAS

### 🎯 **Dashboard** (`index.php`)
- Estadísticas de ventas (hoy, mes)
- Productos bajo stock
- Tiendas activas  
- Ventas por tienda (últimos 7 días)
- Productos más vendidos (30 días)
- Movimientos recientes de inventario
- Alertas de stock crítico

### 📦 **Gestión de Productos** (`productos.php`, `ver_componentes.php`)
- **Elementos individuales**: Productos básicos con código, nombre, precios
- **Conjuntos/Kits**: Productos compuestos por múltiples elementos
- **Componentes**: Gestión de qué elementos forman cada conjunto
- **Análisis de precios**: Cálculo automático de márgenes
- **Códigos únicos**: Sistema de códigos para identificación

### 🏪 **Gestión de Tiendas** (`tiendas.php`)  
- Crear múltiples tiendas
- Asignar encargados a cada tienda
- Información completa (nombre, dirección, teléfono)
- Estado activo/inactivo

### 📊 **Control de Inventarios** (`inventarios.php`)
- **Stock por tienda**: Cantidad disponible de cada producto
- **Transferencias**: Mover productos entre tiendas
- **Ajustes**: Corregir cantidades de inventario
- **Stock mínimo**: Alertas automáticas
- **Historial**: Rastreo completo de movimientos
- **Validaciones**: Prevenir stock negativo (configurable)

### 💰 **Sistema de Ventas** (`ventas.php`)
- **Ventas múltiples productos**: Carrito de compras
- **Validación de stock**: Verificar disponibilidad antes de vender
- **Conjuntos inteligentes**: Al vender conjunto, descuenta componentes
- **Historial completo**: Todas las ventas con detalles
- **Cálculo automático**: Totales y subtotales

### 👥 **Gestión de Usuarios** (`usuarios.php`)
- Crear usuarios con roles específicos
- Asignar/cambiar roles dinámicamente  
- Activar/desactivar usuarios
- Estadísticas por rol
- Validación de emails únicos

### 🔐 **Roles y Permisos** (`roles.php`)
- **Crear roles personalizados**: Más allá de los predefinidos
- **Asignación granular**: 4 niveles por cada módulo
- **Editor visual**: Interface con checkboxes
- **Herencia automática**: Marcar "leer" automáticamente
- **Roles de sistema**: No se pueden eliminar

### ⚙️ **Sistema de Personalización** (`configuracion.php`)

#### **Configuración General**
- Nombre del sistema
- Datos de la empresa
- Información de contacto

#### **Gestión de Monedas** 
- **Monedas soportadas**: USD, EUR, MXN, COP, ARS, CLP, PEN
- **Formato personalizable**: Símbolo, posición, decimales
- **Separadores**: Decimales y miles configurables  
- **Vista previa**: Cambios en tiempo real

#### **Temas y Colores**
- **4 temas predefinidos**: Default, Dark Mode, Corporate Blue, Green Nature
- **Editor de colores**: Personalizar colores primarios, sidebar, topbar. Consulta la [Guía de Paleta de Colores](GUIA_COLORES.md) para usar tokens (`--primary-color`, `--success-color`, etc.) y utilidades de Bootstrap sin `!important`.
- **Aplicación inmediata**: Sin necesidad de recargar

#### **Configuración de Inventario**
- Stock mínimo por defecto
- Permitir stock negativo (sí/no)
- Alertas de stock bajo (sí/no)

#### **Exportar/Importar**
- Backup completo de configuración
- Restaurar configuraciones previas
- Formato JSON estándar

### 🎨 **Modo Edición**
- **Activación**: Desde menú de usuario
- **Edición in-situ**: Clic en cualquier texto editable
- **Sin código**: Cambiar nombres, etiquetas, títulos
- **Guardado automático**: Cambios se aplican inmediatamente
- **Indicador visual**: Alerta amarilla cuando está activo

---

## 🎨 SISTEMA DE PERSONALIZACIÓN

### Configuraciones Disponibles

#### **Configuraciones Generales** (`configuraciones` table)
```php
'nombre_sistema' => 'Sistema de Inventarios'
'empresa_nombre' => 'Mi Empresa'  
'empresa_direccion' => ''
'empresa_telefono' => ''
'empresa_email' => ''
```

#### **Configuraciones de Moneda**
```php
'moneda_codigo' => 'USD'           // Código ISO
'moneda_nombre' => 'Dólar Americano'
'simbolo_moneda' => '$'            // Símbolo a mostrar
'posicion_simbolo' => 'antes'      // antes/despues
'separador_decimal' => '.'         // . o ,
'separador_miles' => ','           // , . o espacio
'decimales_mostrar' => '2'         // 0-4 decimales
```

#### **Configuraciones de Interfaz**  
```php
'tema_actual' => 'default'         // Tema seleccionado
'color_primario' => '#007bff'      // Color principal
'color_secundario' => '#6c757d'    // Color secundario
'sidebar_width' => '280px'         // Ancho del sidebar
'sidebar_color' => '#2c3e50'       // Color del sidebar
'topbar_color' => '#007bff'        // Color barra superior
```

### Etiquetas Editables (`etiquetas_personalizadas` table)
- **Menús**: Nombres de opciones del menú
- **Botones**: Textos de botones  
- **Títulos**: Títulos de páginas y secciones
- **General**: Etiquetas comunes del sistema

### Funciones de Configuración
```php
// En includes/config_functions.php
cargarConfiguracion()                          // Cargar todas las configs
obtenerConfiguracion($clave, $default)        // Obtener config específica
actualizarConfiguracion($clave, $valor)       // Actualizar config
cargarEtiquetas()                             // Cargar etiquetas personalizadas
obtenerEtiqueta($clave, $default)             // Obtener etiqueta específica
actualizarEtiqueta($clave, $valor)            // Actualizar etiqueta
formatearMoneda($cantidad, $incluir_simbolo)  // Formatear según config
obtenerTemas()                                // Obtener temas disponibles  
aplicarTema($tema_id)                         // Cambiar tema
exportarConfiguracion()                       // Exportar todas las configs
importarConfiguracion($datos)                 // Importar configuraciones
```

---

## 🚀 CONFIGURACIÓN Y DEPLOYMENT

### Instalación Paso a Paso

#### 1. **Preparar Entorno**
- XAMPP con PHP 7.4+ y MySQL 8.0+
- Extensiones: PDO, PDO_MySQL, JSON

#### 2. **Base de Datos** (ORDEN CRÍTICO)
```sql
-- 1. Ejecutar PRIMERO
SOURCE database.sql;

-- 2. Ejecutar SEGUNDO  
SOURCE permisos_fixed.sql;

-- 3. Ejecutar TERCERO
SOURCE configuracion_update.sql;
```

#### 3. **Configuración**
- Verificar `config/database.php` apunta a `inventario-camencha`
- Crear carpeta en: `C:/xampp/htdocs/inventario-claude/`
- Permisos de escritura en directorio

#### 4. **Acceso Inicial**
- URL: `http://localhost/inventario-claude`
- Usuario: `admin@inventario.com`
- Password: `password`

### Variables de Entorno (Configurables)
```php
// config/database.php
$host = 'localhost';
$db_name = 'inventario-camencha'; 
$username = 'root';
$password = '';
```

### Estructura de Sesión
```php
$_SESSION = [
    'usuario_id' => 1,
    'usuario_nombre' => 'Administrador',
    'usuario_email' => 'admin@inventario.com', 
    'rol' => 'admin',
    'rol_id' => 1,
    'rol_nombre' => 'Administrador',
    'permisos' => [...],  // Array de permisos cargados
    'modo_edicion' => false  // Estado del modo edición
];
```

---

## 💻 CÓDIGOS IMPORTANTES

### Snippet de Verificación de Permisos
```php
// Al inicio de cada página
require_once 'includes/auth.php';
require_once 'config/database.php';  
require_once 'includes/config_functions.php';

verificarLogin();                    // Verificar sesión
verificarPermiso('modulo_ver');      // Verificar permiso específico
```

### Snippet del Nuevo Layout
```php
// Variables para el layout
$titulo = "Mi Página - Sistema de Inventarios";
$css_adicional = ['assets/css/mi-estilo.css'];
$js_adicional = ['assets/js/mi-script.js'];

// Header
include 'includes/layout_header.php';

// Contenido de la página aquí

// Footer  
include 'includes/layout_footer.php';
```

### Snippet de Formateo de Moneda
```php
// Formatear moneda según configuración
echo formatearMoneda(1234.56);        // $1,234.56
echo formatearMoneda(1234.56, false); // 1,234.56 (sin símbolo)
```

### Snippet de Validación de Permisos en Templates
```php
<?php if (tienePermiso('productos_crear', 'crear')): ?>
    <button class="btn btn-primary">Crear Producto</button>
<?php endif; ?>
```

### Snippet para Modo Edición
```php
<span class="editable" data-label="etiqueta_clave">
    Texto Editable
</span>
```

### JavaScript para Sidebar
```javascript
// Toggle sidebar
toggleSidebar();

// Establecer estado específico
setSidebarState('sidebar-expanded');  // o 'sidebar-collapsed'

// Mostrar notificación
showToast('Mensaje', 'success');     // success, danger, warning, info
```

### CSS Variables Dinámicas
```css
:root {
    --primary-color: #007bff;      /* Desde configuración */
    --secondary-color: #6c757d;    /* Desde configuración */
    --sidebar-width: 280px;        /* Desde configuración */
    --sidebar-color: #2c3e50;      /* Desde configuración */ 
    --topbar-color: #007bff;       /* Desde configuración */
}
```

---

## 🔧 RESOLUCIÓN DE PROBLEMAS

### Problemas Comunes

#### **Error: Sidebar no aparece**
**Síntomas**: Menú lateral no se muestra
**Solución**:
```javascript
// En consola del navegador
localStorage.clear();
document.cookie.split(";").forEach(c => {
    document.cookie = c.replace(/^ +/, "").replace(/=.*/, "=;expires=" + new Date().toUTCString() + ";path=/");
});
location.reload();
```

#### **Error: Permisos no funcionan**
**Síntomas**: Usuario no puede acceder a páginas que debería
**Diagnóstico**:
```sql
-- Verificar usuario y rol
SELECT u.*, r.nombre as rol_nombre FROM usuarios u 
LEFT JOIN roles r ON u.rol_id = r.id 
WHERE u.email = 'usuario@email.com';

-- Verificar permisos del rol
SELECT p.nombre, rp.* FROM rol_permisos rp
JOIN permisos p ON rp.permiso_id = p.id
WHERE rp.rol_id = [ID_DEL_ROL];
```

#### **Error: Base de datos**
**Síntomas**: Error de conexión o tablas faltantes
**Solución**: Re-ejecutar SQLs en orden:
1. `database.sql`
2. `permisos_fixed.sql`  
3. `configuracion_update.sql`

#### **Error: Configuración no se guarda**
**Síntomas**: Cambios en configuración no persisten
**Verificar**:
```php
// Verificar permisos
var_dump(tienePermiso('config_sistema'));

// Verificar escritura en BD
try {
    actualizarConfiguracion('test', 'value');
} catch (Exception $e) {
    echo $e->getMessage();
}
```

#### **Error: JavaScript**
**Síntomas**: Funciones no definidas
**Solución**: Verificar que se carga `assets/js/admin.js` y Bootstrap JS

#### **Error: Estilos no cargan**
**Síntomas**: Diseño roto o sin estilos
**Verificar**: 
- `assets/css/admin.css` existe y es accesible
- Bootstrap CSS se carga desde CDN
- Variables CSS se cargan desde configuración

### Logs y Debugging

#### **Habilitar Error Reporting**
```php
// Al inicio de cualquier página PHP
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
```

#### **Debug de Permisos**
```php
// En cualquier página, agregar temporalmente:
echo '<pre>';
var_dump($_SESSION['permisos']);
var_dump($_SESSION['rol_id']);  
echo '</pre>';
```

#### **Debug de Configuración**
```php
// Ver todas las configuraciones
$config = cargarConfiguracion();
echo '<pre>' . print_r($config, true) . '</pre>';
```

### Comandos Útiles

#### **Reset Completo de Usuario Admin**
```sql
-- Restablecer password del admin
UPDATE usuarios SET 
password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
rol_id = 1, 
activo = 1 
WHERE email = 'admin@inventario.com';
```

#### **Limpiar Configuraciones**
```sql
-- Restablecer configuraciones a default
DELETE FROM configuraciones;
DELETE FROM etiquetas_personalizadas;
-- Luego re-ejecutar configuracion_update.sql
```

#### **Reset de Permisos**
```sql
-- Re-asignar todos los permisos al admin
DELETE FROM rol_permisos WHERE rol_id = 1;
INSERT INTO rol_permisos (rol_id, permiso_id, puede_crear, puede_leer, puede_actualizar, puede_eliminar)
SELECT 1, id, TRUE, TRUE, TRUE, TRUE FROM permisos;
```

---

## 📝 NOTAS ADICIONALES

### Estado Actual del Sistema
- ✅ **Completamente funcional** para uso en producción
- ✅ **Todas las funcionalidades principales** implementadas
- ✅ **Sistema de permisos robusto** y granular  
- ✅ **Diseño responsive** optimizado para PC/Tablet
- ✅ **Personalización completa** sin tocar código
- ✅ **Documentación exhaustiva** para mantenimiento

### Próximas Mejoras Sugeridas
- 📊 **Gráficos interactivos** en dashboard (Chart.js)
- 📧 **Notificaciones por email** para stock bajo
- 📱 **PWA** para acceso móvil offline  
- 🔔 **Alertas en tiempo real** (WebSockets)
- 📈 **Reportes avanzados** con exportación
- 🌐 **API REST** para integraciones
- 🔐 **2FA** para mayor seguridad
- 📦 **Gestión de proveedores** y compras

### Compatibilidad
- **PHP**: 7.4 - 8.2
- **MySQL**: 5.7 - 8.0  
- **Navegadores**: Chrome 60+, Firefox 55+, Safari 12+, Edge 79+
- **Dispositivos**: Desktop, Tablet (Mobile con limitaciones menores)

### Licencia y Créditos
- **Desarrollado**: Con Claude AI (Anthropic)
- **Framework CSS**: Bootstrap 5.1.3
- **Iconos**: Font Awesome 6.0
- **Base**: PHP Nativo + MySQL

---

## 🎯 CHECKLIST DE VERIFICACIÓN COMPLETA

### ✅ Instalación
- [ ] XAMPP instalado y funcionando
- [ ] Base de datos `inventario-camencha` creada
- [ ] Los 3 archivos SQL ejecutados EN ORDEN
- [ ] Configuración de BD apunta a la BD correcta
- [ ] Archivos en `C:/xampp/htdocs/inventario-claude/`

### ✅ Funcionalidades Core
- [ ] Login funciona con admin@inventario.com / password
- [ ] Dashboard muestra estadísticas
- [ ] Sidebar lateral aparece y funciona en todas las páginas
- [ ] Submenús se despliegan correctamente  
- [ ] Productos: crear elementos y conjuntos
- [ ] Inventarios: transferir entre tiendas
- [ ] Ventas: realizar venta y descontar stock
- [ ] Usuarios: crear y asignar roles

### ✅ Sistema de Permisos  
- [ ] Roles predefinidos funcionan correctamente
- [ ] Crear rol personalizado
- [ ] Asignar permisos granulares (crear/leer/actualizar/eliminar)
- [ ] Menú se adapta según permisos del usuario
- [ ] Páginas bloquean acceso sin permisos

### ✅ Personalización
- [ ] Configuración: cambiar datos de empresa
- [ ] Monedas: cambiar símbolo, formato, decimales
- [ ] Temas: aplicar tema predefinido
- [ ] Colores: personalizar colores del sistema  
- [ ] Modo edición: cambiar textos sin código
- [ ] Exportar/Importar configuración

### ✅ Responsividad
- [ ] Desktop: sidebar expandible/colapsable
- [ ] Tablet: diseño se adapta automáticamente
- [ ] Mobile: sidebar como overlay (opcional)

---

**📌 NOTA IMPORTANTE**: Esta documentación contiene TODA la información necesaria para entender, mantener y expandir el sistema. Guarda este archivo como referencia principal para futuras sesiones de desarrollo.

**🚀 El sistema está COMPLETO y listo para uso en producción.**
