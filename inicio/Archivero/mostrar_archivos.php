<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require('conexion_archivero.php');
session_start();

// ===== VERIFICACIÓN DE SESIÓN Y ROL SOLO ADMIN =====
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

// 1. Configuración de paginación
$por_pagina = 15;
$pagina = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
$offset = ($pagina - 1) * $por_pagina;

// 2. Parámetros de búsqueda
$termino = $_GET['termino'] ?? '';
$tipo = $_GET['tipo'] ?? '';

// 3. Ordenamiento seguro
$orden = in_array($_GET['orden'] ?? '', ['codigo', 'nombre', 'tipo', 'fecha_creacion']) 
         ? $_GET['orden'] 
         : 'fecha_creacion';
$direccion = ($_GET['dir'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';

// 4. Consulta base
$query = "SELECT SQL_CALC_FOUND_ROWS
            a.id, 
            a.codigo, 
            a.nombre, 
            a.tipo, 
            DATE_FORMAT(a.fecha_creacion, '%d/%m/%Y') as fecha_creacion,
            a.descripcion,
            CONCAT(
                e.nombre, ' → Piso ', p.numero, 
                ' → ', o.nombre, 
                ' → Estante ', es.codigo,
                ' → Cajón ', c.codigo
            ) AS ubicacion
          FROM archivos a
          JOIN archivo_ubicacion au ON a.id = au.archivo_id
          JOIN cajones c ON au.cajon_id = c.id
          JOIN estantes es ON c.estante_id = es.id
          JOIN oficinas o ON es.oficina_id = o.id
          JOIN pisos p ON o.piso_id = p.id
          JOIN edificios e ON p.edificio_id = e.id
          WHERE 1=1";

$params = [];
$types = '';

// 5. Filtros mejorados
if (!empty($termino)) {
    $query .= " AND (a.nombre LIKE ? 
              OR a.codigo LIKE ? 
              OR a.descripcion LIKE ?
              OR e.nombre LIKE ?
              OR p.numero LIKE ?
              OR o.nombre LIKE ?
              OR es.codigo LIKE ?
              OR c.codigo LIKE ?)";
              
    array_push($params, 
        "%$termino%", "%$termino%", "%$termino%",
        "%$termino%", "%$termino%", "%$termino%",
        "%$termino%", "%$termino%"
    );
    $types .= 'ssssssss';
}

if (!empty($tipo)) {
    $query .= " AND a.tipo = ?";
    array_push($params, $tipo);
    $types .= 's';
}

// 6. Ordenamiento y límites
$query .= " ORDER BY $orden $direccion LIMIT ? OFFSET ?";
array_push($params, $por_pagina, $offset);
$types .= 'ii';

// 7. Ejecución segura de la consulta
$result = null;
$total_registros = 0;
$total_paginas = 1;

if ($stmt = $conexion->prepare($query)) {
    if (!empty($types)) $stmt->bind_param($types, ...$params);
    
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $total_registros = $conexion->query("SELECT FOUND_ROWS()")->fetch_row()[0] ?? 0;
        $total_paginas = max(1, ceil($total_registros / $por_pagina));
    } else {
        die("Error ejecutando consulta: " . $conexion->error);
    }
} else {
    die("Error preparando consulta: " . $conexion->error);
}
require $_SERVER['DOCUMENT_ROOT']."/proyecto/inicio/sidebar.php";

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestor de Archivos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2c3e59;
            --secondary-color: #3498db;
        }

        .hover-shadow { 
            transition: all 0.2s ease; 
        }
        .hover-shadow:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 5px 15px rgba(0,0,0,0.1); 
        }
        .badge-ubicacion { 
            background-color: #e9ecef; 
            color: #495057; 
            border: 1px solid #dee2e6; 
        }
        
        .table {
            border-collapse: collapse !important;
        }

        .table thead tr {
            background: linear-gradient(135deg, 
                var(--primary-color) 30%, 
                var(--secondary-color) 70%
            ) !important;
            border: none !important;
        }

        .table thead th {
            border: none !important;
            color: white !important;
            padding: 1rem !important;
            vertical-align: middle !important;
            position: relative;
        }

        .table thead th:not(:last-child)::after {
            content: '';
            position: absolute;
            right: 0;
            top: 15%;
            height: 70%;
            width: 1px;
            background: rgba(255,255,255,0.3);
        }

        .table thead th a {
            color: white !important;
            text-decoration: none !important;
            display: block;
        }

        .table thead th .fas {
            filter: drop-shadow(0 1px 1px rgba(0,0,0,0.2));
        }
    </style>
