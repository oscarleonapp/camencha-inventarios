# Sistema de Gestión de Boletas

## Instalación

### 1. Crear la tabla de boletas
Ejecuta el siguiente comando SQL en tu base de datos:
```bash
mysql -u root -p inventario_sistema < crear_tabla_boletas.sql
```

### 2. Agregar permisos
Ejecuta el script de permisos:
```bash
mysql -u root -p inventario_sistema < agregar_permisos_boletas.sql
```

### 3. Verificar permisos de carpeta
Asegúrate de que la carpeta `uploads/boletas/` tenga permisos de escritura:
```bash
chmod 755 uploads/
chmod 755 uploads/boletas/
```

## Funcionalidades Implementadas

### ✅ **Subida de Boletas**
- **Campos obligatorios**: Número de boleta, Fecha, Proveedor, Descripción, Imagen
- **Validaciones**: 
  - Números de boleta únicos
  - Tipos de archivo (JPG, PNG, GIF)
  - Tamaño máximo 5MB
  - Formulario con protección CSRF

### ✅ **Gestión de Imágenes**
- **Almacenamiento seguro** en `uploads/boletas/`
- **Nombres únicos** con timestamp
- **Vista previa** antes de subir
- **Visualización modal** de imágenes
- **Descarga directa** de archivos

### ✅ **Búsqueda y Filtros**
- **Filtros disponibles**:
  - Número de boleta (búsqueda parcial)
  - Proveedor (búsqueda parcial)
  - Rango de fechas (desde/hasta)
- **Resultados en tiempo real**

### ✅ **Seguridad**
- **Protección de archivos** con .htaccess
- **Validación de tipos MIME**
- **Protección CSRF** en formularios
- **Control de permisos** por roles
- **Sanitización** de datos de entrada

### ✅ **Interfaz de Usuario**
- **Diseño responsive** con Bootstrap 5
- **Modales** para subir y ver imágenes
- **Estadísticas** de uso (total boletas, proveedores, espacio)
- **Mensajes** de éxito/error
- **Confirmaciones** para eliminación

## Permisos del Sistema

| Permiso | Descripción | Rol Admin | Rol Encargado |
|---------|-------------|-----------|---------------|
| `boletas_ver` | Ver boletas subidas | ✅ | ✅ |
| `boletas_crear` | Subir nuevas boletas | ✅ | ✅ |
| `boletas_eliminar` | Eliminar boletas | ✅ | ❌ |

## Estructura de Base de Datos

### Tabla: `boletas`
```sql
id                     INT AUTO_INCREMENT PRIMARY KEY
numero_boleta          VARCHAR(50) UNIQUE NOT NULL
fecha                  DATE NOT NULL
proveedor              VARCHAR(200) NOT NULL
descripcion            TEXT NOT NULL
imagen_path            VARCHAR(500) NOT NULL
imagen_nombre_original VARCHAR(255) NOT NULL
imagen_size            INT
usuario_id             INT NOT NULL (FK a usuarios)
created_at             TIMESTAMP DEFAULT CURRENT_TIMESTAMP
updated_at             TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
```

## Ubicación de Archivos

```
/inventario-claude/
├── boletas.php                    # Página principal de gestión
├── uploads/
│   ├── .htaccess                 # Protección de seguridad
│   └── boletas/                  # Imágenes de boletas
│       ├── BOL-2024-001_20240816123045.jpg
│       └── BOL-2024-002_20240816123246.png
├── crear_tabla_boletas.sql       # Script de creación de tabla
└── agregar_permisos_boletas.sql  # Script de permisos
```

## Uso del Sistema

### 1. **Subir Boleta**
- Hacer clic en "Subir Boleta"
- Completar todos los campos obligatorios
- Seleccionar imagen (máx 5MB)
- Ver vista previa automática
- Confirmar subida

### 2. **Buscar Boletas**
- Usar filtros en la parte superior
- Buscar por número parcial o proveedor
- Filtrar por rango de fechas
- Resultados instantáneos

### 3. **Ver Imagen**
- Hacer clic en "Ver" en cualquier boleta
- Imagen se abre en modal de pantalla completa
- Opción de descargar imagen original

### 4. **Eliminar Boleta** (solo admin)
- Hacer clic en ícono de papelera
- Confirmar eliminación (irreversible)
- Se elimina registro y archivo físico

## Consideraciones de Seguridad

1. **Validación de archivos**: Solo imágenes permitidas
2. **Protección de directorio**: .htaccess bloquea scripts
3. **Nombres únicos**: Previene conflictos y sobrescritura
4. **Control de acceso**: Sistema de permisos por roles
5. **Protección CSRF**: Tokens en todos los formularios

## Mantenimiento

- **Limpieza periódica**: Revisar espacio usado en estadísticas
- **Backup de imágenes**: Incluir carpeta `uploads/` en respaldos
- **Logs de errores**: Monitorear errores de subida en logs PHP
- **Permisos de carpeta**: Verificar permisos de escritura regularmente

## Troubleshooting

### Error: "No se puede subir archivo"
- Verificar permisos de carpeta `uploads/boletas/` (755)
- Verificar configuración PHP `upload_max_filesize` y `post_max_size`

### Error: "Número de boleta ya existe"
- Cada número debe ser único en el sistema
- Usar formato como BOL-YYYY-NNN para organización

### Error: "Acceso denegado"
- Verificar que el usuario tenga permisos `boletas_ver`
- Contactar administrador para asignar permisos

## Configuración PHP Recomendada

```ini
upload_max_filesize = 10M
post_max_size = 12M
max_execution_time = 60
memory_limit = 128M
```