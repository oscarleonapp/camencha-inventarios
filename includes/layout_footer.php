</div> <!-- Cierre main-content -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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

// Responsiveness: envolver tablas automáticamente para scroll horizontal en pantallas pequeñas
document.addEventListener('DOMContentLoaded', function() {
    const wrapTable = (tbl) => {
        if (!tbl || !(tbl instanceof HTMLElement)) return;
        if (tbl.classList.contains('no-auto-responsive') || tbl.hasAttribute('data-no-responsive')) return;
        if (tbl.closest('[class*="table-responsive"], .responsive-auto-wrapper')) return;
        const wrapper = document.createElement('div');
        wrapper.className = 'table-responsive responsive-auto-wrapper';
        tbl.parentNode.insertBefore(wrapper, tbl);
        wrapper.appendChild(tbl);
    };

    try {
        const tables = document.querySelectorAll('table.table');
        tables.forEach(wrapTable);

        // Etiquetas específicas por página para min-width dirigidos
        const page = (location.pathname.split('/').pop() || '').toLowerCase();
        const pageClassMap = {
            'contabilidad_reconciliacion.php': 'reconciliacion-table',
            'boletas.php': 'boletas-table',
            'aprobacion_ventas_vendedor.php': 'aprobacion-vendedor-table',
            'inventarios.php': 'inventarios-table',
            'cotizaciones.php': 'cotizaciones-table',
            'proveedores.php': 'proveedores-table',
            'usuarios.php': 'usuarios-table',
            'reportes_vendedores.php': 'reportes-vendedores-table',
            'ventas.php': 'ventas-table',
            'historial_ventas.php': 'historial-ventas-table',
            'reorden.php': 'reorden-table',
            'reparaciones.php': 'reparaciones-table',
            'reparaciones_enviar.php': 'reparaciones-enviar-table',
            'reparaciones_recibir.php': 'reparaciones-recibir-table',
            'compras.php': 'compras-table',
            'traslados.php': 'traslados-table',
            'gerente_dashboard.php': 'gerente-dashboard-table',
            'ranking_vendedores.php': 'ranking-vendedores-table'
        };

        // Listados auxiliares sin clase específica en el markup
        Object.assign(pageClassMap, {
            'lista_tiendas.php': 'lista-tiendas-table',
            'lista_encargados.php': 'lista-encargados-table',
            'tiendas.php': 'tiendas-table'
        });
        const extraClass = pageClassMap[page];
        if (extraClass) {
            tables.forEach(tbl => tbl.classList.add(extraClass));
        }

        // Observar tablas añadidas dinámicamente (AJAX/partials)
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((m) => {
                m.addedNodes.forEach((node) => {
                    if (node.nodeType !== 1) return; // ELEMENT_NODE
                    if (node.matches && node.matches('table.table')) {
                        wrapTable(node);
                        if (extraClass) node.classList.add(extraClass);
                    } else if (node.querySelectorAll) {
                        node.querySelectorAll('table.table').forEach(t => {
                            wrapTable(t);
                            if (extraClass) t.classList.add(extraClass);
                        });
                    }
                });
            });
        });
        observer.observe(document.body, { childList: true, subtree: true });
    } catch (e) {
        console.warn('Auto-responsive tables wrapper error:', e);
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

// PWA Service Worker Registration
if ('serviceWorker' in navigator) {
    window.addEventListener('load', function() {
        navigator.serviceWorker.register('/inventario-claude/sw.js')
            .then(function(registration) {
                console.log('[PWA] Service Worker registrado exitosamente:', registration.scope);
                
                // Verificar actualizaciones
                registration.addEventListener('updatefound', function() {
                    const newWorker = registration.installing;
                    newWorker.addEventListener('statechange', function() {
                        if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                            // Nueva versión disponible
                            showUpdateAvailable(registration);
                        }
                    });
                });
            })
            .catch(function(error) {
                console.log('[PWA] Error al registrar Service Worker:', error);
            });
            
        // Manejar updates del SW
        navigator.serviceWorker.addEventListener('controllerchange', function() {
            console.log('[PWA] Service Worker actualizado');
            showSuccess('Aplicación actualizada correctamente', {
                title: 'Sistema Actualizado',
                duration: 3000
            });
        });
    });
    
    // Detectar cuando la app está offline/online
    window.addEventListener('online', function() {
        showSuccess('Conexión restaurada', {
            title: 'Conectado',
            icon: true,
            duration: 3000
        });
    });
    
    window.addEventListener('offline', function() {
        showWarning('Sin conexión a internet. Algunas funciones pueden estar limitadas.', {
            title: 'Sin Conexión',
            icon: true,
            duration: 0 // Persistente hasta que se restaure la conexión
        });
    });
}

// PWA Install Prompt
let deferredPrompt;
window.addEventListener('beforeinstallprompt', function(e) {
    console.log('[PWA] Evento beforeinstallprompt disparado');
    e.preventDefault();
    deferredPrompt = e;
    
    // Mostrar botón de instalación en el menú o como toast
    showInstallPrompt();
});

