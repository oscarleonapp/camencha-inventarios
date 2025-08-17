# Sistema de Importación Masiva de Productos

## Descripción

El sistema de importación masiva permite al cliente cargar grandes cantidades de productos y conjuntos desde archivos Excel o CSV, automatizando la creación masiva y evitando la entrada manual de datos.

## Características Principales

### ✅ Implementado Completamente
- **Importación desde Excel/CSV** con validación automática
- **Generación automática de códigos** únicos para cada producto
- **Soporte para elementos y conjuntos** con componentes
- **Validación de datos** completa antes de inserción
- **Procesamiento por lotes** con transacciones seguras
- **Reportes detallados** de importación con errores y advertencias
- **Plantilla descargable** con formato correcto y ejemplos
- **Interfaz intuitiva** con drag & drop

## Archivos del Sistema

### Archivos Principales
```
includes/excel_importer.php     # Clase principal de importación
includes/SimpleXLSX.php         # Librería para leer Excel/CSV
importar_productos.php          # Interfaz web de importación
uploads/temp/                   # Directorio para archivos temporales
uploads/temp/.htaccess          # Protección de archivos
```

### Integración en Navbar
- Agregado al menú **Productos → Importar Productos**
- Requiere permiso `productos_crear`

## Formato de Archivo Soportado

### Formatos Aceptados
- **CSV** (.csv) - Recomendado para máxima compatibilidad
- **Excel** (.xlsx, .xls) - Se sugiere conversión a CSV

### Estructura del Archivo

#### Columnas Requeridas
| Columna | Descripción | Valores Permitidos |
|---------|-------------|-------------------|
| `nombre` | Nombre del producto | Texto, obligatorio |
| `tipo` | Tipo de producto | "elemento" o "conjunto" |
| `precio_venta` | Precio de venta | Número decimal positivo |
| `precio_compra` | Precio de compra | Número decimal positivo |

#### Columnas Opcionales
| Columna | Descripción | Ejemplo |
|---------|-------------|---------|
| `descripcion` | Descripción del producto | "Laptop para oficina" |
| `proveedor` | Nombre del proveedor | "Dell Technologies" |
| `componente_1` | Código/nombre del componente 1 | "PROD-2025-00000001" |
| `cantidad_1` | Cantidad del componente 1 | 2 |
| `componente_2` | Código/nombre del componente 2 | "Mouse Inalámbrico" |
| `cantidad_2` | Cantidad del componente 2 | 1 |
| ... | Hasta `componente_10` y `cantidad_10` | |

### Ejemplo de Archivo CSV
```csv
nombre,tipo,descripcion,precio_venta,precio_compra,proveedor,componente_1,cantidad_1,componente_2,cantidad_2
"Laptop Dell Inspiron","elemento","Laptop para oficina",15000.00,12000.00,"Dell Technologies","","","",""
"Mouse Inalámbrico","elemento","Mouse óptico inalámbrico",250.00,180.00,"Logitech","","","",""
"Kit Oficina Básico","conjunto","Kit completo para oficina",15500.00,12500.00,"","PROD-2025-00000001",1,"PROD-2025-00000002",1
```

## Funcionalidades Técnicas

### Clase `ExcelImporter`

#### Métodos Principales

```php
// Procesar archivo Excel/CSV
$resultado = $excelImporter->procesarArchivo($archivo_path, $opciones);

// Generar plantilla de ejemplo
$plantilla = $excelImporter->generarPlantilla();

// Obtener estadísticas
$stats = $excelImporter->obtenerEstadisticas();
```

#### Opciones de Importación
```php
$opciones = [
    'sobrescribir_duplicados' => false,    // Sobrescribir productos duplicados
    'crear_proveedores' => false,          // Crear proveedores automáticamente
    'validar_componentes' => true          // Validar componentes antes de crear conjuntos
];
```

### Validaciones Implementadas

#### Validaciones de Archivo
- ✅ Formato de archivo (CSV, Excel)
- ✅ Tamaño máximo (10MB)
- ✅ Estructura de columnas requeridas
- ✅ Encoding UTF-8 automático

#### Validaciones de Datos
- ✅ Nombres de productos únicos y obligatorios
- ✅ Tipos válidos (elemento/conjunto)
- ✅ Precios numéricos positivos
- ✅ Precio de venta >= precio de compra
- ✅ Proveedores existentes en BD
- ✅ Componentes válidos para conjuntos

