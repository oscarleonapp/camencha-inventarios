-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 17-08-2025 a las 03:44:03
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `inventario_sistema`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `boletas`
--

CREATE TABLE `boletas` (
  `id` int(11) NOT NULL,
  `numero` varchar(100) NOT NULL,
  `fecha` date NOT NULL,
  `proveedor` varchar(200) NOT NULL,
  `descripcion` text NOT NULL,
  `imagen` varchar(255) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias`
--

CREATE TABLE `categorias` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `categorias`
--

INSERT INTO `categorias` (`id`, `nombre`, `descripcion`, `activo`, `fecha_creacion`) VALUES
(1, 'General', 'Categoría general', 1, '2025-08-17 00:31:29'),
(2, 'Electrónicos', 'Productos electrónicos', 1, '2025-08-17 00:31:29'),
(3, 'Accesorios', 'Accesorios diversos', 1, '2025-08-17 00:31:29');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `comisiones_vendedores`
--

CREATE TABLE `comisiones_vendedores` (
  `id` int(11) NOT NULL,
  `venta_id` int(11) NOT NULL,
  `vendedor_id` int(11) NOT NULL,
  `monto_venta` decimal(10,2) NOT NULL,
  `porcentaje_comision` decimal(5,2) NOT NULL,
  `monto_comision` decimal(10,2) NOT NULL,
  `fecha_venta` date NOT NULL,
  `pagada` tinyint(1) DEFAULT 0,
  `fecha_calculo` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `compras`
--

CREATE TABLE `compras` (
  `id` int(11) NOT NULL,
  `numero` varchar(50) NOT NULL,
  `proveedor_id` int(11) NOT NULL,
  `fecha` datetime DEFAULT current_timestamp(),
  `total` decimal(10,2) DEFAULT 0.00,
  `estado` enum('pendiente','recibida','cancelada') DEFAULT 'pendiente',
  `notas` text DEFAULT NULL,
  `usuario_id` int(11) NOT NULL,
  `tienda_id` int(11) NOT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `configuraciones`
--

CREATE TABLE `configuraciones` (
  `id` int(11) NOT NULL,
  `clave` varchar(100) NOT NULL,
  `valor` text DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `tipo` enum('texto','numero','booleano','json') DEFAULT 'texto',
  `categoria` varchar(50) DEFAULT 'general',
  `fecha_modificacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `configuraciones`
--

INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `descripcion`, `tipo`, `categoria`, `fecha_modificacion`) VALUES
(1, 'nombre_empresa', 'Sistema de Inventario', 'Nombre de la empresa', 'texto', 'general', '2025-08-17 00:29:56'),
(2, 'moneda', 'USD', 'Moneda del sistema', 'texto', 'general', '2025-08-17 00:29:56'),
(3, 'tema_activo', 'default', 'Tema visual activo', 'texto', 'apariencia', '2025-08-17 00:29:56'),
(4, 'items_por_pagina', '10', 'Items por página en listados', 'texto', 'general', '2025-08-17 00:29:56'),
(5, 'color_primario', '#007bff', NULL, 'texto', 'visual', '2025-08-17 00:55:33'),
(6, 'color_secundario', '#6c757d', NULL, 'texto', 'visual', '2025-08-17 00:55:33'),
(7, 'color_exito', '#28a745', NULL, 'texto', 'visual', '2025-08-17 00:43:47'),
(8, 'color_peligro', '#dc3545', NULL, 'texto', 'visual', '2025-08-17 00:43:47'),
(9, 'color_advertencia', '#ffc107', NULL, 'texto', 'visual', '2025-08-17 00:43:47'),
(10, 'color_info', '#17a2b8', NULL, 'texto', 'visual', '2025-08-17 00:43:47'),
(11, 'color_sidebar', '#343a40', NULL, 'texto', 'visual', '2025-08-17 00:55:33'),
(12, 'color_navbar', '#ffffff', NULL, 'texto', 'visual', '2025-08-17 00:43:47'),
(13, 'color_texto', '#212529', NULL, 'texto', 'visual', '2025-08-17 00:43:47'),
(14, 'color_fondo', '#f8f9fa', NULL, 'texto', 'visual', '2025-08-17 00:43:47'),
(35, 'sidebar_estilo', 'oscuro', NULL, 'texto', 'visual', '2025-08-17 00:44:52'),
(36, 'navbar_estilo', 'oscuro', NULL, 'texto', 'visual', '2025-08-17 00:45:25'),
(37, 'bordes_redondeados', '0.5', NULL, 'texto', 'visual', '2025-08-17 00:45:10'),
(38, 'sombras_activas', '1', NULL, 'texto', 'visual', '2025-08-17 00:44:52'),
(39, 'animaciones_activas', '1', NULL, 'texto', 'visual', '2025-08-17 00:44:52'),
(40, 'modo_compacto', '1', NULL, 'texto', 'visual', '2025-08-17 00:44:52');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cotizaciones`
--

CREATE TABLE `cotizaciones` (
  `id` int(11) NOT NULL,
  `numero` varchar(50) NOT NULL,
  `cliente_nombre` varchar(200) NOT NULL,
  `cliente_email` varchar(100) DEFAULT NULL,
  `cliente_telefono` varchar(20) DEFAULT NULL,
  `fecha` datetime DEFAULT current_timestamp(),
  `total` decimal(10,2) DEFAULT 0.00,
  `estado` enum('borrador','enviada','aceptada','rechazada') DEFAULT 'borrador',
  `notas` text DEFAULT NULL,
  `usuario_id` int(11) NOT NULL,
  `tienda_id` int(11) NOT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_compras`
