<?php
ob_start();
session_start();
date_default_timezone_set('America/Caracas');
require $_SERVER['DOCUMENT_ROOT']."/proyecto/inicio/sidebar.php";
?>
<!DOCTYPE html>
<html lang="es" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cargando...</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-main: #eef1f6;
            --bg-gradient: linear-gradient(120deg, #eef1f6 0%, #dbe6f6 100%);
            --text-main: #232323;
            --loader-accent: #4538b5;
            --loader-bg: #fff;
            --loader-shadow: 0 6px 34px 0 rgba(67, 56, 202, 0.14);
            --loader-ring-bg: #e0e7ff;
            --loader-ring-accent: #bc519c;
        }
        [data-theme="dark"] {
            --bg-main: #15171c;
            --bg-gradient: linear-gradient(120deg, #23242a 0%, #191c22 100%);
            --text-main: #f4f6fb;
            --loader-accent: #8c7be7;
            --loader-bg: #23242a;
            --loader-shadow: 0 8px 30px 0 rgba(35,34,58,0.22);
            --loader-ring-bg: #23242a;
            --loader-ring-accent: #ff94e8;
        }
        html, body {
            font-family: 'Inter', system-ui, Arial, sans-serif;
            background: var(--bg-gradient) fixed;
            color: var(--text-main);
            min-height: 100vh;
        }
        #main-content {
            margin-left: var(--sidebar-collapsed, 75px);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            transition: margin-left 0.3s cubic-bezier(.86,0,.07,1);
        }
        .loader-container {
            background: var(--loader-bg);
            border-radius: 1.5rem;
            box-shadow: var(--loader-shadow);
            padding: 2.2rem 2.7rem;
            min-width: 370px;
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
            /* Un pequeño "pulse" */
            animation: pulseLogo 1.2s infinite alternate;
        }
        @keyframes pulseLogo {
            0% { box-shadow: 0 2px 10px #8882; transform: translate(-50%, -50%) scale(1);}
            100% { box-shadow: 0 6px 28px #4538b555; transform: translate(-50%, -50%) scale(1.07);}
        }
        /* SVG spinner */
        .progress-spinner {
            position: absolute;
            left: 0; top: 0;
            width: 144px;
            height: 144px;
            z-index: 1;
            transform: rotate(-90deg); /* para que empiece arriba */
        }
        .progress-spinner__circle-bg {
            stroke: var(--loader-ring-bg);
            stroke-width: 8;
        }
        .progress-spinner__circle {
            stroke: var(--loader-accent);
            stroke-width: 8;
            stroke-linecap: round;
            stroke-dasharray: 410;
            stroke-dashoffset: 410;
            animation: dashdraw 1.2s cubic-bezier(.65,.05,.36,1) forwards, dashspin 1.0s linear 1.2s infinite;
            /* dashdraw: forma el círculo, dashspin: gira el círculo completo */
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
            color: var(--loader-accent);
            letter-spacing: .5px;
            text-align: center;
        }
        .loader-text {
            font-size: 1.07rem;
            color: var(--text-main);
            text-align: center;
        }
        @media (max-width: 500px) {
            .loader-container { min-width: unset; padding: 1.3rem 0.6rem;}
            #main-content { padding: 0; }
            .logo-spinner-wrapper { width: 96px; height: 96px; }
            .loader-logo { width: 60px; height: 60px; }
            .progress-spinner { width: 96px; height: 96px; }
        }
    </style>
</head>
<body>
<button class="theme-toggle-btn" id="themeToggleBtn" title="Cambiar modo" aria-label="Cambiar modo claro/oscuro" style="position:fixed; top:18px; right:18px; z-index:1101;">
    <i id="themeToggleIcon" class="fas fa-moon"></i>
</button>
<div id="main-content">
    <div class="loader-container">
        <div class="logo-spinner-wrapper">
            <!-- SVG spinner animado -->
            <svg class="progress-spinner" viewBox="0 0 144 144">
                <circle class="progress-spinner__circle-bg" cx="72" cy="72" r="65" fill="none"/>
                <circle class="progress-spinner__circle" cx="72" cy="72" r="65" fill="none"/>
            </svg>
            <!-- Imagen de la institución -->
            <img src="img/logo-sapnna.png" alt="Logo Institución" class="loader-logo" />
        </div>
        <div class="loader-title">
            Cargando...
        </div>
        <div class="loader-text">Por favor espera un momento</div>
    </div>
</div>
<script>
    // Igual que en el dashboard
    var themeToggleBtn = document.getElementById('themeToggleBtn');
    var themeToggleIcon = document.getElementById('themeToggleIcon');
    var htmlTag = document.documentElement;
    let theme = localStorage.getItem('theme') || 'light';
    htmlTag.setAttribute('data-theme', theme);
    themeToggleIcon.className = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
    themeToggleBtn.addEventListener('click', function() {
        let current = htmlTag.getAttribute('data-theme');
        let next = current === 'dark' ? 'light' : 'dark';
        htmlTag.setAttribute('data-theme', next);
        localStorage.setItem('theme', next);
        themeToggleIcon.className = next === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php ob_end_flush(); ?>