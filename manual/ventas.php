<h1><i class="fas fa-shopping-cart text-primary"></i> Gestión de Ventas</h1>

<div class="tip-box">
    <h5><i class="fas fa-chart-line"></i> Control Total de Ventas</h5>
    <p>El sistema de ventas proporciona un control completo desde el punto de venta hasta reportes avanzados, incluyendo gestión de vendedores, comisiones y análisis de performance.</p>
</div>

<h2>🛒 Sistemas de Venta Disponibles</h2>

<div class="row">
    <div class="col-md-6">
        <div class="feature-card">
            <div class="feature-icon">
                <i class="fas fa-cash-register"></i>
            </div>
            <h5>POS (Punto de Venta)</h5>
            <ul>
                <li>Interfaz optimizada para ventas rápidas</li>
                <li>Escáner QR integrado</li>
                <li>Cálculo automático de totales</li>
                <li>Asignación de vendedores</li>
                <li>Validación automática de stock</li>
            </ul>
            <a href="manual.php?seccion=pos" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-external-link-alt"></i> Ver Manual del POS
            </a>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="feature-card">
            <div class="feature-icon">
                <i class="fas fa-list-alt"></i>
            </div>
            <h5>Gestión Tradicional</h5>
            <ul>
                <li>Formularios detallados de venta</li>
                <li>Selección manual de productos</li>
                <li>Control granular de precios</li>
                <li>Notas y observaciones</li>
                <li>Descuentos y promociones</li>
            </ul>
        </div>
    </div>
</div>

<h2>📋 Historial y Seguimiento de Ventas</h2>

<div class="screenshot-container text-center mb-4">
    <div class="alert alert-info">
        <h6><i class="fas fa-info-circle"></i> Página Principal de Ventas</h6>
        <p class="mb-0">Accede a <strong>ventas.php</strong> para ver el historial completo, detalles de cada venta y opciones de gestión avanzada.</p>
    </div>
</div>

<div class="step-card">
    <div class="d-flex align-items-start">
        <div class="step-number">1</div>
        <div class="step-content">
            <h5>Información de Cada Venta</h5>
            
            <div class="row">
                <div class="col-md-6">
                    <h6>📊 Datos Principales:</h6>
                    <ul>
                        <li><strong>ID de Venta:</strong> Número único consecutivo</li>
                        <li><strong>Fecha y Hora:</strong> Timestamp completo</li>
                        <li><strong>Tienda:</strong> Sucursal donde se realizó</li>
                        <li><strong>Vendedor:</strong> Personal asignado (opcional)</li>
                        <li><strong>Usuario:</strong> Quien procesó la venta</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h6>💰 Información Financiera:</h6>
                    <ul>
                        <li><strong>Subtotal:</strong> Suma de productos</li>
                        <li><strong>Descuento:</strong> Cantidad descontada</li>
                        <li><strong>Total Final:</strong> Monto cobrado</li>
                        <li><strong>Estado:</strong> Completada, Reembolsada, Pendiente</li>
                        <li><strong>Comisiones:</strong> Calculadas automáticamente</li>
                    </ul>
                </div>
            </div>
            
            <div class="info-box">
                <h6><i class="fas fa-search"></i> Búsqueda y Filtros</h6>
                <p>El historial incluye opciones de búsqueda por:</p>
                <ul class="mb-0">
                    <li><strong>Fechas:</strong> Rango personalizable</li>
                    <li><strong>Vendedor:</strong> Performance individual</li>
                    <li><strong>Tienda:</strong> Ventas por sucursal</li>
                    <li><strong>Estado:</strong> Completadas, reembolsadas, etc.</li>
                    <li><strong>Monto:</strong> Rangos de valores</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<h2>📄 Detalles de Venta</h2>

