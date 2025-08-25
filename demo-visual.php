<?php
$titulo = "Demostración Visual - Sistema Moderno";
require_once 'includes/auth.php';
require_once 'config/database.php';
require_once 'includes/config_functions.php';

verificarLogin();
verificarPermiso('config_sistema');

include 'includes/layout_header.php';
?>

<div class="animate-fade-in-up">
    <!-- Header de demostración -->
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h1 class="text-gradient mb-2">
                <i class="fas fa-palette me-3"></i>
                Demostración del Nuevo Diseño
            </h1>
            <p class="text-muted mb-0">Explora todas las mejoras visuales y componentes modernos</p>
        </div>
        <div class="d-flex gap-3">
            <button class="btn btn-secondary interactive-hover" onclick="history.back()">
                <i class="fas fa-arrow-left me-2"></i> Volver
            </button>
            <button class="btn btn-primary interactive-hover" onclick="location.reload()">
                <i class="fas fa-sync-alt me-2"></i> Actualizar Vista
            </button>
        </div>
    </div>

    <!-- Stats Cards Demo -->
    <div class="mb-5">
        <h3 class="mb-4">
            <i class="fas fa-chart-bar text-primary me-2"></i>
            Cards de Estadísticas Modernas
        </h3>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-up"></i>
                        <span>+15%</span>
                    </div>
                </div>
                <div class="stat-value">2,847</div>
                <div class="stat-label">Usuarios Activos</div>
                <div class="mt-3 d-flex justify-content-between align-items-center">
                    <small class="text-muted">Este mes</small>
                    <strong class="text-primary">+425 nuevos</strong>
                </div>
            </div>

            <div class="stat-card success">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-up"></i>
                        <span>+23%</span>
                    </div>
                </div>
                <div class="stat-value">Q 45,280</div>
                <div class="stat-label">Ingresos del Día</div>
                <div class="mt-3 d-flex justify-content-between align-items-center">
                    <small class="text-muted">Meta: Q 40,000</small>
                    <strong class="text-success">113% cumplido</strong>
                </div>
            </div>

            <div class="stat-card warning">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="stat-change negative">
                        <i class="fas fa-arrow-down"></i>
                        <span>-5%</span>
                    </div>
                </div>
                <div class="stat-value">12</div>
                <div class="stat-label">Alertas Pendientes</div>
                <div class="mt-3">
                    <small class="text-muted">8 críticas, 4 moderadas</small>
                </div>
            </div>

            <div class="stat-card info">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-change">
                        <i class="fas fa-check-circle"></i>
                        <span>En tiempo</span>
                    </div>
                </div>
                <div class="stat-value">98.7%</div>
                <div class="stat-label">Tiempo de Respuesta</div>
                <div class="mt-3">
                    <small class="text-muted">Promedio: 1.2s</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Botones Demo -->
    <div class="mb-5">
        <h3 class="mb-4">
            <i class="fas fa-mouse-pointer text-primary me-2"></i>
            Botones y Acciones Modernas
        </h3>
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Botones Primarios</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex flex-wrap gap-3">
                            <button class="btn btn-primary interactive-hover">
                                <i class="fas fa-save me-2"></i> Guardar
                            </button>
                            <button class="btn btn-success interactive-hover">
                                <i class="fas fa-check me-2"></i> Confirmar
                            </button>
                            <button class="btn btn-warning interactive-hover">
                                <i class="fas fa-exclamation me-2"></i> Advertencia
                            </button>
                            <button class="btn btn-danger interactive-hover">
                                <i class="fas fa-trash me-2"></i> Eliminar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Botones Secundarios</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex flex-wrap gap-3">
                            <button class="btn btn-outline-primary interactive-hover">
                                <i class="fas fa-edit me-2"></i> Editar
                            </button>
                            <button class="btn btn-secondary interactive-hover">
                                <i class="fas fa-times me-2"></i> Cancelar
                            </button>
                            <button class="btn btn-outline-success btn-sm interactive-hover">
                                <i class="fas fa-download me-2"></i> Pequeño
                            </button>
                            <button class="btn btn-primary btn-lg interactive-hover">
                                <i class="fas fa-rocket me-2"></i> Grande
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Formularios Demo -->
    <div class="mb-5">
        <h3 class="mb-4">
            <i class="fas fa-edit text-primary me-2"></i>
            Formularios Modernos
        </h3>
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Campos de Entrada</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label class="form-label">Nombre completo</label>
                            <input type="text" class="form-control" placeholder="Ingresa tu nombre completo">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-envelope"></i>
                                </span>
                                <input type="email" class="form-control" placeholder="ejemplo@correo.com">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Descripción</label>
                            <textarea class="form-control" rows="3" placeholder="Escribe una descripción..."></textarea>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Elementos Adicionales</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label class="form-label">Categoría</label>
                            <select class="form-control">
                                <option>Seleccionar categoría</option>
                                <option>Electrónicos</option>
                                <option>Ropa</option>
                                <option>Hogar</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Precio</label>
                            <div class="input-group">
                                <span class="input-group-text">Q</span>
                                <input type="number" class="form-control" placeholder="0.00">
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="demo-check">
                                <label class="form-check-label" for="demo-check">
                                    Producto activo
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Badges y Alertas Demo -->
    <div class="mb-5">
        <h3 class="mb-4">
            <i class="fas fa-tags text-primary me-2"></i>
            Badges y Alertas
        </h3>
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Estados y Badges</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex flex-wrap gap-3 mb-4">
                            <span class="badge badge-primary">Activo</span>
                            <span class="badge badge-success">Completado</span>
                            <span class="badge badge-warning">Pendiente</span>
                            <span class="badge badge-danger">Crítico</span>
                            <span class="badge badge-info">Información</span>
                        </div>
                        <div class="d-flex flex-wrap gap-3">
                            <span class="badge badge-primary">
                                <i class="fas fa-check me-1"></i> Con icono
                            </span>
                            <span class="badge badge-success">
                                <i class="fas fa-star me-1"></i> Destacado
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Alertas Informativas</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>
                            <strong>¡Éxito!</strong> La operación se completó correctamente.
                        </div>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Advertencia:</strong> Revisa los datos antes de continuar.
                        </div>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Información:</strong> Nueva actualización disponible.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla Demo -->
    <div class="mb-5">
        <h3 class="mb-4">
            <i class="fas fa-table text-primary me-2"></i>
            Tablas Modernas
        </h3>
        <div class="card interactive-hover">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i>
                        Lista de Productos Demo
                    </h5>
                    <span class="badge badge-primary">25 items</span>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Código</th>
                                <th>Estado</th>
                                <th>Stock</th>
                                <th>Precio</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="stat-icon me-3" style="width: 40px; height: 40px; font-size: 1rem;">
                                            <i class="fas fa-laptop"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold">Laptop Gaming ROG</div>
                                            <small class="text-muted">Computadoras</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <code class="bg-light px-2 py-1 rounded">LAP-001</code>
                                </td>
                                <td>
                                    <span class="badge badge-success">Activo</span>
                                </td>
                                <td>
                                    <span class="badge badge-warning">12 unidades</span>
                                </td>
                                <td>
                                    <strong class="text-success">Q 8,500.00</strong>
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="stat-icon me-3" style="width: 40px; height: 40px; font-size: 1rem;">
                                            <i class="fas fa-mobile-alt"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold">iPhone 14 Pro</div>
                                            <small class="text-muted">Smartphones</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <code class="bg-light px-2 py-1 rounded">PHN-045</code>
                                </td>
                                <td>
                                    <span class="badge badge-success">Activo</span>
                                </td>
                                <td>
                                    <span class="badge badge-danger">3 unidades</span>
                                </td>
                                <td>
                                    <strong class="text-success">Q 15,200.00</strong>
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="stat-icon me-3" style="width: 40px; height: 40px; font-size: 1rem;">
                                            <i class="fas fa-headphones"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold">Auriculares Sony WH-1000XM4</div>
                                            <small class="text-muted">Audio</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <code class="bg-light px-2 py-1 rounded">AUD-089</code>
                                </td>
                                <td>
                                    <span class="badge badge-success">Activo</span>
                                </td>
                                <td>
                                    <span class="badge badge-success">25 unidades</span>
                                </td>
                                <td>
                                    <strong class="text-success">Q 2,800.00</strong>
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Animaciones Demo -->
    <div class="mb-5">
        <h3 class="mb-4">
            <i class="fas fa-magic text-primary me-2"></i>
            Efectos y Animaciones
        </h3>
        <div class="row">
            <div class="col-md-4">
                <div class="card interactive-hover">
                    <div class="card-body text-center">
                        <div class="stat-icon mx-auto mb-3">
                            <i class="fas fa-mouse-pointer"></i>
                        </div>
                        <h5>Hover Effects</h5>
                        <p class="text-muted">Pasa el cursor sobre este card para ver el efecto de elevación.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card animate-pulse">
                    <div class="card-body text-center">
                        <div class="stat-icon mx-auto mb-3">
                            <i class="fas fa-heart"></i>
                        </div>
                        <h5>Pulse Animation</h5>
                        <p class="text-muted">Este card tiene una animación de pulso continua.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body text-center">
                        <div class="stat-icon mx-auto mb-3">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <h5>Gradientes</h5>
                        <p class="text-muted">Los iconos usan gradientes modernos para mayor atractivo visual.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Información adicional -->
    <div class="card">
        <div class="card-header bg-gradient">
            <h5 class="mb-0" style="color: white !important;">
                <i class="fas fa-info-circle me-2"></i>
                Características del Nuevo Diseño
            </h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6 class="text-primary">Mejoras Visuales</h6>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-check text-success me-2"></i> Paleta de colores profesional</li>
                        <li><i class="fas fa-check text-success me-2"></i> Tipografía moderna (Inter & Poppins)</li>
                        <li><i class="fas fa-check text-success me-2"></i> Sombras y gradientes sutiles</li>
                        <li><i class="fas fa-check text-success me-2"></i> Bordes redondeados consistentes</li>
                        <li><i class="fas fa-check text-success me-2"></i> Espaciado armónico</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h6 class="text-primary">Funcionalidades</h6>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-check text-success me-2"></i> Animaciones fluidas</li>
                        <li><i class="fas fa-check text-success me-2"></i> Microinteracciones</li>
                        <li><i class="fas fa-check text-success me-2"></i> Hover effects profesionales</li>
                        <li><i class="fas fa-check text-success me-2"></i> Responsive design mejorado</li>
                        <li><i class="fas fa-check text-success me-2"></i> Sistema de componentes consistente</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Demo de notificaciones toast
function mostrarToastDemo(tipo) {
    const mensajes = {
        'success': 'Operación completada exitosamente',
        'error': 'Error en la operación',
        'warning': 'Advertencia: Revisa los datos',
        'info': 'Nueva información disponible'
    };
    
    if (tipo === 'success') {
        showSuccess(mensajes[tipo], { title: 'Éxito' });
    } else if (tipo === 'error') {
        showError(mensajes[tipo], { title: 'Error' });
    } else if (tipo === 'warning') {
        showWarning(mensajes[tipo], { title: 'Advertencia' });
    } else {
        showInfo(mensajes[tipo], { title: 'Información' });
    }
}

// Auto-mostrar toast de bienvenida
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(() => {
        showNotification(
            'Diseño Renovado',
            'Bienvenido al nuevo sistema con diseño profesional y moderno',
            'primary',
            { duration: 5000 }
        );
    }, 1000);
});
</script>

<?php include 'includes/layout_footer.php'; ?>