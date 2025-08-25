// Sistema de administración JavaScript

// Inicialización
document.addEventListener('DOMContentLoaded', function() {
    initializeTooltips();
    initializeCharts();
    initializeDataTables();
    initializeSidebar();
    
    // Configurar estado inicial del sidebar
    const savedState = getCookie('sidebar_state') || (window.innerWidth > 768 ? 'sidebar-expanded' : 'sidebar-collapsed');
    setSidebarState(savedState);
});

// Función para inicializar sidebar
function initializeSidebar() {
    // Manejar clics en submenús
    document.querySelectorAll('.submenu-toggle').forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            
            const targetId = this.getAttribute('data-bs-target');
            const submenu = document.querySelector(targetId);
            
            if (submenu) {
                const isExpanded = submenu.classList.contains('show');
                
                // Cerrar otros submenús
                document.querySelectorAll('.submenu.show').forEach(openSubmenu => {
                    if (openSubmenu !== submenu) {
                        openSubmenu.classList.remove('show');
                        const otherToggle = document.querySelector(`[data-bs-target="#${openSubmenu.id}"]`);
                        if (otherToggle) {
                            otherToggle.setAttribute('aria-expanded', 'false');
                        }
                    }
                });
                
                // Toggle el submenú actual
                if (isExpanded) {
                    submenu.classList.remove('show');
                    this.setAttribute('aria-expanded', 'false');
                } else {
                    submenu.classList.add('show');
                    this.setAttribute('aria-expanded', 'true');
                }
            }
        });
    });
    
    // Auto-abrir submenú si hay una página activa dentro
    document.querySelectorAll('.submenu-link.active').forEach(activeLink => {
        const submenu = activeLink.closest('.submenu');
        if (submenu) {
            submenu.classList.add('show');
            const toggle = document.querySelector(`[data-bs-target="#${submenu.id}"]`);
            if (toggle) {
                toggle.setAttribute('aria-expanded', 'true');
            }
        }
    });
}

// Funciones de utilidad
function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
}

function setCookie(name, value, days) {
    const expires = new Date();
    expires.setTime(expires.getTime() + (days * 24 * 60 * 60 * 1000));
    document.cookie = name + '=' + value + ';expires=' + expires.toUTCString() + ';path=/';
}

// Inicializar tooltips
function initializeTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

// Inicializar gráficos (Chart.js si está disponible)
function initializeCharts() {
    if (typeof Chart !== 'undefined') {
        // Configuración global de Chart.js
        Chart.defaults.font.family = "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif";
        Chart.defaults.color = '#6c757d';
        Chart.defaults.borderColor = '#dee2e6';
    }
}

// Inicializar DataTables si está disponible
function initializeDataTables() {
    if (typeof $.fn.DataTable !== 'undefined') {
        $('.data-table').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.12.1/i18n/es-ES.json'
            },
            responsive: true,
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Todos"]],
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                 '<"row"<"col-sm-12"tr>>' +
                 '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        });
    }
}

// Sistema de notificaciones
class NotificationSystem {
    constructor() {
        this.container = this.createContainer();
    }
    
    createContainer() {
        let container = document.querySelector('.toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
            container.style.zIndex = '1055';
            document.body.appendChild(container);
        }
        return container;
    }
    
    show(message, type = 'info', duration = 5000) {
        const toast = document.createElement('div');
        const textColor = (type === 'warning' || type === 'light') ? 'text-dark' : 'text-white';
        const closeBtnClass = (textColor === 'text-dark') ? 'btn-close' : 'btn-close-white';
        toast.className = `toast align-items-center ${textColor} bg-${type}`;
        toast.setAttribute('role', 'alert');
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas fa-${this.getIcon(type)} me-2"></i>
                    ${message}
                </div>
                <button type="button" class="btn-close ${closeBtnClass} me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;
        
        this.container.appendChild(toast);
        
        const bsToast = new bootstrap.Toast(toast, {
            delay: duration
        });
        
        bsToast.show();
        
        toast.addEventListener('hidden.bs.toast', () => {
            toast.remove();
        });
    }
    
    getIcon(type) {
        const icons = {
            'success': 'check-circle',
            'danger': 'exclamation-triangle',
            'warning': 'exclamation-circle',
            'info': 'info-circle'
        };
        return icons[type] || 'info-circle';
    }
    
    success(message) { this.show(message, 'success'); }
    error(message) { this.show(message, 'danger'); }
    warning(message) { this.show(message, 'warning'); }
    info(message) { this.show(message, 'info'); }
}

// Instancia global del sistema de notificaciones
window.notifications = new NotificationSystem();

// Función global para mostrar notificaciones (compatibilidad)
function showToast(message, type = 'info') {
    window.notifications.show(message, type);
}