</head>
<body class="bg-light">
    <div class="container-lg py-4">
        <header class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 fw-bold text-primary">
                <i class="fas fa-archive me-2"></i>Gestión de Archivos
            </h1>
            <div class="d-flex gap-2">
                <a href="agregar_archivo.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Nuevo
                </a>
                <a href="exportar_csv.php" class="btn btn-success">
                    <i class="fas fa-file-csv me-2"></i>Exportar
                </a>
            </div>
        </header>

        <form method="GET" class="bg-white rounded-3 shadow-sm p-4 mb-4">
            <div class="row g-3">
                <div class="col-md-8">
                    <div class="input-group">
                        <input type="text" name="termino" value="<?= htmlspecialchars($termino) ?>" 
                               class="form-control" placeholder="Buscar por nombre, código, descripción o ubicación...">
                        <button type="submit" class="btn btn-outline-primary">
                            <i class="fas fa-search"></i>
                        </button>
                        <a href="mostrar_archivos2.php" class="btn btn-outline-secondary">
                            <i class="fas fa-sync"></i>
                        </a>
                    </div>
                </div>
                <div class="col-md-4">
                    <select name="tipo" class="form-select">
                        <option value="">Todos los tipos</option>
                        <option value="PDF" <?= $tipo === 'PDF' ? 'selected' : '' ?>>PDF</option>
                        <option value="Carpeta" <?= $tipo === 'Carpeta' ? 'selected' : '' ?>>Carpetas</option>
                        <option value="Documento" <?= $tipo === 'Documento' ? 'selected' : '' ?>>Documentos</option>
                    </select>
                </div>
            </div>
        </form>

        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <?php 
                                function sortLink($column, $label) {
                                    global $orden, $direccion, $_GET;
                                    ?>
                                    <th class="align-middle text-white position-relative">
                                        <a href="?<?= http_build_query(array_merge($_GET, [
                                            'orden' => $column,
                                            'dir' => ($orden === $column && $direccion === 'ASC') ? 'DESC' : 'ASC'
                                        ])) ?>" class="text-white text-decoration-none">
                                            <?= $label ?>
                                            <?php if ($orden === $column): ?>
                                                <i class="fas fa-sort-<?= $direccion === 'ASC' ? 'up' : 'down' ?> ms-1"></i>
                                            <?php else: ?>
                                                <i class="fas fa-sort ms-1"></i>
                                            <?php endif; ?>
                                        </a>
                                    </th>
                                    <?php
                                }
                                sortLink('codigo', 'Código');
                                sortLink('nombre', 'Nombre');
                                sortLink('tipo', 'Tipo');
                                sortLink('fecha_creacion', 'Fecha');
                                ?>
                                <th class="text-white position-relative">Ubicación</th>
                                <th class="text-end text-white position-relative">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result && $result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr class="hover-shadow">
                                        <td class="fw-bold"><?= htmlspecialchars($row['codigo']) ?></td>
                                        <td><?= htmlspecialchars($row['nombre']) ?></td>
                                        <td>
                                            <span class="badge rounded-pill bg-<?= match(strtolower($row['tipo'])) {
                                                'pdf' => 'danger',
                                                'documento' => 'primary',
                                                'carpeta' => 'success',
                                                default => 'secondary'
                                            } ?>">
                                                <?= htmlspecialchars($row['tipo']) ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($row['fecha_creacion']) ?></td>
                                        <td>
                                            <div class="d-flex gap-2 flex-wrap">
                                                <?php foreach (explode(' → ', $row['ubicacion']) as $parte): ?>
                                                    <span class="badge badge-ubicacion rounded-pill">
                                                        <?= htmlspecialchars($parte) ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>
                                        </td>
                                        <td class="text-end">
                                            <div class="d-flex gap-2 justify-content-end">
                                                <a href="editar_archivo.php?id=<?= $row['id'] ?>" 
                                                class="btn btn-sm btn-outline-primary"
                                                title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="eliminar_archivo.php?id=<?= $row['id'] ?>" 
                                                class="btn btn-sm btn-outline-danger"
                                                title="Eliminar"
                                                onclick="return confirm('¿Eliminar permanentemente este archivo?')">
                                                    <i class="fas fa-trash-alt"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 bg-light">
                                        <div class="py-4">
                                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                            <h4 class="text-muted">No se encontraron archivos</h4>
                                            <p class="text-muted">Prueba ajustando los filtros de búsqueda</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php if ($total_paginas > 1): ?>
        <nav class="mt-4">
            <ul class="pagination justify-content-center">
                <?php if ($pagina > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pagina' => $pagina - 1])) ?>">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    </li>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                    <li class="page-item <?= $i == $pagina ? 'active' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pagina' => $i])) ?>">
                            <?= $i ?>
                        </a>
                    </li>
                <?php endfor; ?>
                
                <?php if ($pagina < $total_paginas): ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pagina' => $pagina + 1])) ?>">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
$conexion->close();
?>