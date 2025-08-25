<h1><i class="fas fa-boxes text-primary"></i> Gestión de Productos</h1>

<div class="tip-box">
    <h5><i class="fas fa-info-circle"></i> Módulo Central del Sistema</h5>
    <p>La gestión de productos es el corazón de tu inventario. Aquí aprenderás a crear, editar y organizar tu catálogo de manera eficiente.</p>
</div>

<h2>📦 Tipos de Productos</h2>

<div class="row">
    <div class="col-md-6">
        <div class="feature-card">
            <div class="feature-icon">
                <i class="fas fa-cube"></i>
            </div>
            <h5>Elementos</h5>
            <p>Productos individuales que se venden por unidad.</p>
            <ul>
                <li>Mouse, teclado, monitor</li>
                <li>Código automático: <code>EL-001</code></li>
                <li>Stock individual</li>
                <li>QR único generado automáticamente</li>
            </ul>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="feature-card">
            <div class="feature-icon">
                <i class="fas fa-cubes"></i>
            </div>
            <h5>Conjuntos</h5>
            <p>Productos compuestos por varios elementos.</p>
            <ul>
                <li>PC completa, kit de oficina</li>
                <li>Código automático: <code>CJ-001</code></li>
                <li>Stock calculado por componentes</li>
                <li>QR del conjunto principal</li>
            </ul>
        </div>
    </div>
</div>

<h2>➕ Crear un Nuevo Producto</h2>

<div class="screenshot-container text-center mb-4">
    <img src="assets/screenshots/productos-gestion.png" alt="Pantalla de Gestión de Productos" class="img-fluid rounded shadow" style="max-width: 100%; height: auto;">
    <p class="text-muted mt-2"><small><i class="fas fa-camera"></i> Pantalla principal de gestión de productos</small></p>
</div>

<div class="step-card">
    <div class="d-flex align-items-start">
        <div class="step-number">1</div>
        <div class="step-content">
            <h5>Acceder al Formulario</h5>
            <p><strong>Navegación:</strong> Menú → Productos → Gestión de Productos → "Nuevo Producto"</p>
            <div class="kbd-shortcut">
                <strong>Atajo rápido:</strong> Desde cualquier página, busca el botón verde <button class="btn btn-success btn-sm"><i class="fas fa-plus"></i> Nuevo</button>
            </div>
        </div>
    </div>
</div>

<div class="step-card">
    <div class="d-flex align-items-start">
        <div class="step-number">2</div>
        <div class="step-content">
            <h5>Completar Información Básica</h5>
            <div class="row">
                <div class="col-md-6">
                    <h6>📝 Campos Obligatorios:</h6>
                    <ul>
                        <li><strong>Nombre:</strong> Descriptivo y claro</li>
                        <li><strong>Precio de Venta:</strong> En quetzales (Q)</li>
                        <li><strong>Tipo:</strong> Elemento o Conjunto</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h6>🔧 Campos Opcionales:</h6>
                    <ul>
                        <li><strong>Código:</strong> Se genera automáticamente</li>
                        <li><strong>Descripción:</strong> Detalles adicionales</li>
                        <li><strong>Precio de Compra:</strong> Para cálculos de margen</li>
                        <li><strong>Categoría:</strong> Para organización</li>
                    </ul>
                </div>
            </div>
            
            <div class="info-box">
                <h6><i class="fas fa-magic"></i> Generación Automática de Códigos</h6>
                <p>El sistema genera códigos únicos automáticamente:</p>
                <ul class="mb-0">
                    <li><strong>Elementos:</strong> <code>2024-EL-001</code>, <code>2024-EL-002</code>, etc.</li>
                    <li><strong>Conjuntos:</strong> <code>2024-CJ-001</code>, <code>2024-CJ-002</code>, etc.</li>
                    <li>Incluye el año para trazabilidad temporal</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="step-card">
    <div class="d-flex align-items-start">
        <div class="step-number">3</div>
        <div class="step-content">
            <h5>Configurar Conjuntos (Si aplica)</h5>
            <p>Si seleccionaste "Conjunto", aparecerá la sección de componentes:</p>
            <div class="warning-box">
                <h6><i class="fas fa-exclamation-triangle"></i> Importante para Conjuntos</h6>
                <ul class="mb-0">
                    <li>Agrega todos los elementos que incluye</li>
                    <li>Especifica la cantidad de cada componente</li>
                    <li>El stock se calculará automáticamente</li>
                    <li>Ejemplo: PC = 1 Monitor + 1 CPU + 1 Teclado + 1 Mouse</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<h2>📋 Lista de Productos</h2>