function showInstallPrompt() {
    // Solo mostrar si no está ya instalado
    if (!window.matchMedia('(display-mode: standalone)').matches) {
        showNotification(
            'Instalar Aplicación',
            'Instala el Sistema de Inventarios en tu dispositivo para acceso rápido y funciones offline.',
            'primary',
            {
                duration: 10000,
                dismissible: true
            }
        );
        
        // Agregar botón de instalación al menú de usuario si no existe
        const userDropdown = document.querySelector('.dropdown-menu');
        if (userDropdown && !document.querySelector('#install-pwa-btn')) {
            const installBtn = document.createElement('li');
            installBtn.innerHTML = `
                <a class="dropdown-item" href="#" id="install-pwa-btn" onclick="installPWA()">
                    <i class="fas fa-download"></i> Instalar App
                </a>
            `;
            userDropdown.insertBefore(installBtn, userDropdown.querySelector('.dropdown-divider'));
        }
    }
}

async function installPWA() {
    if (deferredPrompt) {
        deferredPrompt.prompt();
        const { outcome } = await deferredPrompt.userChoice;
        
        if (outcome === 'accepted') {
            console.log('[PWA] Usuario aceptó la instalación');
            showSuccess('Aplicación instalada correctamente', {
                title: 'Instalación Exitosa',
                duration: 5000
            });
            
            // Ocultar botón de instalación
            const installBtn = document.querySelector('#install-pwa-btn');
            if (installBtn) {
                installBtn.parentElement.remove();
            }
        } else {
            console.log('[PWA] Usuario rechazó la instalación');
        }
        
        deferredPrompt = null;
    }
}

// Detectar si ya está instalado como PWA
window.addEventListener('appinstalled', function() {
    console.log('[PWA] Aplicación instalada');
    showSuccess('Sistema instalado como aplicación', {
        title: 'Instalación Completada',
        duration: 5000
    });
    
    // Ocultar prompts de instalación
    const installBtn = document.querySelector('#install-pwa-btn');
    if (installBtn) {
        installBtn.parentElement.remove();
    }
});

// Mostrar notificación cuando hay una nueva versión disponible
function showUpdateAvailable(registration) {
    showNotification(
        'Actualización Disponible',
        'Nueva versión de la aplicación lista. Haz clic para actualizar.',
        'info',
        {
            duration: 0, // Persistente
            dismissible: false
        }
    );
    
    // Agregar botón de actualización
    const updateBtn = document.createElement('button');
    updateBtn.className = 'btn btn-sm btn-primary ms-2';
    updateBtn.innerHTML = '<i class="fas fa-sync-alt"></i> Actualizar';
    updateBtn.onclick = function() {
        const newWorker = registration.waiting;
        if (newWorker) {
            newWorker.postMessage({ type: 'SKIP_WAITING' });
        }
        window.location.reload();
    };
    
    // Buscar el toast de actualización y agregar el botón
    setTimeout(() => {
        const toastBody = document.querySelector('.toast:last-child .toast-body');
        if (toastBody) {
            toastBody.appendChild(updateBtn);
        }
    }, 100);
}

// Funciones PWA adicionales
function getPWADisplayMode() {
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches;
    if (document.referrer.startsWith('android-app://')) {
        return 'twa';
    } else if (navigator.standalone || isStandalone) {
        return 'standalone';
    }
    return 'browser';
}

// Cache management functions
function updateCache() {
    if ('serviceWorker' in navigator && navigator.serviceWorker.controller) {
        navigator.serviceWorker.controller.postMessage({ type: 'CACHE_UPDATE' });
        showInfo('Actualizando datos en cache...', {
            title: 'Sincronización',
            duration: 3000
        });
    }
}

function clearCache() {
    if ('serviceWorker' in navigator && navigator.serviceWorker.controller) {
        navigator.serviceWorker.controller.postMessage({ type: 'CLEAR_CACHE' });
        showWarning('Cache limpiado. La página se recargará.', {
            title: 'Cache Limpiado',
            duration: 3000
        });
        setTimeout(() => window.location.reload(), 2000);
    }
}

async function getCacheStatus() {
    if ('serviceWorker' in navigator && navigator.serviceWorker.controller) {
        const messageChannel = new MessageChannel();
        navigator.serviceWorker.controller.postMessage({ type: 'GET_CACHE_STATUS' }, [messageChannel.port2]);
        
        return new Promise((resolve) => {
            messageChannel.port1.onmessage = (event) => {
                resolve(event.data);
            };
        });
    }
    return null;
}

// Mostrar estado PWA en consola (para debugging)
console.log('[PWA] Display Mode:', getPWADisplayMode());
console.log('[PWA] Online Status:', navigator.onLine);
console.log('[PWA] Service Worker Support:', 'serviceWorker' in navigator);

// Agregar clase CSS para PWA standalone
if (getPWADisplayMode() === 'standalone') {
    document.body.classList.add('pwa-standalone');
}
</script>

</body>
</html>
