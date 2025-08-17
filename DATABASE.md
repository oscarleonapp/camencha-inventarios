# 📊 Documentación de Base de Datos - Sistema de Inventario

## Información General

- **Nombre**: `inventario-camencha-completa`
- **Engine**: InnoDB (transaccional)
- **Charset**: utf8mb4_general_ci
- **Versión**: Compatible MySQL 5.7+ / MariaDB 10.3+

## 📋 Estructura Completa de Tablas

### 🏢 **Tabla: tiendas**
Gestión de sucursales/ubicaciones del negocio.

```sql
CREATE TABLE `tiendas` (
  `id` int(11) PRIMARY KEY AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `direccion` text DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `encargado_id` int(11) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`encargado_id`) REFERENCES `usuarios`(`id`)
);
```

### 👤 **Tabla: usuarios**
Sistema de usuarios con roles y permisos.

```sql
CREATE TABLE `usuarios` (
  `id` int(11) PRIMARY KEY AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) UNIQUE NOT NULL,
  `password` varchar(255) NOT NULL, -- Hash bcrypt
  `rol` varchar(50) DEFAULT NULL, -- Legacy
  `rol_id` int(11) DEFAULT NULL,
  `tienda_id` int(11) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`rol_id`) REFERENCES `roles`(`id`),
  FOREIGN KEY (`tienda_id`) REFERENCES `tiendas`(`id`)
);
```

### 🔐 **Sistema de Roles y Permisos**

#### **Tabla: roles**
```sql
CREATE TABLE `roles` (
  `id` int(11) PRIMARY KEY AUTO_INCREMENT,
  `nombre` varchar(50) UNIQUE NOT NULL,
  `descripcion` text DEFAULT NULL,
  `es_sistema` tinyint(1) DEFAULT 0, -- Roles protegidos
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp DEFAULT CURRENT_TIMESTAMP
);
```

#### **Tabla: permisos**
```sql
CREATE TABLE `permisos` (
  `id` int(11) PRIMARY KEY AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `orden` int(11) DEFAULT 0,
  `modulo` varchar(50) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp DEFAULT CURRENT_TIMESTAMP
);
```