--

CREATE TABLE `detalle_compras` (
  `id` int(11) NOT NULL,
  `compra_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_cotizaciones`
--

CREATE TABLE `detalle_cotizaciones` (
  `id` int(11) NOT NULL,
  `cotizacion_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_ventas`
--

CREATE TABLE `detalle_ventas` (
  `id` int(11) NOT NULL,
  `venta_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `etiquetas_personalizadas`
--

CREATE TABLE `etiquetas_personalizadas` (
  `id` int(11) NOT NULL,
  `clave` varchar(100) NOT NULL,
  `valor` varchar(255) NOT NULL,
  `modulo` varchar(50) DEFAULT 'general',
  `fecha_modificacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventarios`
--

CREATE TABLE `inventarios` (
  `id` int(11) NOT NULL,
  `tienda_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `cantidad` int(11) DEFAULT 0,
  `cantidad_reparacion` int(11) DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `cantidad_minima` int(11) DEFAULT 5
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `logs_sistema`
--

CREATE TABLE `logs_sistema` (
  `id` int(11) NOT NULL,
  `tipo` enum('info','warning','error','debug') DEFAULT 'info',
  `modulo` varchar(50) DEFAULT NULL,
  `mensaje` text NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `logs_sistema`
--

INSERT INTO `logs_sistema` (`id`, `tipo`, `modulo`, `mensaje`, `usuario_id`, `ip`, `fecha`) VALUES
(1, 'info', 'configuracion', 'Configuración modificada: colores_sistema', 1, '::1', '2025-08-17 00:55:33');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `movimientos_inventario`
--

CREATE TABLE `movimientos_inventario` (
  `id` int(11) NOT NULL,
  `tipo` enum('entrada','salida','transferencia','ajuste','devolucion') NOT NULL,
  `producto_id` int(11) NOT NULL,
  `tienda_origen_id` int(11) DEFAULT NULL,
  `tienda_destino_id` int(11) DEFAULT NULL,
  `cantidad` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `fecha` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `permisos`
--

CREATE TABLE `permisos` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `modulo` varchar(50) DEFAULT NULL,
  `orden` int(11) DEFAULT 0,
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `permisos`
--

INSERT INTO `permisos` (`id`, `nombre`, `descripcion`, `modulo`, `orden`, `activo`) VALUES
(1, 'dashboard', 'Ver dashboard', 'dashboard', 0, 1),
(2, 'productos_leer', 'Ver productos', 'productos', 0, 1),
(3, 'productos_crear', 'Crear productos', 'productos', 0, 1),
(4, 'inventarios_leer', 'Ver inventarios', 'inventarios', 0, 1),
(5, 'ventas_leer', 'Ver ventas', 'ventas', 0, 1),
(6, 'ventas_crear', 'Crear ventas', 'ventas', 0, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

CREATE TABLE `productos` (
  `id` int(11) NOT NULL,
  `codigo` varchar(50) NOT NULL,
  `nombre` varchar(200) NOT NULL,
  `precio_venta` decimal(10,2) DEFAULT 0.00,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `proveedor_id` int(11) DEFAULT NULL,
  `categoria_id` int(11) DEFAULT NULL,
  `precio_compra` decimal(10,2) DEFAULT 0.00,
  `descripcion` text DEFAULT NULL,
  `tipo` enum('elemento','conjunto') DEFAULT 'elemento'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `producto_componentes`
--

CREATE TABLE `producto_componentes` (
  `id` int(11) NOT NULL,
  `producto_conjunto_id` int(11) NOT NULL,
  `producto_elemento_id` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `proveedores`
--

CREATE TABLE `proveedores` (
  `id` int(11) NOT NULL,
  `nombre` varchar(200) NOT NULL,
  `contacto` varchar(100) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `proveedores`
--

INSERT INTO `proveedores` (`id`, `nombre`, `contacto`, `telefono`, `email`, `direccion`, `activo`, `fecha_creacion`) VALUES
(1, 'Proveedor General', 'Contacto General', NULL, NULL, NULL, 1, '2025-08-17 00:31:29'),
(2, 'Sin Proveedor', 'N/A', NULL, NULL, NULL, 1, '2025-08-17 00:31:29');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reparaciones`
--

CREATE TABLE `reparaciones` (
  `id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `tienda_id` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `estado` enum('enviado','en_reparacion','completado','perdido') DEFAULT 'enviado',
  `costo_reparacion` decimal(10,2) DEFAULT 0.00,
  `fecha_envio` datetime DEFAULT current_timestamp(),
  `fecha_completado` datetime DEFAULT NULL,
  `usuario_envio_id` int(11) NOT NULL,
  `usuario_retorno_id` int(11) DEFAULT NULL,
  `notas` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `es_sistema` tinyint(1) DEFAULT 0,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id`, `nombre`, `descripcion`, `es_sistema`, `activo`, `fecha_creacion`) VALUES
(1, 'admin', 'Administrador del sistema', 1, 1, '2025-08-17 00:29:56'),
(2, 'encargado', 'Encargado de tienda', 1, 1, '2025-08-17 00:29:56'),
(3, 'vendedor', 'Vendedor', 1, 1, '2025-08-17 00:29:56');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rol_permisos`
--

CREATE TABLE `rol_permisos` (
  `id` int(11) NOT NULL,
  `rol_id` int(11) NOT NULL,
  `permiso_id` int(11) NOT NULL,
  `puede_crear` tinyint(1) DEFAULT 0,
  `puede_leer` tinyint(1) DEFAULT 0,
  `puede_actualizar` tinyint(1) DEFAULT 0,
  `puede_eliminar` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `rol_permisos`
--

INSERT INTO `rol_permisos` (`id`, `rol_id`, `permiso_id`, `puede_crear`, `puede_leer`, `puede_actualizar`, `puede_eliminar`) VALUES
(1, 1, 1, 1, 1, 1, 1),
(2, 1, 4, 1, 1, 1, 1),
(3, 1, 3, 1, 1, 1, 1),
(4, 1, 2, 1, 1, 1, 1),
(5, 1, 6, 1, 1, 1, 1),
(6, 1, 5, 1, 1, 1, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `temas_sistema`
--

CREATE TABLE `temas_sistema` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `css_file` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 0,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tiendas`
--

CREATE TABLE `tiendas` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `tiendas`
--

INSERT INTO `tiendas` (`id`, `nombre`, `activo`, `fecha_creacion`) VALUES
(1, 'Tienda Principal', 1, '2025-08-17 00:27:49');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `rol` varchar(50) DEFAULT 'admin',
  `rol_id` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `email`, `password`, `activo`, `fecha_creacion`, `rol`, `rol_id`) VALUES
(1, 'Administrador', 'admin@inventario.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, '2025-08-17 00:27:49', 'admin', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `vendedores`
--

CREATE TABLE `vendedores` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `comision_porcentaje` decimal(5,2) DEFAULT 0.00,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ventas`
--

CREATE TABLE `ventas` (
  `id` int(11) NOT NULL,
  `fecha` datetime DEFAULT current_timestamp(),
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `vendedor_id` int(11) DEFAULT NULL,
  `tienda_id` int(11) NOT NULL DEFAULT 1,
  `usuario_id` int(11) NOT NULL DEFAULT 1,
  `estado` enum('completada','reembolsada','pendiente') DEFAULT 'completada',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `boletas`
--
ALTER TABLE `boletas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero` (`numero`);

--
-- Indices de la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `comisiones_vendedores`
--
ALTER TABLE `comisiones_vendedores`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `compras`
--
ALTER TABLE `compras`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero` (`numero`);

--
-- Indices de la tabla `configuraciones`
--
ALTER TABLE `configuraciones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `clave` (`clave`);

--
-- Indices de la tabla `cotizaciones`
--
ALTER TABLE `cotizaciones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero` (`numero`);

--
-- Indices de la tabla `detalle_compras`
--
ALTER TABLE `detalle_compras`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `detalle_cotizaciones`
--
ALTER TABLE `detalle_cotizaciones`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `detalle_ventas`
--
ALTER TABLE `detalle_ventas`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `etiquetas_personalizadas`
--
ALTER TABLE `etiquetas_personalizadas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `clave` (`clave`);

--
-- Indices de la tabla `inventarios`
--
ALTER TABLE `inventarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tienda_producto` (`tienda_id`,`producto_id`);

--
-- Indices de la tabla `logs_sistema`
--
ALTER TABLE `logs_sistema`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `movimientos_inventario`
--
ALTER TABLE `movimientos_inventario`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `permisos`
--
ALTER TABLE `permisos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`);

--
-- Indices de la tabla `producto_componentes`
--
ALTER TABLE `producto_componentes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `conjunto_elemento` (`producto_conjunto_id`,`producto_elemento_id`);

--
-- Indices de la tabla `proveedores`
--
ALTER TABLE `proveedores`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `reparaciones`
--
ALTER TABLE `reparaciones`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `rol_permisos`
--
ALTER TABLE `rol_permisos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `rol_permiso` (`rol_id`,`permiso_id`);

--
-- Indices de la tabla `temas_sistema`
--
ALTER TABLE `temas_sistema`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `tiendas`
--
ALTER TABLE `tiendas`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indices de la tabla `vendedores`
--
ALTER TABLE `vendedores`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `ventas`
--
ALTER TABLE `ventas`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `boletas`
--
ALTER TABLE `boletas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `comisiones_vendedores`
--
ALTER TABLE `comisiones_vendedores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `compras`
--
ALTER TABLE `compras`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `configuraciones`
--
ALTER TABLE `configuraciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT de la tabla `cotizaciones`
--
ALTER TABLE `cotizaciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `detalle_compras`
--
ALTER TABLE `detalle_compras`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `detalle_cotizaciones`
--
ALTER TABLE `detalle_cotizaciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `detalle_ventas`
--
ALTER TABLE `detalle_ventas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `etiquetas_personalizadas`
--
ALTER TABLE `etiquetas_personalizadas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `inventarios`
--
ALTER TABLE `inventarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `logs_sistema`
--
ALTER TABLE `logs_sistema`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `movimientos_inventario`
--
ALTER TABLE `movimientos_inventario`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `permisos`
--
ALTER TABLE `permisos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `producto_componentes`
--
ALTER TABLE `producto_componentes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `proveedores`
--
ALTER TABLE `proveedores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `reparaciones`
--
ALTER TABLE `reparaciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `rol_permisos`
--
ALTER TABLE `rol_permisos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `temas_sistema`
--
ALTER TABLE `temas_sistema`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tiendas`
--
ALTER TABLE `tiendas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `vendedores`
--
ALTER TABLE `vendedores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ventas`
--
ALTER TABLE `ventas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
