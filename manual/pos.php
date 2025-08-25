<h1><i class="fas fa-cash-register text-primary"></i> Punto de Venta (POS)</h1>

<div class="tip-box">
    <h5><i class="fas fa-rocket"></i> Ventas Ultrarrápidas</h5>
    <p>El POS está diseñado para procesar ventas de manera eficiente con el mínimo número de clics. Ideal para ventas rápidas y atención al cliente ágil.</p>
</div>

<h2>🖥️ Interfaz del POS</h2>

<div class="row">
    <div class="col-md-6">
        <div class="feature-card">
            <div class="feature-icon">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <h5>Carrito de Compras</h5>
            <ul>
                <li>Vista en tiempo real de productos</li>
                <li>Cálculo automático de totales</li>
                <li>Edición rápida de cantidades</li>
                <li>Eliminación de productos</li>
                <li>Subtotal y total en quetzales (Q)</li>
            </ul>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="feature-card">
            <div class="feature-icon">
                <i class="fas fa-search"></i>
            </div>
            <h5>Búsqueda de Productos</h5>
            <ul>
                <li>Búsqueda instantánea por nombre</li>
                <li>Filtro por código de producto</li>
                <li>Visualización de stock disponible</li>
                <li>Precios actualizados automáticamente</li>
                <li>Solo productos con stock se muestran</li>
            </ul>
        </div>
    </div>
</div>

<h2>🛒 Agregar Productos al Carrito</h2>

<div class="screenshot-container text-center mb-4">
    <img src="assets/screenshots/pos-venta.png" alt="Interfaz del Punto de Venta (POS)" class="img-fluid rounded shadow" style="max-width: 100%; height: auto;">
    <p class="text-muted mt-2"><small><i class="fas fa-camera"></i> Interfaz principal del POS con carrito de compras y búsqueda de productos</small></p>
</div>

<div class="step-card">
    <div class="d-flex align-items-start">
        <div class="step-number">1</div>
        <div class="step-content">
            <h5>Métodos para Agregar Productos</h5>
            
            <div class="row">
                <div class="col-md-4">
                    <h6>🖱️ Método Manual:</h6>
                    <ol>
                        <li>Escribe el nombre del producto</li>
                        <li>Usa la búsqueda inteligente</li>
                        <li>Haz clic en el producto deseado</li>
                        <li>Se agrega automáticamente al carrito</li>
                    </ol>
                </div>
                <div class="col-md-4">
                    <h6>📱 Escáner QR:</h6>
                    <ol>
                        <li>Presiona <kbd>F3</kbd> o el botón QR</li>
                        <li>Permite acceso a la cámara</li>
                        <li>Escanea el código del producto</li>
                        <li>Producto se agrega instantáneamente</li>
                    </ol>
                </div>
                <div class="col-md-4">
                    <h6>⌨️ Código Directo:</h6>
                    <ol>
                        <li>Escribe el código exacto</li>
                        <li>Ejemplo: <code>EL-001</code></li>
                        <li>Presiona <kbd>Enter</kbd></li>
                        <li>Producto se localiza automáticamente</li>
                    </ol>
                </div>
            </div>
            
            <div class="info-box">
                <h6><i class="fas fa-check-circle"></i> Validaciones Automáticas</h6>
                <p>El sistema verifica automáticamente:</p>
                <ul class="mb-0">
                    <li>Stock disponible en la tienda seleccionada</li>
                    <li>Producto está activo y disponible</li>
                    <li>Precios actualizados</li>
                    <li>Permisos de venta del usuario</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<h2>✏️ Gestión del Carrito</h2>

