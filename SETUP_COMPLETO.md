# 🚀 Setup Completo - Sistema de Inventarios v2.0

## ✅ Nuevas Características Implementadas

### 🎨 **Diseño Moderno con Sidebar**
- **Sidebar lateral** desplegable y colapsable
- **Diseño responsivo** que se adapta a móviles y tablets
- **Navegación moderna** con iconos y transiciones suaves
- **Toggle automático** que recuerda el estado del sidebar

### 🛠️ **Sistema de Personalización Completo**
- **Panel de configuración** para personalizar todo el sistema
- **Gestión de monedas** (símbolo, formato, decimales)
- **Temas y colores** predefinidos y personalizables
- **Modo edición** para cambiar textos sin tocar código
- **Exportar/importar** configuraciones

### 🔐 **Sistema de Permisos Avanzado**
- **Roles granulares** con permisos específicos
- **Menú dinámico** basado en permisos
- **Gestión visual** de roles y permisos
- **5 roles predefinidos** listos para usar

## 📋 Instalación Paso a Paso

### 1. **Importar Base de Datos**
```sql
-- Ejecutar AMBOS archivos en phpMyAdmin en este orden:
1. permisos_fixed.sql
2. configuracion_update.sql
```

### 2. **Verificar Estructura de Archivos**
```
inventario-claude/
├── assets/
│   ├── css/admin.css          # Estilos del nuevo diseño
│   └── js/admin.js            # JavaScript principal
├── includes/
│   ├── layout_header.php      # Nuevo header con sidebar
│   ├── layout_footer.php      # Nuevo footer
│   ├── config_functions.php   # Funciones de configuración
│   ├── toggle_edit_mode.php   # Sistema modo edición
│   └── update_label.php       # Actualizar etiquetas
├── configuracion.php          # Panel de personalización
├── roles.php                  # Gestión de roles/permisos
├── usuarios.php               # Gestión de usuarios
└── permisos_fixed.sql         # Base de datos de permisos
```

### 3. **Acceder al Sistema**
- **URL**: http://localhost/inventario-claude
- **Usuario**: admin@inventario.com
- **Contraseña**: password

## 🎯 Funcionalidades Principales

### **1. Sidebar Responsivo**
- **Clic en hamburguesa** para expandir/colapsar
- **Auto-colapso en móviles** al seleccionar opción
- **Estado persistente** que se recuerda entre sesiones

### **2. Sistema de Roles y Permisos**
#### **Roles Predefinidos:**
- **👑 Administrador**: Acceso total al sistema
- **👔 Gerente**: Gestión completa excepto configuración crítica
- **🏪 Encargado de Tienda**: Operaciones de tienda
- **💰 Vendedor**: Solo ventas y consultas básicas
- **👁️ Solo Lectura**: Solo visualización

#### **Permisos por Módulo:**
- Dashboard, Tiendas, Usuarios, Productos
- Inventarios, Ventas, Reportes, Configuración
- **4 niveles de acceso**: Crear, Leer, Actualizar, Eliminar

### **3. Panel de Personalización**
**Ve a: Menú Usuario → Configuración**

#### **🎨 Temas y Colores**
- **4 temas predefinidos**: Default, Dark Mode, Corporate Blue, Green Nature
- **Editor de colores**: Personaliza colores primarios, sidebar, topbar
- **Vista previa en tiempo real** de los cambios

#### **💰 Gestión de Monedas**
- **Múltiples monedas**: USD, EUR, MXN, COP, ARS, CLP, PEN
- **Formato personalizable**: Símbolo, posición, decimales
- **Separadores**: Decimales y miles configurables
- **Vista previa automática** del formato

#### **✏️ Modo Edición**
- **Activar desde**: Menú Usuario → "Activar Modo Edición"
- **Clic en cualquier texto** para editarlo en tiempo real
- **Sin tocar código**: Cambia nombres de menús, botones, etiquetas
- **Cambios instantáneos** que se guardan automáticamente

### **4. Configuraciones Avanzadas**
- **Información de empresa**: Nombre, dirección, contacto
- **Configuración de inventario**: Stock mínimo, alertas
- **Exportar/Importar**: Respaldar toda la configuración
- **Sistema de cache**: Para rendimiento óptimo