<div class="step-card">
    <div class="d-flex align-items-start">
        <div class="step-number">2</div>
        <div class="step-content">
            <h5>Vista Detallada de Cada Transacción</h5>
            
            <div class="row">
                <div class="col-md-6">
                    <h6>🛍️ Productos Vendidos:</h6>
                    <ul>
                        <li><strong>Lista completa:</strong> Todos los items</li>
                        <li><strong>Códigos:</strong> Identificación única</li>
                        <li><strong>Cantidades:</strong> Unidades vendidas</li>
                        <li><strong>Precios unitarios:</strong> Al momento de venta</li>
                        <li><strong>Subtotales:</strong> Cantidad × Precio</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h6>⚙️ Acciones Disponibles:</h6>
                    <ul>
                        <li><strong>Ver detalles:</strong> Información completa</li>
                        <li><strong>Imprimir recibo:</strong> Comprobante</li>
                        <li><strong>Procesar reembolso:</strong> Si está permitido</li>
                        <li><strong>Editar notas:</strong> Observaciones</li>
                        <li><strong>Exportar datos:</strong> PDF, Excel</li>
                    </ul>
                </div>
            </div>
            
            <div class="tip-box">
                <h6><i class="fas fa-receipt"></i> Acceso Rápido</h6>
                <p>Usa <strong>detalle_venta.php?id=X</strong> para acceder directamente al detalle de cualquier venta. Los ID son consecutivos y únicos en todo el sistema.</p>
            </div>
        </div>
    </div>
</div>

<h2>↩️ Sistema de Reembolsos</h2>

<div class="step-card">
    <div class="d-flex align-items-start">
        <div class="step-number">3</div>
        <div class="step-content">
            <h5>Gestión Completa de Devoluciones</h5>
            
            <div class="row">
                <div class="col-md-6">
                    <h6>🔄 Proceso de Reembolso:</h6>
                    <ol>
                        <li><strong>Localizar venta:</strong> Por ID o búsqueda</li>
                        <li><strong>Verificar estado:</strong> Solo ventas completadas</li>
                        <li><strong>Seleccionar productos:</strong> Total o parcial</li>
                        <li><strong>Ingresar razón:</strong> Motivo del reembolso</li>
                        <li><strong>Confirmar operación:</strong> Cambios irreversibles</li>
                        <li><strong>Actualización automática:</strong> Inventario y finanzas</li>
                    </ol>
                </div>
                <div class="col-md-6">
                    <h6>📊 Efectos del Reembolso:</h6>
                    <ul>
                        <li><strong>Estado de venta:</strong> Cambia a "Reembolsada"</li>
                        <li><strong>Inventario:</strong> Productos regresan al stock</li>
                        <li><strong>Comisiones:</strong> Se reducen automáticamente</li>
                        <li><strong>Reportes:</strong> Ajustes en métricas</li>
                        <li><strong>Historial:</strong> Registro del reembolso</li>
                    </ul>
                </div>
            </div>
            
            <div class="warning-box">
                <h6><i class="fas fa-exclamation-triangle"></i> Consideraciones Importantes</h6>
                <ul class="mb-0">
                    <li><strong>Permisos requeridos:</strong> Solo usuarios autorizados</li>
                    <li><strong>Límite de tiempo:</strong> Configurable por política empresa</li>
                    <li><strong>Registro completo:</strong> Quién, cuándo y por qué</li>
                    <li><strong>Impacto en reportes:</strong> Afecta métricas del periodo</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<h2>👥 Gestión de Vendedores</h2>

