<?php
require('../conexion_archivero.php');

ob_start();
session_start();
//verificarAdministrador();

// Obtener todos los edificios
$query = "SELECT * FROM edificios ORDER BY nombre";
$result = $conexion->query($query);
require $_SERVER['DOCUMENT_ROOT']."/proyecto/inicio/sidebar.php";
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Edificios</title>
    <link rel="stylesheet" href="../styles.css">
    <!-- Bootstrap y Bootstrap Icons para mejor visual -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .edificio-header {
            background: linear-gradient(135deg, #2c3e50, #3498db);
            color: white;
            border-radius: 15px;
            margin-bottom: 2rem;
        }
        /* Botón Nuevo Edificio estilo gestión vacaciones */
        .btn-add {
            background: #fff;
            color: #2c3e50 !important;
            border-radius: 50px;
            padding: 12px 32px;
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 1.5rem;
            border: 2px solid #2c3e50;
            box-shadow: 0 2px 8px rgba(44,62,80,0.08);
            transition: background 0.2s, color 0.2s, border 0.2s;
            display: inline-block;
        }
        .btn-add:hover {
            background: #2c3e50;
            color: #fff !important;
            border: 2px solid #2c3e50;
        }
        /* Botones de acción estilo gestión vacaciones */
        .btn-action {
            border: none;
            border-radius: 50px;
            padding: 7px 16px;
            font-size: 1.1rem;
            font-weight: 500;
            margin-right: 6px;
            transition: background 0.2s, color 0.2s, transform 0.2s;
            box-shadow: 0 1px 4px rgba(44,62,80,0.07);
            display: inline-flex;
            align-items: center;
        }
        .btn-edit {
            background: #ffc107;
            color: #212529 !important;
        }
        .btn-edit:hover {
            background: #ffcd39;
            color: #212529 !important;
            transform: scale(1.05);
        }
        .btn-delete {
            background: #dc3545;
            color: #fff !important;
        }
        .btn-delete:hover {
            background: #bb2d3b;
            color: #fff !important;
            transform: scale(1.05);
        }
        .btn-pisos {
            background: #0dcaf0;
            color: #fff !important;
            border-radius: 50px;
            padding: 7px 20px;
            font-size: 1rem;
            font-weight: 500;
            margin-right: 6px;
            transition: background 0.2s, color 0.2s, transform 0.2s;
            box-shadow: 0 1px 4px rgba(44,62,80,0.07);
            display: inline-flex;
            align-items: center;
        }
        .btn-pisos:hover {
            background: #31d2f2;
            color: #fff !important;
            transform: scale(1.05);
        }
        .table thead {
            background: #2c3e50;
            color: #fff;
        }
        .table tbody tr:hover {
            background: #f0f8ff;
        }
        .table td, .table th {
            vertical-align: middle;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="edificio-header p-4 mb-4 d-flex justify-content-between align-items-center">
            <h1 class="mb-0">
                <i class="bi bi-building me-3"></i> Gestión de Edificios
            </h1>
            <a href="forms/edificio_form.php" class="btn-add">
                <i class="bi bi-plus-circle me-2"></i>Nuevo Edificio
            </a>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Dirección</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($edificio = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($edificio['nombre']) ?></td>
                        <td><?= htmlspecialchars($edificio['direccion']) ?></td>
                        <td>
                            <a href="pisos.php?edificio_id=<?= $edificio['id'] ?>" class="btn-pisos" title="Ver Pisos">
                                Pisos
                            </a>
                            <a href="forms/edificio_form.php?id=<?= $edificio['id'] ?>" class="btn-action btn-edit" title="Editar">
                                <i class="bi bi-pencil-square"></i>
                            </a>
                            <a href="acciones/eliminar_edificio.php?id=<?= $edificio['id'] ?>"
                               class="btn-action btn-delete"
                               onclick="return confirm('¿Eliminar este edificio y todos sus datos asociados?')"
                               title="Eliminar">
                                <i class="bi bi-trash3-fill"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>