#### **Tabla: rol_permisos**
```sql
CREATE TABLE `rol_permisos` (
  `id` int(11) PRIMARY KEY AUTO_INCREMENT,
  `rol_id` int(11) NOT NULL,
  `permiso_id` int(11) NOT NULL,
  `puede_crear` tinyint(1) DEFAULT 0,
  `puede_leer` tinyint(1) DEFAULT 1,
  `puede_actualizar` tinyint(1) DEFAULT 0,
  `puede_eliminar` tinyint(1) DEFAULT 0,
  `fecha_asignacion` timestamp DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_rol_permiso` (`rol_id`, `permiso_id`),
  FOREIGN KEY (`rol_id`) REFERENCES `roles`(`id`),
  FOREIGN KEY (`permiso_id`) REFERENCES `permisos`(`id`)
);
```

### 📦 **Sistema de Productos**

#### **Tabla: productos**
Catálogo principal de productos (elementos y conjuntos).

```sql
CREATE TABLE `productos` (
  `id` int(11) PRIMARY KEY AUTO_INCREMENT,
  `codigo` varchar(50) UNIQUE NOT NULL,
  `nombre` varchar(200) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `precio_venta` decimal(10,2) NOT NULL DEFAULT 0.00,
  `precio_compra` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tipo` enum('elemento','conjunto') DEFAULT 'elemento',
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp DEFAULT CURRENT_TIMESTAMP
);
```

#### **Tabla: producto_componentes**
Relación de componentes para productos tipo "conjunto".

```sql
CREATE TABLE `producto_componentes` (
  `id` int(11) PRIMARY KEY AUTO_INCREMENT,
  `producto_conjunto_id` int(11) NOT NULL,
  `producto_elemento_id` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1,
  `fecha_creacion` timestamp DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`producto_conjunto_id`) REFERENCES `productos`(`id`),
  FOREIGN KEY (`producto_elemento_id`) REFERENCES `productos`(`id`)
);
```

### 📋 **Sistema de Inventarios**

#### **Tabla: inventarios**
Control de stock por producto y tienda.

```sql
CREATE TABLE `inventarios` (
  `id` int(11) PRIMARY KEY AUTO_INCREMENT,
  `tienda_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `cantidad` int(11) DEFAULT 0,
  `cantidad_minima` int(11) DEFAULT 5,
  `cantidad_reparacion` int(11) DEFAULT 0, -- Stock en reparación
  `ubicacion` varchar(100) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  UNIQUE KEY `unique_inventario` (`tienda_id`, `producto_id`),
  FOREIGN KEY (`tienda_id`) REFERENCES `tiendas`(`id`),
  FOREIGN KEY (`producto_id`) REFERENCES `productos`(`id`)
);
```

#### **Tabla: movimientos_inventario**
Historial de todos los movimientos de stock.

```sql
CREATE TABLE `movimientos_inventario` (
  `id` int(11) PRIMARY KEY AUTO_INCREMENT,
  `tipo` enum('entrada','salida','transferencia','ajuste','devolucion') NOT NULL,
  `producto_id` int(11) NOT NULL,
  `tienda_origen_id` int(11) DEFAULT NULL,
  `tienda_destino_id` int(11) DEFAULT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(10,2) DEFAULT NULL,
  `total` decimal(10,2) DEFAULT NULL,
  `motivo` varchar(255) DEFAULT NULL,
  `referencia_id` int(11) DEFAULT NULL,
  `referencia_tipo` enum('venta','compra','transferencia','ajuste','devolucion') DEFAULT NULL,
  `usuario_id` int(11) NOT NULL,
  `fecha` datetime DEFAULT current_timestamp(),
  `notas` text DEFAULT NULL,
  FOREIGN KEY (`producto_id`) REFERENCES `productos`(`id`),
  FOREIGN KEY (`tienda_origen_id`) REFERENCES `tiendas`(`id`),
  FOREIGN KEY (`tienda_destino_id`) REFERENCES `tiendas`(`id`),
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`)
);
```

### 💰 **Sistema de Ventas**

#### **Tabla: ventas**
Registro principal de ventas.

```sql
CREATE TABLE `ventas` (
  `id` int(11) PRIMARY KEY AUTO_INCREMENT,
  `tienda_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `vendedor_id` int(11) DEFAULT NULL, -- NUEVO: Vendedor asignado
  `fecha_venta` timestamp DEFAULT CURRENT_TIMESTAMP,
  `total` decimal(10,2) NOT NULL,
  `estado` enum('completada','reembolsada','pendiente') DEFAULT 'completada',
  `razon_reembolso` varchar(255) DEFAULT NULL,
  `fecha_reembolso` timestamp NULL DEFAULT NULL,
  `notas` text DEFAULT NULL,
  FOREIGN KEY (`tienda_id`) REFERENCES `tiendas`(`id`),
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`),
  FOREIGN KEY (`vendedor_id`) REFERENCES `vendedores`(`id`)
);
```

#### **Tabla: detalle_ventas**
Items específicos de cada venta.

```sql
CREATE TABLE `detalle_ventas` (
  `id` int(11) PRIMARY KEY AUTO_INCREMENT,
  `venta_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  FOREIGN KEY (`venta_id`) REFERENCES `ventas`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`producto_id`) REFERENCES `productos`(`id`)
);
```

### 👥 **Sistema de Vendedores** ⭐ NUEVO

#### **Tabla: vendedores**
```sql
CREATE TABLE `vendedores` (
  `id` int(11) PRIMARY KEY AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `email` varchar(100) UNIQUE DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `comision_porcentaje` decimal(5,2) DEFAULT 0.00,
  `tienda_principal_id` int(11) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_contratacion` date DEFAULT NULL,
  `fecha_creacion` timestamp DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`tienda_principal_id`) REFERENCES `tiendas`(`id`)
);
```

#### **Tabla: comisiones_vendedores**
```sql
CREATE TABLE `comisiones_vendedores` (
  `id` int(11) PRIMARY KEY AUTO_INCREMENT,
  `venta_id` int(11) NOT NULL,
  `vendedor_id` int(11) NOT NULL,
  `monto_venta` decimal(10,2) NOT NULL,
  `porcentaje_comision` decimal(5,2) NOT NULL,
  `monto_comision` decimal(10,2) NOT NULL,
  `fecha_venta` date NOT NULL,
  `mes_comision` varchar(7) NOT NULL, -- YYYY-MM
  `pagada` tinyint(1) DEFAULT 0,
  `fecha_pago` date DEFAULT NULL,
  `notas_pago` text DEFAULT NULL,
  `fecha_calculo` timestamp DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_venta_vendedor` (`venta_id`, `vendedor_id`),
  FOREIGN KEY (`venta_id`) REFERENCES `ventas`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`vendedor_id`) REFERENCES `vendedores`(`id`)
);
```

### 🔧 **Sistema de Reparaciones** ⭐ NUEVO

#### **Tabla: reparaciones**
```sql
CREATE TABLE `reparaciones` (
  `id` int(11) PRIMARY KEY AUTO_INCREMENT,
  `tienda_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1,
  `estado` enum('enviado','en_reparacion','completado','perdido') DEFAULT 'enviado',
  `motivo` varchar(255) DEFAULT NULL,
  `costo_reparacion` decimal(10,2) DEFAULT 0.00,
  `proveedor_reparacion` varchar(100) DEFAULT NULL,
  `fecha_envio` timestamp DEFAULT CURRENT_TIMESTAMP,
  `fecha_retorno` timestamp NULL DEFAULT NULL,
  `usuario_envio_id` int(11) NOT NULL,
  `usuario_retorno_id` int(11) DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `numero_orden` varchar(50) DEFAULT NULL,
  FOREIGN KEY (`tienda_id`) REFERENCES `tiendas`(`id`),
  FOREIGN KEY (`producto_id`) REFERENCES `productos`(`id`),
  FOREIGN KEY (`usuario_envio_id`) REFERENCES `usuarios`(`id`),
  FOREIGN KEY (`usuario_retorno_id`) REFERENCES `usuarios`(`id`)
);
```

### ⚙️ **Sistema de Configuración** ⭐ NUEVO

#### **Tabla: configuraciones**
Parámetros configurables del sistema.

```sql
CREATE TABLE `configuraciones` (
  `id` int(11) PRIMARY KEY AUTO_INCREMENT,
  `clave` varchar(100) UNIQUE NOT NULL,
  `valor` text NOT NULL,
  `tipo` enum('texto','numero','booleano','json') DEFAULT 'texto',
  `descripcion` varchar(255) DEFAULT NULL,
  `categoria` varchar(50) DEFAULT 'general',
  `actualizado_por` int(11) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

**Configuraciones por defecto**:
- `nombre_empresa`, `simbolo_moneda`, `decimales_mostrar`
- `color_primario`, `color_secundario`, `tema_actual`
- `zona_horaria`, `idioma`, `limite_productos_por_pagina`
- `permitir_stock_negativo`, `alerta_stock_minimo`

#### **Tabla: etiquetas_personalizadas**
Personalización de textos de interfaz.

```sql
CREATE TABLE `etiquetas_personalizadas` (
  `id` int(11) PRIMARY KEY AUTO_INCREMENT,
  `clave` varchar(100) UNIQUE NOT NULL,
  `valor_original` varchar(255) NOT NULL,
  `valor_personalizado` varchar(255) DEFAULT NULL,
  `categoria` varchar(50) DEFAULT 'general',
  `actualizado_por` int(11) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

#### **Tabla: temas_sistema**
Gestión de temas visuales.

```sql
CREATE TABLE `temas_sistema` (
  `id` int(11) PRIMARY KEY AUTO_INCREMENT,
  `nombre` varchar(50) UNIQUE NOT NULL,
  `nombre_display` varchar(100) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `color_primario` varchar(7) DEFAULT '#007bff',
  `color_secundario` varchar(7) DEFAULT '#6c757d',
  `color_success` varchar(7) DEFAULT '#28a745',
  `color_danger` varchar(7) DEFAULT '#dc3545',
  `color_warning` varchar(7) DEFAULT '#ffc107',
  `color_info` varchar(7) DEFAULT '#17a2b8',
  `sidebar_color` varchar(7) DEFAULT '#2c3e50',
  `topbar_color` varchar(7) DEFAULT '#007bff',
  `es_default` tinyint(1) DEFAULT 0,
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

## 🔄 Relaciones y Claves Foráneas

### Principales Relaciones:
1. **usuarios** ↔ **tiendas** (encargado_id, tienda_id)
2. **productos** ↔ **producto_componentes** (conjuntos-elementos)
3. **inventarios** ↔ **tiendas** + **productos** (stock por ubicación)
4. **ventas** ↔ **detalle_ventas** (maestro-detalle)
5. **ventas** ↔ **vendedores** (comisiones)
6. **reparaciones** ↔ **inventarios** (stock reservado)
7. **roles** ↔ **permisos** via **rol_permisos** (many-to-many)

### Integridad Referencial:
- Todas las FK con `RESTRICT` por defecto
- `detalle_ventas` con `CASCADE` en eliminación de venta
- `comisiones_vendedores` con `CASCADE` en eliminación de venta

## 📊 Índices de Performance

### Índices Principales:
```sql
-- Búsquedas frecuentes
CREATE INDEX idx_ventas_fecha ON ventas(fecha_venta);
CREATE INDEX idx_movimientos_fecha ON movimientos_inventario(fecha);
CREATE INDEX idx_vendedor_mes ON comisiones_vendedores(vendedor_id, mes_comision);
CREATE INDEX idx_reparaciones_estado ON reparaciones(estado);

-- Consultas de inventario
CREATE INDEX idx_inventario_tienda ON inventarios(tienda_id);
CREATE INDEX idx_inventario_producto ON inventarios(producto_id);

-- Sistema de permisos
CREATE INDEX idx_rol_permisos_rol ON rol_permisos(rol_id);
```

## 🔧 Triggers y Procedimientos

### Triggers Implementados:

#### **Actualización de Comisiones**
```sql
-- Trigger en ventas para calcular comisiones automáticamente
-- Se ejecuta en INSERT/UPDATE de tabla ventas
```

#### **Control de Stock en Reparaciones**
```sql
-- Trigger para actualizar cantidad_reparacion en inventarios
-- Se ejecuta en INSERT/UPDATE/DELETE de tabla reparaciones
```

#### **Historial de Movimientos**
```sql
-- Trigger para registrar automáticamente movimientos
-- Se ejecuta en UPDATE de inventarios.cantidad
```

## 🚀 Optimizaciones de Performance

### Particionamiento:
- **movimientos_inventario**: Partición por mes/año
- **comisiones_vendedores**: Partición por mes_comision

### Configuraciones Recomendadas:
```sql
-- MySQL/MariaDB optimizado para inventario
SET innodb_buffer_pool_size = 256M;
SET innodb_log_file_size = 64M;
SET query_cache_size = 32M;
SET max_connections = 200;
```

## 📋 Datos de Ejemplo

### Usuarios por Defecto:
- **Admin**: admin@inventario.com / password (rol: admin)

### Roles por Defecto:
- **admin**: Acceso completo al sistema
- **encargado**: Acceso limitado por tienda

### Permisos por Módulo:
- dashboard, productos_*, inventarios_*, ventas_*
- reparaciones_*, usuarios_*, config_*

### Configuraciones por Defecto:
- Empresa: "CAMENCHA" - Guatemala, Bolivar
- Moneda: "$" con 2 decimales
- Tema: "default" (azul corporativo)

## 🔍 Consultas Útiles

### Dashboard Estadísticas:
```sql
-- Ventas del mes actual
SELECT COUNT(*) as total_ventas, SUM(total) as monto_total 
FROM ventas 
WHERE MONTH(fecha_venta) = MONTH(CURDATE()) 
AND YEAR(fecha_venta) = YEAR(CURDATE());

-- Top 10 vendedores
SELECT v.nombre, v.apellido, COUNT(ve.id) as ventas, 
       SUM(ve.total) as total_vendido,
       SUM(c.monto_comision) as comisiones
FROM vendedores v
LEFT JOIN ventas ve ON v.id = ve.vendedor_id
LEFT JOIN comisiones_vendedores c ON v.id = c.vendedor_id
WHERE MONTH(ve.fecha_venta) = MONTH(CURDATE())
GROUP BY v.id
ORDER BY total_vendido DESC
LIMIT 10;
```

### Inventario Crítico:
```sql
SELECT p.codigo, p.nombre,
       (i.cantidad - COALESCE(i.cantidad_reparacion,0)) AS cantidad_disponible,
       i.cantidad_minima,
       t.nombre AS tienda
FROM inventarios i
JOIN productos p ON i.producto_id = p.id
JOIN tiendas t ON i.tienda_id = t.id
WHERE (i.cantidad - COALESCE(i.cantidad_reparacion,0)) <= i.cantidad_minima
ORDER BY (i.cantidad - COALESCE(i.cantidad_reparacion,0)) ASC;
```

### Reparaciones Pendientes:
```sql
SELECT r.*, p.codigo, p.nombre, t.nombre as tienda,
       DATEDIFF(NOW(), r.fecha_envio) as dias_pendiente
FROM reparaciones r
JOIN productos p ON r.producto_id = p.id
JOIN tiendas t ON r.tienda_id = t.id
WHERE r.estado IN ('enviado', 'en_reparacion')
ORDER BY r.fecha_envio ASC;
```

## 📁 Scripts de Mantenimiento

### Backup Completo:
```bash
mysqldump -u root -p inventario-camencha-completa > backup_$(date +%Y%m%d).sql
```

### Limpieza de Logs:
```sql
-- Limpiar movimientos antiguos (más de 1 año)
DELETE FROM movimientos_inventario 
WHERE fecha < DATE_SUB(NOW(), INTERVAL 1 YEAR);

-- Archivar comisiones pagadas antiguas
-- (Mover a tabla de archivo histórico)
```

---

**Base de Datos actualizada**: Agosto 2025  
**Compatibilidad**: MySQL 5.7+ / MariaDB 10.3+  
**Tamaño estimado**: ~50MB con datos operativos  
**Performance**: Optimizada para hasta 10,000 productos y 100,000 transacciones