<div class="step-card">
    <div class="d-flex align-items-start">
        <div class="step-number">4</div>
        <div class="step-content">
            <h5>Sistema de Comisiones y Performance</h5>
            
            <div class="row">
                <div class="col-md-6">
                    <h6>👤 Información de Vendedores:</h6>
                    <ul>
                        <li><strong>Datos personales:</strong> Nombre, contacto</li>
                        <li><strong>Porcentaje comisión:</strong> Configurable individual</li>
                        <li><strong>Estado activo/inactivo:</strong> Control de acceso</li>
                        <li><strong>Tienda asignada:</strong> Sucursal principal</li>
                        <li><strong>Fecha de ingreso:</strong> Historial laboral</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h6>💼 Cálculo de Comisiones:</h6>
                    <ul>
                        <li><strong>Automático:</strong> En cada venta procesada</li>
                        <li><strong>Porcentaje fijo:</strong> Sobre total de venta</li>
                        <li><strong>Acumulativo:</strong> Suma mensual</li>
                        <li><strong>Reportes detallados:</strong> Performance individual</li>
                        <li><strong>Histórico completo:</strong> Evolución temporal</li>
                    </ul>
                </div>
            </div>
            
            <div class="info-box">
                <h6><i class="fas fa-trophy"></i> Rankings y Estadísticas</h6>
                <p>El sistema genera automáticamente:</p>
                <ul class="mb-0">
                    <li><strong>Top 10 vendedores:</strong> Por mes y periodo</li>
                    <li><strong>Ventas totales:</strong> Cantidad y monto</li>
                    <li><strong>Promedio por venta:</strong> Ticket promedio</li>
                    <li><strong>Tendencias:</strong> Crecimiento o declive</li>
                    <li><strong>Comparativas:</strong> Entre vendedores y periodos</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<h2>📈 Reportes de Ventas</h2>

<div class="step-card">
    <div class="d-flex align-items-start">
        <div class="step-number">5</div>
        <div class="step-content">
            <h5>Análisis Completo de Performance</h5>
            
            <div class="row">
                <div class="col-md-6">
                    <h6>📊 Reportes Disponibles:</h6>
                    <ul>
                        <li><strong>Ventas por periodo:</strong> Diario, semanal, mensual</li>
                        <li><strong>Performance por tienda:</strong> Comparativas</li>
                        <li><strong>Productos más vendidos:</strong> Rankings</li>
                        <li><strong>Comisiones vendedores:</strong> Individual y grupal</li>
                        <li><strong>Análisis financiero:</strong> Márgenes y rentabilidad</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h6>📋 Formatos de Exportación:</h6>
                    <ul>
                        <li><strong>Excel (.xlsx):</strong> Análisis avanzado</li>
                        <li><strong>PDF:</strong> Presentaciones ejecutivas</li>
                        <li><strong>CSV:</strong> Importación a otros sistemas</li>
                        <li><strong>JSON:</strong> Integración con APIs</li>
                        <li><strong>Vista web:</strong> Consulta inmediata</li>
                    </ul>
                </div>
            </div>
            
            <div class="tip-box">
                <h6><i class="fas fa-calendar-alt"></i> Periodos Personalizables</h6>
                <p>Todos los reportes permiten seleccionar:</p>
                <ul class="mb-0">
                    <li><strong>Fechas específicas:</strong> Rango personalizado</li>
                    <li><strong>Periodos preset:</strong> Hoy, semana, mes, año</li>
                    <li><strong>Comparativas:</strong> Mismo periodo año anterior</li>
                    <li><strong>Filtros múltiples:</strong> Tienda, vendedor, producto</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<h2>🏪 Control por Tiendas</h2>

<div class="step-card">
    <div class="d-flex align-items-start">
        <div class="step-number">6</div>
        <div class="step-content">
            <h5>Gestión Multi-Sucursal</h5>
            
            <div class="row">
                <div class="col-md-6">
                    <h6>🏢 Funcionalidades por Tienda:</h6>
                    <ul>
                        <li><strong>Ventas independientes:</strong> Stock por sucursal</li>
                        <li><strong>Reportes separados:</strong> Performance individual</li>
                        <li><strong>Vendedores asignados:</strong> Por ubicación</li>
                        <li><strong>Permisos específicos:</strong> Acceso controlado</li>
                        <li><strong>Inventarios separados:</strong> Stock diferenciado</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h6>🔐 Control de Acceso:</h6>
                    <ul>
                        <li><strong>Administradores:</strong> Acceso total</li>
                        <li><strong>Encargados:</strong> Solo tiendas asignadas</li>
                        <li><strong>Vendedores:</strong> Solo su tienda</li>
                        <li><strong>Auditores:</strong> Solo lectura</li>
                        <li><strong>Configuración flexible:</strong> Permisos personalizables</li>
                    </ul>
                </div>
            </div>
            
            <div class="warning-box">
                <h6><i class="fas fa-shield-alt"></i> Seguridad Multi-Tienda</h6>
                <p>El sistema garantiza que:</p>
                <ul class="mb-0">
                    <li>Los usuarios solo vean ventas de sus tiendas autorizadas</li>
                    <li>No se pueda vender stock de otras sucursales</li>
                    <li>Los reportes respeten las restricciones de acceso</li>
                    <li>Los cambios se registren con trazabilidad completa</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<h2>💳 Métodos de Pago y Facturación</h2>

