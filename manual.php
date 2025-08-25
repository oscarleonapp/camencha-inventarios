<?php
$titulo = "Manual de Uso - Sistema de Inventarios";
require_once 'includes/auth.php';
require_once 'config/database.php';
require_once 'includes/config_functions.php';

verificarLogin();
verificarPermiso('dashboard');

$database = new Database();
$db = $database->getConnection();

// Obtener la sección a mostrar
$seccion = $_GET['seccion'] ?? 'inicio';

include 'includes/layout_header.php';
?>

<style>
.manual-container {
    background: white;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
    padding: 30px;
    margin-bottom: 30px;
}

.manual-nav {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 30px;
    border: 1px solid #e9ecef;
}

.manual-nav .nav-link {
    color: #495057;
    border-radius: 8px;
    margin-bottom: 8px;
    padding: 12px 16px;
    transition: all 0.3s ease;
}

.manual-nav .nav-link:hover {
    background: #e9ecef;
    color: #007bff;
}

.manual-nav .nav-link.active {
    background: #007bff;
    color: white;
}

.step-card {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 20px;
    transition: all 0.3s ease;
}

.step-card:hover {
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.step-number {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #007bff, #0056b3);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    margin-right: 15px;
    flex-shrink: 0;
}

.step-content h5 {
    margin-bottom: 10px;
    color: #333;
}

.step-content p {
    color: #666;
    margin-bottom: 10px;
}

.tip-box {
    background: linear-gradient(135deg, #28a745, #20c997);
    color: white;
    padding: 15px;
    border-radius: 10px;
    margin: 20px 0;
}

.warning-box {
    background: linear-gradient(135deg, #ffc107, #fd7e14);
    color: white;
    padding: 15px;
    border-radius: 10px;
    margin: 20px 0;
}

.info-box {
    background: linear-gradient(135deg, #17a2b8, #138496);
    color: white;
    padding: 15px;
    border-radius: 10px;
    margin: 20px 0;
}

.kbd-shortcut {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 5px;
    padding: 10px;
    margin: 10px 0;
    font-family: monospace;
}

.feature-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.feature-card {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 10px;
    padding: 20px;
    text-align: center;
    transition: all 0.3s ease;
}

.feature-card:hover {
    background: white;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.feature-icon {
    font-size: 3rem;
    margin-bottom: 15px;
    background: linear-gradient(135deg, #007bff, #0056b3);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

@media (max-width: 768px) {
    .manual-container {
        padding: 20px;
        margin: 0 -15px;
        border-radius: 0;
    }
    
    .feature-grid {
        grid-template-columns: 1fr;
    }
}

.screenshot-container {
    background: #f8f9fa;
    border: 2px dashed #e9ecef;
    border-radius: 15px;
    padding: 20px;
    margin: 30px 0;
    transition: all 0.3s ease;
}

.screenshot-container:hover {
    border-color: #007bff;
    background: #f0f7ff;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,123,255,0.1);
}

.screenshot-container img {
    border: 3px solid #fff;
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    transition: all 0.3s ease;
}

.screenshot-container img:hover {
    transform: scale(1.02);
    box-shadow: 0 12px 35px rgba(0,0,0,0.2);
}

/* Navegación flotante interna */
.floating-nav {
    position: fixed;
    top: 50%;
    right: 20px;
    transform: translateY(-50%);
    background: white;
    border-radius: 10px;
    box-shadow: 0 5px 25px rgba(0,0,0,0.15);
    padding: 15px;
    max-width: 200px;
    z-index: 1000;
    transition: all 0.3s ease;
    opacity: 0;
    visibility: hidden;
}

.floating-nav.visible {
    opacity: 1;
    visibility: visible;
}

.floating-nav h6 {
    color: #495057;
    margin-bottom: 10px;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.floating-nav ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.floating-nav li {
    margin-bottom: 5px;
}

.floating-nav a {
    color: #6c757d !important;
    text-decoration: none;
    font-size: 0.85rem;
    display: block;
    padding: 5px 8px;
    border-radius: 5px;
    transition: all 0.2s ease;
}

.floating-nav a:hover {
    background: #f8f9fa;
    color: #007bff !important;
}

.floating-nav a.active {
    background: #007bff;
    color: white !important;
}

/* Botón para mostrar/ocultar navegación */
.nav-toggle {
    position: fixed;
    top: 50%;
    right: 20px;
    transform: translateY(-50%);
    background: #007bff;
    color: white;
    border: none;
    border-radius: 50%;
    width: 50px;
    height: 50px;
    cursor: pointer;
    box-shadow: 0 3px 15px rgba(0,123,255,0.3);
    z-index: 999;
    transition: all 0.3s ease;
}

.nav-toggle:hover {
    background: #0056b3;
    transform: translateY(-50%) scale(1.1);
}

/* Botón de "Volver arriba" */
.back-to-top {
    position: fixed;
    bottom: 30px;
    right: 30px;
    background: #28a745;
    color: white;
    border: none;
    border-radius: 50%;
    width: 50px;
    height: 50px;
    cursor: pointer;
    box-shadow: 0 3px 15px rgba(40,167,69,0.3);
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
    z-index: 998;
}

.back-to-top.visible {
    opacity: 1;
    visibility: visible;
}

.back-to-top:hover {
    background: #218838;
    transform: scale(1.1);
}

/* Indicador de progreso de lectura */
.reading-progress {
    position: fixed;
    top: 0;
    left: 0;
    width: 0%;
    height: 3px;
    background: linear-gradient(90deg, #007bff, #28a745);
    z-index: 9999;
    transition: width 0.1s ease;
}

/* Resaltado de secciones al hacer scroll */
h2[id], h3[id] {
    scroll-margin-top: 100px;
}

.section-highlight {
    animation: highlightSection 2s ease-out;
}

@keyframes highlightSection {
    0% {
        background: rgba(0,123,255,0.1);
        padding: 10px;
        border-radius: 10px;
    }
    100% {
        background: transparent;
        padding: 0;
    }
}

/* Mejoras para dispositivos móviles */
@media (max-width: 768px) {
    .floating-nav,
    .nav-toggle {
        display: none;
    }
    
    .back-to-top {
        bottom: 20px;
        right: 20px;
        width: 45px;
        height: 45px;
    }
}
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4 rs-wrap-sm">
                <h2><i class="fas fa-book-open text-primary"></i> Manual de Uso del Sistema</h2>
                <div class="btn-group">
                    <button class="btn btn-outline-primary" onclick="window.print()">
                        <i class="fas fa-print"></i> Imprimir
                    </button>
                    <a href="index.php" class="btn btn-outline-secondary">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Navegación del Manual -->
        <div class="col-md-3">
            <div class="manual-nav">
                <h5><i class="fas fa-list"></i> Índice</h5>
                <nav class="nav nav-pills flex-column">
                    <a class="nav-link <?php echo ($seccion == 'inicio') ? 'active' : ''; ?>" href="manual.php">
                        <i class="fas fa-home"></i> Inicio
                    </a>
                    <a class="nav-link <?php echo ($seccion == 'inicio-rapido') ? 'active' : ''; ?>" href="manual.php?seccion=inicio-rapido">
                        <i class="fas fa-rocket"></i> Inicio Rápido
                    </a>
                    <a class="nav-link <?php echo ($seccion == 'productos') ? 'active' : ''; ?>" href="manual.php?seccion=productos">
                        <i class="fas fa-boxes"></i> Gestión de Productos
                    </a>
                    <a class="nav-link <?php echo ($seccion == 'inventarios') ? 'active' : ''; ?>" href="manual.php?seccion=inventarios">
                        <i class="fas fa-warehouse"></i> Control de Inventarios
                    </a>
                    <a class="nav-link <?php echo ($seccion == 'pos') ? 'active' : ''; ?>" href="manual.php?seccion=pos">
                        <i class="fas fa-cash-register"></i> Punto de Venta (POS)
                    </a>
                    <a class="nav-link <?php echo ($seccion == 'qr') ? 'active' : ''; ?>" href="manual.php?seccion=qr">
                        <i class="fas fa-qrcode"></i> Escáner QR
                    </a>
                    <a class="nav-link <?php echo ($seccion == 'ventas') ? 'active' : ''; ?>" href="manual.php?seccion=ventas">
                        <i class="fas fa-shopping-cart"></i> Gestión de Ventas
                    </a>
                    <a class="nav-link <?php echo ($seccion == 'reportes') ? 'active' : ''; ?>" href="manual.php?seccion=reportes">
                        <i class="fas fa-chart-line"></i> Reportes
                    </a>
                    <a class="nav-link <?php echo ($seccion == 'faq') ? 'active' : ''; ?>" href="manual.php?seccion=faq">
                        <i class="fas fa-question-circle"></i> Preguntas Frecuentes
                    </a>
                </nav>
            </div>
        </div>

        <!-- Contenido del Manual -->
        <div class="col-md-9">
            <div class="manual-container">
                <?php
                switch ($seccion) {
                    case 'inicio':
                        include 'manual/inicio.php';
                        break;
                    case 'inicio-rapido':
                        include 'manual/inicio-rapido.php';
                        break;
                    case 'productos':
                        include 'manual/productos.php';
                        break;
                    case 'inventarios':
                        include 'manual/inventarios.php';
                        break;
                    case 'pos':
                        include 'manual/pos.php';
                        break;
                    case 'qr':
                        include 'manual/qr.php';
                        break;
                    case 'ventas':
                        include 'manual/ventas.php';
                        break;
                    case 'reportes':
                        include 'manual/reportes.php';
                        break;
                    case 'faq':
                        include 'manual/faq.php';
                        break;
                    default:
                        include 'manual/inicio.php';
                        break;
                }
                ?>
            </div>
        </div>
    </div>
</div>

<!-- Indicador de progreso de lectura -->
<div class="reading-progress"></div>

<!-- Navegación flotante interna -->
<button class="nav-toggle" onclick="toggleFloatingNav()" title="Mostrar navegación">
    <i class="fas fa-list"></i>
</button>

<div class="floating-nav" id="floatingNav">
    <h6><i class="fas fa-map-signs"></i> En esta página</h6>
    <ul id="sectionsList">
        <!-- Se llena dinámicamente con JavaScript -->
    </ul>
</div>

<!-- Botón volver arriba -->
<button class="back-to-top" onclick="scrollToTop()" title="Volver arriba">
    <i class="fas fa-arrow-up"></i>
</button>

<script>
// Variables globales
let floatingNavVisible = false;
let sections = [];

// Inicialización cuando la página carga
document.addEventListener('DOMContentLoaded', function() {
    initializeNavigation();
    initializeScrollEffects();
    enhanceContent();
});

// Inicializar navegación flotante
function initializeNavigation() {
    // Encontrar todas las secciones (h2 y h3)
    sections = [];
    const headings = document.querySelectorAll('.manual-container h2, .manual-container h3');
    
    headings.forEach((heading, index) => {
        // Crear ID si no existe
        if (!heading.id) {
            heading.id = 'section-' + index;
        }
        
        sections.push({
            id: heading.id,
            title: heading.textContent.replace(/[🔍📊💳📱🏪✏️🔧💡❓🏆]/g, '').trim(),
            level: heading.tagName,
            element: heading
        });
    });
    
    // Llenar navegación flotante
    populateFloatingNav();
}

// Llenar la navegación flotante con secciones
function populateFloatingNav() {
    const sectionsList = document.getElementById('sectionsList');
    sectionsList.innerHTML = '';
    
    sections.forEach(section => {
        const li = document.createElement('li');
        const a = document.createElement('a');
        a.href = '#' + section.id;
        a.textContent = section.title;
        a.className = section.level === 'H3' ? 'subsection' : '';
        a.addEventListener('click', function(e) {
            e.preventDefault();
            scrollToSection(section.id);
        });
        
        li.appendChild(a);
        sectionsList.appendChild(li);
    });
}

// Scroll suave a una sección
function scrollToSection(sectionId) {
    const target = document.getElementById(sectionId);
    if (target) {
        target.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
        
        // Resaltar la sección
        target.classList.add('section-highlight');
        setTimeout(() => {
            target.classList.remove('section-highlight');
        }, 2000);
    }
}

// Inicializar efectos de scroll
function initializeScrollEffects() {
    let ticking = false;
    
    function updateScrollEffects() {
        const scrollTop = window.pageYOffset;
        const docHeight = document.documentElement.scrollHeight - window.innerHeight;
        const scrollPercent = (scrollTop / docHeight) * 100;
        
        // Actualizar barra de progreso
        const progressBar = document.querySelector('.reading-progress');
        if (progressBar) {
            progressBar.style.width = scrollPercent + '%';
        }
        
        // Mostrar/ocultar botón "volver arriba"
        const backToTop = document.querySelector('.back-to-top');
        if (backToTop) {
            if (scrollTop > 300) {
                backToTop.classList.add('visible');
            } else {
                backToTop.classList.remove('visible');
            }
        }
        
        // Actualizar navegación activa
        updateActiveSection();
        
        ticking = false;
    }
    
    window.addEventListener('scroll', function() {
        if (!ticking) {
            requestAnimationFrame(updateScrollEffects);
            ticking = true;
        }
    });
}

// Actualizar sección activa en navegación
function updateActiveSection() {
    const scrollTop = window.pageYOffset + 150; // Offset para el header
    
    // Remover clase activa de todos los enlaces
    document.querySelectorAll('.floating-nav a').forEach(link => {
        link.classList.remove('active');
    });
    
    // Encontrar la sección actual
    let currentSection = null;
    sections.forEach(section => {
        const element = section.element;
        if (element.offsetTop <= scrollTop) {
            currentSection = section;
        }
    });
    
    // Marcar como activa
    if (currentSection) {
        const activeLink = document.querySelector(`.floating-nav a[href="#${currentSection.id}"]`);
        if (activeLink) {
            activeLink.classList.add('active');
        }
    }
}

// Toggle navegación flotante
function toggleFloatingNav() {
    const floatingNav = document.getElementById('floatingNav');
    const navToggle = document.querySelector('.nav-toggle');
    
    floatingNavVisible = !floatingNavVisible;
    
    if (floatingNavVisible) {
        floatingNav.classList.add('visible');
        navToggle.innerHTML = '<i class="fas fa-times"></i>';
        navToggle.style.right = '240px'; // Mover botón
    } else {
        floatingNav.classList.remove('visible');
        navToggle.innerHTML = '<i class="fas fa-list"></i>';
        navToggle.style.right = '20px';
    }
}

// Scroll al top
function scrollToTop() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}

// Mejorar contenido existente
function enhanceContent() {
    // Smooth scrolling para enlaces internos
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Resaltar código en bloques de ejemplo
    document.querySelectorAll('code').forEach(block => {
        block.style.background = '#f8f9fa';
        block.style.padding = '2px 6px';
        block.style.borderRadius = '4px';
        block.style.border = '1px solid #e9ecef';
    });

    // Agregar iconos a listas importantes
    document.querySelectorAll('.step-content ul li').forEach(li => {
        if (!li.querySelector('i')) {
            li.innerHTML = '<i class="fas fa-check text-success me-2"></i>' + li.innerHTML;
        }
    });
    
    // Hacer capturas clickeables para zoom
    document.querySelectorAll('.screenshot-container img').forEach(img => {
        img.style.cursor = 'pointer';
        img.addEventListener('click', function() {
            // Crear modal para vista ampliada
            const modal = document.createElement('div');
            modal.className = 'modal fade';
            modal.innerHTML = `
                <div class="modal-dialog modal-xl">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">${this.alt}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body text-center">
                            <img src="${this.src}" class="img-fluid" alt="${this.alt}">
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
            
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();
            
            // Limpiar modal al cerrar
            modal.addEventListener('hidden.bs.modal', function() {
                document.body.removeChild(modal);
            });
        });
    });
}

// Atajos de teclado para navegación
document.addEventListener('keydown', function(e) {
    // Esc para cerrar navegación flotante
    if (e.key === 'Escape' && floatingNavVisible) {
        toggleFloatingNav();
    }
    
    // Ctrl + M para toggle navegación
    if (e.ctrlKey && e.key === 'm') {
        e.preventDefault();
        toggleFloatingNav();
    }
    
    // Ctrl + ↑ para ir arriba
    if (e.ctrlKey && e.key === 'ArrowUp') {
        e.preventDefault();
        scrollToTop();
    }
});
</script>

<?php include 'includes/layout_footer.php'; ?>
