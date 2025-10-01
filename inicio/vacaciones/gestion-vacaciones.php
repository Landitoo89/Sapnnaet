<?php
session_start();
require '../conexion.php';
require $_SERVER['DOCUMENT_ROOT']."/proyecto/inicio/sidebar.php";

// ACTUALIZAR AUTOMÁTICAMENTE ESTADO LABORAL SEGÚN VACACIONES Y REPOSOS VIGENTES
try {
    // 1. Poner activo si NO existen vacaciones NI reposos vigentes
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

    // 2. Poner vacaciones si tiene vacaciones vigentes y ningún reposo vigente
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

    // 3. Poner reposo si tiene reposo vigente (prioridad sobre vacaciones)
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

// Configuración de paginación
$registros_por_pagina = 10;
$pagina_actual = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
$offset = ($pagina_actual - 1) * $registros_por_pagina;

try {
    // Total solo de vacaciones originales
    $stmt_total = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM vacaciones v
        WHERE v.vacacion_original_id IS NULL
    ");
    $stmt_total->execute();
    $total_registros = $stmt_total->fetch(PDO::FETCH_ASSOC)['total'];
    $total_paginas = max(1, ceil($total_registros / $registros_por_pagina));

    // Traer vacaciones originales, fragmentos, reposo y observaciones
    $stmt = $conn->prepare("
        SELECT 
            v1.id_vacaciones,
            v1.id_pers,
            v1.fecha_inicio AS fecha_inicio1,
            v1.fecha_fin AS fecha_fin1,
            v1.estado AS estado1,
            v2.fecha_inicio AS fecha_inicio2,
            v2.fecha_fin AS fecha_fin2,
            v2.estado AS estado2,
            r.id_reposo,
            r.fecha_inicio AS fecha_reposo_inicio,
            r.fecha_fin AS fecha_reposo_fin,
            r.motivo_reposo,
            r.observaciones,
            p.nombres,
            p.apellidos,
            d.nombre AS departamento,
            c.nombre AS cargo,
            dl.fecha_ingreso,
            dl.estado AS estado_laboral
        FROM vacaciones v1
        INNER JOIN datos_personales p ON v1.id_pers = p.id_pers
        INNER JOIN datos_laborales dl ON v1.id_pers = dl.id_pers
        LEFT JOIN departamentos d ON dl.id_departamento = d.id_departamento
        LEFT JOIN cargos c ON dl.id_cargo = c.id_cargo
        LEFT JOIN vacaciones v2 ON v2.vacacion_original_id = v1.id_vacaciones
        LEFT JOIN reposos r ON r.vacacion_interrumpida_id = v1.id_vacaciones
        WHERE v1.vacacion_original_id IS NULL
        ORDER BY v1.fecha_inicio DESC
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindValue(':limit', $registros_por_pagina, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $vacaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error de base de datos: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Vacaciones</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="css/styles.css" rel="stylesheet">
    <style>
        .vacation-header {
            background: linear-gradient(135deg, #2c3e50, #3498db);
            color: white;
            border-radius: 15px;
        }
        .status-badge {
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 500;
            transition: transform 0.2s;
        }
        .badge-activo { background: #d4edda; color: #155724; border: 2px solid #155724;}
        .badge-inactivo { background: #f8d7da; color: #721c24; border: 2px solid #721c24;}
        .badge-vacaciones { background: #fff3cd; color: #856404; border: 2px solid #856404;}
        .badge-reposo { background: #d1ecf1; color: #0c5460; border: 2px solid #0c5460;}
        .badge-interrumpida { background: #ffe6e6; color: #b10000; border: 2px solid #b10000;}
        .badge-pendiente_reposo { background: #e6f1ff; color: #084b9a; border: 2px solid #084b9a;}
        .badge-unificada { background: #d1e7dd; color: #19562d; border: 2px solid #19562d; }
        .badge-simple { background: #fff3cd; color: #856404; border: 2px solid #856404;}
        .info-btn {
            background: none;
            border: none;
            color: #0d6efd;
            font-size: 1.2em;
            padding: 0;
            margin-left: 5px;
        }
        .info-btn:hover { color: #0a58ca; }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="vacation-header p-4 mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="mb-0">
                    <i class="bi bi-palm-tree-fill me-3"></i> Gestión de Vacaciones
                </h1>
                <div>
                    <a href="registrar-vacacion.php" class="btn btn-light btn-lg me-2">
                        <i class="bi bi-plus-circle me-2"></i>Nueva Vacación
                    </a>
                    <a href="gestion-periodos.php" class="btn btn-outline-light btn-lg">
                        <i class="bi bi-calendar-range me-2"></i>Gestión de Períodos
                    </a>
                </div>
            </div>
        </div>

        <?php if(isset($_SESSION['mensaje'])): ?>
        <div class="alert alert-<?= $_SESSION['mensaje']['tipo'] ?> alert-dismissible fade show" role="alert">
            <div class="d-flex align-items-center">
                <i class="bi <?= $_SESSION['mensaje']['tipo'] == 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' ?> me-2"></i>
                <div>
                    <h5 class="mb-0"><?= $_SESSION['mensaje']['titulo'] ?></h5>
                    <p class="mb-0"><?= $_SESSION['mensaje']['contenido'] ?></p>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['mensaje']); ?>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Empleado</th>
                        <th>Departamento</th>
                        <th>Cargo</th>
                        <th>Período de Vacaciones</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
    <?php if(count($vacaciones) > 0): ?>
        <?php foreach($vacaciones as $vacacion): ?>
        <tr>
            <td><?= htmlspecialchars($vacacion['nombres'] . ' ' . $vacacion['apellidos']) ?></td>
            <td><?= htmlspecialchars($vacacion['departamento'] ?? 'N/A') ?></td>
            <td><?= htmlspecialchars($vacacion['cargo'] ?? 'N/A') ?></td>
            <td>
                <?php
                // Rango total de vacaciones (inicio hasta fin de fragmento reanudado si existe, si no hasta fin1)
                $fecha_fin_total = $vacacion['fecha_fin2'] ?: $vacacion['fecha_fin1'];
                echo "<b>Del:</b> " . date('d/m/Y', strtotime($vacacion['fecha_inicio1'])) . " al " . date('d/m/Y', strtotime($fecha_fin_total));
                // Si hay reposo, muestra botón de info
                if ($vacacion['fecha_reposo_inicio']) {
                    echo '<button class="info-btn" data-bs-toggle="modal" data-bs-target="#reposoModal'.$vacacion['id_vacaciones'].'" title="Ver detalle de interrupción"><i class="bi bi-info-circle"></i></button>';
                }
                ?>
            </td>
            <td>
                <?php
                // INICIO DEL CAMBIO DE VISUALIZACIÓN DE ESTADO
                $hoy = date('Y-m-d');
                $fecha_fin_total = $vacacion['fecha_fin2'] ?: $vacacion['fecha_fin1'];

                // Si la vacación fue interrumpida por reposo
                if ($vacacion['fecha_reposo_inicio']) {
                    // Si el reposo ya culminó
                    if ($vacacion['fecha_reposo_fin'] < $hoy) {
                        echo "<span class='status-badge badge-reposo'>Reposo Culminado</span>";
                    } else {
                        echo "<span class='status-badge badge-unificada'>
                                Vacaciones (Interrumpidas por Reposo)
                              </span>";
                    }
                }
                // Si la vacación ya culminó y NO fue interrumpida
                elseif ($fecha_fin_total < $hoy) {
                    echo "<span class='status-badge badge-activo'>Vacaciones Culminadas</span>";
                }
                // Estados restantes
                elseif ($vacacion['estado1'] == 'interrumpida') {
                    echo "<span class='status-badge badge-interrumpida'>Interrumpida</span>";
                }
                elseif ($vacacion['estado1'] == 'vacaciones') {
                    echo "<span class='status-badge badge-vacaciones'>Vacaciones</span>";
                }
                elseif ($vacacion['estado1'] == 'pendiente_reposo') {
                    echo "<span class='status-badge badge-pendiente_reposo'>Por Reanudar</span>";
                }
                else {
                    echo "<span class='status-badge badge-simple'>".ucfirst($vacacion['estado1'])."</span>";
                }
                // FIN DEL CAMBIO DE VISUALIZACIÓN DE ESTADO
                ?>
            </td>
            <td>
                <div class="d-flex gap-2">
                    <form method="GET" action="generar-reporte-vacacion.php" target="_blank">
                        <input type="hidden" name="id_vacacion" value="<?= $vacacion['id_vacaciones'] ?>">
                        <button type="submit" class="btn btn-sm btn-info" title="Generar PDF">
                            <i class="bi bi-file-earmark-pdf"></i>
                        </button>
                    </form>
                    <a href="#" class="btn btn-danger btn-sm action-btn delete-btn" 
                       data-bs-toggle="modal" 
                       data-bs-target="#confirmModal"
                       data-url="eliminar-vacacion.php?id=<?= $vacacion['id_vacaciones'] ?>&pagina=<?= $pagina_actual ?>">
                        <i class="bi bi-trash3-fill"></i>
                    </a>
                </div>
            </td>
        </tr>
        <?php if ($vacacion['fecha_reposo_inicio']): ?>
        <!-- Modal de observaciones/reposo -->
        <div class="modal fade" id="reposoModal<?= $vacacion['id_vacaciones'] ?>" tabindex="-1" aria-labelledby="reposoModalLabel<?= $vacacion['id_vacaciones'] ?>" aria-hidden="true">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header bg-info">
                <h5 class="modal-title" id="reposoModalLabel<?= $vacacion['id_vacaciones'] ?>">
                    <i class="bi bi-activity"></i> Detalle de Interrupción por Reposo
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
              <div class="modal-body">
                <p><b>Período de Reposo:</b><br>
                   <?= date('d/m/Y', strtotime($vacacion['fecha_reposo_inicio'])) ?> al <?= date('d/m/Y', strtotime($vacacion['fecha_reposo_fin'])) ?>
                </p>
                <p>
                  <b>Motivo:</b><br>
                  <?= htmlspecialchars($vacacion['motivo_reposo']) ?>
                </p>
                <?php if (!empty($vacacion['observaciones'])): ?>
                <hr>
                <p><b>Observaciones:</b><br>
                  <?= htmlspecialchars($vacacion['observaciones']) ?>
                </p>
                <?php endif; ?>
                <?php if ($vacacion['fecha_inicio2'] && $vacacion['fecha_fin2']): ?>
                <hr>
                <p>
                  <b>Vacaciones reanudadas:</b><br>
                  <?= date('d/m/Y', strtotime($vacacion['fecha_inicio2'])) ?> al <?= date('d/m/Y', strtotime($vacacion['fecha_fin2'])) ?>
                </p>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
        <?php endif; ?>
        <?php endforeach; ?>
    <?php else: ?>
        <tr>
            <td colspan="6" class="text-center py-4">
                <i class="bi bi-calendar-x-fill display-4 text-muted mb-3"></i>
                <h4 class="text-muted">No hay registros de vacaciones</h4>
            </td>
        </tr>
    <?php endif; ?>
    </tbody>
</table>
        </div>

        <?php if($total_paginas > 1): ?>
        <nav aria-label="Page navigation" class="mt-4">
            <ul class="pagination justify-content-center">
                <li class="page-item <?= $pagina_actual == 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="?pagina=<?= $pagina_actual - 1 ?>">
                        <i class="bi bi-chevron-left"></i>
                    </a>
                </li>
                <?php 
                $max_paginas_visibles = 5;
                $inicio = max(1, $pagina_actual - floor($max_paginas_visibles / 2));
                $fin = min($total_paginas, $inicio + $max_paginas_visibles - 1);
                for($i = $inicio; $i <= $fin; $i++): ?>
                    <li class="page-item <?= $i == $pagina_actual ? 'active' : '' ?>">
                        <a class="page-link" href="?pagina=<?= $i ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= $pagina_actual == $total_paginas ? 'disabled' : '' ?>">
                    <a class="page-link" href="?pagina=<?= $pagina_actual + 1 ?>">
                        <i class="bi bi-chevron-right"></i>
                    </a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>

        <div class="modal fade" id="confirmModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">
                            <i class="bi bi-exclamation-octagon-fill me-2"></i>
                            Confirmar Eliminación
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-center py-4">
                        <i class="bi bi-trash3-fill display-4 text-danger mb-3"></i>
                        <h4 class="mb-3">¿Eliminar registro de vacaciones?</h4>
                        <p class="text-muted">Esta acción reactivará al empleado y no se puede deshacer</p>
                    </div>
                    <div class="modal-footer justify-content-center">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle me-2"></i>Cancelar
                        </button>
                        <a id="deleteButton" href="#" class="btn btn-danger">
                            <i class="bi bi-trash3-fill me-2"></i>Confirmar
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Manejo de eliminación
        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.getElementById('deleteButton').href = this.dataset.url;
            });
        });

        // Efectos hover
        document.querySelectorAll('.status-badge, .action-btn').forEach(element => {
            element.addEventListener('mouseenter', function() {
                this.style.transform = 'scale(1.05)';
            });
            element.addEventListener('mouseleave', function() {
                this.style.transform = 'scale(1)';
            });
        });
    </script>
</body>
</html>