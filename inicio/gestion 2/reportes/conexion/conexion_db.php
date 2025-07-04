<?php
// conexion_db.php
$servidor = 'localhost';      // Servidor de la base de datos
$basedatos = 'rrhh';    // Nombre de tu base de datos
$usuario = 'root'; // Usuario de MySQL
$contraseña = ''; // Contraseña de MySQL
$charset = 'utf8mb4';

$dsn = "mysql:host=$servidor;dbname=$basedatos;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Reportar errores como excepciones
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Obtener resultados como arrays asociativos
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Deshabilitar la emulación de preparaciones
];

// Crear conexión
$conexion = new mysqli($servidor, $usuario, $contraseña, $basedatos);

// Verificar conexión
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

try {
    $conn = new PDO($dsn, $usuario, $contraseña, $options);
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

try {
    $pdo = new PDO("mysql:host=$servidor;dbname=$basedatos;charset=utf8", $usuario, $contraseña);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("No se pudo conectar a la base de datos: " . $e->getMessage());
}
?>

