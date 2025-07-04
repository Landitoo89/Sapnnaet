<?php
require('../../conexion_archivero.php');
require '../../funciones.php'; // Asegúrate de que este archivo exista y contenga obtenerEdificio y mostrarMensajes

//verificarAdministrador(); // Uncomment this line if you need admin verification

$edificio = ['id' => 0, 'nombre' => '', 'direccion' => ''];
$titulo = "Nuevo Edificio";

if (isset($_GET['id'])) {
    $edificio = obtenerEdificio($_GET['id']);
    $titulo = "Editar Edificio";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $titulo ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        /* Estilos generales para el cuerpo */
        body { font-family: 'Inter', sans-serif; background-color: #f8f9fa; padding: 20px;}

        /* Contenedor principal del formulario */
        .form-container-custom {
            background: white;
            padding: 2.5rem;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            max-width: 700px; /* Ancho máximo para formularios */
            margin: 2rem auto;
        }

        /* Encabezado principal del formulario */
        .form-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }
        .form-header h1 {
            font-size: 2rem;
            font-weight: 700;
            color: #343a40;
        }
        .form-header .btn-secondary {
            border-radius: 50px;
            padding: 8px 20px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
        }

        /* Encabezados de sección dentro del formulario */
        .form-section-header {
            background-color: #e9ecef;
            padding: 1rem 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            border-left: 5px solid #007bff; /* Color primario de Bootstrap */
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .form-section-header h2 {
            margin-bottom: 0;
            color: #343a40;
            font-size: 1.5rem;
            font-weight: 600;
            display: flex;
            align-items: center;
        }
        .form-section-header h2 i {
            margin-right: 10px;
            color: #007bff;
        }

        /* Estilos de los grupos de formulario */
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.5rem;
            display: block;
        }
        .form-control, .form-select {
            border-radius: 8px;
            padding: 0.75rem 1rem;
            border: 1px solid #ced4da;
            transition: all 0.3s ease;
        }
        .form-control:focus, .form-select:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.25rem rgba(0, 123, 255, 0.25);
        }
        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }

        /* Acciones del formulario (botones) */
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 2.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #eee;
        }
        .form-actions .btn {
            padding: 10px 25px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 50px;
            transition: all 0.3s ease;
        }
        .form-actions .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
            box-shadow: 0 4px 10px rgba(0,123,255,0.2);
        }
        .form-actions .btn-primary:hover {
            background-color: #0056b3;
            border-color: #0056b3;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0,123,255,0.3);
        }
        .form-actions .btn-secondary {
            background-color: #6c757d;
            border-color: #6c757d;
            color: white;
            box-shadow: 0 4px 10px rgba(108,117,125,0.2);
        }
        .form-actions .btn-secondary:hover {
            background-color: #5a6268;
            border-color: #545b62;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(108,117,125,0.3);
        }

        /* Mensajes de Bootstrap (alertas) */
        .alert {
            border-radius: 10px;
            display: flex;
            align-items: center;
            padding: 1rem 1.5rem;
            margin-bottom: 2rem;
        }
        .alert .alert-heading {
            font-size: 1.25rem;
            margin-bottom: 0.5rem;
        }
        .alert ul {
            margin-bottom: 0;
            padding-left: 20px;
        }
        .alert i {
            font-size: 1.5rem;
            margin-right: 15px;
        }

        /* Breadcrumbs */
        .breadcrumbs {
            background: #e9ecef;
            padding: 10px 15px;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            font-size: 0.9rem;
            color: #6c757d;
        }
        .breadcrumbs a {
            color: #007bff;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }
        .breadcrumbs a:hover {
            color: #0056b3;
            text-decoration: underline;
        }
        .breadcrumbs span {
            margin: 0 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="breadcrumbs">
            <a href="../../mostrar_archivos.php">Inicio</a>
            <span>&gt;</span>
            <a href="../index.php">Edificios</a>
            <span>&gt;</span>
            <span><?= $titulo ?></span>
        </div>

        <div class="form-container-custom">
            <div class="form-header">
                <h1>
                    <i class="bi bi-building me-2"></i><?= $titulo ?>
                </h1>
                <a href="../index.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Volver a Edificios
                </a>
            </div>

            <?php // Función para mostrar mensajes de sesión, asumiendo que está definida en funciones.php o similar
            if (function_exists('mostrarMensajes')) {
                mostrarMensajes();
            } else {
                // Mensajes de sesión básicos si la función no existe
                if (isset($_SESSION['mensaje'])) {
                    echo '<div class="alert alert-' . htmlspecialchars($_SESSION['mensaje']['tipo']) . ' alert-dismissible fade show" role="alert">';
                    echo '<div class="d-flex align-items-center">';
                    echo '<i class="bi ' . (($_SESSION['mensaje']['tipo'] == 'success') ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill') . ' me-2"></i>';
                    echo '<div><h5 class="mb-0">' . htmlspecialchars($_SESSION['mensaje']['titulo']) . '</h5>';
                    echo '<p class="mb-0">' . htmlspecialchars($_SESSION['mensaje']['contenido']) . '</p></div></div>';
                    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
                    echo '</div>';
                    unset($_SESSION['mensaje']);
                }
            }
            ?>

            <form action="../acciones/guardar_edificio.php" method="POST">
                <?php if ($edificio['id'] > 0): ?>
                    <input type="hidden" name="id" value="<?= htmlspecialchars($edificio['id']) ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="nombre">Nombre:</label>
                    <input type="text" id="nombre" name="nombre" class="form-control" value="<?= htmlspecialchars($edificio['nombre']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="direccion">Dirección:</label>
                    <textarea id="direccion" name="direccion" class="form-control" rows="3"><?= htmlspecialchars($edificio['direccion']) ?></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-2"></i>Guardar
                    </button>
                    <a href="../index.php" class="btn btn-secondary">
                        <i class="bi bi-x-circle me-2"></i>Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle (popper included) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
