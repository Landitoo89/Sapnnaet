<?php
require('../../conexion_archivero.php');
require('../../funciones.php'); // Asegúrate de que este archivo exista y contenga obtenerEdificio, obtenerPiso, obtenerOficina, obtenerEstante, obtenerCajon y mostrarMensajes

//verificarAdministrador(); // Uncomment this line if you need admin verification

$estante_id = $_GET['estante_id'] ?? 0;
$cajon = ['id' => 0, 'codigo' => '', 'descripcion' => '', 'estante_id' => $estante_id];
$titulo = "Nuevo Cajón";

if (isset($_GET['id'])) {
    $cajon_id = $_GET['id'];
    $cajon = obtenerCajon($cajon_id);
    $titulo = "Editar Cajón";

    if (!$cajon) {
        // Redirigir con un mensaje de error si el cajón no se encuentra
        $_SESSION['mensaje'] = [
            'titulo' => 'Error',
            'contenido' => 'Cajón no encontrado.',
            'tipo' => 'danger'
        ];
        header('Location: ../cajones.php?estante_id=' . $estante_id);
        exit;
    }
}

// Obtener datos de la jerarquía
$estante = obtenerEstante($cajon['estante_id']);
if (!$estante) {
    // Redirigir si el estante padre no existe
    $_SESSION['mensaje'] = [
        'titulo' => 'Error',
        'contenido' => 'Estante padre no encontrado.',
        'tipo' => 'danger'
    ];
    header('Location: ../estantes.php?oficina_id=' . $oficina['id']); // Asumiendo que $oficina está disponible o se obtiene
    exit;
}
$oficina = obtenerOficina($estante['oficina_id']);
if (!$oficina) {
    // Redirigir si la oficina padre no existe
    $_SESSION['mensaje'] = [
        'titulo' => 'Error',
        'contenido' => 'Oficina padre no encontrada.',
        'tipo' => 'danger'
    ];
    header('Location: ../oficinas.php?piso_id=' . $piso['id']); // Asumiendo que $piso está disponible o se obtiene
    exit;
}
$piso = obtenerPiso($oficina['piso_id']);
if (!$piso) {
    // Redirigir si el piso padre no existe
    $_SESSION['mensaje'] = [
        'titulo' => 'Error',
        'contenido' => 'Piso padre no encontrado.',
        'tipo' => 'danger'
    ];
    header('Location: ../pisos.php?edificio_id=' . $edificio['id']); // Asumiendo que $edificio está disponible o se obtiene
    exit;
}
$edificio = obtenerEdificio($piso['edificio_id']);
if (!$edificio) {
    // Redirigir si el edificio padre no existe
    $_SESSION['mensaje'] = [
        'titulo' => 'Error',
        'contenido' => 'Edificio padre no encontrado.',
        'tipo' => 'danger'
    ];
    header('Location: ../index.php'); // Redirigir a la lista de edificios
    exit;
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
        /* Estilos específicos para input-group en cajones */
        .input-group-text {
            background-color: #e9ecef;
            border: 1px solid #ced4da;
            border-right: none;
            border-radius: 8px 0 0 8px;
            padding: 0.75rem 1rem;
        }
        .input-group .form-control {
            border-radius: 0 8px 8px 0;
        }
        .form-text {
            font-size: 0.875em;
            color: #6c757d;
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
            <a href="../pisos.php?edificio_id=<?= htmlspecialchars($edificio['id']) ?>">Piso <?= htmlspecialchars($piso['numero']) ?></a>
            <span>&gt;</span>
            <a href="../oficinas.php?piso_id=<?= htmlspecialchars($oficina['id']) ?>"><?= htmlspecialchars($oficina['nombre']) ?></a>
            <span>&gt;</span>
            <a href="../estantes.php?oficina_id=<?= htmlspecialchars($estante['oficina_id']) ?>">Estante <?= htmlspecialchars($estante['codigo']) ?></a>
            <span>&gt;</span>
            <span><?= $titulo ?></span>
        </div>

        <div class="form-container-custom">
            <div class="form-header">
                <h1>
                    <i class="bi bi-grid-fill me-2"></i><?= $titulo ?>
                </h1>
                <a href="../cajones.php?estante_id=<?= htmlspecialchars($estante['id']) ?>" class="btn btn-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Volver a Cajones
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
            
            <p class="text-muted mb-4">Ubicación: <strong><?= htmlspecialchars($edificio['nombre']) ?> &rarr; Piso <?= htmlspecialchars($piso['numero']) ?> &rarr; <?= htmlspecialchars($oficina['nombre']) ?> &rarr; Estante <?= htmlspecialchars($estante['codigo']) ?></strong></p>

            <form action="../acciones/guardar_cajon.php" method="POST">
                <input type="hidden" name="estante_id" value="<?= htmlspecialchars($estante['id']) ?>">
                
                <?php if ($cajon['id'] > 0): ?>
                    <input type="hidden" name="id" value="<?= htmlspecialchars($cajon['id']) ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="codigo">Código:</label>
                    <input type="text" id="codigo" name="codigo" class="form-control"
                           value="<?= htmlspecialchars($cajon['codigo']) ?>"
                           required pattern="[A-Z0-9-]{1,20}"
                           title="Solo letras mayúsculas, números y guiones (máx. 20 caracteres)">
                </div>

                <div class="form-group">
                    <label for="descripcion">Descripción:</label>
                    <textarea id="descripcion" name="descripcion" class="form-control" rows="3"><?= htmlspecialchars($cajon['descripcion']) ?></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-2"></i>Guardar
                    </button>
                    <a href="../cajones.php?estante_id=<?= htmlspecialchars($estante['id']) ?>" class="btn btn-secondary">
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
