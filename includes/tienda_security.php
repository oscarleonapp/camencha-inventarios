<?php
// Funciones de seguridad por tienda

/**
 * Obtiene las tiendas asignadas al usuario actual
 * @param PDO $db - Conexión a base de datos
 * @param int $usuario_id - ID del usuario
 * @return array - Array de IDs de tiendas asignadas
 */
function getTiendasUsuario($db, $usuario_id) {
    $query = "SELECT tienda_id FROM usuario_tiendas WHERE usuario_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$usuario_id]);
    
    $tiendas = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $tiendas[] = $row['tienda_id'];
    }
    
    return $tiendas;
}

/**
 * Verifica si el usuario tiene acceso a una tienda específica
 * @param PDO $db - Conexión a base de datos 
 * @param int $usuario_id - ID del usuario
 * @param int $tienda_id - ID de la tienda
 * @return bool - True si tiene acceso, false si no
 */
function tieneAccesoTienda($db, $usuario_id, $tienda_id) {
    // Los administradores tienen acceso a todas las tiendas
    if (esAdmin()) {
        return true;
    }
    
    $tiendas_usuario = getTiendasUsuario($db, $usuario_id);
    return in_array($tienda_id, $tiendas_usuario);
}

/**
 * Genera cláusula WHERE para filtrar por tiendas del usuario
 * @param PDO $db - Conexión a base de datos
 * @param int $usuario_id - ID del usuario
 * @param string $campo_tienda - Nombre del campo tienda en la consulta (ej: 't.id', 'tienda_id')
 * @return array - ['where' => string, 'params' => array]
 */
function getFiltroTiendas($db, $usuario_id, $campo_tienda = 'tienda_id') {
    // Los administradores ven todas las tiendas
    if (esAdmin()) {
        return ['where' => '', 'params' => []];
    }
    
    $tiendas_usuario = getTiendasUsuario($db, $usuario_id);
    
    if (empty($tiendas_usuario)) {
        // Usuario sin tiendas asignadas - no ve nada
        return ['where' => "$campo_tienda = -1", 'params' => []];
    }
    
    $placeholders = str_repeat('?,', count($tiendas_usuario) - 1) . '?';
    return [
        'where' => "$campo_tienda IN ($placeholders)",
        'params' => $tiendas_usuario
    ];
}


/**
 * Obtiene la tienda principal del usuario (primera asignada como principal)
 * @param PDO $db - Conexión a base de datos
 * @param int $usuario_id - ID del usuario
 * @return int|null - ID de la tienda principal o null si no tiene
 */
function getTiendaPrincipalUsuario($db, $usuario_id) {
    $query = "SELECT tienda_id FROM usuario_tiendas WHERE usuario_id = ? AND es_principal = 1 LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute([$usuario_id]);
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['tienda_id'] : null;
}

/**
 * Valida que una operación sea válida para las tiendas del usuario
 * Lanza excepción si no tiene permisos
 * @param PDO $db - Conexión a base de datos
 * @param int $usuario_id - ID del usuario
 * @param int $tienda_id - ID de la tienda a validar
 * @param string $operacion - Descripción de la operación para el error
 * @throws Exception si no tiene acceso
 */
function validarAccesoTienda($db, $usuario_id, $tienda_id, $operacion = 'esta operación') {
    if (!tieneAccesoTienda($db, $usuario_id, $tienda_id)) {
        throw new Exception("No tienes permisos para realizar $operacion en esta tienda.");
    }
}

/**
 * Obtiene información completa de las tiendas asignadas al usuario
 * @param PDO $db - Conexión a base de datos
 * @param int $usuario_id - ID del usuario
 * @return array - Array con datos completos de las tiendas
 */
function getTiendasUsuarioCompleta($db, $usuario_id) {
    if (esAdmin()) {
        // Admin ve todas las tiendas
        $query = "SELECT * FROM tiendas WHERE activo = 1 ORDER BY nombre";
        $stmt = $db->prepare($query);
        $stmt->execute();
    } else {
        // Usuario normal solo sus tiendas asignadas
        $query = "SELECT t.*, ut.es_principal 
                  FROM tiendas t 
                  INNER JOIN usuario_tiendas ut ON t.id = ut.tienda_id 
                  WHERE ut.usuario_id = ? AND t.activo = 1 
                  ORDER BY ut.es_principal DESC, t.nombre";
        $stmt = $db->prepare($query);
        $stmt->execute([$usuario_id]);
    }
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>