<?php
// conexion.php - Configuración de conexión a la base de datos
$host = 'localhost';      // Servidor de la base de datos
$dbname = 'buscador_archivos';    // Nombre de tu base de datos
$username = 'root'; // Usuario de MySQL
$password = ''; // Contraseña de MySQL

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    
    // Configurar atributos de PDO
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    
    // Configurar zona horaria si es necesario
    $pdo->exec("SET time_zone = '-04:00';"); // Ajusta según tu ubicación
    
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