<div class="step-card">
    <div class="step-content">
        <h5><i class="fas fa-credit-card text-primary"></i> Gestión Financiera</h5>
        
        <div class="row">
            <div class="col-md-6">
                <h6>💰 Información Registrada:</h6>
                <ul>
                    <li><strong>Subtotal:</strong> Suma de productos</li>
                    <li><strong>Descuentos:</strong> Promociones aplicadas</li>
                    <li><strong>Total final:</strong> Monto a cobrar</li>
                    <li><strong>Moneda:</strong> Quetzales (Q) por defecto</li>
                    <li><strong>Fecha y hora:</strong> Timestamp preciso</li>
                </ul>
            </div>
            <div class="col-md-6">
                <h6>📄 Comprobantes y Documentos:</h6>
                <ul>
                    <li><strong>Recibo de venta:</strong> Comprobante simple</li>
                    <li><strong>Detalle completo:</strong> Lista de productos</li>
                    <li><strong>Información fiscal:</strong> Si está configurada</li>
                    <li><strong>Datos del vendedor:</strong> Para comisiones</li>
                    <li><strong>Notas adicionales:</strong> Observaciones</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<h2>🔧 Configuración del Sistema</h2>

<div class="step-card">
    <div class="step-content">
        <h5><i class="fas fa-cogs text-primary"></i> Personalización Avanzada</h5>
        
        <div class="row">
            <div class="col-md-6">
                <h6>⚙️ Configuraciones Disponibles:</h6>
                <ul>
                    <li><strong>Moneda predeterminada:</strong> Símbolo y formato</li>
                    <li><strong>Impuestos:</strong> Porcentajes y cálculos</li>
                    <li><strong>Descuentos máximos:</strong> Límites por usuario</li>
                    <li><strong>Periodo reembolsos:</strong> Días permitidos</li>
                    <li><strong>Numeración:</strong> Formato de IDs</li>
                </ul>
            </div>
            <div class="col-md-6">
                <h6>📊 Métricas y KPIs:</h6>
                <ul>
                    <li><strong>Metas de venta:</strong> Por vendedor y tienda</li>
                    <li><strong>Alertas automáticas:</strong> Objetivos no cumplidos</li>
                    <li><strong>Tendencias:</strong> Crecimiento y declives</li>
                    <li><strong>Comparativas:</strong> Periodos anteriores</li>
                    <li><strong>Proyecciones:</strong> Estimaciones futuras</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<h2>📱 Uso en Dispositivos Móviles</h2>

<div class="row">
    <div class="col-md-6">
        <div class="tip-box">
            <h5><i class="fas fa-mobile-alt"></i> Optimización Móvil</h5>
            <ul class="mb-0">
                <li><strong>POS responsive:</strong> Funciona en tablets</li>
                <li><strong>Escáner QR:</strong> Optimizado para móviles</li>
                <li><strong>Consultas rápidas:</strong> Historial accesible</li>
                <li><strong>Interfaz táctil:</strong> Botones grandes</li>
                <li><strong>Offline básico:</strong> Algunas funciones sin internet</li>
            </ul>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="info-box">
            <h5><i class="fas fa-wifi"></i> Conectividad</h5>
            <ul class="mb-0">
                <li><strong>Conexión estable:</strong> Para sincronización inmediata</li>
                <li><strong>Backup local:</strong> Ventas se guardan temporalmente</li>
                <li><strong>Sincronización automática:</strong> Al restaurar conexión</li>
                <li><strong>Notificaciones:</strong> Estado de conectividad</li>
                <li><strong>Modo offline:</strong> Funcionalidad limitada</li>
            </ul>
        </div>
    </div>
</div>

