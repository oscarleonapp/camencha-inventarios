# 🔧 INSTRUCCIONES DE INSTALACIÓN - SISTEMA INVENTARIO

## ⚡ Instalación Rápida

### 1. Pre-requisitos
- **XAMPP** instalado y funcionando
- **MySQL/MariaDB** corriendo
- **PHP 8.0+** activo

### 2. Instalar Base de Datos

#### Opción A: Desde phpMyAdmin (Recomendado)
1. Abrir **phpMyAdmin** en: `http://localhost/phpmyadmin`
2. Ir a la pestaña **"Importar"**
3. Seleccionar el archivo: `instalar_bd.sql`
4. Hacer clic en **"Continuar"**

#### Opción B: Desde línea de comandos
```bash
# Navegar a la carpeta del proyecto
cd C:\xampp\htdocs\inventario-claude

# Ejecutar el script SQL
mysql -u root -p < instalar_bd.sql
```

### 3. Verificar Instalación
1. La base de datos `inventario_sistema` debe estar creada
2. Debe contener **16 tablas**
3. Usuario admin debe existir: `admin@inventario.com`

### 4. Acceder al Sistema
- **URL**: `http://localhost/inventario-claude/`
- **Usuario**: `admin@inventario.com`
- **Contraseña**: `password`

---

## 🛠️ Resolución de Problemas Comunes

### Error: "Base de datos no encontrada"
```
✅ SOLUCIÓN:
- Verificar que MySQL esté corriendo en XAMPP
- Ejecutar nuevamente instalar_bd.sql
- Revisar config/database.php
```

### Error: "Access denied for user 'root'"
```
✅ SOLUCIÓN:
- En phpMyAdmin, ir a "Cuentas de usuario"
- Verificar que el usuario 'root' no tenga contraseña
- O actualizar config/database.php con la contraseña correcta
```

### Error: "Cannot load file instalar_bd.sql"
```
✅ SOLUCIÓN:
- Verificar que el archivo esté en la carpeta del proyecto
- Usar phpMyAdmin en lugar de línea de comandos
- Verificar permisos de archivo
```

---

## 📋 Verificación Post-Instalación

### Tablas creadas (16 total):
- ✅ roles
- ✅ permisos  
- ✅ rol_permisos
- ✅ tiendas
- ✅ usuarios
- ✅ productos
- ✅ producto_componentes
- ✅ inventarios
- ✅ vendedores
- ✅ ventas
- ✅ detalle_ventas
- ✅ comisiones_vendedores
- ✅ reparaciones
- ✅ traslados
- ✅ reembolsos
- ✅ configuraciones
- ✅ etiquetas_personalizadas
- ✅ temas_sistema

### Datos iniciales:
- **Usuario admin**: admin@inventario.com / password
- **Roles**: admin, encargado, vendedor
- **Tiendas**: Tienda Principal, Sucursal Norte
- **Productos**: 5 productos de ejemplo
- **Vendedores**: 3 vendedores de ejemplo

---

## 🔐 Configuración de Seguridad

El sistema incluye:
- ✅ Protección CSRF
- ✅ Sesiones seguras
- ✅ Validación de entrada
- ✅ Output encoding (XSS)
- ✅ Cabeceras de seguridad HTTP
- ✅ Logging seguro de errores

---

## 📞 Soporte

### Archivos importantes:
- `config/database.php` - Configuración BD
- `includes/auth.php` - Autenticación
- `CLAUDE.md` - Documentación completa

### URLs de prueba:
- Dashboard: `http://localhost/inventario-claude/`
- Login: `http://localhost/inventario-claude/login.php`
- Productos: `http://localhost/inventario-claude/productos.php`

---

**✅ SISTEMA LISTO PARA USAR**

*Versión 2.1 - Agosto 2025 - Sistema Inventario Completo*