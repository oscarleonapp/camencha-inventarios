# Sistema de Validación de Vendedores

## Descripción
Sistema completo para que vendedores reporten sus ventas y los encargados las validen, incluyendo ranking competitivo entre vendedores.

## Componentes del Sistema

### 1. Dashboard de Vendedor (`vendedor_dashboard.php`)
- Panel para que vendedores ingresen sus ventas del día
- Muestra estadísticas personales (ventas, comisiones, ranking)
- Sistema de matching automático con ventas del sistema
- Interface simple y enfocada solo en funciones de vendedor

**Características:**
- Reporte de ventas con fecha, total y descripción de productos
- Vista de ventas reportadas (pendientes, aprobadas, rechazadas)
- Estadísticas del mes actual
- Posición en el ranking de vendedores

### 2. Panel de Aprobación (`aprobacion_ventas_vendedor.php`)
- Interface para encargados/gerentes
- Revisión de ventas reportadas por vendedores
- Sistema de matching automático y manual
- Aprobación/rechazo con motivos

**Características:**
- Dashboard con estadísticas del día
- Lista de reportes pendientes con nivel de confianza
- Sistema de matching inteligente basado en:
  - Diferencia de monto (≤Q1.00 = 40%, ≤Q5.00 = 30%, etc.)
  - Diferencia de fecha (mismo día = 40%, ±1 día = 30%, etc.)
  - Mismo vendedor (+20%)
- Búsqueda manual de matches cuando el automático falla
- Motivos categorizados para rechazos

### 3. Ranking de Vendedores (`ranking_vendedores.php`)
- Sistema competitivo de ranking mensual
- Podio visual para top 3 vendedores
- Estadísticas detalladas y comparativas
- Filtros por mes/año/tienda

**Características:**
- Podio visual con medallas para top 3
- Tabla completa con métricas detalladas
- Cálculo de puntos basado en:
  - Ventas validadas (10 puntos c/u)
  - Volumen de ventas (1 punto por Q100)
  - Consistencia (bonus por días activos)
  - Ticket promedio alto (bonus por >Q200)
- Comparación con mes anterior
- Estadísticas de performance

## Base de Datos

### Tablas Nuevas

#### `ventas_reportadas_vendedor`
```sql
- id (PK)
- vendedor_id (FK vendedores)
- venta_id (FK ventas, nullable)
- fecha_venta
- total_reportado
- descripcion_productos
- observaciones
- fecha_reporte
- estado (pendiente/aprobado/rechazado)
- confianza_match (0.00-1.00)
- match_manual (boolean)
- usuario_match_id (quien asignó match manual)
- fecha_match
- usuario_aprobador_id
- fecha_procesamiento
- motivo_rechazo
```

#### `ranking_vendedores`
```sql
- id (PK)
- vendedor_id (FK vendedores)
- mes, anio
- ventas_validadas
- total_ventas
- promedio_venta
- total_comisiones
- puntos_ranking
- posicion_ranking
- venta_minima, venta_maxima
- dias_activos
- actualizado (timestamp)
```

### Modificación a Tabla Existente
- `vendedores`: Agregada columna `usuario_id` para vincular con usuarios del sistema

## Endpoints AJAX

### Sistema de Reportes
- `ajax/procesar_reporte_venta.php` - Procesar reporte de vendedor
- `ajax/get_reporte_detalle.php` - Obtener detalles de reporte para modal
- `ajax/procesar_aprobacion_venta.php` - Aprobar/rechazar reportes

### Sistema de Matching
- `ajax/procesar_matching_automatico.php` - Ejecutar matching automático masivo
- `ajax/buscar_match_manual.php` - Búsqueda manual de ventas candidatas
- `ajax/asignar_match_manual.php` - Asignar match seleccionado manualmente

### Sistema de Ranking
- `ajax/actualizar_ranking_vendedores.php` - Actualizar ranking del mes especificado

## Algoritmo de Matching

### Scoring de Confianza
El sistema calcula un score de 0.0 a 1.0 basado en:

1. **Diferencia de Monto:**
   - ≤ Q0.50: 0.5 puntos
   - ≤ Q1.00: 0.4 puntos
   - ≤ Q2.00: 0.3 puntos
   - ≤ Q5.00: 0.2 puntos
   - ≤ Q10.00: 0.1 puntos

2. **Diferencia de Fecha:**
   - Mismo día: 0.3 puntos
   - ±1 día: 0.2 puntos
   - ±2 días: 0.1 puntos

3. **Vendedor:**
   - Mismo vendedor: 0.2 puntos
   - Sin vendedor asignado: 0.1 puntos

### Umbral de Confianza
- ≥0.9: Alta confianza (recomendado aprobar automáticamente)
- 0.7-0.89: Confianza media (revisar antes de aprobar)
- <0.7: Baja confianza (verificar cuidadosamente)

## Permisos Requeridos

### Para Vendedores
- `ventas_crear`: Acceso al dashboard de vendedor

### Para Encargados/Gerentes
- `ventas_ver`: Acceso al panel de aprobación y ranking

## Navegación

Los nuevos módulos se agregaron al menú principal bajo "Vendedores":
- Dashboard Vendedor
- Aprobar Ventas
- Ranking Vendedores

## Flujo de Trabajo

1. **Vendedor reporta venta:**
   - Ingresa fecha, monto, descripción
   - Sistema busca automáticamente matches
   - Queda pendiente de aprobación

2. **Sistema matching automático:**
   - Se ejecuta automáticamente al reportar
   - Busca ventas en rango ±2 días
   - Asigna score de confianza

3. **Encargado revisa:**
   - Ve dashboard con reportes pendientes
   - Analiza nivel de confianza y detalles
   - Puede buscar matches manualmente si es necesario
   - Aprueba o rechaza con motivo

4. **Actualización de comisiones:**
   - Al aprobar, se actualiza/crea registro en `comisiones_vendedores`
   - Se valida la comisión automáticamente

5. **Actualización de ranking:**
   - Se puede ejecutar manualmente desde la interfaz
   - Recalcula puntos y posiciones
   - Actualiza estadísticas del período

## Características de Seguridad

- Validación de entrada en todos los formularios
- Tokens CSRF en acciones críticas
- Logs de auditoría para todas las acciones
- Verificación de permisos en cada endpoint
- Transacciones de base de datos para operaciones críticas

## Próximas Mejoras Sugeridas

1. **Notificaciones automáticas** cuando hay reportes pendientes
2. **Dashboard ejecutivo** con métricas globales de performance
3. **Incentivos automáticos** basados en ranking (bonos, comisiones extra)
4. **Reportes de discrepancias** para análisis de diferencias sistemáticas
5. **API móvil** para facilitar reportes desde dispositivos móviles
6. **Integración con WhatsApp/SMS** para notificaciones rápidas