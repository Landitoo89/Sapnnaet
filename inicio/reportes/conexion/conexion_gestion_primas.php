<?php
// conexion.php - Configuración de conexión a la base de datos
$servidor = 'localhost';      // Servidor de la base de datos
$basedatos = 'rrhh';    // Nombre de tu base de datos
$usuario = 'root'; // Usuario de MySQL
$contraseña = ''; // Contraseña de MySQL

try {
    $conexion = new PDO("mysql:host=$servidor;dbname=$basedatos;charset=utf8", $usuario, $contraseña);
    
    // Configurar atributos de PDO
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conexion->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $conexion->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    
    // Configurar zona horaria si es necesario
    $conexion->exec("SET time_zone = '-04:00';"); // Ajusta según tu ubicación
    
} catch (PDOException $e) {
    // Registrar error en archivo de logs
    error_log('Error de conexión: ' . $e->getMessage());
    
    // Mostrar mensaje seguro en producción
    die(json_encode([
        'error' => 'Error de conexión a la base de datos',
        'dev_message' => (ENVIRONMENT === 'development') ? $e->getMessage() : null
    ]));
}

// Constante para verificar si la conexión está incluida
define('DB_CONNECTED', true);
?>