<div class="step-card">
    <div class="d-flex align-items-start">
        <div class="step-number">2</div>
        <div class="step-content">
            <h5>Modificar Productos en el Carrito</h5>
            
            <div class="row">
                <div class="col-md-6">
                    <h6>🔢 Cambiar Cantidades:</h6>
                    <ul>
                        <li>Haz clic en el campo de cantidad</li>
                        <li>Escribe la nueva cantidad</li>
                        <li>Presiona <kbd>Enter</kbd> o haz clic fuera</li>
                        <li>Total se recalcula automáticamente</li>
                    </ul>
                    
                    <h6>🗑️ Eliminar Productos:</h6>
                    <ul>
                        <li>Haz clic en el icono <i class="fas fa-trash text-danger"></i></li>
                        <li>Producto se elimina inmediatamente</li>
                        <li>Total se actualiza automáticamente</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h6>💰 Información Mostrada:</h6>
                    <ul>
                        <li><strong>Nombre:</strong> Descripción del producto</li>
                        <li><strong>Precio Unitario:</strong> En quetzales (Q)</li>
                        <li><strong>Cantidad:</strong> Unidades a vender</li>
                        <li><strong>Subtotal:</strong> Precio × Cantidad</li>
                        <li><strong>Stock:</strong> Disponible después de la venta</li>
                    </ul>
                </div>
            </div>
            
            <div class="warning-box">
                <h6><i class="fas fa-exclamation-triangle"></i> Validación de Stock</h6>
                <p>Si intentas vender más cantidad de la disponible, el sistema te alertará y limitará la cantidad al stock disponible.</p>
            </div>
        </div>
    </div>
</div>

<h2>👤 Asignación de Vendedor</h2>

<div class="step-card">
    <div class="d-flex align-items-start">
        <div class="step-number">3</div>
        <div class="step-content">
            <h5>Sistema de Comisiones</h5>
            
            <div class="row">
                <div class="col-md-6">
                    <h6>👥 Selección de Vendedor:</h6>
                    <ul>
                        <li>Lista desplegable con vendedores activos</li>
                        <li>Opción "Sin vendedor" disponible</li>
                        <li>Vendedor predeterminado configurable</li>
                        <li>Solo vendedores de la tienda actual</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h6>💼 Beneficios del Sistema:</h6>
                    <ul>
                        <li>Cálculo automático de comisiones</li>
                        <li>Reportes de performance por vendedor</li>
                        <li>Seguimiento de metas de ventas</li>
                        <li>Rankings y estadísticas</li>
                    </ul>
                </div>
            </div>
            
            <div class="tip-box">
                <h6><i class="fas fa-percent"></i> Comisiones Automáticas</h6>
                <p>Cada vendedor tiene un porcentaje de comisión configurado. El sistema calcula automáticamente sus ganancias basado en las ventas procesadas.</p>
            </div>
        </div>
    </div>
</div>

<h2>💳 Procesar la Venta</h2>

<div class="step-card">
    <div class="d-flex align-items-start">
        <div class="step-number">4</div>
        <div class="step-content">
            <h5>Finalización de la Transacción</h5>
            
            <div class="row">
                <div class="col-md-6">
                    <h6>✅ Antes de Procesar:</h6>
                    <ul>
                        <li>Revisa productos en el carrito</li>
                        <li>Verifica cantidades y precios</li>
                        <li>Selecciona vendedor (opcional)</li>
                        <li>Confirma tienda correcta</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h6>🎯 Métodos de Procesamiento:</h6>
                    <ul>
                        <li>Botón "Procesar Venta"</li>
                        <li>Atajo de teclado <kbd>F2</kbd></li>
                        <li>Confirmación antes de guardar</li>
                        <li>Mensaje de éxito al completar</li>
                    </ul>
                </div>
            </div>
            
            <div class="info-box">
                <h6><i class="fas fa-sync-alt"></i> Actualizaciones Automáticas</h6>
                <p>Al procesar la venta:</p>
                <ul class="mb-0">
                    <li>El inventario se reduce automáticamente</li>
                    <li>Se registra la venta en el historial</li>
                    <li>Se calculan las comisiones del vendedor</li>
                    <li>Se actualiza el dashboard en tiempo real</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<h2>⌨️ Atajos de Teclado</h2>

<div class="row">
    <div class="col-md-6">
        <div class="kbd-shortcut">
            <h5><i class="fas fa-keyboard text-primary"></i> Atajos Principales</h5>
            <table class="table table-sm">
                <tr>
                    <td><kbd>F1</kbd></td>
                    <td>Enfoque en búsqueda de productos</td>
                </tr>
                <tr>
                    <td><kbd>F2</kbd></td>
                    <td>Procesar venta actual</td>
                </tr>
                <tr>
                    <td><kbd>F3</kbd></td>
                    <td>Abrir escáner QR</td>
                </tr>
                <tr>
                    <td><kbd>Esc</kbd></td>
                    <td>Cerrar modales/cancelar</td>
                </tr>
                <tr>
                    <td><kbd>Enter</kbd></td>
                    <td>Confirmar acciones</td>
                </tr>
            </table>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="kbd-shortcut">
            <h5><i class="fas fa-mouse text-primary"></i> Atajos con Mouse</h5>
            <table class="table table-sm">
                <tr>
                    <td>Doble clic</td>
                    <td>Editar cantidad en carrito</td>
                </tr>
                <tr>
                    <td>Clic derecho</td>
                    <td>Menú contextual (navegadores)</td>
                </tr>
                <tr>
                    <td>Arrastrar</td>
                    <td>No implementado</td>
                </tr>
                <tr>
                    <td>Hover</td>
                    <td>Información adicional en tooltips</td>
                </tr>
            </table>
        </div>
    </div>
