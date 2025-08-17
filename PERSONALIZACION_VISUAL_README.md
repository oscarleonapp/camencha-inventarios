# Sistema de Personalización Visual - Documentación Completa

## Descripción

El Sistema de Personalización Visual permite al cliente personalizar completamente la apariencia del sistema de inventarios, incluyendo colores institucionales, logos, fuentes, layout y efectos visuales. Esto proporciona control total sobre la identidad visual del sistema.

## Características Principales

### ✅ Implementado Completamente
- **Editor de colores institucionales** con vista previa en tiempo real
- **Gestor de logos y branding** con subida segura de imágenes
- **Selector de fuentes** con previsualización
- **Configuración de layout** y efectos visuales
- **Temas predefinidos** listos para usar
- **Exportar/Importar configuraciones** como archivos JSON
- **Vista previa flotante** en tiempo real
- **Aplicación automática** en todo el sistema

## Archivos del Sistema

### Archivos Principales
```
personalizacion_visual.php        # Interfaz principal de personalización
includes/estilos_dinamicos.php    # Generador de CSS dinámico
estilos_dinamicos.css.php         # Endpoint CSS que sirve estilos personalizados
uploads/branding/                 # Directorio para logos e imágenes
uploads/branding/.htaccess         # Protección del directorio
```

### Integración en el Sistema
- **Menú**: Agregado en `Administración → Personalización Visual`
- **Layout**: Integrado en `includes/layout_header.php`
- **Navbar**: Logos dinámicos en `includes/navbar.php`
- **Permisos**: Requiere `config_sistema`

## Funcionalidades Detalladas

### 1. **Editor de Colores Institucionales**

#### Colores Principales
- **Color Primario**: Usado en botones principales, enlaces y elementos destacados
- **Color Secundario**: Para elementos secundarios y texto muted
- **Color de Éxito**: Alertas positivas, mensajes de confirmación
- **Color de Peligro**: Errores, advertencias críticas, botones de eliminación
- **Color de Advertencia**: Alertas informativas
- **Color de Información**: Mensajes informativos, tooltips

#### Colores de Interface
- **Color Sidebar**: Fondo de la barra lateral de navegación
- **Color Navbar**: Fondo de la barra superior
- **Color de Texto**: Color principal del texto
- **Color de Fondo**: Fondo general de páginas

#### Características
- 🎨 **Selector de color visual** con input type="color"
- 👁️ **Vista previa en tiempo real** al cambiar colores
- 🔄 **Restaurar valores por defecto** con un clic
- 📱 **Preview flotante** del sistema con los nuevos colores

### 2. **Gestor de Logos y Branding**

#### Información de la Empresa
- **Nombre de la Empresa**: Mostrado en navbar y títulos
- **Eslogan/Descripción**: Subtítulo descriptivo

#### Logos e Imágenes
- **Logo Principal**: Para headers y pantallas de login (200x60px recomendado)
- **Logo Pequeño**: Para navbar y espacios reducidos (40x40px recomendado)
- **Favicon**: Icono del navegador (32x32px recomendado)

#### Características de Seguridad
- ✅ **Validación de tipos de archivo**: JPG, PNG, GIF, SVG, ICO
- ✅ **Límites de tamaño**: Logo principal 2MB, logo pequeño 1MB, favicon 512KB
- ✅ **Directorio protegido** con .htaccess
- ✅ **Nombres únicos** con timestamp para evitar conflictos
- ✅ **Preview automático** de imágenes subidas

### 3. **Selector de Fuentes**

#### Fuentes Disponibles
**Principales (títulos y headers):**
- Inter (Recomendado)
- Roboto
- Open Sans
- Lato
- Source Sans Pro
- Montserrat
- Nunito
- Poppins
- Arial
- Helvetica

**Secundarias (texto general):**
- System UI (Recomendado)
- Inter
- Roboto
- Arial
- Georgia
- Times New Roman

#### Características
- 🔤 **Vista previa en vivo** de las fuentes seleccionadas
- 🌐 **Carga automática de Google Fonts** si es necesario
- ⚡ **Optimización de rendimiento** con preconnect
- 📱 **Fallbacks seguros** a fuentes del sistema

### 4. **Configuración de Layout**

#### Estilos Disponibles
**Sidebar:**
- **Oscuro**: Fondo oscuro con texto claro (recomendado)
- **Claro**: Fondo claro con texto oscuro
- **Colorido**: Usa el color primario como gradiente