#### Validaciones de Negocio
- ✅ Códigos únicos automáticos
- ✅ Conjuntos con al menos un componente
- ✅ Referencias de componentes válidas
- ✅ Transacciones atómicas (todo o nada)

## Proceso de Importación

### Flujo de Trabajo

1. **Subida de Archivo**
   - Validación de formato y tamaño
   - Almacenamiento temporal seguro
   - Verificación de estructura

2. **Procesamiento de Datos**
   - Lectura línea por línea
   - Validación de cada registro
   - Generación de códigos únicos

3. **Creación en Base de Datos**
   - Transacción iniciada
   - Inserción de productos
   - Creación de relaciones de componentes
   - Commit o rollback según errores

4. **Reporte de Resultados**
   - Conteo de procesados/saltados
   - Lista detallada de errores
   - Advertencias y sugerencias
   - Cleanup de archivos temporales

### Manejo de Errores

```php
// Tipos de errores
$errores = [
    'archivo' => 'Problemas con el archivo subido',
    'formato' => 'Estructura incorrecta del Excel',
    'datos' => 'Datos inválidos en filas específicas',
    'negocio' => 'Reglas de negocio no cumplidas'
];

// Tipos de advertencias
$advertencias = [
    'proveedor_no_encontrado' => 'Proveedor no existe',
    'componente_faltante' => 'Componente no encontrado',
    'precio_inconsistente' => 'Precio venta < compra'
];
```

## Interfaz de Usuario

### Página Principal: `importar_productos.php`

#### Características de la UI
- 🎨 **Diseño responsive** con Bootstrap 5
- 📱 **Drag & drop** para archivos
- 📊 **Progreso visual** durante procesamiento
- 📋 **Reportes detallados** con tablas interactivas
- 💡 **Guías contextuales** y tooltips

#### Secciones de la Interfaz

1. **Formulario de Subida**
   - Campo de archivo con validación
   - Opciones de importación (checkboxes)
   - Botón de procesamiento

2. **Guía de Formato**
   - Lista de columnas requeridas/opcionales
   - Ejemplos de formato
   - Botón de descarga de plantilla

3. **Resultados de Importación**
   - Estadísticas generales
   - Lista de errores y advertencias
   - Tabla detallada de productos procesados

4. **Instrucciones para Excel**
   - Pasos para convertir Excel a CSV
   - Tips para evitar problemas de encoding

### Integración con Sistema Existente

#### Permisos Requeridos
- `productos_crear` - Para acceder a la funcionalidad
- `productos_ver` - Para ver resultados

#### Generación Automática de Códigos
```php
// Integración con CodigoGenerator
$tipo_codigo = ($tipo === 'conjunto') ? 'conjunto' : 'producto';
$codigo = $this->codigoGenerator->generarCodigo($tipo_codigo);
```

#### Logging de Actividades
```php
// Log automático de importaciones
getLogger()->info('import_productos', 'productos', 
    "Importación masiva completada", [
        'archivo' => $archivo['name'],
        'procesados' => $resultado['procesados'],
        'saltados' => $resultado['saltados']
    ]
);
```

## Ejemplos de Uso

### Caso 1: Importar Solo Elementos
```csv
nombre,tipo,descripcion,precio_venta,precio_compra,proveedor
"Laptop Dell Inspiron","elemento","Laptop para oficina",15000.00,12000.00,"Dell Technologies"
"Monitor Samsung 24","elemento","Monitor LED 24 pulgadas",2500.00,2000.00,"Samsung"
"Teclado Mecánico","elemento","Teclado mecánico RGB",800.00,600.00,"Corsair"
```

### Caso 2: Importar Elementos y Conjuntos
```csv
nombre,tipo,descripcion,precio_venta,precio_compra,proveedor,componente_1,cantidad_1,componente_2,cantidad_2
"Mouse Óptico","elemento","Mouse con cable USB",150.00,100.00,"Logitech","","","",""
"Alfombrilla","elemento","Alfombrilla para mouse",50.00,30.00,"Genérico","","","",""
"Kit Gamer","conjunto","Kit completo para gaming",800.00,600.00,"","Mouse Óptico",1,"Alfombrilla",1
```

