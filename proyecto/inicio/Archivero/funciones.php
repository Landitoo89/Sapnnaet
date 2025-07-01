<?php

function obtenerPiso($id) {
    global $conexion;
    $stmt = $conexion->prepare("SELECT * FROM pisos WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc() ?? die("Piso no encontrado");
}

function obtenerOficina($id) {
    global $conexion;
    $stmt = $conexion->prepare("SELECT * FROM oficinas WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc() ?? die("Oficina no encontrada");
}

function obtenerEstante($id) {
    global $conexion;
    $stmt = $conexion->prepare("SELECT * FROM estantes WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc() ?? die("Estante no encontrado");
}

function obtenerCajon($id) {
    global $conexion;
    $stmt = $conexion->prepare("SELECT * FROM cajones WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc() ?? die("Cajón no encontrado");
}

?>