// Sistema de confirmación
function confirmAction(message, callback, title = '¿Estás seguro?') {
    const modalHtml = `
        <div class="modal fade" id="confirmModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">${title}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>${message}</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-danger" id="confirmBtn">Confirmar</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remover modal existente si hay uno
    const existingModal = document.getElementById('confirmModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    // Agregar nuevo modal
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    const modal = new bootstrap.Modal(document.getElementById('confirmModal'));
    
    document.getElementById('confirmBtn').addEventListener('click', () => {
        callback();
        modal.hide();
    });
    
    modal.show();
    
    // Limpiar después de cerrar
    document.getElementById('confirmModal').addEventListener('hidden.bs.modal', () => {
        document.getElementById('confirmModal').remove();
    });
}

// Funciones de sidebar
function toggleSidebar() {
    const body = document.body;
    const isExpanded = body.classList.contains('sidebar-expanded');
    const isMobile = window.innerWidth <= 768;
    
    if (isMobile) {
        // En móvil, solo toggle expanded/collapsed
        if (isExpanded) {
            setSidebarState('sidebar-collapsed');
        } else {
            setSidebarState('sidebar-expanded');
        }
    } else {
        // En desktop/tablet, toggle expanded/collapsed
        if (isExpanded) {
            setSidebarState('sidebar-collapsed');
        } else {
            setSidebarState('sidebar-expanded');
        }
    }
}

function setSidebarState(state) {
    const body = document.body;
    
    // Remover clases existentes
    body.classList.remove('sidebar-expanded', 'sidebar-collapsed');
    
    // Agregar nueva clase
    body.classList.add(state);
    
    // Guardar estado
    setCookie('sidebar_state', state, 30);
    
    // En sidebar colapsado, cerrar todos los submenús
    if (state === 'sidebar-collapsed') {
        document.querySelectorAll('.submenu.show').forEach(submenu => {
            submenu.classList.remove('show');
        });
        document.querySelectorAll('.submenu-toggle[aria-expanded="true"]').forEach(toggle => {
            toggle.setAttribute('aria-expanded', 'false');
        });
    }
}

// Formateo de números y moneda
function formatCurrency(amount, symbol = '$') {
    return symbol + parseFloat(amount).toLocaleString('es-ES', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function formatNumber(number) {
    return parseFloat(number).toLocaleString('es-ES');
}

// Funciones AJAX helpers
function makeRequest(url, method = 'GET', data = null) {
    const options = {
        method: method,
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    };
    
    if (data && (method === 'POST' || method === 'PUT')) {
        options.body = JSON.stringify(data);
    }
    
    return fetch(url, options)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        });
}

// Validación de formularios
function validateForm(form) {
    let isValid = true;
    const requiredFields = form.querySelectorAll('[required]');
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('is-invalid');
            isValid = false;
        } else {
            field.classList.remove('is-invalid');
        }
    });
    
    return isValid;
}

// Funciones de utilidad para tablas
function addTableRow(tableId, data) {
    const table = document.getElementById(tableId);
    const tbody = table.querySelector('tbody');
    const row = tbody.insertRow();
    
    data.forEach(cellData => {
        const cell = row.insertCell();
        cell.innerHTML = cellData;
    });
    
    return row;
}

function removeTableRow(button) {
    const row = button.closest('tr');
    confirmAction('¿Estás seguro de que quieres eliminar esta fila?', () => {
        row.remove();
        showToast('Fila eliminada', 'success');
    });
}

// Sistema de búsqueda en tiempo real
function setupRealTimeSearch(inputId, tableId) {
    const searchInput = document.getElementById(inputId);
    const table = document.getElementById(tableId);
    
    if (!searchInput || !table) return;
    
    searchInput.addEventListener('input', function() {
        const filter = this.value.toLowerCase();
        const rows = table.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
    });
}

// Funciones de tema y personalización
function changeTheme(themeName) {
    document.documentElement.setAttribute('data-theme', themeName);
    setCookie('theme', themeName, 365);
    showToast('Tema cambiado correctamente', 'success');
}

function updateSystemColors(primary, secondary) {
    const root = document.documentElement;
    root.style.setProperty('--primary-color', primary);
    root.style.setProperty('--secondary-color', secondary);
    
    // Guardar en localStorage
    localStorage.setItem('custom-colors', JSON.stringify({primary, secondary}));
    showToast('Colores actualizados', 'success');
}

// Cargar colores personalizados al iniciar
document.addEventListener('DOMContentLoaded', function() {
    const savedColors = localStorage.getItem('custom-colors');
    if (savedColors) {
        const colors = JSON.parse(savedColors);
        updateSystemColors(colors.primary, colors.secondary);
    }
});

// Exportar funciones globalmente
window.toggleSidebar = toggleSidebar;
window.showToast = showToast;
window.confirmAction = confirmAction;
window.formatCurrency = formatCurrency;
window.formatNumber = formatNumber;
window.makeRequest = makeRequest;
window.validateForm = validateForm;
window.notifications = window.notifications;
