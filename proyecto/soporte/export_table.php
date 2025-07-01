<?php
session_start();
date_default_timezone_set('America/Caracas');
require_once __DIR__ . '/conexion/conexion_db.php';

// Solo admin
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'admin') {
    header('Location: index.php');
    exit;
}

if (!isset($_POST['tablas']) || !is_array($_POST['tablas']) || count($_POST['tablas']) === 0) {
    echo '<script>alert("Debes seleccionar al menos una tabla.");window.history.back();</script>'; exit;
}

// Sanitizar nombres de tablas (solo letras, números y guion bajo)
$tablas = array_filter($_POST['tablas'], function($t) {
    return preg_match('/^[a-zA-Z0-9_]+$/', $t);
});
if (count($tablas) === 0) {
    echo '<script>alert("Selección inválida.");window.history.back();</script>'; exit;
}

$fecha = date('Y-m-d_H-i-s');
$mysqldump = 'C:\\laragon\\bin\\mysql\\mysql-8.4.3-winx64\\bin\\mysqldump.exe';
$backup_dir = __DIR__ . '/../BD/';

$files = [];
$errors = [];

foreach ($tablas as $tabla) {
    $backupFile = $backup_dir . "rrhh_{$tabla}_{$fecha}.sql";
    $command = "\"{$mysqldump}\" --user={$usuario} --password={$contraseña} --host={$servidor} {$basedatos} " . escapeshellarg($tabla);

    $output = shell_exec($command);
    file_put_contents($backupFile, $output);
    if (stripos($output, 'CREATE TABLE') !== false) {
        $files[] = $backupFile;
        // Registrar en respaldos_tablas
        $stmt = $conexion->prepare("INSERT INTO respaldos_tablas (tabla, archivo, usuario) VALUES (?, ?, ?)");
        $ruta_relativa = 'BD/' . basename($backupFile);
        $usuario_actual = $_SESSION['usuario']['nombre'] . " " . $_SESSION['usuario']['apellido'];
        $stmt->bind_param("sss", $tabla, $ruta_relativa, $usuario_actual);
        $stmt->execute();
        $stmt->close();
    } else {
        $errors[] = "Error al respaldar la tabla <b>$tabla</b>:<pre>$output</pre>";
        // @unlink($backupFile);
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Respaldo de Tablas</title>
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
        <h2>Respaldo completado</h2>
        <?php if ($files): ?>
            <p>Se generaron los siguientes archivos:</p>
            <ul>
                <?php foreach ($files as $file): ?>
                    <li>
                        <span><?= basename($file) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <?php if ($errors): ?>
            <div class="error-msg">
                <?= implode('<hr>', $errors) ?>
            </div>
        <?php endif; ?>
        <br>
        <a class="btn-back" href="index.php">&larr; Volver</a>
    </div>
</body>
</html>