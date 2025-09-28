<?php
session_start();
require '../conexion.php';
require_once 'actualizar_estado_laboral.php';
require $_SERVER['DOCUMENT_ROOT']."/proyecto/inicio/sidebar.php";

// ACTUALIZAR AUTOMÁTICAMENTE REPOSOS CULMINADOS AL INGRESAR
try {
    // 1. Reposos activos cuya fecha de fin ya pasó: poner al empleado en "activo" y al reposo en "cumplido"
    $update_reposo = $conn->prepare("
        UPDATE datos_laborales dl
        INNER JOIN reposos r ON dl.id_pers = r.id_pers
        SET dl.estado = 'activo', r.estado = 'cumplido'
        WHERE r.estado = 'activo'
          AND r.fecha_fin < CURDATE()
    ");
    $update_reposo->execute();
} catch (PDOException $e) {
    // Puedes mostrar un mensaje, pero no es necesario si solo actualizas
}

$rol = $_SESSION['usuario']['rol'] ?? '';

// Acciones POST para eliminar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_reposo'])) {
    $id_reposo = $_POST['id_reposo'];
    $id_pers = $_POST['id_pers'];

    try {
        $conn->beginTransaction();

        $stmt_get_reposo_data = $conn->prepare("SELECT ruta_archivo_adjunto, vacacion_interrumpida_id FROM reposos WHERE id_reposo = ?");
        $stmt_get_reposo_data->execute([$id_reposo]);
        $reposo_data = $stmt_get_reposo_data->fetch(PDO::FETCH_ASSOC);

        if ($reposo_data && !empty($reposo_data['ruta_archivo_adjunto']) && file_exists($reposo_data['ruta_archivo_adjunto'])) {
            unlink($reposo_data['ruta_archivo_adjunto']);
        }

        $stmt_delete_reposo = $conn->prepare("DELETE FROM reposos WHERE id_reposo = ?");
        $stmt_delete_reposo->execute([$id_reposo]);

        if ($reposo_data && !empty($reposo_data['vacacion_interrumpida_id'])) {
            $vacacion_original_id = $reposo_data['vacacion_interrumpida_id'];

            $stmt_vac_pendiente = $conn->prepare("
                SELECT id_vacaciones, fecha_inicio, fecha_fin
                FROM vacaciones
                WHERE vacacion_original_id = ? AND estado = 'pendiente_reposo'
                ORDER BY fecha_inicio ASC
                LIMIT 1
            ");
            $stmt_vac_pendiente->execute([$vacacion_original_id]);
            $vacacion_pendiente = $stmt_vac_pendiente->fetch(PDO::FETCH_ASSOC);

            if ($vacacion_pendiente) {
                $stmt_update_vacacion_pendiente = $conn->prepare("
                    UPDATE vacaciones
                    SET estado = 'vacaciones'
                    WHERE id_vacaciones = ?
                ");
                $stmt_update_vacacion_pendiente->execute([$vacacion_pendiente['id_vacaciones']]);
                $stmt_update_estado_laboral = $conn->prepare("UPDATE datos_laborales SET estado = 'vacaciones' WHERE id_pers = ?");
                $stmt_update_estado_laboral->execute([$id_pers]);
                $_SESSION['mensaje'] = [
                    'titulo' => '¡Éxito!',
                    'contenido' => 'Reposo eliminado y vacación reanudada correctamente.',
                    'tipo' => 'success'
                ];
            } else {
                $stmt_update_estado_laboral = $conn->prepare("UPDATE datos_laborales SET estado = 'activo' WHERE id_pers = ?");
                $stmt_update_estado_laboral->execute([$id_pers]);
                $_SESSION['mensaje'] = [
                    'titulo' => '¡Éxito!',
                    'contenido' => 'Reposo eliminado y empleado reactivado correctamente.',
                    'tipo' => 'success'
                ];
            }
        } else {
            $stmt_update_estado_laboral = $conn->prepare("UPDATE datos_laborales SET estado = 'activo' WHERE id_pers = ?");
            $stmt_update_estado_laboral->execute([$id_pers]);
            $_SESSION['mensaje'] = [
                'titulo' => '¡Éxito!',
                'contenido' => 'Reposo eliminado y empleado reactivado correctamente.',
                'tipo' => 'success'
            ];
        }

        $conn->commit();

    } catch (PDOException $e) {
        $conn->rollBack();
        $_SESSION['mensaje'] = [
            'titulo' => 'Error',
            'contenido' => 'Error al eliminar el reposo: ' . $e->getMessage(),
            'tipo' => 'danger'
        ];
    }
    header("Location: gestion-reposos.php");
    exit;
}

// Configuración de paginación
$registros_por_pagina = 10;
$pagina_actual = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
$offset = ($pagina_actual - 1) * $registros_por_pagina;

try {
    $stmt_total = $conn->prepare("
        SELECT COUNT(*) AS total 
        FROM reposos r
        INNER JOIN datos_laborales dl ON r.id_pers = dl.id_pers
    ");
    $stmt_total->execute();
    $total_registros = $stmt_total->fetch(PDO::FETCH_ASSOC)['total'];
    $total_paginas = max(1, ceil($total_registros / $registros_por_pagina));

    $stmt = $conn->prepare("
        SELECT 
            r.id_reposo,
            r.id_pers,
            r.tipo_concesion,
            r.motivo_reposo,
            r.dias_otorgados,
            r.fecha_inicio,
            r.fecha_fin,
            r.estado AS estado_reposo,
            r.observaciones,
            r.ruta_archivo_adjunto,
            r.vacacion_interrumpida_id,
            p.nombres,
            p.apellidos,
            c.nombre AS cargo_nombre,
            dl.estado AS estado_laboral
        FROM reposos r
        INNER JOIN datos_personales p ON r.id_pers = p.id_pers
        INNER JOIN datos_laborales dl ON r.id_pers = dl.id_pers
        LEFT JOIN cargos c ON dl.id_cargo = c.id_cargo
        ORDER BY r.fecha_inicio DESC
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindValue(':limit', $registros_por_pagina, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $reposos = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error de base de datos: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Reposos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .reposo-header {
            background: linear-gradient(135deg, #4CAF50, #8bc34a);
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
        .badge-cumplido { background: #f8d7da; color: #721c24; border: 2px solid #721c24;}
        .badge-pendiente { background: #fff3cd; color: #664d03; border: 2px solid #664d03;}
        .badge-simple { background: #e7eaf6; color: #22223b; border: 2px solid #22223b;}
        .table thead.table-dark th { background: #2c3e50; color: #fff; }
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
        <div class="reposo-header p-4 mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="mb-0">
                    <i class="bi bi-person-hearts me-3"></i> Gestión de Reposos
                </h1>
                <a href="registrar-reposo.php" class="btn btn-light btn-lg">
                    <i class="bi bi-plus-circle me-2"></i>Nuevo Reposo
                </a>
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
                        <th>Cargo</th>
                        <th>Tipo</th>
                        <th>Motivo</th>
                        <th>Fecha Inicio</th>
                        <th>Fecha Fin</th>
                        <th>Días</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                  <?php if(count($reposos) > 0): ?>
                    <?php foreach($reposos as $reposo): ?>
                    <tr>
                      <td><?= htmlspecialchars($reposo['nombres'] . ' ' . $reposo['apellidos']) ?></td>
                      <td><?= htmlspecialchars($reposo['cargo_nombre'] ?? 'N/A') ?></td>
                      <td><?= ucfirst(htmlspecialchars($reposo['tipo_concesion'])) ?></td>
                      <td>
                        <?= htmlspecialchars($reposo['motivo_reposo']) ?>
                        <?php if (!empty($reposo['observaciones'])): ?>
                        <button class="info-btn" data-bs-toggle="modal" data-bs-target="#obsModal<?= $reposo['id_reposo'] ?>" title="Ver observaciones"><i class="bi bi-info-circle"></i></button>
                        <?php endif; ?>
                      </td>
                      <td><?= date('d/m/Y', strtotime($reposo['fecha_inicio'])) ?></td>
                      <td><?= date('d/m/Y', strtotime($reposo['fecha_fin'])) ?></td>
                      <td><?= htmlspecialchars($reposo['dias_otorgados']) ?></td>
                      <td>
                        <?php
                          $estado_display = '';
                          $badge_class = '';
                          if ($reposo['estado_reposo'] === 'activo') {
                              $estado_display = 'Activo';
                              $badge_class = 'badge-activo';
                          } elseif ($reposo['estado_reposo'] === 'cumplido') {
                              $estado_display = 'Cumplido';
                              $badge_class = 'badge-cumplido';
                          } elseif ($reposo['estado_reposo'] === 'pendiente') {
                              $estado_display = 'Pendiente';
                              $badge_class = 'badge-pendiente';
                          } else {
                              $estado_display = ucfirst($reposo['estado_reposo']);
                              $badge_class = 'badge-simple';
                          }
                        ?>
                        <span class="status-badge <?= $badge_class ?>"><?= $estado_display ?></span>
                      </td>
                      <td>
                        <div class="d-flex gap-2">
                          <form method="GET" action="generar-reporte-reposo.php" target="_blank">
                            <input type="hidden" name="id_reposo" value="<?= $reposo['id_reposo'] ?>">
                            <button type="submit" class="btn btn-sm btn-info" title="Generar PDF">
                              <i class="bi bi-file-earmark-pdf"></i>
                            </button>
                          </form>
                          <?php if (!empty($reposo['ruta_archivo_adjunto'])): ?>
                            <a href="<?= htmlspecialchars($reposo['ruta_archivo_adjunto']) ?>" target="_blank" class="btn btn-sm btn-secondary" title="Ver archivo adjunto">
                              <i class="bi bi-paperclip"></i>
                            </a>
                          <?php endif; ?>
                          <a href="#" class="btn btn-danger btn-sm action-btn delete-btn"
                             data-bs-toggle="modal"
                             data-bs-target="#confirmModal"
                             data-id-reposo="<?= $reposo['id_reposo'] ?>"
                             data-id-pers="<?= $reposo['id_pers'] ?>"
                             data-nombre-empleado="<?= htmlspecialchars($reposo['nombres'] . ' ' . $reposo['apellidos']) ?>">
                              <i class="bi bi-trash3-fill"></i>
                          </a>
                        </div>
                      </td>
                    </tr>
                    <?php if (!empty($reposo['observaciones'])): ?>
                    <!-- Modal de Observaciones -->
                    <div class="modal fade" id="obsModal<?= $reposo['id_reposo'] ?>" tabindex="-1" aria-labelledby="obsModalLabel<?= $reposo['id_reposo'] ?>" aria-hidden="true">
                        <div class="modal-dialog">
                          <div class="modal-content">
                            <div class="modal-header bg-info">
                              <h5 class="modal-title" id="obsModalLabel<?= $reposo['id_reposo'] ?>">
                                  <i class="bi bi-activity"></i> Observaciones del Reposo
                              </h5>
                              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                              <p><?= nl2br(htmlspecialchars($reposo['observaciones'])) ?></p>
                            </div>
                          </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <tr>
                      <td colspan="9" class="text-center py-4">
                        <i class="bi bi-calendar-x-fill display-4 text-muted mb-3"></i>
                        <h4 class="text-muted">No hay registros de reposos</h4>
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

        <!-- Modal de Confirmación de Eliminación -->
        <div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title" id="confirmModalLabel">
                            <i class="bi bi-exclamation-octagon-fill me-2"></i>
                            Confirmar Eliminación de Reposo
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body text-center py-4">
                        <i class="bi bi-trash3-fill display-4 text-danger mb-3"></i>
                        <h4 class="mb-3">¿Está seguro de eliminar el reposo de <span id="nombreEmpleadoEliminar" class="fw-bold"></span>?</h4>
                        <p class="text-muted">Esta acción eliminará el registro de reposo y reactivará el estado laboral del empleado.</p>
                    </div>
                    <div class="modal-footer justify-content-center">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle me-2"></i>Cancelar
                        </button>
                        <form id="deleteReposoForm" method="POST" action="">
                            <input type="hidden" name="eliminar_reposo" value="1">
                            <input type="hidden" name="id_reposo" id="idReposoEliminar">
                            <input type="hidden" name="id_pers" id="idPersEliminar">
                            <button type="submit" class="btn btn-danger">
                                <i class="bi bi-trash3-fill me-2"></i>Confirmar Eliminación
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Modal eliminación: pasar datos al modal
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function() {
                const idReposo = this.dataset.idReposo;
                const idPers = this.dataset.idPers;
                const nombreEmpleado = this.dataset.nombreEmpleado;
                document.getElementById('idReposoEliminar').value = idReposo;
                document.getElementById('idPersEliminar').value = idPers;
                document.getElementById('nombreEmpleadoEliminar').textContent = nombreEmpleado;
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