# Sistema de Control de Ingresos Multi-Nivel

## Descripción General
Sistema completo de control de ingresos con flujo de aprobación de 3 niveles: **Encargado → Gerente → Contabilidad**. Permite el seguimiento y validación de todas las ventas desde el reporte inicial hasta la reconciliación final con las boletas físicas.

## Flujo de Trabajo Completo

### 1. **Nivel Encargado - Reporte Diario** (`reporte_ingresos_diario.php`)
**Responsable:** Encargados de tienda  
**Función:** Reportar los ingresos diarios reales por método de pago

#### Características:
- Desglose por método de pago (efectivo, tarjeta, transferencia, otros)
- Comparación automática con datos del sistema
- Cálculo de diferencias en tiempo real
- Validación de fechas (no permite fechas futuras)
- Un reporte por tienda por día
- Actualización permitida solo si no está aprobado

#### Datos Capturados:
- Fecha del reporte
- Total por método de pago
- Observaciones del encargado
- Cálculo automático de total general
- Comparación con ventas del sistema

### 2. **Nivel Gerente - Supervisión** (`gerente_dashboard.php`)
**Responsable:** Gerentes de tienda/región  
**Función:** Revisar y aprobar/rechazar reportes de encargados

#### Características:
- Dashboard con estadísticas del día por tienda
- Comparación detallada: Reportado vs Sistema
- Análisis de diferencias con alertas automáticas
- Filtros por fecha y tienda
- Aprobación masiva o individual
- Motivos categorizados para rechazos

#### Funcionalidades:
- Vista consolidada de todas las tiendas
- Alertas para diferencias significativas (>Q50 o >5%)
- Detalle completo de cada reporte
- Generación de reportes gerenciales
- Seguimiento de patrones de diferencias

#### Estados de Aprobación:
- `pendiente` - Esperando revisión del gerente
- `aprobado_gerente` - Aprobado, pasa a contabilidad
- `rechazado_gerente` - Rechazado, regresa al encargado

### 3. **Nivel Contabilidad - Reconciliación Final** (`contabilidad_reconciliacion.php`)
**Responsable:** Departamento de contabilidad  
**Función:** Reconciliación final con boletas físicas

#### Características:
- Comparación triple: Sistema ↔ Reportado ↔ Boletas Físicas
- Gestión de boletas físicas con imágenes
- Sugerencias automáticas basadas en ventas del sistema
- Control de diferencias con tolerancias configurables
- Verificación individual de boletas

#### Proceso de Reconciliación:
1. **Recepción automática** de reportes aprobados por gerentes
2. **Registro de boletas físicas** con validación de imágenes
3. **Comparación automática** de totales y cantidades
4. **Análisis de diferencias** con recomendaciones del sistema
5. **Aprobación final** o marcado como "con diferencias"

## Base de Datos

### Tablas Principales

#### `reportes_diarios_encargado`
```sql
- id (PK)
- tienda_id (FK tiendas)
- fecha_reporte (DATE)
- encargado_id (FK usuarios)
- total_efectivo, total_tarjeta, total_transferencia, total_otros
- total_general (GENERATED COLUMN)
- observaciones
- estado (ENUM: pendiente, aprobado_gerente, rechazado_gerente, etc.)
- gerente_id, fecha_revision_gerente, observaciones_gerente
- contabilidad_id, fecha_revision_contabilidad, observaciones_contabilidad
```

#### `reconciliacion_boletas`
```sql
- id (PK)
- tienda_id (FK tiendas)
- fecha_reconciliacion (DATE)
- reporte_diario_id (FK reportes_diarios_encargado)
- total_sistema, ventas_sistema
- total_boletas_fisicas, cantidad_boletas_fisicas
- diferencia_monto, diferencia_cantidad (GENERATED COLUMNS)
- estado (ENUM: pendiente, revisando, aprobado, con_diferencias, rechazado)
- usuario_contabilidad_id
- observaciones
```

#### `detalle_boletas_fisicas`
```sql
- id (PK)
- reconciliacion_id (FK reconciliacion_boletas)
- numero_boleta, fecha_boleta, total_boleta
- metodo_pago (ENUM: efectivo, tarjeta, transferencia, otros)
- observaciones, imagen_boleta
- verificado (BOOLEAN)
```

### Extensión de Tablas Existentes

#### `ventas_reportadas_vendedor` (Actualizada)
- Agregados campos para flujo multi-nivel
- Estados expandidos para incluir aprobaciones de gerente y contabilidad
- Referencias a usuarios aprobadores en cada nivel

## Características Avanzadas

### 1. **Sistema de Matching Inteligente**
- **Sugerencias automáticas** basadas en ventas del sistema
- **Matching por monto y hora** para facilitar identificación
- **Tolerancias configurables** para diferencias aceptables

### 2. **Validación de Boletas Físicas**
- **Subida de imágenes** con validación de formato y tamaño
- **Numeración única** por boleta para evitar duplicados
- **Verificación individual** con trazabilidad completa
- **Metadatos completos** (fecha, hora, método de pago, observaciones)

### 3. **Análisis de Diferencias Automático**
- **Alertas por umbrales** configurables (Q10, Q50, 5%)
- **Recomendaciones del sistema** basadas en patrones
- **Seguimiento de tendencias** por tienda y encargado
- **Reportes de discrepancias** para análisis gerencial

### 4. **Control de Permisos Granular**
- **Encargados:** Solo su tienda asignada
- **Gerentes:** Todas las tiendas bajo supervisión
- **Contabilidad:** Acceso completo para reconciliación
- **Administradores:** Control total del sistema

