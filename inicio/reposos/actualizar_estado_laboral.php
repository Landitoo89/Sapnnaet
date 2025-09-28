<?php
// Ejecutar esta lógica justo después de conectar a la base de datos

try {
    // 1. Marcar como activo si no tiene vacaciones NI reposos vigentes
    $stmt = $conn->prepare("
        UPDATE datos_laborales dl
        SET dl.estado = 'activo'
        WHERE (
            NOT EXISTS (
                SELECT 1 FROM vacaciones v
                WHERE v.id_pers = dl.id_pers
                  AND v.fecha_fin >= CURDATE()
                  AND v.estado IN ('vacaciones', 'pendiente_reposo', 'interrumpida')
            )
            AND NOT EXISTS (
                SELECT 1 FROM reposos r
                WHERE r.id_pers = dl.id_pers
                  AND r.fecha_fin >= CURDATE()
                  AND r.estado = 'activo'
            )
        )
        AND dl.estado IN ('vacaciones','reposo')
    ");
    $stmt->execute();

    // 2. Marcar como vacaciones si tiene vacaciones vigentes y ningún reposo vigente
    $stmt = $conn->prepare("
        UPDATE datos_laborales dl
        SET dl.estado = 'vacaciones'
        WHERE EXISTS (
            SELECT 1 FROM vacaciones v
            WHERE v.id_pers = dl.id_pers
              AND v.fecha_fin >= CURDATE()
              AND v.estado IN ('vacaciones', 'pendiente_reposo')
        )
        AND NOT EXISTS (
            SELECT 1 FROM reposos r
            WHERE r.id_pers = dl.id_pers
              AND r.fecha_fin >= CURDATE()
              AND r.estado = 'activo'
        )
        AND dl.estado <> 'vacaciones'
    ");
    $stmt->execute();

    // 3. Marcar como reposo si tiene reposo vigente (prioridad sobre vacaciones)
    $stmt = $conn->prepare("
        UPDATE datos_laborales dl
        SET dl.estado = 'reposo'
        WHERE EXISTS (
            SELECT 1 FROM reposos r
            WHERE r.id_pers = dl.id_pers
              AND r.fecha_fin >= CURDATE()
              AND r.estado = 'activo'
        )
        AND dl.estado <> 'reposo'
    ");
    $stmt->execute();
} catch (PDOException $e) {
    // Puedes mostrar un mensaje, loguear el error, etc.
}
?>