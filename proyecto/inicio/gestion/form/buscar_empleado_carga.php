<?php
session_start();
require_once __DIR__ . '/../conexion/conexion_db.php';
$conn = new mysqli($servidor, $usuario, $contraseña, $basedatos);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

$searchTerm = $_GET['q'] ?? '';
$empleados = [];

if (!empty($searchTerm)) {
    // Se eliminó 'ds.fecha_registro' ya que no existe en la tabla datos_socioeconomicos
    $stmt = $conn->prepare("
        SELECT dp.id_pers, dp.nombres, dp.apellidos, dp.cedula_identidad,
               ds.id_socioeconomico
        FROM datos_personales dp
        LEFT JOIN datos_socioeconomicos ds ON dp.id_pers = ds.id_pers
        WHERE dp.nombres LIKE ? OR dp.apellidos LIKE ? OR dp.cedula_identidad LIKE ?
        ORDER BY dp.apellidos, dp.nombres
    ");
    $searchTermLike = "%" . $searchTerm . "%";
    $stmt->bind_param("sss", $searchTermLike, $searchTermLike, $searchTermLike);
    $stmt->execute();
    $result = $stmt->get_result();
    $empleados = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buscar Empleado para Carga Familiar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/formularios_styles.css">
    <style>
        .form-container-custom {
            background: white;
            padding: 2.5rem;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            max-width: 900px;
            margin: 2rem auto;
        }
        .search-results-table {
            margin-top: 2rem;
            border-collapse: collapse;
            width: 100%;
        }
        .search-results-table th, .search-results-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        .search-results-table th {
            background-color: var(--primary-color);
            color: white;
            position: sticky;
            top: 0;
            z-index: 1;
        }
        .search-results-table tbody tr:hover {
            background-color: #f2f2f2;
        }
        .alert-info {
            background-color: #e0f7fa;
            color: #007bb2;
            border-color: #b3e5fc;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-container-custom">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="text-primary mb-0">
                    <i class="fas fa-search me-2"></i>Buscar Empleado para Carga Familiar
                </h1>
                <a href="../gestion_carga.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Volver a Gestión de Cargas
                </a>
            </div>

            <?php if(isset($_SESSION['mensaje'])): ?>
                <div class="alert alert-<?= $_SESSION['mensaje']['tipo'] ?> alert-dismissible fade show" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="bi <?= $_SESSION['mensaje']['tipo'] == 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' ?> me-2"></i>
                        <div>
                            <h5 class="mb-0"><?= htmlspecialchars($_SESSION['mensaje']['titulo']) ?></h5>
                            <p class="mb-0"><?= htmlspecialchars($_SESSION['mensaje']['contenido']) ?></p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['mensaje']); ?>
            <?php endif; ?>

            <form method="GET" action="buscar_empleado_carga.php" class="mb-4">
                <div class="input-group">
                    <input type="text" class="form-control form-control-lg" name="q" placeholder="Buscar por nombre o cédula del empleado..." value="<?= htmlspecialchars($searchTerm) ?>">
                    <button class="btn btn-primary" type="submit">
                        <i class="fas fa-search me-2"></i>Buscar
                    </button>
                    <?php if (!empty($searchTerm)): ?>
                        <a href="buscar_empleado_carga.php" class="btn btn-outline-danger ms-2">
                            <i class="fas fa-times me-2"></i>Limpiar
                        </a>
                    <?php endif; ?>
                </div>
            </form>

            <?php if (!empty($searchTerm) && empty($empleados)): ?>
                <div class="alert alert-warning text-center">
                    <i class="fas fa-exclamation-triangle me-2"></i>No se encontraron empleados con el término "<?= htmlspecialchars($searchTerm) ?>".
                </div>
            <?php elseif (empty($searchTerm)): ?>
                 <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle me-2"></i>Ingrese un término de búsqueda para encontrar empleados.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover search-results-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombres</th>
                                <th>Apellidos</th>
                                <th>Cédula</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($empleados as $empleado): ?>
                                <tr>
                                    <td><?= htmlspecialchars($empleado['id_pers']) ?></td>
                                    <td><?= htmlspecialchars($empleado['nombres']) ?></td>
                                    <td><?= htmlspecialchars($empleado['apellidos']) ?></td>
                                    <td><?= htmlspecialchars($empleado['cedula_identidad']) ?></td>
                                    <td>
                                        <?php if (!empty($empleado['id_socioeconomico'])): ?>
                                            <a href="form_cargafamiliar.php?id_pers=<?= htmlspecialchars($empleado['id_pers']) ?>&nombres=<?= urlencode($empleado['nombres']) ?>&apellidos=<?= urlencode($empleado['apellidos']) ?>&id_socioeco=<?= htmlspecialchars($empleado['id_socioeconomico']) ?>" class="btn btn-success btn-sm">
                                                <i class="fas fa-plus-circle me-2"></i>Añadir Carga
                                            </a>
                                        <?php else: ?>
                                            <button class="btn btn-secondary btn-sm" disabled title="Este empleado no tiene datos socioeconómicos registrados.">
                                                <i class="fas fa-exclamation-circle me-2"></i>Requiere Datos Socioeconómicos
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
