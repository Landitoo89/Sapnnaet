<?php
require('../conexion_archivero.php');
require('../funciones.php'); // Assuming funciones.php contains obtenerEdificio, obtenerPiso, obtenerOficina, obtenerEstante, etc.

//verificarAdministrador();
$oficina_id = $_GET['oficina_id'];

// Obtener datos de la oficina, piso y edificio
$oficina = obtenerOficina($oficina_id);
$piso = obtenerPiso($oficina['piso_id']);
$edificio = obtenerEdificio($piso['edificio_id']);

// Obtener estantes de la oficina
$query = "SELECT e.*, 
            (SELECT COUNT(*) FROM cajones WHERE estante_id = e.id) AS total_cajones
          FROM estantes e
          WHERE e.oficina_id = ?
          ORDER BY e.codigo";
$stmt = $conexion->prepare($query);
$stmt->bind_param('i', $oficina_id);
$stmt->execute();
$result = $stmt->get_result();

require $_SERVER['DOCUMENT_ROOT']."/proyecto/inicio/sidebar.php";
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estantes de <?= htmlspecialchars($oficina['nombre']) ?></title>
    <!-- Bootstrap 5 y Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Font Awesome para íconos adicionales -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body { margin: 0; font-family: 'Arial', sans-serif; }
        .sidebar {
            position: fixed; top: 0; left: 0; height: 100%; width: 190px;
            background: #343a40; color: #fff; transition: width 0.3s;
            overflow: hidden; z-index: 1000; box-shadow: 0 0 12px #0002;
            display: flex; flex-direction: column;
        }
        .sidebar.collapsed { width: 75px; }
        .sidebar .logo { text-align: center; padding: 15px; border-bottom: 1px solid #495057;}
        .sidebar .logo img { max-width: 100%; height: auto;}
        .sidebar .sidebar-scroll {
            flex: 1 1 auto;
            overflow-y: auto;
            overflow-x: hidden;
            scrollbar-width: thin;
            scrollbar-color: #7da2e3 #22272b;
        }
        .sidebar .sidebar-scroll::-webkit-scrollbar {
            width: 7px;
            background: #22272b;
        }
        .sidebar .sidebar-scroll::-webkit-scrollbar-thumb {
            background: #7da2e3;
            border-radius: 4px;
        }
        .sidebar .menu { list-style: none; padding: 0; margin: 0;}
        .sidebar .menu-item, .sidebar .menu-link {
            display: flex; align-items: center; padding: 15px 20px;
            color: #adb5bd; text-decoration: none; font-size: 16px;
            transition: background-color 0.3s; cursor: pointer;
            user-select: none;
        }
        .sidebar .menu-item:hover, .sidebar .menu-link:hover { background: #495057; color: #fff;}
        .sidebar .menu-item i, .sidebar .menu-link i { margin-right: 15px; font-size: 1.2em;}
        .sidebar.collapsed .menu-item i, .sidebar.collapsed .menu-link i { margin-right: 0; }
        .sidebar .menu-item span, .sidebar .menu-link span { white-space: nowrap; overflow: hidden; text-overflow: ellipsis;}
        .sidebar.collapsed .menu-item span, .sidebar.collapsed .menu-link span { display: none; }
        .sidebar .submenu { list-style: none; padding: 0; margin: 0; display: none; background: #495057;}
        .sidebar .submenu.open { display: block; animation: fadeInMenu 0.22s;}
        @keyframes fadeInMenu { from {opacity:0; transform:translateY(-10px);} to {opacity:1; transform:none;} }
        .sidebar .submenu-item { padding: 10px 20px; color: #adb5bd; text-decoration: none; font-size: 14px; transition: background 0.3s; cursor: pointer;}
        .sidebar .submenu-item:hover { background: #6c757d; color: #fff;}
        .toggle-btn {
            position: absolute; top: 50%; left: 90%; transform: translate(-50%, -50%);
            background: transparent; color: #007bff; border: none; border-radius: 50%; cursor: pointer; padding: 10px; transition: all 0.3s;
        }
        .toggle-btn i { font-size: 1.5em;}
        .sidebar.collapsed .toggle-btn { left: 83%; color: #000;}
        @media (max-width: 700px) {
            .sidebar {width: 100vw; min-width: 0; max-width: 100vw;}
            .sidebar.collapsed {width: 60px;}
        }
        .main-content { margin-left: 190px; padding: 2rem 1rem; transition: margin-left 0.3s;}
        .sidebar.collapsed ~ .main-content { margin-left: 75px; }
        @media (max-width: 700px) {
            .main-content { margin-left: 60px; }
        }

        .header-section {
            background: linear-gradient(135deg, #2c3e50, #3498db);
            color: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn-add-new {
            background: #fff;
            color: #2c3e50 !important;
            border-radius: 50px;
            padding: 12px 32px;
            font-weight: 600;
            font-size: 1.1rem;
            border: 2px solid #2c3e50;
            box-shadow: 0 2px 8px rgba(44,62,80,0.08);
            transition: background 0.2s, color 0.2s, border 0.2s, transform 0.2s;
            display: inline-flex;
            align-items: center;
        }
        .btn-add-new:hover {
            background: #2c3e50;
            color: #fff !important;
            border: 2px solid #2c3e50;
            transform: translateY(-2px);
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
        .action-btn {
            border: none;
            border-radius: 50px;
            padding: 7px 16px;
            font-size: 1.1rem;
            font-weight: 500;
            margin-right: 6px;
            transition: transform 0.2s; /* Only transform for hover, colors handled by Bootstrap */
            box-shadow: 0 1px 4px rgba(44,62,80,0.07);
            display: inline-flex;
            align-items: center;
        }
        .action-btn:hover {
            transform: scale(1.05);
        }
        .empty-state {
            text-align: center;
            padding: 40px 0;
            color: #6c757d;
        }
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 15px;
        }
        .breadcrumbs {
            background: #e9ecef;
            padding: 10px 15px;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
        }
        .breadcrumbs a {
            color: #007bff;
            text-decoration: none;
            font-weight: 500;
        }
        .breadcrumbs a:hover {
            text-decoration: underline;
        }
        .breadcrumbs span {
            color: #6c757d;
            margin: 0 5px;
        }
    </style>
</head>
<body>
    <?php include $_SERVER['DOCUMENT_ROOT']."/proyecto/inicio/sidebar.php"; ?>

    <div class="main-content" id="main-content">
        <div class="breadcrumbs">
            <a href="../mostrar_archivos.php">Inicio</a> 
            <span>&gt;</span>
            <a href="index.php">Edificios</a>
            <span>&gt;</span>
            <a href="pisos.php?edificio_id=<?= $edificio['id'] ?>">Piso <?= $piso['numero'] ?></a>
            <span>&gt;</span>
            <a href="oficinas.php?piso_id=<?= $piso['id'] ?>"><?= htmlspecialchars($oficina['nombre']) ?></a>
            <span>&gt;</span>
            <span>Estantes</span>
        </div>

        <div class="header-section">
            <h1 class="mb-0">
                <i class="bi bi-card-checklist me-3"></i> Estantes de <?= htmlspecialchars($oficina['nombre']) ?>
            </h1>
            <a href="forms/estante_form.php?oficina_id=<?= $oficina_id ?>" class="btn-add-new">
                <i class="bi bi-plus-circle me-2"></i>Nuevo Estante
            </a>
        </div>
        
        <p class="text-muted mb-4">Ubicación: <strong><?= htmlspecialchars($edificio['nombre']) ?> &gt; Piso <?= $piso['numero'] ?></strong></p>

        <?php // mostrarMensajes(); // Si tienes una función para mostrar mensajes, actívala aquí ?>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Descripción</th>
                        <th>Cajones</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($estante = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($estante['codigo']) ?></td>
                            <td><?= htmlspecialchars($estante['descripcion']) ?></td>
                            <td><?= $estante['total_cajones'] ?></td>
                            <td>
                                <a href="cajones.php?estante_id=<?= $estante['id'] ?>" class="btn btn-sm btn-info action-btn" title="Ver Cajones">
                                    <i class="bi bi-grid-fill me-1"></i> Cajones
                                </a>
                                <a href="forms/estante_form.php?id=<?= $estante['id'] ?>" class="btn btn-sm btn-warning action-btn" title="Editar">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                                <a href="acciones/eliminar_estante.php?id=<?= $estante['id'] ?>" 
                                   class="btn btn-sm btn-danger action-btn"
                                   onclick="return confirm('¿Eliminar este estante y todos sus cajones?')"
                                   title="Eliminar">
                                    <i class="bi bi-trash3-fill"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="empty-state">
                                <i class="bi bi-box-seam-fill"></i>
                                <p>No hay estantes registrados en esta oficina.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <!-- Bootstrap JS Bundle (popper included) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sidebar logic (assuming it's in sidebar.php and you need this for responsiveness)
        const sidebar = document.getElementById('sidebar');
        const toggleBtn = document.getElementById('toggle-btn');
        const submenus = document.querySelectorAll('.submenu');
        const menuItems = document.querySelectorAll('.menu-item');
        const mainContent = document.getElementById('main-content');

        if (toggleBtn) {
            toggleBtn.addEventListener('click', () => {
                sidebar.classList.toggle('collapsed');
                if (sidebar.classList.contains('collapsed')) {
                    mainContent.style.marginLeft = '75px';
                    toggleBtn.innerHTML = '<i class="fas fa-chevron-right"></i>';
                } else {
                    mainContent.style.marginLeft = '190px';
                    toggleBtn.innerHTML = '<i class="fas fa-chevron-left"></i>';
                }
            });
        }

        menuItems.forEach(item => {
            item.addEventListener('click', function (e) {
                const link = item.getAttribute('data-link');
                const submenuId = item.getAttribute('data-submenu');
                const submenu = submenuId ? document.getElementById(submenuId) : null;
                submenus.forEach(sm => { if(sm !== submenu) sm.classList.remove('open'); });
                if (submenu) {
                    e.stopPropagation();
                    if (sidebar.classList.contains('collapsed')) {
                        sidebar.classList.remove('collapsed');
                        mainContent.style.marginLeft = '190px';
                        if (toggleBtn) toggleBtn.innerHTML = '<i class="fas fa-chevron-left"></i>';
                        setTimeout(() => submenu.classList.add('open'), 200);
                    } else {
                        submenu.classList.toggle('open');
                    }
                } else if (link && link !== "#") {
                    window.location.href = link;
                }
            });
            item.addEventListener('keydown', function(e) {
                if (e.key === "Enter" || e.key === " ") {
                    item.click();
                }
            });
        });

        const sidebarScroll = document.querySelector('.sidebar-scroll');
        if (sidebarScroll) {
            sidebarScroll.addEventListener('wheel', function(e) {
                this.scrollTop += e.deltaY;
                e.preventDefault();
            });
        }
    </script>
</body>
</html>
