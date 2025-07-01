<?php
require('conexion_archivero.php');
session_start();
//Verificar permisos de admin
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// 1. Configuración de paginación
$por_pagina = 15;
$pagina = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
$offset = ($pagina - 1) * $por_pagina;

// 2. Parámetros de búsqueda y filtros
$termino = $_GET['termino'] ?? '';
$tipo = $_GET['tipo'] ?? '';
$fecha_desde = $_GET['fecha_desde'] ?? '';
$fecha_hasta = $_GET['fecha_hasta'] ?? '';

// 3. Ordenamiento
$orden = in_array($_GET['orden'] ?? '', ['codigo', 'nombre', 'tipo', 'fecha_creacion']) 
         ? $_GET['orden'] 
         : 'fecha_creacion';
$direccion = ($_GET['dir'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';

// 4. Consulta base con prepared statements
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

// 5. Filtros con prepared statements
if (!empty($termino)) {
    $query .= " AND (a.nombre LIKE ? OR a.codigo LIKE ? OR a.descripcion LIKE ?)";
    $params = array_merge($params, ["%$termino%", "%$termino%", "%$termino%"]);
    $types .= 'sss';
}

if (!empty($tipo)) {
    $query .= " AND a.tipo = ?";
    $params[] = $tipo;
    $types .= 's';
}

if (!empty($fecha_desde) && !empty($fecha_hasta)) {
    $query .= " AND a.fecha_creacion BETWEEN ? AND ?";
    $params[] = $fecha_desde;
    $params[] = $fecha_hasta;
    $types .= 'ss';
}

// 6. Ordenamiento seguro
$query .= " ORDER BY $orden $direccion LIMIT ? OFFSET ?";
$params = array_merge($params, [$por_pagina, $offset]);
$types .= 'ii';

// 7. Ejecutar consulta
$stmt = $conexion->prepare($query);
if ($types) $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// 8. Obtener total de registros
$total_registros = $conexion->query("SELECT FOUND_ROWS()")->fetch_row()[0];
$total_paginas = ceil($total_registros / $por_pagina);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestor de Archivos</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js">

</head>
<body>
    <div class="container">
        <h1 class="main-title">Gestión de Archivos</h1>

        <!-- Barra de herramientas -->
        <div class="toolbar">
            <a href="agregar_archivo.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nuevo Archivo
            </a>
            <a href="exportar_csv.php" class="btn btn-export">
                <i class="fas fa-file-csv"></i> Exportar CSV
            </a>
        </div>

        <!-- Filtros -->
        <form method="GET" class="filtros">
            <div class="filtro-group">
                <input type="text" name="termino" value="<?= htmlspecialchars($termino) ?>" 
                       placeholder="Buscar..." class="search-input">
                <button type="submit" class="btn btn-search">
                    <i class="fas fa-search"></i>
                </button>
                <a href="mostrar_archivos.php" class="btn btn-clear">
                    <i class="fas fa-sync"></i>
                </a>
            </div>
            
            <div class="filtro-avanzado">
                <div class="filtro-group">
                    <label>Tipo:</label>
                    <select name="tipo" class="select-filter">
                        <option value="">Todos</option>
                        <option value="PDF" <?= $tipo === 'PDF' ? 'selected' : '' ?>>PDF</option>
                        <option value="Carpeta" <?= $tipo === 'Carpeta' ? 'selected' : '' ?>>Carpetas</option>
                        <option value="Documento" <?= $tipo === 'Documento' ? 'selected' : '' ?>>Documentos</option>
                    </select>
                </div>
                
                <div class="filtro-group">
                    <label>Fecha:</label>
                    <input type="date" name="fecha_desde" value="<?= $fecha_desde ?>" 
                           class="date-filter" max="<?= date('Y-m-d') ?>">
                    <input type="date" name="fecha_hasta" value="<?= $fecha_hasta ?>" 
                           class="date-filter" max="<?= date('Y-m-d') ?>">
                </div>
            </div>
        </form>

        <!-- Tabla responsive -->
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th class="<?= $orden === 'codigo' ? 'active-sort' : '' ?>">
                            <a href="?<?= http_build_query(array_merge($_GET, [
                                'orden' => 'codigo', 
                                'dir' => ($orden === 'codigo' && $direccion === 'ASC') ? 'DESC' : 'ASC'
                            ])) ?>">
                                Código
                                <?= $orden === 'codigo' ? ($direccion === 'ASC' ? '↑' : '↓') : '' ?>
                            </a>
                        </th>
                        <th class="<?= $orden === 'nombre' ? 'active-sort' : '' ?>">
                            <a href="?<?= http_build_query(array_merge($_GET, [
                                'orden' => 'nombre', 
                                'dir' => ($orden === 'nombre' && $direccion === 'ASC') ? 'DESC' : 'ASC'
                            ])) ?>">
                                Nombre
                                <?= $orden === 'nombre' ? ($direccion === 'ASC' ? '↑' : '↓') : '' ?>
                            </a>
                        </th>
                        <th class="<?= $orden === 'tipo' ? 'active-sort' : '' ?>">
                            <a href="?<?= http_build_query(array_merge($_GET, [
                                'orden' => 'tipo', 
                                'dir' => ($orden === 'tipo' && $direccion === 'ASC') ? 'DESC' : 'ASC'
                            ])) ?>">
                                Tipo
                                <?= $orden === 'tipo' ? ($direccion === 'ASC' ? '↑' : '↓') : '' ?>
                            </a>
                        </th>
                        <th class="<?= $orden === 'fecha_creacion' ? 'active-sort' : '' ?>">
                            <a href="?<?= http_build_query(array_merge($_GET, [
                                'orden' => 'fecha_creacion', 
                                'dir' => ($orden === 'fecha_creacion' && $direccion === 'ASC') ? 'DESC' : 'ASC'
                            ])) ?>">
                                Fecha
                                <?= $orden === 'fecha_creacion' ? ($direccion === 'ASC' ? '↑' : '↓') : '' ?>
                            </a>
                        </th>
                        <th>Ubicación</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['codigo']) ?></td>
                                <td><?= htmlspecialchars($row['nombre']) ?></td>
                                <td>
                                    <span class="badge badge-<?= strtolower($row['tipo']) ?>">
                                        <?= $row['tipo'] ?>
                                    </span>
                                </td>
                                <td><?= $row['fecha_creacion'] ?></td>
                                <td class="ubicacion">
                                    <?php 
                                    $partes = explode(' → ', $row['ubicacion']);
                                    foreach ($partes as $parte) {
                                        echo '<a href="buscar_por_ubicacion.php?q='.urlencode($parte).'">'
                                            .htmlspecialchars($parte).'</a> → ';
                                    }
                                    echo rtrim(' → ', '→ ');
                                    ?>
                                </td>
                                <td class="acciones">
                                    <a href="editar_archivo.php?id=<?= $row['id'] ?>" 
                                       class="btn-action btn-edit" 
                                       title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="eliminar_archivo.php?id=<?= $row['id'] ?>" 
                                       class="btn-action btn-delete" 
                                       title="Eliminar"
                                       onclick="return confirm('¿Eliminar permanentemente este archivo?')">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="empty-state">
                                <img src="img/no-data.svg" alt="Sin resultados" class="empty-img">
                                <h3>No se encontraron archivos</h3>
                                <p>Intenta ajustar tus filtros de búsqueda</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        <?php if ($total_paginas > 1): ?>
        <div class="paginacion">
            <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['pagina' => $i])) ?>" 
                   class="<?= $i == $pagina ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Scripts adicionales -->
    <script>
    // Manejar filtros de fecha
    document.querySelectorAll('.date-filter').forEach(input => {
        input.addEventListener('change', function() {
            if(this.value > new Date().toISOString().split('T')[0]) {
                this.value = '';
            }
        });
    });
    </script>
</body>
</html>

<?php
$conexion->close();
?>