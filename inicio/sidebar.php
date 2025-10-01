<?php
$rol = $_SESSION['usuario']['rol'] ?? '';
$nombre = $_SESSION['usuario']['nombre'] ?? 'Usuario';
$apellido = $_SESSION['usuario']['apellido'] ?? 'Usuario';
$correo = $_SESSION['usuario']['correo'] ?? '';
$avatar = $_SESSION['usuario']['avatar'] ?? '/proyecto/inicio/img/avatar-default.png';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Sidebar Minimalista Mejorado</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- Bootstrap CSS y Font Awesome -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
  <style>
    body { margin: 0; font-family: 'Arial', sans-serif; }
    .sidebar {
      display: flex;
      flex-direction: column;
      height: 100vh;
      width: 200px;
      min-width: 68px;
      background: #343a40;
      position: fixed; top: 0; left: 0;
      z-index: 1000;
      box-shadow: 0 0 12px #0002;
      transition: width 0.3s;
      overflow: hidden;
    }
    .sidebar.collapsed { width: 68px; }
    .logo, .profile, .logout-fixed { flex-shrink: 0; }
    .logo { text-align: center; padding: 16px 0 14px 0; border-bottom: 1px solid #495057;}
    .logo img { max-width: 92%; height: auto;}
    .profile {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 6px;
      padding: 16px 18px 8px 18px;
      border-top: 1px solid #495057;
      background: #2d3238;
      min-height: 88px;
      position: relative;
      width: 100%;
    }
    .profile img {
      width: 38px; height: 38px;
      border-radius: 50%;
      object-fit: cover;
      background: #adb5bd;
      border: 2px solid #495057;
      flex-shrink: 0;
      transition: width 0.3s, height 0.3s;
    }
    .profile-info {
      display: flex;
      flex-direction: column;
      min-width: 0;
      align-items: center;
      width: 100%;
    }
    .profile-info .profile-name {
      font-size: 15px;
      font-weight: bold;
      color: #fff;
      white-space: nowrap;
      text-overflow: ellipsis;
      overflow: hidden;
      max-width: 120px;
      text-align: center;
    }
    .profile-info .profile-mail {
      font-size: 12px;
      color: #adb5bd;
      white-space: nowrap;
      text-overflow: ellipsis;
      overflow: hidden;
      max-width: 120px;
      text-align: center;
    }
    .profile-clock {
      width: 100%;
      text-align: center;
      padding: 2px 0 0 0;
      font-size: 1.02em;
      color: #a6e0ff;
      font-family: 'Segoe UI Mono', Consolas, monospace;
      letter-spacing: 1px;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      margin-top: 0.18em;
      margin-bottom: 0.2em;
      transition: font-size 0.2s;
      white-space: nowrap;
      overflow: hidden;
      min-height: 1.3em;
      background: none;
      z-index: 1;
    }
    .profile-clock .profile-clock-icon { display: inline-block; }
    .sidebar.collapsed .profile-info { display: none; }
    .sidebar.collapsed .profile { padding: 16px 0 6px 0;}
    .sidebar.collapsed .profile img { width: 34px; height: 34px; }
    .sidebar.collapsed .profile-clock { font-size: 0.97em; padding: 0; }
    .sidebar.collapsed .profile-clock .profile-clock-icon { display: none; }
    .profile-clock span { display: inline-block; min-width: 55px; text-align: left; }
    .sidebar-scroll {
      flex: 1 1 auto;
      min-height: 0;
      overflow-y: auto;
      overflow-x: hidden;
      scrollbar-width: thin;
      scrollbar-color: #7da2e3 #22272b;
    }
    .sidebar-scroll::-webkit-scrollbar { width: 7px; background: #22272b; }
    .sidebar-scroll::-webkit-scrollbar-thumb { background: #7da2e3; border-radius: 4px; }
    .menu { list-style: none; padding: 0; margin: 0;}
    .menu-item, .menu-link {
      display: flex; align-items: center; padding: 13px 18px;
      color: #adb5bd; text-decoration: none; font-size: 16px;
      transition: background-color 0.3s; cursor: pointer;
      user-select: none; outline: none;
      background: none; border: none;
    }
    .menu-link.active, .menu-item.active,
    .menu-link:focus, .menu-item:focus {
      background: #007bff; color: #fff;
    }
    .menu-item:hover, .menu-link:hover { background: #495057; color: #fff;}
    .menu-item i, .menu-link i { margin-right: 15px; font-size: 1.2em; min-width: 23px; text-align: center;}
    .sidebar.collapsed .menu-item i, .sidebar.collapsed .menu-link i { margin-right: 0; }
    .menu-item span, .menu-link span { white-space: nowrap; overflow: hidden; text-overflow: ellipsis;}
    .sidebar.collapsed .menu-item span, .sidebar.collapsed .menu-link span { display: none; }
    .submenu {
      list-style: none; padding: 0; margin: 0; display: none; background: #495057;
      transition: max-height 0.3s, opacity 0.22s;
      opacity: 0; max-height: 0;
    }
    .submenu.open {
      display: block; opacity: 1; max-height: 500px;
      animation: fadeInMenu 0.22s;
    }
    @keyframes fadeInMenu { from {opacity:0; transform:translateY(-10px);} to {opacity:1; transform:none;} }
    .submenu-item {
      padding: 10px 36px; color: #adb5bd; text-decoration: none; font-size: 14px;
      transition: background 0.3s; cursor: pointer; display: block;
      border-left: 2px solid transparent;
    }
    .submenu-item:hover, .submenu-item.active {
      background: #6c757d; color: #fff; border-left: 2px solid #007bff;
    }
    .logout-fixed {
      flex-shrink: 0;
      position: relative;
      border-top: 1px solid #495057;
      background: #2d3238;
      z-index: 2;
    }
    .logout-fixed .menu-link {
      padding: 14px 18px;
      color: #adb5bd;
      display: flex; align-items: center;
      background: none;
    }
    .logout-fixed .menu-link:hover {
      background: #495057; color: #fff;
    }
    .sidebar.collapsed .logout-fixed .menu-link span { display: none; }
    .sidebar.collapsed .logout-fixed .menu-link { justify-content: center; }
    .toggle-btn-minimal {
      position: fixed;
      top: 28px;
      left: 200px;
      width: 28px;
      height: 28px;
      padding: 0;
      background: none;
      color: #007bff;
      border: none;
      outline: none;
      cursor: pointer;
      z-index: 1100;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: left 0.3s, color 0.2s;
    }
    .toggle-btn-minimal:hover { color: #0056b3; }
    .toggle-btn-minimal i { font-size: 1.5em; }
    .sidebar.collapsed ~ .toggle-btn-minimal { left: 68px; }
    .sidebar-overlay {
      position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
      background: rgba(0,0,0,0.2); z-index: 999; display: none;
    }
    .sidebar.open-mobile ~ .sidebar-overlay { display: block; }
    .sidebar hr { border-color: #555; margin: 10px 0; }
    .submenu-arrow { margin-left: auto; transition: transform 0.2s; }
    .submenu-arrow.open { transform: rotate(90deg); }
    .menu-item:focus-visible, .menu-link:focus-visible { outline: 2px solid #007bff; }
    /* Loader Overlay */
    #loaderOverlay {
      display: none;
      position: fixed;
      z-index: 20000;
      inset: 0;
      background: rgba(30,34,60,0.16);
      justify-content: center;
      align-items: center;
    }
    .loader-container {
      background: #fff;
      border-radius: 1.5rem;
      box-shadow: 0 6px 34px 0 rgba(67, 56, 202, 0.14);
      padding: 2.2rem 2.7rem;
      min-width: 340px;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 1.2rem;
      position: relative;
    }
    .logo-spinner-wrapper {
      position: relative;
      width: 144px;
      height: 144px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 1.3rem;
    }
    .loader-logo {
      width: 100px;
      height: 100px;
      object-fit: contain;
      border-radius: 50%;
      background: #fff;
      position: absolute;
      left: 50%; top: 50%;
      transform: translate(-50%, -50%);
      box-shadow: 0 2px 10px #8883;
      z-index: 2;
      animation: pulseLogo 1.2s infinite alternate;
    }
    @keyframes pulseLogo {
      0% { box-shadow: 0 2px 10px #8882; transform: translate(-50%, -50%) scale(1);}
      100% { box-shadow: 0 6px 28px #4538b555; transform: translate(-50%, -50%) scale(1.07);}
    }
    .progress-spinner {
      position: absolute;
      left: 0; top: 0;
      width: 144px;
      height: 144px;
      z-index: 1;
      transform: rotate(-90deg);
    }
    .progress-spinner__circle-bg {
      stroke: #e0e7ff;
      stroke-width: 8;
    }
    .progress-spinner__circle {
      stroke: #4538b5;
      stroke-width: 8;
      stroke-linecap: round;
      stroke-dasharray: 410;
      stroke-dashoffset: 410;
      animation: dashdraw 1.2s cubic-bezier(.65,.05,.36,1) forwards, dashspin 1.0s linear 1.2s infinite;
      transform-origin: 50% 50%;
    }
    @keyframes dashdraw {
      0% { stroke-dashoffset: 410; }
      100% { stroke-dashoffset: 0; }
    }
    @keyframes dashspin {
      0% { transform: rotate(0deg);}
      100% { transform: rotate(360deg);}
    }
    .loader-title {
      font-size: 1.45rem;
      font-weight: 900;
      color: #4538b5;
      letter-spacing: .5px;
      text-align: center;
    }
    .loader-text {
      font-size: 1.07rem;
      color: #232323;
      text-align: center;
    }
    @media (max-width: 700px) {
      .sidebar {width: 95vw; min-width: 0; max-width: 95vw;}
      .sidebar.collapsed {width: 58px;}
      .toggle-btn-minimal { left: 95vw; top: 18px;}
      .sidebar.collapsed ~ .toggle-btn-minimal { left: 58px;}
      .profile-info .profile-name, .profile-info .profile-mail { max-width: 60vw; }
      .profile-clock { font-size: 1em; }
      .loader-container { min-width: unset; padding: 1.3rem 0.6rem;}
      .logo-spinner-wrapper { width: 96px; height: 96px; }
      .loader-logo { width: 60px; height: 60px; }
      .progress-spinner { width: 96px; height: 96px; }
    }
  </style>
</head>
<body>
  <!-- Loader Overlay para transición entre páginas -->
  <div id="loaderOverlay">
    <div class="loader-container">
      <div class="logo-spinner-wrapper">
        <svg class="progress-spinner" viewBox="0 0 144 144">
          <circle class="progress-spinner__circle-bg" cx="72" cy="72" r="65" fill="none"/>
          <circle class="progress-spinner__circle" cx="72" cy="72" r="65" fill="none"/>
        </svg>
        <img src="/proyecto/inicio/img/logo-sapnna.png" alt="Logo Institución" class="loader-logo" />
      </div>
      <div class="loader-title">Cambiando de módulo...</div>
      <div class="loader-text">Por favor espera mientras se carga la página</div>
    </div>
  </div>
  <div class="sidebar collapsed" id="sidebar" role="navigation" aria-label="Menú principal">
    <div class="logo">
      <img src="/proyecto/inicio/img/logo-sapnna.png" alt="Logo institucional" draggable="false">
    </div>
    <div class="profile" style="cursor:pointer" onclick="window.location.href='/proyecto/inicio/perfil_usuario.php'">
      <img src="<?= htmlspecialchars($avatar) ?>" alt="Avatar usuario" referrerpolicy="no-referrer">
      <div class="profile-info">
        <span class="profile-name"><?= htmlspecialchars($nombre), (' '), htmlspecialchars($apellido) ?></span>
        <span class="profile-mail"><?= htmlspecialchars($correo) ?></span>
        <span class="profile-mail"><i class="fas fa-user-shield me-1"></i><?= ucfirst($rol) ?></span>
      </div>
      <div class="profile-clock">
        <span class="profile-clock-icon"><i class="fas fa-clock"></i></span>
        <span id="sidebarClock">--:--</span>
      </div>
    </div>
    <div class="sidebar-scroll">
      <ul class="menu" style="padding-bottom:80px">
        <!-- Dashboard -->
        <li>
          <a href="/proyecto/inicio/index.php" class="menu-link" tabindex="0" data-bs-toggle="tooltip" data-bs-placement="right" title="Dashboard">
            <i class="fas fa-home"></i>
            <span>Dashboard</span>
          </a>
        </li>
        <!-- RRHH -->
        <li>
          <div class="menu-item" data-link="#" data-submenu="submenu-rrhh" tabindex="0" aria-haspopup="true" aria-expanded="false" aria-controls="submenu-rrhh" data-bs-toggle="tooltip" data-bs-placement="right" title="RRHH">
            <i class="fas fa-id-badge"></i>
            <span>RRHH</span>
            <i class="fas fa-chevron-right submenu-arrow"></i>
          </div>
          <ul class="submenu" id="submenu-rrhh" aria-label="RRHH">
            <li><a href="/proyecto/inicio/gestion/gestion_personal.php" class="submenu-item" data-bs-toggle="tooltip" data-bs-placement="right" title="Datos personales">Datos personales</a></li>
            <li><a href="/proyecto/inicio/gestion/gestion_socioeconomicos.php" class="submenu-item" data-bs-toggle="tooltip" data-bs-placement="right" title="Socioeconómicos">Socioeconómicos</a></li>
            <li><a href="/proyecto/inicio/gestion/gestion_carga.php" class="submenu-item" data-bs-toggle="tooltip" data-bs-placement="right" title="Carga familiar">Carga familiar</a></li>
            <li><a href="/proyecto/inicio/gestion/gestion_laboral.php" class="submenu-item" data-bs-toggle="tooltip" data-bs-placement="right" title="Datos laborales">Datos laborales</a></li>
            <li><a href="/proyecto/inicio/carga/carga_masiva_rrhh.php" class="submenu-item" data-bs-toggle="tooltip" data-bs-placement="right" title="Carga Masiva de Empleados">Carga Masiva de Empleados</a></li>
            <?php if ($rol === 'admin'): ?>
              <li><a href="/proyecto/inicio/gestion/gestion_contrato.php" class="submenu-item" data-bs-toggle="tooltip" data-bs-placement="right" title="Confg RRHH">Confg RRHH</a></li>
            <?php endif; ?>
          </ul>
        </li>
        <!-- ... el resto de tu menú igual ... -->
        <!-- Vacaciones -->
        <li>
          <div class="menu-item" data-link="#" data-submenu="submenu-vacaciones" tabindex="0" aria-haspopup="true" aria-expanded="false" aria-controls="submenu-vacaciones" data-bs-toggle="tooltip" data-bs-placement="right" title="Vacaciones">
            <i class="fas fa-umbrella-beach"></i>
            <span>Vacaciones</span>
            <i class="fas fa-chevron-right submenu-arrow"></i>
          </div>
          <ul class="submenu" id="submenu-vacaciones" aria-label="Vacaciones">
            <li><a href="/proyecto/inicio/vacaciones/gestion-vacaciones.php" class="submenu-item" data-bs-toggle="tooltip" data-bs-placement="right" title="Gestión">Gestión</a></li>
            <?php if ($rol === 'admin' || $rol === 'supervisor'): ?>
              <li><a href="/proyecto/inicio/vacaciones/gestion-periodos.php" class="submenu-item" data-bs-toggle="tooltip" data-bs-placement="right" title="Periodos">Periodos</a></li>
            <?php endif; ?>
          </ul>
        </li>
        <!-- Solicitudes -->
        <li>
          <div class="menu-item" data-link="#" data-submenu="submenu-solicitudes" tabindex="0" aria-haspopup="true" aria-expanded="false" aria-controls="submenu-solicitudes" data-bs-toggle="tooltip" data-bs-placement="right" title="Solicitudes">
            <i class="fas fa-envelope"></i>
            <span>Solicitudes</span>
            <i class="fas fa-chevron-right submenu-arrow"></i>
          </div>
          <ul class="submenu" id="submenu-solicitudes" aria-label="Solicitudes">
            <li><a href="/proyecto/inicio/reposos/gestion-reposos.php" class="submenu-item" data-bs-toggle="tooltip" data-bs-placement="right" title="Reposos">Reposos</a></li>
            <li><a href="/proyecto/inicio/constancia/generar-constancia.php" class="submenu-item" data-bs-toggle="tooltip" data-bs-placement="right" title="Constancia de trabajo">Constancia de trabajo</a></li>
          </ul>
        </li>
        <?php if ($rol === 'admin' || $rol === 'supervisor'): ?>
        <li>
          <a href="/proyecto/inicio/reportes/reportes.php" class="menu-link" tabindex="0" data-bs-toggle="tooltip" data-bs-placement="right" title="Reportes">
            <i class="fas fa-chart-pie"></i>
            <span>Reportes</span>
          </a>
        </li>
        <?php endif; ?>
        <!-- Cumpleaños -->
        <li>
          <a href="/proyecto/inicio/cumple/calendario.php" class="menu-link" tabindex="0" data-bs-toggle="tooltip" data-bs-placement="right" title="Cumpleaños">
            <i class="fas fa-birthday-cake"></i>
            <span>Cumpleaños</span>
          </a>
        </li>
        <!-- Archivero -->
        <li>
          <div class="menu-item" data-link="#" data-submenu="submenu-archivero" tabindex="0" aria-haspopup="true" aria-expanded="false" aria-controls="submenu-archivero" data-bs-toggle="tooltip" data-bs-placement="right" title="Archivero">
            <i class="fas fa-archive"></i>
            <span>Archivero</span>
            <i class="fas fa-chevron-right submenu-arrow"></i>
          </div>
          <ul class="submenu" id="submenu-archivero" aria-label="Archivero">
            <li><a href="/proyecto/inicio/Archivero/mostrar_archivos.php" class="submenu-item" data-bs-toggle="tooltip" data-bs-placement="right" title="Gestión de archivos">Gestión de archivos</a></li>
            <li><a href="/proyecto/inicio/Archivero/agregar_archivo.php" class="submenu-item" data-bs-toggle="tooltip" data-bs-placement="right" title="Agregar archivo">Agregar archivo</a></li>
            <?php if ($rol === 'admin'): ?>
              <li><a href="/proyecto/inicio/Archivero/ubicaciones/index.php" class="submenu-item" data-bs-toggle="tooltip" data-bs-placement="right" title="Confg Archivero">Confg Archivero</a></li>
            <?php endif; ?>
          </ul>
        </li>
        <?php if ($rol === 'admin'): ?>
        <li>
          <div class="menu-item" data-link="#" data-submenu="submenu-usuarios" tabindex="0" aria-haspopup="true" aria-expanded="false" aria-controls="submenu-usuarios" data-bs-toggle="tooltip" data-bs-placement="right" title="Usuarios">
            <i class="fas fa-users-cog"></i>
            <span>Usuarios</span>
            <i class="fas fa-chevron-right submenu-arrow"></i>
          </div>
          <ul class="submenu" id="submenu-usuarios" aria-label="Usuarios">
            <li><a href="/proyecto/gestion-usuarios.php" class="submenu-item" data-bs-toggle="tooltip" data-bs-placement="right" title="Gestión de usuarios">Gestión de usuarios</a></li>
            <li><a href="/proyecto/register-admin.php" class="submenu-item" data-bs-toggle="tooltip" data-bs-placement="right" title="Registrar">Registrar</a></li>
          </ul>
        </li>
        <li>
          <div class="menu-item" data-link="#" data-submenu="submenu-config" tabindex="0" aria-haspopup="true" aria-expanded="false" aria-controls="submenu-config" data-bs-toggle="tooltip" data-bs-placement="right" title="Configuración y Soporte">
            <i class="fas fa-cogs"></i>
            <span>Configuración y Soporte</span>
            <i class="fas fa-chevron-right submenu-arrow"></i>
          </div>
          <ul class="submenu" id="submenu-config" aria-label="Configuración y Soporte">
            <li><a href="/proyecto/soporte/logs_admin_cyber.php" class="submenu-item" data-bs-toggle="tooltip" data-bs-placement="right" title="Logs para acciones">Logs para acciones</a></li>
            <li><a href="/proyecto/soporte/session_logs.php" class="submenu-item" data-bs-toggle="tooltip" data-bs-placement="right" title="Logs de sesión">Logs de sesión</a></li>
            <li><a href="/proyecto/soporte/index.php" class="submenu-item" data-bs-toggle="tooltip" data-bs-placement="right" title="Respaldos de BD">Respaldos de la BD</a></li>
            <li><a href="/proyecto/soporte/restaurar_bd.php" class="submenu-item" data-bs-toggle="tooltip" data-bs-placement="right" title="Restauraciones de la BD">Restauraciones de la BD</a></li>
          </ul>
        </li>
        <?php endif; ?>
      </ul>
    </div>
    <div class="logout-fixed">
      <a href="/proyecto/logout.php" class="menu-link" tabindex="0" data-bs-toggle="tooltip" data-bs-placement="right" title="Logout">
        <i class="fas fa-sign-out-alt"></i>
        <span>Logout</span>
      </a>
    </div>
  </div>
  <button class="toggle-btn-minimal" id="toggle-btn" aria-label="Expandir/cerrar menú lateral">
    <i class="fas fa-chevron-right"></i>
  </button>
  <div class="sidebar-overlay" id="sidebar-overlay"></div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    const sidebar = document.getElementById('sidebar');
    const toggleBtn = document.getElementById('toggle-btn');
    const submenus = document.querySelectorAll('.submenu');
    const menuItems = document.querySelectorAll('.menu-item');
    const submenuArrows = document.querySelectorAll('.submenu-arrow');
    const overlay = document.getElementById('sidebar-overlay');
    const sidebarClock = document.getElementById('sidebarClock');
    const clockIcon = document.querySelector('.profile-clock-icon');

    // Hora dinámica en el sidebar
    function updateSidebarClock() {
      const now = new Date();
      const h = now.getHours().toString().padStart(2,'0');
      const m = now.getMinutes().toString().padStart(2,'0');
      const s = now.getSeconds().toString().padStart(2,'0');
      if (sidebar.classList.contains('collapsed')) {
        sidebarClock.textContent = h + ':' + m;
        clockIcon.style.display = 'none';
      } else {
        sidebarClock.textContent = h + ':' + m + ':' + s;
        clockIcon.style.display = 'inline-block';
      }
    }
    setInterval(updateSidebarClock, 1000);
    updateSidebarClock();

    // Toggle sidebar
    toggleBtn.addEventListener('click', () => {
      sidebar.classList.toggle('collapsed');
      toggleBtn.innerHTML = sidebar.classList.contains('collapsed')
        ? '<i class="fas fa-chevron-right"></i>'
        : '<i class="fas fa-chevron-left"></i>';
      if (sidebar.classList.contains('collapsed')) {
        submenus.forEach(submenu => submenu.classList.remove('open'));
        document.querySelectorAll('.submenu-arrow').forEach(arrow => arrow.classList.remove('open'));
        menuItems.forEach(item => item.setAttribute('aria-expanded', 'false'));
      }
      if (sidebar.classList.contains('collapsed')) {
        toggleBtn.style.left = window.innerWidth > 700 ? '68px' : '58px';
      } else {
        toggleBtn.style.left = window.innerWidth > 700 ? '200px' : '95vw';
      }
      updateSidebarClock();
    });

    menuItems.forEach(item => {
      item.addEventListener('click', function (e) {
        const submenuId = item.getAttribute('data-submenu');
        const submenu = submenuId ? document.getElementById(submenuId) : null;
        submenus.forEach(sm => { if(sm !== submenu) sm.classList.remove('open'); });
        document.querySelectorAll('.submenu-arrow').forEach(arrow => arrow.classList.remove('open'));
        menuItems.forEach(mi => mi.setAttribute('aria-expanded', 'false'));
        if (submenu) {
          e.stopPropagation();
          if (sidebar.classList.contains('collapsed')) {
            sidebar.classList.remove('collapsed');
            toggleBtn.innerHTML = '<i class="fas fa-chevron-left"></i>';
            setTimeout(() => {
              submenu.classList.add('open');
              item.querySelector('.submenu-arrow').classList.add('open');
              item.setAttribute('aria-expanded', 'true');
            }, 200);
            toggleBtn.style.left = window.innerWidth > 700 ? '200px' : '95vw';
          } else {
            const wasOpen = submenu.classList.contains('open');
            submenu.classList.toggle('open');
            item.querySelector('.submenu-arrow').classList.toggle('open', !wasOpen);
            item.setAttribute('aria-expanded', !wasOpen);
          }
        }
      });
      item.addEventListener('keydown', function(e) {
        if (e.key === "Enter" || e.key === " ") {
          item.click();
        }
      });
    });

    function handleMobileSidebar() {
      if (window.innerWidth <= 700) {
        sidebar.classList.add('open-mobile');
        overlay.style.display = 'block';
        toggleBtn.style.left = sidebar.classList.contains('collapsed') ? '58px' : '95vw';
      } else {
        sidebar.classList.remove('open-mobile');
        overlay.style.display = 'none';
        toggleBtn.style.left = sidebar.classList.contains('collapsed') ? '68px' : '200px';
      }
    }
    overlay.addEventListener('click', () => {
      sidebar.classList.add('collapsed');
      sidebar.classList.remove('open-mobile');
      overlay.style.display = 'none';
      toggleBtn.innerHTML = '<i class="fas fa-chevron-right"></i>';
      toggleBtn.style.left = window.innerWidth > 700 ? '68px' : '58px';
      updateSidebarClock();
    });
    window.addEventListener('resize', handleMobileSidebar);
    handleMobileSidebar();

    document.querySelectorAll('.menu-link, .submenu-item').forEach(link => {
      link.addEventListener('click', () => {
        if (window.innerWidth <= 700) {
          sidebar.classList.add('collapsed');
          sidebar.classList.remove('open-mobile');
          overlay.style.display = 'none';
          toggleBtn.innerHTML = '<i class="fas fa-chevron-right"></i>';
          toggleBtn.style.left = '58px';
          updateSidebarClock();
        }
      });
    });

    document.querySelector('.sidebar-scroll').addEventListener('wheel', function(e) {
      this.scrollTop += e.deltaY;
      e.preventDefault();
    });

    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl)
    })

    // Loader para transiciones entre módulos/páginas
    document.querySelectorAll('.menu-link, .submenu-item').forEach(function(link) {
      link.addEventListener('click', function(e) {
        if (
          link.href &&
          !link.hasAttribute('target') &&
          !link.href.startsWith('javascript:') &&
          !link.href.startsWith('#')
        ) {
          var loader = document.getElementById('loaderOverlay');
          loader.style.display = 'flex';
          var start = Date.now();
          // El navegador va a navegar, pero si la página carga MUY rápido, el loader se queda
          window.addEventListener('pageshow', function() {
            loader.style.display = 'none';
          });
          // Si la navegación es AJAX o SPA y quieres ocultarlo manualmente:
          setTimeout(function() {
            loader.style.display = 'none';
          }, 1500);
        }
      });
    });
    window.addEventListener('pageshow', function() {
      document.getElementById('loaderOverlay').style.display = 'none';
    });
  </script>
</body>
</html>