**Navbar:**
- **Claro**: Fondo claro con borde inferior (recomendado)
- **Oscuro**: Fondo oscuro con texto claro
- **Colorido**: Gradiente horizontal del color primario

#### Efectos Visuales
- **Bordes Redondeados**: Slider para controlar el radio de bordes (0-1rem)
- **Sombras Activas**: Toggle para habilitar/deshabilitar sombras en cards
- **Animaciones Activas**: Toggle para transiciones suaves
- **Modo Compacto**: Reduce espaciado para pantallas pequeñas

#### Vista Previa de Layout
- 📦 **Simulación en miniatura** de cards y botones
- 🔄 **Actualización en tiempo real** al cambiar configuraciones
- 👁️ **Preview interactivo** de los efectos

### 5. **Temas Predefinidos**

#### Temas Incluidos
1. **Azul Profesional**
   - Colores: Azul clásico (#007bff) con grises neutros
   - Ideal para: Empresas corporativas, entornos profesionales

2. **Verde Moderno**
   - Colores: Verde fresco (#28a745) con acentos cálidos
   - Ideal para: Empresas ecológicas, startups tecnológicas

3. **Gris Minimalista**
   - Colores: Paleta de grises con acentos sutiles
   - Ideal para: Diseño limpio, empresas minimalistas

4. **Oscuro Elegante**
   - Colores: Púrpura (#6f42c1) con fondos oscuros
   - Ideal para: Ambiente nocturno, empresas tecnológicas

#### Aplicación de Temas
- 🎯 **Un clic para aplicar** cualquier tema
- ⚠️ **Confirmación antes de cambios** permanentes
- 💾 **Guardado manual** para confirmar cambios

### 6. **Exportar/Importar Configuraciones**

#### Exportación
```json
{
    "nombre": "Tema Personalizado",
    "version": "1.0",
    "fecha_exportacion": "2025-08-16 14:30:00",
    "configuracion": {
        "color_primario": "#007bff",
        "color_secundario": "#6c757d",
        "nombre_empresa": "Mi Empresa",
        // ... todas las configuraciones
    }
}
```

#### Importación
- 📁 **Selección de archivo JSON** con validación
- ✅ **Verificación de estructura** antes de aplicar
- 🔒 **Transacción segura** (todo o nada)
- 📝 **Log de cambios** para auditoría

#### Reseteo de Tema
- ⚠️ **Zona de peligro** claramente marcada
- 🔴 **Confirmación doble** antes de resetear
- 🏠 **Valores por defecto** del sistema

## Implementación Técnica

### Base de Datos

#### Tabla `configuraciones`
```sql
categoria = 'visual'
clave = 'color_primario' | 'color_secundario' | ... | 'modo_compacto'
valor = valor_configuracion
```

#### Campos Almacenados
```php
[
    'color_primario' => '#007bff',
    'color_secundario' => '#6c757d',
    'color_exito' => '#28a745',
    'color_peligro' => '#dc3545',
    'color_advertencia' => '#ffc107',
    'color_info' => '#17a2b8',
    'color_sidebar' => '#343a40',
    'color_navbar' => '#ffffff',
    'color_texto' => '#212529',
    'color_fondo' => '#f8f9fa',
    'logo_principal' => 'nombre_archivo.png',
    'logo_pequeno' => 'nombre_archivo.png',
    'favicon' => 'nombre_archivo.ico',
    'nombre_empresa' => 'Mi Empresa S.A.',
    'eslogan_empresa' => 'Innovación y Calidad',
    'fuente_principal' => 'Inter',
    'fuente_secundaria' => 'system-ui',
    'sidebar_estilo' => 'oscuro',
    'navbar_estilo' => 'claro',
    'bordes_redondeados' => '0.375rem',
    'sombras_activas' => '1',
    'animaciones_activas' => '1',
    'modo_compacto' => '0'
]
```

### Generación de CSS Dinámico

#### Proceso
1. **Lectura de configuración** desde base de datos
2. **Generación de variables CSS** personalizadas
3. **Aplicación de estilos** según configuración
4. **Cache optimizado** con headers HTTP

#### Variables CSS Generadas
```css
:root {
    --color-primario: #007bff;
    --color-secundario: #6c757d;
    --fuente-principal: 'Inter', sans-serif;
    --fuente-secundaria: 'system-ui', sans-serif;
    --bordes-redondeados: 0.375rem;
    --sombra-card: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
    --transicion-normal: 0.3s ease-in-out;
    /* ... más variables */
}
```

#### Aplicaciones CSS
```css
/* Bootstrap Override */
.btn-primary { background-color: var(--color-primario) !important; }
.text-primary { color: var(--color-primario) !important; }

/* Layout Personalizado */
.sidebar { background-color: var(--color-sidebar) !important; }
.navbar { background-color: var(--color-navbar) !important; }

/* Tipografía */
body { font-family: var(--fuente-secundaria); }
h1, h2, h3, h4, h5, h6 { font-family: var(--fuente-principal); }

/* Efectos */
.card { 
    border-radius: var(--bordes-redondeados);
    box-shadow: var(--sombra-card);
    transition: var(--transicion-normal);
}
```

### Integración con el Sistema

#### Layout Header (`includes/layout_header.php`)
```php
<!-- Estilos Dinámicos Personalizados -->
<link rel="stylesheet" href="estilos_dinamicos.css.php?v=<?php echo time(); ?>">

<!-- Favicon Dinámico -->
<?php if (!empty($config_visual['favicon'])): ?>
<link rel="icon" type="image/x-icon" href="uploads/branding/<?php echo $config_visual['favicon']; ?>">
<?php endif; ?>

<!-- Google Fonts Dinámicas -->
<?php if (!empty($fuentes_necesarias)): ?>
<link href="https://fonts.googleapis.com/css2?family=<?php echo implode('&family=', $fuentes_necesarias); ?>&display=swap" rel="stylesheet">
<?php endif; ?>
```

#### Navbar (`includes/navbar.php`)
```php
<a class="navbar-brand" href="index.php">
    <?php if (!empty($config_visual['logo_pequeno'])): ?>
        <img src="uploads/branding/<?php echo $config_visual['logo_pequeno']; ?>" alt="Logo" style="height: 32px;">
    <?php else: ?>
        <i class="fas fa-boxes"></i>
    <?php endif; ?>
    <?php echo $config_visual['nombre_empresa']; ?>
</a>
```

### Seguridad

#### Validaciones Implementadas
- ✅ **Verificación de permisos**: Solo usuarios con `config_sistema`
- ✅ **Protección CSRF**: Tokens en todos los formularios
- ✅ **Validación de archivos**: Tipos y tamaños permitidos
- ✅ **Sanitización de entrada**: Escape de datos antes de almacenar
- ✅ **Directorio protegido**: .htaccess para uploads
- ✅ **Logs de auditoría**: Registro de todos los cambios

#### Manejo de Archivos
```apache
# uploads/branding/.htaccess
<Files "*">
    Order Deny,Allow
    Deny from all
</Files>

<FilesMatch "\.(jpg|jpeg|png|gif|svg|ico)$">
    Order Allow,Deny
    Allow from all
</FilesMatch>

AddHandler cgi-script .php .pl .py .jsp .asp .sh .cgi
Options -ExecCGI
```

## Uso del Sistema

### Acceso
1. **Login**: Ingresar con usuario que tenga permisos `config_sistema`
2. **Navegación**: `Administración → Personalización Visual`
3. **Interfaz**: Sistema de pestañas con vista previa

### Flujo de Trabajo Recomendado

#### 1. Configuración Inicial
1. **Colores**: Establecer paleta institucional
2. **Branding**: Subir logos y configurar nombre de empresa
3. **Fuentes**: Seleccionar tipografía apropiada
4. **Layout**: Ajustar estilo de interface

#### 2. Refinamiento
1. **Vista Previa**: Usar preview flotante para verificar cambios
2. **Ajustes**: Modificar efectos visuales según preferencia
3. **Pruebas**: Verificar en diferentes pantallas y navegadores

#### 3. Finalización
1. **Guardar**: Confirmar cada sección modificada
2. **Exportar**: Crear backup del tema personalizado
3. **Documentar**: Registrar configuración para futuras referencias

### Mejores Prácticas

#### Diseño
- 🎨 **Contraste adecuado**: Asegurar legibilidad del texto
- 📱 **Responsive**: Verificar en dispositivos móviles
- 🔤 **Fuentes legibles**: Evitar fuentes decorativas para texto general
- 🖼️ **Logos optimizados**: Usar formatos vectoriales cuando sea posible

#### Rendimiento
- ⚡ **Imágenes ligeras**: Optimizar tamaño de logos
- 🔄 **Cache activado**: Los estilos se cachean automáticamente
- 🌐 **Google Fonts**: Solo cargar fuentes necesarias
- 📱 **Modo compacto**: Para dispositivos con pantalla pequeña

#### Mantenimiento
- 💾 **Backup regular**: Exportar configuración periódicamente
- 📝 **Documentar cambios**: Registro de modificaciones importantes
- 🔒 **Restricción de acceso**: Solo administradores autorizados
- 🧪 **Pruebas previas**: Verificar en entorno de desarrollo

## Troubleshooting

### Problemas Comunes

#### 1. "Los colores no se aplican"
**Causa:** Cache del navegador o CSS no actualizado  
**Solución:** 
- Refrescar con Ctrl+F5
- Verificar que `estilos_dinamicos.css.php` sea accesible
- Comprobar permisos de archivo

#### 2. "Las fuentes no cargan"
**Causa:** Problemas con Google Fonts o conectividad  
**Solución:**
- Verificar conexión a internet
- Comprobar nombres de fuentes en el código
- Usar fuentes del sistema como fallback

#### 3. "Los logos no se muestran"
**Causa:** Archivos no subidos correctamente o permisos  
**Solución:**
- Verificar que existe `uploads/branding/`
- Comprobar permisos de escritura (755)
- Revisar tamaño y formato de archivo

#### 4. "Vista previa no funciona"
**Causa:** JavaScript deshabilitado o errores  
**Solución:**
- Habilitar JavaScript en el navegador
- Comprobar consola del navegador por errores
- Recargar la página completamente

#### 5. "Error al importar tema"
**Causa:** Archivo JSON inválido o corrupto  
**Solución:**
- Verificar formato JSON válido
- Comprobar estructura del archivo exportado
- Revisar logs del sistema para detalles

### Mensajes de Error Técnicos

```php
// Errores de archivo
"Error al subir archivo: [código]"
"Archivo demasiado grande. Máximo Xmb permitido."
"Formato de archivo no permitido. Solo se permiten: JPG, PNG, GIF, SVG, ICO"

// Errores de configuración
"Error al guardar configuración: [detalle]"
"Configuración visual no encontrada"
"Error en transacción de base de datos"

// Errores de importación
"Archivo de tema inválido"
"Estructura JSON no válida"
"Error al aplicar configuración importada"
```

## API y Funciones Útiles

### Funciones Principales

```php
// Obtener configuración visual
$config = obtenerConfiguracionVisual();
$color_primario = obtenerConfiguracionVisual('color_primario');

// Generar CSS dinámico
$css = generarEstilosDinamicos();

// Aplicar colores personalizados
aplicarColoresTemporales($colores);

// Ajustar brillo de colores
$color_claro = adjustBrightness('#007bff', 20);  // +20% brillo
$color_oscuro = adjustBrightness('#007bff', -20); // -20% brillo
```

### Eventos JavaScript

```javascript
// Actualizar vista previa
actualizarVistaPrevia();

// Aplicar tema predefinido
aplicarTema('azul_profesional');

// Restaurar colores por defecto
restaurarColoresDefecto();

// Previsualizar fuentes
actualizarPrevisualizacionFuente();
```

## Roadmap Futuro

### Mejoras Planeadas
- 🌙 **Modo oscuro automático** basado en hora del día
- 🎨 **Editor de CSS avanzado** para usuarios técnicos
- 📊 **Analíticas de uso** de temas y configuraciones
- 🔄 **Sincronización multi-sitio** para empresas con múltiples instalaciones
- 📱 **App móvil** para configuración remota
- 🎯 **Configuración por rol** (diferentes temas para diferentes usuarios)

### Integraciones Futuras
- 🏢 **Integración con Active Directory** para logos corporativos
- 🌐 **API REST** para configuración programática
- 📋 **Plantillas de industria** (retail, manufactura, servicios)
- 🔗 **Sincronización con herramientas de design** (Figma, Adobe)

---

## Resumen de Implementación

✅ **Sistema 100% Funcional** - Listo para personalización completa  
✅ **Interfaz Intuitiva** - UI/UX optimizada con vista previa en tiempo real  
✅ **Seguridad Robusta** - Validaciones completas y protección de archivos  
✅ **Documentación Completa** - Guías detalladas para administradores  
✅ **Integración Perfecta** - Compatible con todo el sistema existente  

**El cliente tiene ahora control total sobre la apariencia visual del sistema, pudiendo personalizar colores, logos, fuentes, layout y efectos para adaptarlo completamente a su identidad institucional.**

---

**Versión:** 1.0  
**Fecha:** Agosto 2025  
**Estado:** ✅ Implementado y Documentado Completamente