## Endpoints AJAX

### Procesamiento de Datos
- `ajax/procesar_reporte_ingresos.php` - Guardar/actualizar reportes de encargados
- `ajax/procesar_aprobacion_gerencial.php` - Aprobar/rechazar por gerentes
- `ajax/procesar_reconciliacion.php` - Procesar reconciliación final

### Gestión de Boletas Físicas
- `ajax/gestionar_boletas_fisicas.php` - Interface para gestión de boletas
- `ajax/registrar_boleta_fisica.php` - Registrar nueva boleta física
- `ajax/verificar_boleta_fisica.php` - Verificar boleta individual
- `ajax/actualizar_totales_reconciliacion.php` - Recalcular totales

### Consultas y Reportes
- `ajax/get_reporte_gerencial_detalle.php` - Detalle para gerentes
- `ajax/get_reconciliacion_detalle.php` - Detalle para contabilidad
- `ajax/get_historial_reportes.php` - Historial de reportes

## Navegación en el Sistema

### Menú "Control Ingresos"
1. **Reporte Ingresos Diario** - Para encargados
2. **Dashboard Gerencial** - Para gerentes
3. **Reconciliación Contabilidad** - Para contabilidad

### Permisos Requeridos
- `ventas_crear` - Encargados (reportar ingresos)
- `ventas_ver` - Gerentes (aprobar reportes)
- `config_sistema` - Contabilidad (reconciliación final)

## Características de Seguridad

### 1. **Validaciones de Entrada**
- Fechas no futuras para reportes
- Montos no negativos
- Formatos de archivo válidos para imágenes
- Unicidad por tienda/fecha

### 2. **Control de Estados**
- Transiciones de estado controladas
- Solo usuarios autorizados pueden cambiar estados
- Trazabilidad completa de cambios
- Logs de auditoría en todas las acciones

### 3. **Protección de Archivos**
- Directorio protegido para imágenes de boletas
- Validación de tipos MIME
- Límites de tamaño (5MB máximo)
- Nombres de archivo únicos para evitar conflictos

## Flujos de Trabajo Típicos

### Flujo Normal (Sin Diferencias)
1. **08:00** - Encargado reporta ingresos del día anterior
2. **09:00** - Gerente revisa y aprueba (diferencias ≤ 2%)
3. **10:00** - Contabilidad recibe para reconciliación
4. **11:00** - Contabilidad registra boletas físicas
5. **11:30** - Sistema valida automáticamente
6. **12:00** - Aprobación final de contabilidad

### Flujo con Diferencias Menores (Q10-Q50)
1. Encargado reporta con diferencia moderada
2. Gerente solicita aclaración pero aprueba
3. Contabilidad prioriza para revisión detallada
4. Registro manual de boletas con observaciones
5. Análisis de causas de diferencia
6. Aprobación con observaciones documentadas

### Flujo con Diferencias Significativas (>Q50 o >5%)
1. Encargado reporta con diferencia alta
2. Gerente rechaza y solicita re-revisión
3. Encargado corrige y vuelve a reportar
4. Gerente aprueba después de corrección
5. Contabilidad marca como "con diferencias"
6. Escalación a supervisión para investigación

## Reportes y Análisis

### 1. **Reportes Gerenciales**
- Comparativo diario por tienda
- Tendencias de diferencias por encargado
- Análisis de métodos de pago
- Eficiencia de proceso de aprobación

### 2. **Reportes de Contabilidad**
- Reconciliación diaria completa
- Diferencias no resueltas
- Boletas faltantes o duplicadas
- Estadísticas de verificación

### 3. **Análisis Ejecutivo**
- KPIs de control interno
- Tendencias de diferencias por región
- Performance de encargados
- Efectividad del sistema de control

## Configuración y Mantenimiento

### Variables de Sistema
- Tolerancias para diferencias (configurables)
- Usuarios por defecto para contabilidad
- Límites de archivo para imágenes
- Retención de datos históricos

### Mantenimiento Regular
- Limpieza de imágenes antiguas
- Archivado de reconciliaciones completadas
- Actualización de permisos de usuario
- Respaldo de datos críticos

### Monitoreo
- Reportes no procesados > 24 horas
- Diferencias sistemáticas por tienda
- Boletas faltantes por reconciliación
- Performance del sistema de matching

## Próximas Mejoras Sugeridas

1. **Automatización Avanzada**
   - OCR para lectura automática de boletas
   - Integración con sistemas de POS
   - Notificaciones automáticas por WhatsApp/SMS

2. **Análisis Predictivo**
   - Detección de patrones de diferencias
   - Predicción de riesgo de discrepancias
   - Recomendaciones de mejora de procesos

3. **Mobile First**
   - App móvil para encargados
   - Escaneo de boletas con cámara
   - Reportes en tiempo real

4. **Integraciones**
   - ERP empresarial
   - Sistemas bancarios
   - Plataformas de análisis (BI)

---

## Estado Actual del Sistema
✅ **Completamente implementado y funcional**
- Base de datos diseñada e implementada
- Interfaces web responsive y funcionales  
- Flujo de trabajo de 3 niveles operativo
- Sistema de validación de boletas físicas
- Integración completa con sistema existente
- Navegación y permisos configurados
- Logs de auditoría y trazabilidad completa

**Fecha de implementación:** Agosto 2024  
**Versión:** 1.0 - Sistema Completo de Control Multi-Nivel