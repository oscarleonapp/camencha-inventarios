<h1><i class="fas fa-question-circle text-primary"></i> Preguntas Frecuentes (FAQ)</h1>

<div class="tip-box">
    <h5><i class="fas fa-lightbulb"></i> Dudas Más Comunes</h5>
    <p>Encuentra respuestas rápidas a las preguntas más frecuentes sobre el uso del Sistema de Inventarios. Si no encuentras lo que buscas, consulta con tu administrador.</p>
</div>

<h2>🏪 Gestión de Tiendas y Permisos</h2>

<div class="row">
    <div class="col-md-6">
        <div class="step-card">
            <h5><i class="fas fa-store text-primary"></i> ¿Cómo sé qué tiendas puedo ver?</h5>
            <p><strong>Respuesta:</strong> El sistema muestra automáticamente solo las tiendas para las que tienes permisos:</p>
            <ul>
                <li><strong>Administrador:</strong> Ve todas las tiendas del sistema</li>
                <li><strong>Encargado:</strong> Solo las tiendas asignadas específicamente</li>
                <li><strong>Usuario básico:</strong> Según permisos individuales configurados</li>
            </ul>
            <div class="info-box">
                <small><i class="fas fa-info-circle"></i> Si necesitas acceso a más tiendas, contacta a tu administrador.</small>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="step-card">
            <h5><i class="fas fa-lock text-primary"></i> ¿Por qué no puedo editar ciertos productos?</h5>
            <p><strong>Respuesta:</strong> Los permisos se configuran por módulo y acción:</p>
            <ul>
                <li><strong>Ver:</strong> Puedes consultar la información</li>
                <li><strong>Crear:</strong> Puedes agregar nuevos productos</li>
                <li><strong>Editar:</strong> Puedes modificar productos existentes</li>
                <li><strong>Eliminar:</strong> Puedes desactivar productos</li>
            </ul>
            <div class="warning-box">
                <small><i class="fas fa-shield-alt"></i> Los permisos varían según tu rol en el sistema.</small>
            </div>
        </div>
    </div>
</div>

<h2>📦 Productos y Códigos</h2>

<div class="step-card">
    <div class="step-content">
        <h5><i class="fas fa-barcode text-primary"></i> ¿Puedo cambiar el código de un producto?</h5>
        <p><strong>Respuesta:</strong> No se recomienda cambiar códigos una vez generados automáticamente.</p>
        
        <div class="row">
            <div class="col-md-6">
                <h6>✅ Códigos Automáticos (Recomendado):</h6>
                <ul>
                    <li>Se generan únicos: <code>2024-EL-001</code></li>
                    <li>No hay conflictos</li>
                    <li>Incluyen el año para trazabilidad</li>
                    <li>Compatible con QR automático</li>
                </ul>
            </div>
            <div class="col-md-6">
                <h6>⚠️ Códigos Manuales (Con cuidado):</h6>
                <ul>
                    <li>Deben ser únicos en el sistema</li>
                    <li>No usar caracteres especiales</li>
                    <li>Mantener formato consistente</li>
                    <li>Verificar que no exista antes</li>
                </ul>
            </div>
        </div>
        
        <div class="tip-box">
            <small><i class="fas fa-magic"></i> <strong>Recomendación:</strong> Deja el campo código vacío para que el sistema genere uno automáticamente.</small>
        </div>
    </div>
</div>

<div class="step-card">
    <div class="step-content">
        <h5><i class="fas fa-cubes text-primary"></i> ¿Cuál es la diferencia entre Elemento y Conjunto?</h5>
        
        <div class="row">
            <div class="col-md-6">
                <div class="feature-card">
                    <h6><i class="fas fa-cube text-success"></i> Elemento</h6>
                    <ul>
                        <li>Producto individual</li>
                        <li>Stock propio independiente</li>
                        <li>Se vende por unidad</li>
                        <li>Ejemplo: Mouse, Teclado, Monitor</li>
                        <li>Código: <code>2024-EL-###</code></li>
                    </ul>
                </div>
            </div>
            <div class="col-md-6">
                <div class="feature-card">
                    <h6><i class="fas fa-cubes text-info"></i> Conjunto</h6>
                    <ul>
                        <li>Producto compuesto</li>
                        <li>Stock calculado por componentes</li>
                        <li>Se vende como paquete</li>
                        <li>Ejemplo: PC Completa, Kit de Oficina</li>
                        <li>Código: <code>2024-CJ-###</code></li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="info-box">
            <h6><i class="fas fa-calculator"></i> Cálculo de Stock en Conjuntos</h6>
            <p>El stock de un conjunto se calcula automáticamente: si tienes 5 monitores, 3 CPUs y 10 teclados, solo puedes armar 3 conjuntos PC completa (limitado por las CPUs).</p>
        </div>
    </div>