<h2>🚨 Solución de Problemas</h2>

<div class="step-card">
    <div class="step-content">
        <h5><i class="fas fa-tools text-primary"></i> Problemas Comunes en Ventas</h5>
        
        <div class="row">
            <div class="col-md-6">
                <h6>❌ "No se puede procesar la venta"</h6>
                <p><strong>Verificar:</strong></p>
                <ul>
                    <li>Stock suficiente en todos los productos</li>
                    <li>Usuario tiene permisos de venta</li>
                    <li>Tienda seleccionada está activa</li>
                    <li>Conexión a internet estable</li>
                    <li>Sesión de usuario no expirada</li>
                </ul>
                
                <h6>💰 "Totales no coinciden"</h6>
                <p><strong>Posibles causas:</strong></p>
                <ul>
                    <li>Precios cambiaron durante la venta</li>
                    <li>Descuentos aplicados incorrectamente</li>
                    <li>Error en cantidades ingresadas</li>
                    <li>Problemas de redondeo decimal</li>
                </ul>
            </div>
            <div class="col-md-6">
                <h6>🔍 "No aparecen las ventas"</h6>
                <p><strong>Verificar filtros:</strong></p>
                <ul>
                    <li>Rango de fechas incluye las ventas</li>
                    <li>Tienda seleccionada es la correcta</li>
                    <li>Estado de venta apropiado</li>
                    <li>Usuario tiene acceso a esa tienda</li>
                </ul>
                
                <h6>🏪 "Problemas con múltiples tiendas"</h6>
                <p><strong>Configuración:</strong></p>
                <ul>
                    <li>Permisos correctamente asignados</li>
                    <li>Inventarios separados por tienda</li>
                    <li>Usuarios asignados a tiendas específicas</li>
                    <li>Sincronización entre sucursales</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<h2>📊 Métricas y Análisis</h2>

<div class="step-card">
    <div class="step-content">
        <h5><i class="fas fa-chart-bar text-primary"></i> KPIs Importantes de Ventas</h5>
        
        <div class="row">
            <div class="col-md-4">
                <h6>💵 Métricas Financieras:</h6>
                <ul>
                    <li><strong>Ventas totales:</strong> Por periodo</li>
                    <li><strong>Ticket promedio:</strong> Valor medio</li>
                    <li><strong>Margen de ganancia:</strong> Rentabilidad</li>
                    <li><strong>Crecimiento:</strong> Vs periodos anteriores</li>
                </ul>
            </div>
            <div class="col-md-4">
                <h6>👥 Métricas de Personal:</h6>
                <ul>
                    <li><strong>Ventas por vendedor:</strong> Performance individual</li>
                    <li><strong>Comisiones generadas:</strong> Total pagado</li>
                    <li><strong>Productividad:</strong> Ventas por hora</li>
                    <li><strong>Rotación:</strong> Cambios de personal</li>
                </ul>
            </div>
            <div class="col-md-4">
                <h6>🏪 Métricas Operativas:</h6>
                <ul>
                    <li><strong>Ventas por tienda:</strong> Performance sucursales</li>
                    <li><strong>Productos top:</strong> Más vendidos</li>
                    <li><strong>Rotación inventario:</strong> Velocidad de venta</li>
                    <li><strong>Reembolsos:</strong> Porcentaje y causas</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="text-center mt-4">
    <h3>¿Listo para dominar las ventas?</h3>
    <p>El sistema de ventas es completo y potente. Explora cada funcionalidad para maximizar tus resultados.</p>
    <div class="btn-group">
        <a href="pos.php" class="btn btn-success">
            <i class="fas fa-cash-register"></i> Ir al POS
        </a>
        <a href="ventas.php" class="btn btn-primary">
            <i class="fas fa-list"></i> Ver Historial
        </a>
        <a href="reportes_vendedores.php" class="btn btn-outline-primary">
            <i class="fas fa-chart-line"></i> Reportes
        </a>
        <a href="manual.php?seccion=inventarios" class="btn btn-outline-secondary">
            <i class="fas fa-boxes"></i> Gestión de Inventarios
        </a>
    </div>
</div>