<?php
require('../../conexion_archivero.php');
//verificarAdministrador();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $nombre = htmlspecialchars(trim($_POST['nombre']));
        $direccion = htmlspecialchars(trim($_POST['direccion']));

        if (empty($nombre)) {
            throw new Exception("El nombre es requerido");
        }

        if ($id > 0) {
            $stmt = $conexion->prepare("UPDATE edificios SET nombre=?, direccion=? WHERE id=?");
            $stmt->bind_param('ssi', $nombre, $direccion, $id);
        } else {
            $stmt = $conexion->prepare("INSERT INTO edificios (nombre, direccion) VALUES (?, ?)");
            $stmt->bind_param('ss', $nombre, $direccion);
        }

        $stmt->execute();
        header('Location: ../index.php?success=1');

    } catch (Exception $e) {
        header("Location: ../forms/edificio_form.php?error=" . urlencode($e->getMessage()));
    }
}