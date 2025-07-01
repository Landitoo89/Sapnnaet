<?php
session_start();
date_default_timezone_set('America/Caracas');
require_once __DIR__ . '/conexion/conexion_db.php';

// Solo admin
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'admin') {
    header('Location: index.php');
    exit;
}

$fecha = date('Y-m-d_H-i-s');
$mysqldump = 'C:\\laragon\\bin\\mysql\\mysql-8.4.3-winx64\\bin\\mysqldump.exe';
$backup_dir = __DIR__ . '/../BD/';
$backupFile = $backup_dir . "rrhh_{$fecha}.sql";

$command = "\"{$mysqldump}\" --user={$usuario} --password={$contraseña} --host={$servidor} {$basedatos}";
$output = shell_exec($command);

file_put_contents($backupFile, $output);

$ok = ($output && (stripos($output, 'Dump completed') !== false || stripos($output, 'CREATE TABLE') !== false));

// Registrar respaldo en la tabla respaldos_tablas si todo salió bien
if ($ok) {
    // Usar 'completa' como nombre de tabla para identificar respaldo total
    $stmt = $conexion->prepare("INSERT INTO respaldos_tablas (tabla, archivo, usuario) VALUES (?, ?, ?)");
    $ruta_relativa = 'BD/' . basename($backupFile); // BD/rrhh_yyyy-mm-dd_hh-mm-ss.sql
    $usuario_actual = $_SESSION['usuario']['nombre'] . " " . $_SESSION['usuario']['apellido'];
    $tabla_respaldo = 'completa';
    $stmt->bind_param("sss", $tabla_respaldo, $ruta_relativa, $usuario_actual);
    $stmt->execute();
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Respaldo de Base de Datos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #10151e; color: #aeeeff; }
        .backup-result { background: #16223a; max-width: 600px; margin: 2.5rem auto; border-radius: 16px; padding: 2rem 2.5rem; box-shadow: 0 4px 44px #00ffae22; }
        h2 { color: #00ffae; }
        ul { list-style: none; padding-left: 0; }
        li { margin-bottom: 0.8em; }
        .btn-download { background: #00ffae; color: #10151e; font-weight: bold; border-radius: 8px; padding: 0.5em 1.2em; margin-right: 0.3em; }
        .btn-download:hover { background: #3498db; color: #fff; }
        .error-msg { background: #350a15; color: #ffb6b6; padding: 1em; border-radius: 10px; margin-top:1.5em; }
        a.btn-back { background: #181f29; color: #00ffae; border: 1.5px solid #00ffae77; border-radius: 8px; padding: 0.5em 1.2em;}
        a.btn-back:hover { background: #00ffae; color: #181f29; }
    </style>
</head>
<body>
    <div class="backup-result">
        <h2>Respaldo completo de la Base de Datos</h2>
        <?php if ($ok): ?>
            <p>El respaldo se generó correctamente:</p>
            <ul>
                <li>
                    <span><?= basename($backupFile) ?></span>
                </li>
            </ul>
        <?php else: ?>
            <div class="error-msg">
                Error al realizar el respaldo.<br>
                <pre><?= htmlspecialchars($output) ?></pre>
            </div>
        <?php endif; ?>
        <br>
        <a class="btn-back" href="index.php">&larr; Volver</a>
    </div>
</body>
</html>