</div>

<h2>🏪 Selección de Tienda</h2>

<div class="step-card">
    <div class="d-flex align-items-start">
        <div class="step-number">5</div>
        <div class="step-content">
            <h5>Cambio de Tienda Activa</h5>
            
            <div class="row">
                <div class="col-md-6">
                    <h6>🔄 Proceso de Cambio:</h6>
                    <ol>
                        <li>Usa el selector en la parte superior</li>
                        <li>Selecciona la tienda deseada</li>
                        <li>La página se recarga automáticamente</li>
                        <li>Inventario se actualiza a la nueva tienda</li>
                        <li>Carrito se vacía (por seguridad)</li>
                    </ol>
                </div>
                <div class="col-md-6">
                    <h6>🔒 Restricciones de Acceso:</h6>
                    <ul>
                        <li><strong>Administradores:</strong> Ven todas las tiendas</li>
                        <li><strong>Encargados:</strong> Solo tiendas asignadas</li>
                        <li><strong>Usuarios:</strong> Según permisos específicos</li>
                        <li>Filtros automáticos de seguridad</li>
                    </ul>
                </div>
            </div>
            
            <div class="warning-box">
                <h6><i class="fas fa-exclamation-triangle"></i> Importante</h6>
                <p>Al cambiar de tienda, el carrito actual se vaciará para evitar ventas incorrectas entre tiendas diferentes.</p>
            </div>
        </div>
    </div>
</div>

<h2>📱 Escáner QR Integrado</h2>

<div class="step-card">
    <div class="step-content">
        <h5><i class="fas fa-qrcode text-primary"></i> Funcionalidad de Escáner</h5>
        
        <div class="row">
            <div class="col-md-6">
                <h6>📲 Características:</h6>
                <ul>
                    <li>Cámara integrada en el navegador</li>
                    <li>Detección automática de códigos QR</li>
                    <li>Retroalimentación visual y sonora</li>
                    <li>Funciona en móviles y escritorio</li>
                    <li>Interfaz optimizada para velocidad</li>
                </ul>
            </div>
            <div class="col-md-6">
                <h6>🎯 Uso Práctico:</h6>
                <ul>
                    <li>Ventas ultrarrápidas sin escribir</li>
                    <li>Eliminación de errores de código</li>
                    <li>Ideal para productos con códigos largos</li>
                    <li>Perfecto para atención al cliente ágil</li>
                    <li>Reduce tiempo de entrenamiento de personal</li>
                </ul>
            </div>
        </div>
        
        <div class="info-box">
            <h6><i class="fas fa-mobile-alt"></i> Compatibilidad</h6>
            <p>El escáner funciona en:</p>
            <ul class="mb-0">
                <li><strong>Móviles:</strong> Android, iOS (Safari, Chrome)</li>
                <li><strong>Escritorio:</strong> Chrome, Firefox, Edge, Safari</li>
                <li><strong>Tablets:</strong> iPad, Android tablets</li>
                <li>Requiere conexión HTTPS para acceso a cámara</li>
            </ul>
        </div>
    </div>
</div>

<h2>💡 Tips de Productividad</h2>