</div>

<h2>📊 Inventarios y Stock</h2>

<div class="row">
    <div class="col-md-6">
        <div class="step-card">
            <h5><i class="fas fa-exclamation-triangle text-warning"></i> ¿Por qué aparece stock negativo?</h5>
            <p><strong>Posibles causas:</strong></p>
            <ul>
                <li>Venta procesada antes de actualizar inventario</li>
                <li>Error manual en cantidades</li>
                <li>Productos en reparación no contabilizados</li>
                <li>Traslado entre tiendas mal registrado</li>
            </ul>
            <div class="warning-box">
                <small><i class="fas fa-tools"></i> <strong>Solución:</strong> Corrige manualmente el stock o contacta al administrador.</small>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="step-card">
            <h5><i class="fas fa-sync-alt text-primary"></i> ¿Cómo actualizo el stock rápidamente?</h5>
            <p><strong>Métodos disponibles:</strong></p>
            <ul>
                <li><strong>Manual:</strong> Clic en cantidad → Escribir → Enter</li>
                <li><strong>QR Móvil:</strong> Escanear código → Ingresar cantidad</li>
                <li><strong>Importación:</strong> Excel/CSV masivo</li>
                <li><strong>POS:</strong> Se actualiza automáticamente en ventas</li>
            </ul>
            <div class="tip-box">
                <small><i class="fas fa-mobile-alt"></i> <strong>Más rápido:</strong> Usa el escáner QR desde tu móvil durante conteos físicos.</small>
            </div>
        </div>
    </div>
</div>

<h2>💳 Ventas y POS</h2>

<div class="step-card">
    <div class="step-content">
        <h5><i class="fas fa-shopping-cart text-primary"></i> ¿Por qué no puedo procesar una venta?</h5>
        
        <div class="row">
            <div class="col-md-6">
                <h6>✅ Verificaciones Obligatorias:</h6>
                <ul>
                    <li>Carrito no está vacío</li>
                    <li>Stock suficiente en todos los productos</li>
                    <li>Tienda seleccionada correctamente</li>
                    <li>Productos están activos</li>
                    <li>Conexión a internet estable</li>
                </ul>
            </div>
            <div class="col-md-6">
                <h6>🔒 Permisos Necesarios:</h6>
                <ul>
                    <li>Permiso <code>ventas_crear</code></li>
                    <li>Acceso a la tienda actual</li>
                    <li>Sesión activa y válida</li>
                    <li>Usuario no desactivado</li>
                </ul>
            </div>
        </div>
        
        <div class="info-box">
            <h6><i class="fas fa-lightbulb"></i> Consejo</h6>
            <p>Si continúas teniendo problemas, intenta cambiar a otra tienda y volver a la original, o contacta al administrador.</p>
        </div>
    </div>
</div>

<div class="step-card">
    <div class="step-content">
        <h5><i class="fas fa-undo text-primary"></i> ¿Puedo cancelar o reembolsar una venta?</h5>
        <p><strong>Sí, pero depende de los permisos:</strong></p>
        
        <div class="row">
            <div class="col-md-6">
                <h6>🔄 Proceso de Reembolso:</h6>
                <ol>
                    <li>Ve al historial de ventas</li>
                    <li>Busca la venta específica</li>
                    <li>Haz clic en "Ver Detalles"</li>
                    <li>Usa el botón "Reembolsar"</li>
                    <li>Especifica el motivo</li>
                    <li>Confirma la operación</li>
                </ol>
            </div>
            <div class="col-md-6">
                <h6>⚡ Efectos Automáticos:</h6>
                <ul>
                    <li>Stock se reintegra al inventario</li>
                    <li>Venta cambia a estado "reembolsada"</li>
                    <li>Comisión del vendedor se anula</li>
                    <li>Se registra en logs del sistema</li>
                </ul>
            </div>
        </div>
        
        <div class="warning-box">
            <small><i class="fas fa-exclamation-triangle"></i> <strong>Importante:</strong> Los reembolsos no se pueden deshacer. Verifica cuidadosamente antes de confirmar.</small>
        </div>
    </div>
</div>

<h2>📱 Escáner QR y Móviles</h2>

