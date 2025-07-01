<?php
// Datos de conexión
$servidor = "localhost";
$usuario = "root";
$contraseña = "";
$basedatos = "rrhh";

// Crear conexión
$conexion = new mysqli($servidor, $usuario, $contraseña, $basedatos);

// Verificar conexión
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

//echo "Conexión exitosa a la base de datos.";

//function verificarAdministrador() {
//    if (!isset($_SESSION['es_admin']) || !$_SESSION['es_admin']) {
//        header('Location: ../login.php');
//        exit;
//    }
//}

//function validarID($id) {
//    if (!is_numeric($id)) {
//        die("ID inválido");
//    }
//    return (int)$id;
//}

function obtenerEdificio($id) {
    global $conexion;
    $stmt = $conexion->prepare("SELECT * FROM edificios WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc() ?? die("Edificio no encontrado");
}

function mostrarMensajes() {
    if (!empty($_SESSION['mensaje'])) {
        $mensaje = $_SESSION['mensaje'];
        echo '<div class="alert alert-'.$mensaje['tipo'].'">'.$mensaje['texto'].'</div>';
        unset($_SESSION['mensaje']);
    }
}

?>