<div class="row">
    <div class="col-md-6">
        <div class="tip-box">
            <h5><i class="fas fa-thumbs-up"></i> Mejores Prácticas</h5>
            <ul class="mb-0">
                <li><strong>Usa atajos:</strong> <kbd>F2</kbd> para procesar, <kbd>F3</kbd> para QR</li>
                <li><strong>Escáner principal:</strong> Para productos con códigos largos</li>
                <li><strong>Verifica siempre:</strong> Cantidades y precios antes de procesar</li>
                <li><strong>Asigna vendedor:</strong> Para seguimiento de comisiones</li>
                <li><strong>Mantén limpio:</strong> Elimina productos incorrectos del carrito</li>
            </ul>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="warning-box">
            <h5><i class="fas fa-exclamation-triangle"></i> Evitar</h5>
            <ul class="mb-0">
                <li><strong>Cambiar tienda:</strong> Con carrito lleno (se vacía)</li>
                <li><strong>Cantidades excesivas:</strong> Más del stock disponible</li>
                <li><strong>Procesar sin revisar:</strong> Siempre confirma antes de finalizar</li>
                <li><strong>Olvidar vendedor:</strong> Afecta reportes de comisiones</li>
                <li><strong>Productos inactivos:</strong> El sistema los filtra automáticamente</li>
            </ul>
        </div>
    </div>
</div>

<h2>🔧 Solución de Problemas</h2>

<div class="step-card">
    <div class="step-content">
        <h5><i class="fas fa-tools text-primary"></i> Problemas Comunes y Soluciones</h5>
        
        <div class="row">
            <div class="col-md-6">
                <h6>🔍 "No encuentro el producto"</h6>
                <p><strong>Verificar:</strong></p>
                <ul>
                    <li>Tienda seleccionada correcta</li>
                    <li>Producto tiene stock disponible</li>
                    <li>Producto está activo</li>
                    <li>Búsqueda con términos correctos</li>
                </ul>
                
                <h6>📱 "Escáner QR no abre"</h6>
                <p><strong>Verificar:</strong></p>
                <ul>
                    <li>Navegador compatible</li>
                    <li>Conexión HTTPS</li>
                    <li>Permisos de cámara otorgados</li>
                    <li>Cámara no está en uso por otra app</li>
                </ul>
            </div>
            <div class="col-md-6">
                <h6>❌ "No se procesa la venta"</h6>
                <p><strong>Verificar:</strong></p>
                <ul>
                    <li>Carrito no está vacío</li>
                    <li>Stock suficiente para todos los productos</li>
                    <li>Conexión a internet estable</li>
                    <li>Permisos de venta en la tienda</li>
                </ul>
                
                <h6>🔄 "Carrito se vacía solo"</h6>
                <p><strong>Causas comunes:</strong></p>
                <ul>
                    <li>Cambio de tienda</li>
                    <li>Sesión expirada</li>
                    <li>Página recargada manualmente</li>
                    <li>Navegador cerrado</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<h2>📊 Después de la Venta</h2>

<div class="step-card">
    <div class="step-content">
        <h5><i class="fas fa-chart-line text-primary"></i> Qué Sucede Tras Procesar</h5>
        
        <div class="row">
            <div class="col-md-6">
                <h6>📈 Actualizaciones Automáticas:</h6>
                <ul>
                    <li>Inventario reduce stock vendido</li>
                    <li>Dashboard actualiza métricas del día</li>
                    <li>Historial de ventas registra transacción</li>
                    <li>Comisiones del vendedor se calculan</li>
                </ul>
            </div>
            <div class="col-md-6">
                <h6>📄 Dónde Ver la Información:</h6>
                <ul>
                    <li><strong>Dashboard:</strong> Métricas actualizadas</li>
                    <li><strong>Historial de Ventas:</strong> Detalle completo</li>
                    <li><strong>Reportes de Vendedores:</strong> Comisiones</li>
                    <li><strong>Inventarios:</strong> Stock actualizado</li>
                </ul>
            </div>
        </div>
        
        <div class="tip-box">
            <h6><i class="fas fa-receipt"></i> Comprobante de Venta</h6>
            <p>Cada venta procesada genera un registro único con ID, fecha, productos, cantidades, precios y vendedor asignado. Esta información está disponible en el historial para futuras consultas o reembolsos.</p>
        </div>
    </div>
</div>

<div class="text-center mt-4">
    <h3>¿Listo para gestionar ventas completas?</h3>
    <p>El POS es solo el inicio. Explora el historial de ventas y reportes para análisis completos.</p>
    <div class="btn-group">
        <a href="manual.php?seccion=ventas" class="btn btn-primary">
            <i class="fas fa-shopping-cart"></i> Gestión de Ventas
        </a>
        <a href="manual.php?seccion=qr" class="btn btn-outline-primary">
            <i class="fas fa-qrcode"></i> Dominar Escáner QR
        </a>
    </div>
</div>