<div class="row">
    <div class="col-md-6">
        <div class="step-card">
            <h5><i class="fas fa-camera text-primary"></i> ¿Por qué no funciona el escáner QR?</h5>
            <p><strong>Verificaciones comunes:</strong></p>
            <ul>
                <li><strong>Permisos:</strong> Navegador tiene acceso a cámara</li>
                <li><strong>HTTPS:</strong> La URL debe ser segura</li>
                <li><strong>Navegador:</strong> Chrome, Firefox, Safari, Edge</li>
                <li><strong>Iluminación:</strong> QR debe estar bien iluminado</li>
                <li><strong>Enfoque:</strong> Cámara debe enfocar correctamente</li>
            </ul>
            <div class="tip-box">
                <small><i class="fas fa-mobile-alt"></i> En móviles funciona mejor que en escritorio.</small>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="step-card">
            <h5><i class="fas fa-print text-primary"></i> ¿Cómo imprimo códigos QR de calidad?</h5>
            <p><strong>Mejores prácticas:</strong></p>
            <ul>
                <li><strong>Tamaño:</strong> Mínimo 2cm x 2cm</li>
                <li><strong>Resolución:</strong> 300 DPI o superior</li>
                <li><strong>Papel:</strong> Preferiblemente blanco mate</li>
                <li><strong>Tinta:</strong> Negro sólido, sin manchas</li>
                <li><strong>Protección:</strong> Laminado o funda plástica</li>
            </ul>
            <div class="info-box">
                <small><i class="fas fa-download"></i> Descarga PDF masivo desde productos para mejor calidad.</small>
            </div>
        </div>
    </div>
</div>

<h2>🔄 Traslados y Reparaciones</h2>

<div class="step-card">
    <div class="step-content">
        <h5><i class="fas fa-truck text-primary"></i> ¿Puedo cancelar un traslado entre tiendas?</h5>
        <p><strong>No, los traslados son permanentes.</strong> Una vez confirmado:</p>
        
        <div class="row">
            <div class="col-md-6">
                <h6>❌ No se puede:</h6>
                <ul>
                    <li>Cancelar el traslado</li>
                    <li>Deshacer automáticamente</li>
                    <li>Revertir con un botón</li>
                </ul>
            </div>
            <div class="col-md-6">
                <h6>✅ Sí se puede:</h6>
                <ul>
                    <li>Hacer traslado de vuelta manualmente</li>
                    <li>Ver historial en logs</li>
                    <li>Contactar al administrador</li>
                </ul>
            </div>
        </div>
        
        <div class="warning-box">
            <h6><i class="fas fa-exclamation-triangle"></i> Prevención</h6>
            <p>Siempre verifica dos veces: tienda origen, tienda destino, producto y cantidad antes de confirmar cualquier traslado.</p>
        </div>
    </div>
</div>

<div class="step-card">
    <div class="step-content">
        <h5><i class="fas fa-tools text-primary"></i> ¿Cómo funciona el sistema de reparaciones?</h5>
        
        <div class="row">
            <div class="col-md-4">
                <div class="feature-card">
                    <h6><i class="fas fa-arrow-right text-warning"></i> Envío</h6>
                    <ul>
                        <li>Producto sale del inventario</li>
                        <li>Se marca "en reparación"</li>
                        <li>No disponible para ventas</li>
                        <li>Se registra quién envía</li>
                    </ul>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="feature-card">
                    <h6><i class="fas fa-wrench text-info"></i> Proceso</h6>
                    <ul>
                        <li>Estado: "en_reparacion"</li>
                        <li>Seguimiento en tiempo real</li>
                        <li>Costos de reparación</li>
                        <li>Notas y observaciones</li>
                    </ul>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="feature-card">
                    <h6><i class="fas fa-arrow-left text-success"></i> Recepción</h6>
                    <ul>
                        <li>Producto vuelve al stock</li>
                        <li>O se marca como pérdida</li>
                        <li>Se registra el costo final</li>
                        <li>Historial completo</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<h2>📊 Reportes y Exportaciones</h2>

<div class="row">
    <div class="col-md-6">
        <div class="step-card">
            <h5><i class="fas fa-file-excel text-success"></i> ¿Puedo exportar mis datos?</h5>
            <p><strong>Sí, múltiples opciones disponibles:</strong></p>
            <ul>
                <li><strong>Productos:</strong> Lista completa en Excel</li>
                <li><strong>Inventarios:</strong> Stock por tienda</li>
                <li><strong>Ventas:</strong> Histórico con filtros</li>
                <li><strong>Reportes:</strong> Comisiones de vendedores</li>
            </ul>
            <div class="tip-box">
                <small><i class="fas fa-filter"></i> Usa filtros para exportar solo los datos que necesitas.</small>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="step-card">
            <h5><i class="fas fa-chart-line text-primary"></i> ¿Dónde veo las estadísticas?</h5>
            <p><strong>Información disponible en:</strong></p>
            <ul>
                <li><strong>Dashboard:</strong> Métricas del día</li>
                <li><strong>Reportes:</strong> Análisis detallados</li>
                <li><strong>Vendedores:</strong> Comisiones y performance</li>
                <li><strong>Inventarios:</strong> Alertas de stock</li>
            </ul>
            <div class="info-box">
                <small><i class="fas fa-clock"></i> Los datos se actualizan en tiempo real.</small>
            </div>
        </div>
    </div>
