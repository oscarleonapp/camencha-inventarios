<?php
require_once 'includes/auth.php';

verificarLogin();

echo "<!DOCTYPE html>";
echo "<html><head><title>Test Permisos</title></head><body>";
echo "<h2>Información de Usuario y Permisos</h2>";

echo "<p><strong>Usuario ID:</strong> " . $_SESSION['usuario_id'] . "</p>";
echo "<p><strong>Usuario:</strong> " . $_SESSION['usuario_nombre'] . "</p>";
echo "<p><strong>Email:</strong> " . $_SESSION['usuario_email'] . "</p>";

echo "<h3>Permisos de productos:</h3>";
echo "<ul>";
echo "<li>productos_ver: " . (tienePermiso('productos_ver') ? '✅ SÍ' : '❌ NO') . "</li>";
echo "<li>productos_crear: " . (tienePermiso('productos_crear') ? '✅ SÍ' : '❌ NO') . "</li>";
echo "<li>productos_editar: " . (tienePermiso('productos_editar') ? '✅ SÍ' : '❌ NO') . "</li>";
echo "<li>productos_eliminar: " . (tienePermiso('productos_eliminar') ? '✅ SÍ' : '❌ NO') . "</li>";
echo "</ul>";

echo "</body></html>";
?>