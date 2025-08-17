</div> <!-- Cierre main-content -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/admin.js"></script>
<?php if (isset($js_adicional)): ?>
    <?php foreach ($js_adicional as $js): ?>
        <script src="<?php echo $js; ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>

<script>
// Funciones globales
function toggleSidebar() {
    const body = document.body;
    const isExpanded = body.classList.contains('sidebar-expanded');
    
    if (isExpanded) {
        body.classList.remove('sidebar-expanded');
        body.classList.add('sidebar-collapsed');
        setCookie('sidebar_state', 'sidebar-collapsed', 30);
    } else {
        body.classList.remove('sidebar-collapsed');
        body.classList.add('sidebar-expanded');
        setCookie('sidebar_state', 'sidebar-expanded', 30);
    }
}

function toggleEditMode(state = null) {
    const currentState = document.body.classList.contains('edit-mode');
    const newState = state !== null ? state : !currentState;
    
    fetch('includes/toggle_edit_mode.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ edit_mode: newState })
    })
    .then(() => {
        location.reload();
    });
}

function updateLabel(label, value) {
    fetch('includes/update_label.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ label: label, value: value })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Etiqueta actualizada correctamente', 'success');
        } else {
            showToast('Error al actualizar etiqueta', 'danger');
        }
    });
}

// Sistema de Toast Notifications mejorado
function showToast(message, type = 'info', options = {}) {
    const {
        duration = 5000,
        position = 'bottom-end',
        icon = true,
        dismissible = true,
        title = null
    } = options;
    
    // Iconos por tipo
    const icons = {
        'success': 'fas fa-check-circle',
        'danger': 'fas fa-exclamation-triangle',
        'warning': 'fas fa-exclamation-circle',
        'info': 'fas fa-info-circle',
        'primary': 'fas fa-bell',
        'secondary': 'fas fa-cog'
    };
    
    // Colores de texto para mejor contraste
    const textColors = {
        'warning': 'text-dark',
        'light': 'text-dark'
    };
    
    const textColor = textColors[type] || 'text-white';
    const iconHtml = icon ? `<i class="${icons[type] || 'fas fa-bell'} me-2"></i>` : '';
    const titleHtml = title ? `<div class="fw-bold mb-1">${iconHtml}${title}</div>` : '';
    const messageHtml = title ? message : `${iconHtml}${message}`;
    
    const toastHtml = `
        <div class="toast align-items-center ${textColor} bg-${type} border-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="${duration}">
            <div class="d-flex">
                <div class="toast-body">
                    ${titleHtml}${messageHtml}
                </div>
                ${dismissible ? `<button type="button" class="btn-close ${textColor === 'text-dark' ? '' : 'btn-close-white'} me-2 m-auto" data-bs-dismiss="toast" aria-label="Cerrar"></button>` : ''}
            </div>
        </div>
    `;
    
    let toastContainer = document.querySelector('.toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = `toast-container position-fixed ${getPositionClasses(position)} p-3`;
        toastContainer.style.zIndex = '9999';
        document.body.appendChild(toastContainer);
    }
    
    toastContainer.insertAdjacentHTML('beforeend', toastHtml);
    const toastElement = toastContainer.lastElementChild;
    const toast = new bootstrap.Toast(toastElement, {
        delay: duration,
        autohide: duration > 0
    });
    
    // Remover el toast del DOM después de que se oculte
    toastElement.addEventListener('hidden.bs.toast', function() {
        this.remove();
    });
    
    toast.show();
    
    return toast;
}

// Funciones de conveniencia para diferentes tipos
function showSuccess(message, options = {}) {
    return showToast(message, 'success', { icon: true, ...options });
}

function showError(message, options = {}) {
    return showToast(message, 'danger', { icon: true, duration: 8000, ...options });
}

function showWarning(message, options = {}) {
    return showToast(message, 'warning', { icon: true, duration: 6000, ...options });
}

function showInfo(message, options = {}) {
    return showToast(message, 'info', { icon: true, ...options });
}

// Notificación con título
function showNotification(title, message, type = 'info', options = {}) {
    return showToast(message, type, { title, icon: true, ...options });
}

// Notificación persistente (no se auto-oculta)
function showPersistentToast(message, type = 'info', options = {}) {
    return showToast(message, type, { duration: 0, ...options });
}

// Helper para posiciones
function getPositionClasses(position) {
    const positions = {
        'top-start': 'top-0 start-0',
        'top-center': 'top-0 start-50 translate-middle-x',
        'top-end': 'top-0 end-0',
        'middle-start': 'top-50 start-0 translate-middle-y',
        'middle-center': 'top-50 start-50 translate-middle',
        'middle-end': 'top-50 end-0 translate-middle-y',
        'bottom-start': 'bottom-0 start-0',
        'bottom-center': 'bottom-0 start-50 translate-middle-x',
        'bottom-end': 'bottom-0 end-0'
    };
    
    return positions[position] || positions['bottom-end'];
}

// Limpiar todos los toasts
function clearAllToasts() {
    const toastContainer = document.querySelector('.toast-container');
    if (toastContainer) {
        const toasts = toastContainer.querySelectorAll('.toast');
        toasts.forEach(toast => {
            const bsToast = bootstrap.Toast.getInstance(toast);
            if (bsToast) {
                bsToast.hide();
            }
        });
    }
}

function setCookie(name, value, days) {
    const expires = new Date();
    expires.setTime(expires.getTime() + (days * 24 * 60 * 60 * 1000));
    document.cookie = name + '=' + value + ';expires=' + expires.toUTCString() + ';path=/';
}

// Modo edición
document.addEventListener('DOMContentLoaded', function() {
    if (document.body.classList.contains('edit-mode')) {
        document.querySelectorAll('.editable').forEach(element => {
            element.addEventListener('click', function(e) {
                e.preventDefault();
                const currentText = this.textContent.trim();
                const label = this.dataset.label;
                
                const input = document.createElement('input');
                input.type = 'text';
                input.value = currentText;
                input.className = 'form-control form-control-sm';
                input.style.display = 'inline-block';
                input.style.width = 'auto';
                input.style.minWidth = '150px';
                
                const saveChanges = () => {
                    const newValue = input.value.trim();
                    if (newValue && newValue !== currentText) {
                        updateLabel(label, newValue);
                        this.textContent = newValue;
                    } else {
                        this.textContent = currentText;
                    }
                    this.style.display = '';
                };
                
                input.addEventListener('blur', saveChanges);
                input.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        saveChanges();
                    }
                    if (e.key === 'Escape') {
                        element.textContent = currentText;
                        element.style.display = '';
                    }
                });
                
                this.style.display = 'none';
                this.parentNode.insertBefore(input, this.nextSibling);
                input.focus();
                input.select();
            });
        });
    }
});

// Auto-cerrar sidebar en mobile al hacer click en un enlace
document.querySelectorAll('.nav-link').forEach(link => {
    link.addEventListener('click', function() {
        if (window.innerWidth <= 768) {
            document.body.classList.remove('sidebar-expanded');
            document.body.classList.add('sidebar-collapsed');
        }
    });
});
</script>

</body>
</html>