### Caso 3: Importación con Validaciones
```csv
nombre,tipo,descripcion,precio_venta,precio_compra,proveedor,componente_1,cantidad_1
"Producto Inválido","tipo_erroneo","Descripción",100.00,150.00,"Proveedor Inexistente","",""
```
**Resultado:** Error por tipo inválido y precio de venta menor que compra

## Configuración del Servidor

### Requisitos PHP
```ini
# Configuración recomendada en php.ini
upload_max_filesize = 10M
post_max_size = 12M
max_execution_time = 300
memory_limit = 256M
max_input_time = 300
```

### Permisos de Directorios
```bash
# Crear directorio temporal
mkdir -p uploads/temp/
chmod 755 uploads/temp/

# Asegurar permisos de escritura
chown www-data:www-data uploads/temp/ # En Linux
```

### Archivos de Protección
```apache
# uploads/temp/.htaccess
<Files "*">
    Order Deny,Allow
    Deny from all
</Files>

<FilesMatch "\.(csv|xlsx|xls)$">
    Order Allow,Deny
    Allow from all
</FilesMatch>
```

## Troubleshooting

### Problemas Comunes

#### 1. Error "El archivo es demasiado grande"
**Causa:** Límite de PHP excedido  
**Solución:** Aumentar `upload_max_filesize` en php.ini

#### 2. Error "Formato de archivo no válido"
**Causa:** Extensión no permitida  
**Solución:** Convertir a CSV o verificar extensión del archivo

#### 3. Error "Falta el campo requerido"
**Causa:** Columnas requeridas no encontradas  
**Solución:** Usar plantilla descargable como base

#### 4. Errores de encoding (acentos)
**Causa:** Archivo no en UTF-8  
**Solución:** Guardar CSV con encoding UTF-8 en Excel

#### 5. "No se pudo generar código único"
**Causa:** Problemas con base de datos  
**Solución:** Verificar conexión DB y permisos

### Mensajes de Error Técnicos

```php
// Errores de archivo
"Error en la subida del archivo: [código]"
"El archivo es demasiado grande. Máximo 10MB permitido."
"Formato de archivo no válido. Solo se permiten archivos CSV, Excel"

// Errores de estructura
"El archivo debe tener al menos una fila de encabezados y una fila de datos"
"Falta el campo requerido: 'nombre' en los encabezados"

// Errores de datos
"Fila X: El nombre del producto es requerido"
"Fila X: Precio de venta inválido: 'texto'"
"Fila X: Tipo de producto inválido: 'tipo_erroneo'"

// Errores de negocio
"Ya existe un producto con el código 'CODIGO'"
"Un conjunto debe tener al menos un componente válido"
"Proveedor 'NOMBRE' no encontrado"
```

## Rendimiento y Optimización

### Límites Recomendados
- **Máximo por archivo:** 1,000 productos
- **Tamaño archivo:** 10MB
- **Tiempo ejecución:** 5 minutos
- **Memoria:** 256MB

### Optimizaciones Implementadas
- ✅ **Transacciones por lotes** para mejor rendimiento
- ✅ **Validación temprana** para fallar rápido
- ✅ **Cleanup automático** de archivos temporales
- ✅ **Cache de validaciones** para reducir consultas DB
- ✅ **Prepared statements** para seguridad y velocidad

## Roadmap Futuro

### Mejoras Planeadas
- 📊 **Importación de inventarios** junto con productos
- 🔄 **Actualización masiva** de productos existentes
- 📈 **Dashboard de importaciones** con estadísticas históricas
- 🎯 **Validaciones personalizables** por empresa
- 📱 **API REST** para importación programática
- 🔍 **Vista previa** antes de confirmar importación

### Integraciones Futuras
- 📦 **Importación de imágenes** de productos
- 🏷️ **Generación automática de códigos de barras**
- 🔗 **Sincronización con ERPs** externos
- 📋 **Plantillas personalizables** por cliente

---

## Resumen de Implementación

✅ **Sistema 100% Funcional** - Listo para producción  
✅ **Interfaz Completa** - UI/UX optimizada  
✅ **Validaciones Robustas** - Manejo de errores completo  
✅ **Documentación Completa** - Guías para usuario y desarrollador  
✅ **Integración Perfecta** - Compatible con sistema existente  

**El cliente puede ahora importar masivamente productos desde Excel/CSV con códigos automáticos, validaciones completas y reportes detallados.**

---

**Versión:** 1.0  
**Fecha:** Agosto 2025  
**Estado:** ✅ Implementado y Documentado Completamente