</div>

<h2>🔐 Seguridad y Cuentas</h2>

<div class="step-card">
    <div class="step-content">
        <h5><i class="fas fa-key text-primary"></i> ¿Cómo cambio mi contraseña?</h5>
        <p><strong>Proceso según el sistema:</strong></p>
        
        <div class="row">
            <div class="col-md-6">
                <h6>👤 Autogestión (si está habilitada):</h6>
                <ol>
                    <li>Menú usuario → Perfil</li>
                    <li>Sección "Cambiar Contraseña"</li>
                    <li>Contraseña actual</li>
                    <li>Nueva contraseña (2 veces)</li>
                    <li>Guardar cambios</li>
                </ol>
            </div>
            <div class="col-md-6">
                <h6>🛡️ Por Administrador:</h6>
                <ol>
                    <li>Contacta al administrador</li>
                    <li>Solicita cambio de contraseña</li>
                    <li>Proporciona identificación</li>
                    <li>Recibe nueva contraseña temporal</li>
                    <li>Cámbiala en el primer acceso</li>
                </ol>
            </div>
        </div>
        
        <div class="warning-box">
            <h6><i class="fas fa-shield-alt"></i> Buenas Prácticas de Seguridad</h6>
            <ul class="mb-0">
                <li>Usa contraseñas fuertes (mayús, minus, números, símbolos)</li>
                <li>No compartas tu contraseña con nadie</li>
                <li>Cierra sesión al terminar</li>
                <li>No uses la misma contraseña en otros sitios</li>
            </ul>
        </div>
    </div>
</div>

<h2>❓ ¿No encuentras tu respuesta?</h2>

<div class="row">
    <div class="col-md-4">
        <div class="feature-card text-center">
            <div class="feature-icon">
                <i class="fas fa-book-open"></i>
            </div>
            <h5>Consultar Manual</h5>
            <p>Revisa las guías detalladas paso a paso de cada módulo.</p>
            <a href="manual.php" class="btn btn-primary btn-sm">
                <i class="fas fa-arrow-right"></i> Ver Manual
            </a>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="feature-card text-center">
            <div class="feature-icon">
                <i class="fas fa-user-shield"></i>
            </div>
            <h5>Contactar Administrador</h5>
            <p>Para problemas de permisos, accesos o configuración del sistema.</p>
            <div class="info-box">
                <small>Consulta internamente</small>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="feature-card text-center">
            <div class="feature-icon">
                <i class="fas fa-life-ring"></i>
            </div>
            <h5>Soporte Técnico</h5>
            <p>Para problemas técnicos, errores del sistema o funcionalidades.</p>
            <div class="info-box">
                <small>Documentar el problema con detalles</small>
            </div>
        </div>
    </div>
</div>

<h2>🏆 Tips para Usuarios Expertos</h2>

<div class="row">
    <div class="col-md-6">
        <div class="tip-box">
            <h5><i class="fas fa-keyboard"></i> Atajos de Teclado</h5>
            <ul class="mb-0">
                <li><kbd>F1</kbd> - Enfocar búsqueda en POS</li>
                <li><kbd>F2</kbd> - Procesar venta rápida</li>
                <li><kbd>F3</kbd> - Abrir escáner QR</li>
                <li><kbd>Ctrl + F</kbd> - Buscar en página</li>
                <li><kbd>Esc</kbd> - Cerrar modales</li>
            </ul>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="tip-box">
            <h5><i class="fas fa-rocket"></i> Flujos Eficientes</h5>
            <ul class="mb-0">
                <li>Usa QR para inventarios físicos</li>
                <li>Importa productos masivamente con Excel</li>
                <li>Configura vendedores para comisiones automáticas</li>
                <li>Exporta reportes para análisis externos</li>
                <li>Aprovecha los filtros en todas las listas</li>
            </ul>
        </div>
    </div>
</div>

<div class="text-center mt-4">
    <h3>¿Todo claro?</h3>
    <p>Con estas respuestas deberías poder usar el sistema sin problemas. ¡A trabajar!</p>
    <div class="btn-group">
        <a href="index.php" class="btn btn-primary">
            <i class="fas fa-home"></i> Ir al Dashboard
        </a>
        <a href="manual.php" class="btn btn-outline-primary">
            <i class="fas fa-book"></i> Ver Manual Completo
        </a>
    </div>
</div>