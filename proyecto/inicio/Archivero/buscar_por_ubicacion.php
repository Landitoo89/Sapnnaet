<?php
require('conexion_archivero.php');
session_start();

// 1. Validar parámetro de búsqueda
$termino = isset($_GET['q']) ? trim($_GET['q']) : '';
if (empty($termino)) {
    header('Location: mostrar_archivos.php');
    exit;
}

// 2. Configurar paginación
$por_pagina = 15;
$pagina = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
$offset = ($pagina - 1) * $por_pagina;

// 3. Consulta SQL segura con prepared statements
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
          WHERE CONCAT(
                e.nombre, ' ', p.numero, 
                ' ', o.nombre, 
                ' ', es.codigo,
                ' ', c.codigo
              ) LIKE ?
          ORDER BY a.fecha_creacion DESC
          LIMIT ? OFFSET ?";

// 4. Preparar y ejecutar
$stmt = $conexion->prepare($query);
$busqueda_param = "%$termino%";
$stmt->bind_param('sii', $busqueda_param, $por_pagina, $offset);
$stmt->execute();
$result = $stmt->get_result();

// 5. Obtener total de resultados
$total_registros = $conexion->query("SELECT FOUND_ROWS()")->fetch_row()[0];
$total_paginas = ceil($total_registros / $por_pagina);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultados de Búsqueda</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h1>Resultados para: <?= htmlspecialchars($termino) ?></h1>
        
        <!-- Botones de acción -->
        <div class="toolbar">
            <a href="mostrar_archivos.php" class="btn btn-back">
                ← Volver a todos los archivos
            </a>
        </div>

        <!-- Resultados -->
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Nombre</th>
                        <th>Tipo</th>
                        <th>Fecha</th>
                        <th>Descripción</th>
                        <th>Ubicación</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['codigo']) ?></td>
                                <td><?= htmlspecialchars($row['nombre']) ?></td>
                                <td><span class="badge badge-<?= strtolower($row['tipo']) ?>"><?= $row['tipo'] ?></span></td>
                                <td><?= $row['fecha_creacion'] ?></td>
                                <td><?= htmlspecialchars($row['descripcion']) ?></td>
                                <td class="ubicacion">
                                    <?= str_replace($termino, "<strong>$termino</strong>", htmlspecialchars($row['ubicacion'])) ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="empty-state">
                                No se encontraron archivos en esta ubicación
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
                <a href="?q=<?= urlencode($termino) ?>&pagina=<?= $i ?>" 
                   class="<?= $i == $pagina ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>

<?php
$conexion->close();
?>