## 🔧 Uso del Sistema

### **Gestionar Roles y Permisos**
1. Ve a **"Roles y Permisos"** en el menú
2. **Crear rol personalizado** con nombre y descripción
3. **Asignar permisos** usando checkboxes visuales
4. **Aplicar rol** a usuarios desde "Usuarios"

### **Personalizar Apariencia**
1. Ve a **"Configuración"** → Pestaña **"Temas y Colores"**
2. **Elige tema predefinido** o **personaliza colores**
3. **Cambios se aplican inmediatamente**

### **Configurar Moneda**
1. **Configuración** → **"Moneda"**
2. Selecciona **código de moneda**
3. **Personaliza formato** y **símbolo**
4. **Vista previa en tiempo real**

### **Modo Edición**
1. **Clic en menú usuario** → **"Activar Modo Edición"**
2. **Indicador amarillo** aparece en la esquina
3. **Clic en cualquier texto editable** (destacado en azul)
4. **Edita y presiona Enter** para guardar

## 🎨 Temas Disponibles

### **Default** (Azul corporativo)
- Colores: #007bff, #2c3e50
- **Recomendado para**: Uso empresarial general

### **Dark Mode** (Oscuro moderno)
- Colores: #6c757d, #212529
- **Recomendado para**: Uso nocturno, reducir fatiga visual

### **Corporate Blue** (Azul ejecutivo)
- Colores: #0d6efd, #1a365d
- **Recomendado para**: Empresas corporativas

### **Green Nature** (Verde natural)
- Colores: #198754, #0f5132
- **Recomendado para**: Empresas eco-friendly

## 🔒 Seguridad y Permisos

### **Niveles de Acceso**
- ✅ **Crear**: Puede crear nuevos registros
- 👁️ **Leer**: Puede ver información
- ✏️ **Actualizar**: Puede modificar registros
- ❌ **Eliminar**: Puede eliminar registros

### **Validaciones**
- **Permisos por página**: Cada página valida acceso
- **Menú dinámico**: Solo muestra opciones permitidas
- **Botones condicionales**: Se ocultan según permisos
- **Sesiones seguras**: Manejo seguro de sesiones

## 📱 Compatibilidad

### **Dispositivos**
- ✅ **Desktop**: Experiencia completa
- ✅ **Tablet**: Diseño adaptativo
- ✅ **Móvil**: Sidebar overlay, menús tactiles

### **Navegadores**
- ✅ Chrome 60+
- ✅ Firefox 55+
- ✅ Safari 12+
- ✅ Edge 79+

## 🆘 Solución de Problemas

### **Sidebar no se muestra**
```javascript
// Verificar en consola:
localStorage.clear(); // Limpiar estado guardado
location.reload(); // Recargar página
```

### **Permisos no funcionan**
```sql
-- Verificar en base de datos:
SELECT * FROM usuarios WHERE email = 'tu@email.com';
SELECT * FROM roles WHERE id = [tu_rol_id];
```

### **Configuración no se guarda**
- Verificar permisos de **config_sistema**
- Comprobar conexión a base de datos
- Revisar logs de PHP

### **Modo edición no funciona**
- Verificar permisos de **config_sistema**
- Confirmar JavaScript habilitado
- Verificar sesión activa

## 🔄 Migración desde v1.0

Si ya tienes el sistema anterior:

1. **Respaldar base de datos actual**
2. **Ejecutar archivos SQL** en orden correcto
3. **Copiar nuevos archivos** al directorio
4. **Verificar permisos** de archivos PHP
5. **Probar funcionalidades** paso a paso

## 🚀 Próximas Mejoras

- 📊 **Dashboard con gráficos** interactivos
- 📧 **Sistema de notificaciones** por email
- 🔔 **Alertas en tiempo real**
- 📱 **App móvil nativa**
- 🌐 **API REST completa**

---

## 🎉 ¡Listo para Usar!

El sistema ahora cuenta con:
- **Diseño moderno** y **profesional**
- **Personalización total** sin programar
- **Control granular** de permisos
- **Experiencia responsive** completa

**¿Necesitas ayuda?** Contacta al desarrollador para soporte personalizado.

---

*Sistema de Inventarios v2.0 - Desarrollado con ❤️ usando PHP, MySQL y Bootstrap*