<div class="step-card">
    <div class="step-content">
        <h5><i class="fas fa-list text-primary"></i> Navegación y Filtros</h5>
        
        <div class="row">
            <div class="col-md-6">
                <h6>🔍 Herramientas de Búsqueda:</h6>
                <ul>
                    <li><strong>Barra superior:</strong> Busca por nombre o código</li>
                    <li><strong>Filtro por tipo:</strong> Elementos/Conjuntos</li>
                    <li><strong>Filtro por estado:</strong> Activos/Inactivos</li>
                    <li><strong>Orden:</strong> Por nombre, código o fecha</li>
                </ul>
            </div>
            <div class="col-md-6">
                <h6>ℹ️ Información Mostrada:</h6>
                <ul>
                    <li><strong>Código QR:</strong> <i class="fas fa-qrcode"></i> Descarga individual</li>
                    <li><strong>Stock Total:</strong> Suma de todas las tiendas</li>
                    <li><strong>Precio:</strong> Formatado en quetzales</li>
                    <li><strong>Estado:</strong> Activo/Inactivo visual</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<h2>✏️ Editar Productos</h2>

<div class="step-card">
    <div class="d-flex align-items-start">
        <div class="step-number">4</div>
        <div class="step-content">
            <h5>Modificar Información</h5>
            <p><strong>Acceso:</strong> Lista de productos → Icono <i class="fas fa-edit text-primary"></i> en la fila del producto</p>
            
            <div class="row mt-3">
                <div class="col-md-6">
                    <h6>✅ Cambios Permitidos:</h6>
                    <ul>
                        <li>Nombre del producto</li>
                        <li>Descripción</li>
                        <li>Precios de compra y venta</li>
                        <li>Categoría</li>
                        <li>Estado (Activo/Inactivo)</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h6>🚫 Cambios Restringidos:</h6>
                    <ul>
                        <li>Código del producto (automático)</li>
                        <li>Tipo (Elemento ↔ Conjunto)</li>
                        <li>QR generado (se mantiene)</li>
                    </ul>
                </div>
            </div>
            
            <div class="tip-box">
                <small><i class="fas fa-lightbulb"></i> <strong>Tip:</strong> Los cambios se aplican inmediatamente en todo el sistema, incluyendo inventarios y reportes.</small>
            </div>
        </div>
    </div>
</div>

<h2>📊 Importación Masiva</h2>

<div class="step-card">
    <div class="d-flex align-items-start">
        <div class="step-number">5</div>
        <div class="step-content">
            <h5>Importar desde Excel/CSV</h5>
            <p><strong>Navegación:</strong> Menú → Productos → Importar Productos</p>
            
            <div class="row">
                <div class="col-md-6">
                    <h6>📥 Proceso de Importación:</h6>
                    <ol>
                        <li>Descarga la plantilla Excel</li>
                        <li>Completa los datos en Excel</li>
                        <li>Guarda como .xlsx o .csv</li>
                        <li>Arrastra el archivo al sistema</li>
                        <li>Revisa el reporte de validación</li>
                        <li>Confirma la importación</li>
                    </ol>
                </div>
                <div class="col-md-6">
                    <h6>✅ Ventajas de la Importación:</h6>
                    <ul>
                        <li>Carga masiva de productos</li>
                        <li>Validación automática de datos</li>
                        <li>Reporte de errores detallado</li>
                        <li>Códigos QR generados automáticamente</li>
                        <li>Transacciones seguras</li>
                    </ul>
                </div>
            </div>
            
            <div class="warning-box">
                <h6><i class="fas fa-exclamation-triangle"></i> Formato de la Plantilla</h6>
                <p>Columnas requeridas en Excel:</p>
                <div class="kbd-shortcut">
                    <code>nombre | descripcion | precio_venta | precio_compra | categoria | tipo</code><br>
                    <small>Tipo debe ser: "elemento" o "conjunto"</small>
                </div>
            </div>
        </div>
    </div>
</div>

<h2>🏷️ Códigos QR</h2>

<div class="row">
    <div class="col-md-6">
        <div class="feature-card">
            <h5><i class="fas fa-qrcode text-primary"></i> Generación Automática</h5>
            <ul>
                <li>QR creado al guardar producto</li>
                <li>Contiene: ID, código y nombre</li>
                <li>Compatible con escáner móvil</li>
                <li>Descarga individual desde lista</li>
            </ul>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="feature-card">
            <h5><i class="fas fa-download text-primary"></i> Descarga Masiva</h5>
            <ul>
                <li>PDF con todos los QR</li>
                <li>Formato A4 optimizado</li>
                <li>Listos para imprimir</li>
                <li>Etiquetas auto-adhesivas</li>
            </ul>
        </div>
    </div>
</div>

<h2>🔄 Estados de Productos</h2>

