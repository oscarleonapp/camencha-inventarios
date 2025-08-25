<?php
$titulo = "Demo Toast Notifications";
require_once 'includes/auth.php';

verificarLogin();

require_once 'includes/layout_header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 rs-wrap-sm">
        <h2><i class="fas fa-bell"></i> Demo Toast Notifications</h2>
        <button class="btn btn-outline-danger" onclick="clearAllToasts()">
            <i class="fas fa-times"></i> Limpiar Todos
        </button>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-play"></i> Tipos de Toast Básicos</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <button class="btn btn-success w-100" onclick="showSuccess('Operación completada exitosamente')">
                                <i class="fas fa-check"></i> Success
                            </button>
                        </div>
                        <div class="col-md-6">
                            <button class="btn btn-danger w-100" onclick="showError('Ha ocurrido un error inesperado')">
                                <i class="fas fa-times"></i> Error
                            </button>
                        </div>
                        <div class="col-md-6">
                            <button class="btn btn-warning w-100" onclick="showWarning('Esta acción requiere confirmación')">
                                <i class="fas fa-exclamation-triangle"></i> Warning
                            </button>
                        </div>
                        <div class="col-md-6">
                            <button class="btn btn-info w-100" onclick="showInfo('Nueva información disponible')">
                                <i class="fas fa-info-circle"></i> Info
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <h5><i class="fas fa-star"></i> Toast con Título</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <button class="btn btn-primary w-100" onclick="showNotification('Sistema', 'Nueva actualización disponible', 'primary')">
                                <i class="fas fa-download"></i> Actualización
                            </button>
                        </div>
                        <div class="col-md-6">
                            <button class="btn btn-success w-100" onclick="showNotification('Venta', 'Se registró una nueva venta por Q1,250.00', 'success')">
                                <i class="fas fa-shopping-cart"></i> Nueva Venta
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <h5><i class="fas fa-cog"></i> Toast Personalizados</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <button class="btn btn-secondary w-100" onclick="showToast('Toast sin icono', 'secondary', {icon: false})">
                                Sin Icono
                            </button>
                        </div>
                        <div class="col-md-4">
                            <button class="btn btn-dark w-100" onclick="showPersistentToast('Este toast no se oculta automáticamente', 'dark')">
                                Persistente
                            </button>
                        </div>
                        <div class="col-md-4">
                            <button class="btn btn-outline-primary w-100" onclick="showToast('Toast de larga duración', 'info', {duration: 10000})">
                                10 Segundos
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <h5><i class="fas fa-map-marker-alt"></i> Posiciones</h5>
                </div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-4">
                            <button class="btn btn-sm btn-outline-secondary w-100" onclick="showToast('Top Start', 'info', {position: 'top-start', duration: 3000})">
                                Top Start
                            </button>
                        </div>
                        <div class="col-4">
                            <button class="btn btn-sm btn-outline-secondary w-100" onclick="showToast('Top Center', 'info', {position: 'top-center', duration: 3000})">
                                Top Center
                            </button>
                        </div>
                        <div class="col-4">
                            <button class="btn btn-sm btn-outline-secondary w-100" onclick="showToast('Top End', 'info', {position: 'top-end', duration: 3000})">
                                Top End
                            </button>
                        </div>
                        <div class="col-4">
                            <button class="btn btn-sm btn-outline-secondary w-100" onclick="showToast('Middle Start', 'warning', {position: 'middle-start', duration: 3000})">
                                Middle Start
                            </button>
                        </div>
                        <div class="col-4">
                            <button class="btn btn-sm btn-outline-secondary w-100" onclick="showToast('Middle Center', 'primary', {position: 'middle-center', duration: 3000})">
                                Middle Center
                            </button>
                        </div>
                        <div class="col-4">
                            <button class="btn btn-sm btn-outline-secondary w-100" onclick="showToast('Middle End', 'warning', {position: 'middle-end', duration: 3000})">
                                Middle End
                            </button>
                        </div>
                        <div class="col-4">
                            <button class="btn btn-sm btn-outline-secondary w-100" onclick="showToast('Bottom Start', 'success', {position: 'bottom-start', duration: 3000})">
                                Bottom Start
                            </button>
                        </div>
                        <div class="col-4">
                            <button class="btn btn-sm btn-outline-secondary w-100" onclick="showToast('Bottom Center', 'success', {position: 'bottom-center', duration: 3000})">
                                Bottom Center
                            </button>
                        </div>
                        <div class="col-4">
                            <button class="btn btn-sm btn-outline-secondary w-100" onclick="showToast('Bottom End', 'success', {position: 'bottom-end', duration: 3000})">
                                Bottom End
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <h5><i class="fas fa-code"></i> Simulación de Eventos del Sistema</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <button class="btn btn-outline-success w-100" onclick="simularLogin()">
                                <i class="fas fa-sign-in-alt"></i> Simular Login Exitoso
                            </button>
                        </div>
                        <div class="col-md-6">
                            <button class="btn btn-outline-danger w-100" onclick="simularError()">
                                <i class="fas fa-exclamation-triangle"></i> Simular Error de Sistema
                            </button>
                        </div>
                        <div class="col-md-6">
                            <button class="btn btn-outline-warning w-100" onclick="simularStockBajo()">
                                <i class="fas fa-box"></i> Simular Stock Bajo
                            </button>
                        </div>
                        <div class="col-md-6">
                            <button class="btn btn-outline-info w-100" onclick="simularVenta()">
                                <i class="fas fa-cash-register"></i> Simular Venta
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-book"></i> Documentación de Uso</h5>
                </div>
                <div class="card-body">
                    <h6>Funciones Básicas:</h6>
                    <ul class="list-unstyled">
                        <li><code>showSuccess(message)</code></li>
                        <li><code>showError(message)</code></li>
                        <li><code>showWarning(message)</code></li>
                        <li><code>showInfo(message)</code></li>
                    </ul>

                    <h6 class="mt-3">Función Principal:</h6>
                    <pre class="bg-light p-2 small"><code>showToast(message, type, options)</code></pre>

                    <h6 class="mt-3">Opciones Disponibles:</h6>
                    <ul class="small">
                        <li><strong>duration:</strong> Duración en ms (5000)</li>
                        <li><strong>position:</strong> Posición del toast</li>
                        <li><strong>icon:</strong> Mostrar icono (true)</li>
                        <li><strong>dismissible:</strong> Botón cerrar (true)</li>
                        <li><strong>title:</strong> Título opcional</li>
                    </ul>

                    <h6 class="mt-3">Tipos Disponibles:</h6>
                    <ul class="small">
                        <li>success, danger, warning, info</li>
                        <li>primary, secondary, dark, light</li>
                    </ul>

                    <h6 class="mt-3">Ejemplos de Código:</h6>
                    <pre class="bg-light p-2 small"><code>// Básico
showSuccess('Guardado!');

// Con opciones
showToast('Mensaje', 'warning', {
  duration: 8000,
  position: 'top-center'
});

// Con título
showNotification('Título', 
  'Mensaje', 'success');</code></pre>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Simulaciones de eventos del sistema
function simularLogin() {
    showNotification('Bienvenido', 
        'Has iniciado sesión correctamente como administrador', 
        'success', 
        { duration: 4000 }
    );
}

function simularError() {
    showNotification('Error del Sistema', 
        'No se pudo conectar con la base de datos. Reintentando...', 
        'danger', 
        { duration: 8000 }
    );
}

function simularStockBajo() {
    showNotification('Alerta de Inventario', 
        'El producto "Laptop HP" tiene stock bajo (2 unidades restantes)', 
        'warning', 
        { duration: 7000 }
    );
}

function simularVenta() {
    showNotification('Nueva Venta', 
        'Venta #1234 registrada por Q1,850.00 en Tienda Central', 
        'primary', 
        { duration: 6000 }
    );
}

// Demo automático
setTimeout(() => {
    showInfo('¡Bienvenido al demo de Toast Notifications!');
}, 1000);
</script>

<?php require_once 'includes/layout_footer.php'; ?>