<div class="step-card">
    <div class="step-content">
        <h5><i class="fas fa-toggle-on text-primary"></i> Gestión de Estados</h5>
        
        <div class="row">
            <div class="col-md-6">
                <div class="tip-box">
                    <h6><i class="fas fa-eye"></i> Producto Activo</h6>
                    <ul class="mb-0">
                        <li>Visible en inventarios</li>
                        <li>Disponible para ventas</li>
                        <li>Aparece en búsquedas</li>
                        <li>Se puede escanear QR</li>
                    </ul>
                </div>
            </div>
            <div class="col-md-6">
                <div class="info-box">
                    <h6><i class="fas fa-eye-slash"></i> Producto Inactivo</h6>
                    <ul class="mb-0">
                        <li>Oculto en operaciones</li>
                        <li>Mantiene historial de ventas</li>
                        <li>Preserva datos de inventario</li>
                        <li>Puede reactivarse después</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="kbd-shortcut mt-3">
            <strong>Cambiar Estado:</strong> Lista de productos → Toggle <i class="fas fa-toggle-on"></i>/<i class="fas fa-toggle-off"></i> en columna Estado
        </div>
    </div>
</div>

<h2>📈 Análisis y Reportes</h2>

<div class="step-card">
    <div class="step-content">
        <h5><i class="fas fa-chart-bar text-primary"></i> Información Útil del Catálogo</h5>
        
        <div class="row">
            <div class="col-md-4">
                <h6>💰 Análisis de Precios:</h6>
                <ul>
                    <li>Margen de ganancia por producto</li>
                    <li>Comparativa de precios</li>
                    <li>Productos más/menos rentables</li>
                </ul>
            </div>
            <div class="col-md-4">
                <h6>📦 Control de Stock:</h6>
                <ul>
                    <li>Productos con mayor rotación</li>
                    <li>Stock promedio por categoría</li>
                    <li>Alertas de reabastecimiento</li>
                </ul>
            </div>
            <div class="col-md-4">
                <h6>🏷️ Organización:</h6>
                <ul>
                    <li>Productos por categoría</li>
                    <li>Distribución Elementos vs Conjuntos</li>
                    <li>Productos activos vs inactivos</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<h2>💡 Mejores Prácticas</h2>

<div class="row">
    <div class="col-md-6">
        <div class="tip-box">
            <h5><i class="fas fa-thumbs-up"></i> Recomendaciones</h5>
            <ul class="mb-0">
                <li><strong>Nombres claros:</strong> Evita abreviaciones confusas</li>
                <li><strong>Categorías consistentes:</strong> Usa un estándar de nomenclatura</li>
                <li><strong>Precios actualizados:</strong> Revisa periódicamente</li>
                <li><strong>Descripciones útiles:</strong> Incluye especificaciones clave</li>
                <li><strong>QR impresos:</strong> Coloca códigos en productos físicos</li>
            </ul>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="warning-box">
            <h5><i class="fas fa-exclamation-triangle"></i> Evitar</h5>
            <ul class="mb-0">
                <li><strong>Duplicar productos:</strong> Busca antes de crear</li>
                <li><strong>Códigos manuales:</strong> Usa la generación automática</li>
                <li><strong>Precios en cero:</strong> Puede causar errores en ventas</li>
                <li><strong>Eliminar productos:</strong> Mejor desactivar</li>
                <li><strong>Cambiar tipos:</strong> Elemento ↔ Conjunto genera conflictos</li>
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
                <h6>❌ "Código ya existe"</h6>
                <p><strong>Causa:</strong> Intentaste usar un código manual duplicado</p>
                <p><strong>Solución:</strong> Deja el campo código vacío para generación automática</p>
                
                <h6>🔄 "Error al importar Excel"</h6>
                <p><strong>Causa:</strong> Formato incorrecto o datos faltantes</p>
                <p><strong>Solución:</strong> Descarga la plantilla oficial y revisa el reporte de errores</p>
                
                <h6>📱 "QR no se escanea"</h6>
                <p><strong>Causa:</strong> Calidad de impresión o código dañado</p>
                <p><strong>Solución:</strong> Reimprime el QR desde la lista de productos</p>
            </div>
            <div class="col-md-6">
                <h6>💾 "No se guarda el producto"</h6>
                <p><strong>Causa:</strong> Campos obligatorios faltantes</p>
                <p><strong>Solución:</strong> Verifica nombre, precio y tipo estén completos</p>
                
                <h6>🔍 "No aparece en búsquedas"</h6>
                <p><strong>Causa:</strong> Producto inactivo o sin stock</p>
                <p><strong>Solución:</strong> Verifica estado activo y stock en inventarios</p>
                
                <h6>⚖️ "Stock incorrecto en conjuntos"</h6>
                <p><strong>Causa:</strong> Componentes mal configurados</p>
                <p><strong>Solución:</strong> Revisa que todos los elementos componentes existan</p>
            </div>
        </div>
    </div>
</div>

<div class="text-center mt-4">
    <h3>¿Listo para gestionar inventarios?</h3>
    <p>Con un catálogo bien organizado, el resto del sistema funciona como un reloj.</p>
    <div class="btn-group">
        <a href="manual.php?seccion=inventarios" class="btn btn-primary">
            <i class="fas fa-warehouse"></i> Aprender Inventarios
        </a>
        <a href="manual.php?seccion=pos" class="btn btn-outline-primary">
            <i class="fas fa-cash-register"></i> Continuar con POS
        </a>
    </div>
</div>