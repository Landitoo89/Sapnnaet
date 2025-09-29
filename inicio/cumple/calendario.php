<?php
session_start();
require $_SERVER['DOCUMENT_ROOT']."/proyecto/inicio/sidebar.php";
?>
<!DOCTYPE html>
<html lang='es' data-theme="light">
<head>
    <meta charset='utf-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1'>
    <title>Cumpleaños del Personal</title>
    <!-- Dependencias CSS -->
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css' rel='stylesheet'>
    <link href='https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css' rel='stylesheet'>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&family=Montserrat:wght@700&display=swap" rel="stylesheet">
    <style>
    :root {
        --primary: #e83e8c;
        --secondary: #6f42c1;
        --today: #20c997;
        --surface: rgba(255,255,255,0.8);
        --glass-bg: rgba(255,255,255,0.5);
        --glass-border: rgba(255,255,255,0.4);
        --font-title: 'Montserrat', 'Inter', system-ui, sans-serif;
        --font-text: 'Inter', system-ui, sans-serif;
        --shadow: 0 8px 32px 0 rgba(31,38,135,0.22);
        --radius: 18px;
        --page-bg: linear-gradient(120deg, #f5e9fa 0%, #e0ffe7 100%);
        --header-title: #e83e8c;
        --header-lead: #6c757d;
        --event-today-bg: linear-gradient(135deg, #e7ffe7 60%, #e0e7ff 100%);
        --event-today-border: #20c997;
        --modal-bg: var(--glass-bg);
        --modal-header-masculino: linear-gradient(90deg, #2196f3, #64b5f6);
        --modal-header-femenino: linear-gradient(90deg, #e83e8c, #ffb6e8);
        --modal-header-otro: linear-gradient(90deg, #20c997, #b2f7ef);
    }
    [data-theme="dark"] {
        --primary: #ff94e8;
        --secondary: #a899fa;
        --today: #4ee3c1;
        --surface: rgba(35,36,42,0.94);
        --glass-bg: rgba(35,36,42,0.82);
        --glass-border: rgba(100,100,100,0.13);
        --font-title: 'Montserrat', 'Inter', system-ui, sans-serif;
        --font-text: 'Inter', system-ui, sans-serif;
        --shadow: 0 8px 32px 0 rgba(10,12,30,0.32);
        --radius: 18px;
        --page-bg: linear-gradient(120deg, #1c1b22 0%, #17303a 100%);
        --header-title: #ff94e8;
        --header-lead: #b5b8c0;
        --event-today-bg: linear-gradient(135deg, #23242a 60%, #17303a 100%);
        --event-today-border: #4ee3c1;
        --modal-bg: var(--glass-bg);
        --modal-header-masculino: linear-gradient(90deg, #1565c0, #23242a);
        --modal-header-femenino: linear-gradient(90deg, #e83e8c, #23242a);
        --modal-header-otro: linear-gradient(90deg, #20c997, #23242a);
    }
    body {
        background: var(--page-bg);
        font-family: var(--font-text);
        min-height: 100vh;
        color: #232323;
    }
    [data-theme="dark"] body {
        color: #f5f6fa;
    }
    /* Botón modo oscuro/claro */
    .theme-toggle-btn {
        position: fixed;
        top: 18px;
        right: 18px;
        z-index: 1101;
        background: var(--surface);
        border: 1px solid #b5b8c0;
        color: #232323;
        border-radius: 50%;
        width: 44px;
        height: 44px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        box-shadow: 0 2px 10px #2222;
        transition: background 0.2s, color 0.2s;
    }
    .theme-toggle-btn:hover {
        background: var(--glass-bg);
        color: #bc519c;
    }
    header {
        margin-top: 25px;
    }
    #calendar {
        max-width: 1200px;
        margin: 0 auto 30px auto;
        padding: 18px;
        border-radius: var(--radius);
        background: var(--glass-bg);
        border: 1.5px solid var(--glass-border);
        box-shadow: var(--shadow);
        backdrop-filter: blur(7px);
    }
    .display-4 {
        font-family: var(--font-title);
        color: var(--header-title);
        letter-spacing: 1px;
    }
    .lead {
        font-family: var(--font-text);
        color: var(--header-lead);
    }
    /* FullCalendar Custom */
    .fc-daygrid-day.fc-day-today {
        background: var(--event-today-bg);
        border: 2.5px solid var(--event-today-border);
        box-shadow: 0 0 0 6px rgba(32,201,151,0.10) inset;
    }
    .fc-day-today .fc-daygrid-day-number {
        color: var(--primary);
        font-weight: 800;
        font-size: 1.05em;
        text-shadow: 0 2px 6px #fff;
    }
    .fc-col-header-cell {
        background: linear-gradient(90deg, var(--primary), var(--secondary));
        border: none;
        padding: 13px 0;
    }
    .fc-col-header-cell-cushion {
        color: white !important;
        font-weight: 700;
        font-family: var(--font-title);
        letter-spacing: 1.5px;
        font-size: 1.1em;
    }
    /* Estilos de eventos por género */
    .fc-event {
        border: none !important;
        border-radius: 13px !important;
        margin: 4px !important;
        color: white !important;
        padding: 10px 8px !important;
        box-shadow: 0 1.5px 6px 0 rgba(64, 18, 77, 0.10);
        transition: transform 0.24s;
        cursor: pointer;
        position: relative;
        overflow: visible;
        animation: fadeInUp 0.5s;
    }
    .fc-event:hover {
        transform: scale(1.035) translateY(-2px);
        box-shadow: 0 6px 24px -6px var(--primary);
        z-index: 10 !important;
    }
    .fc-event.evento-masculino {
        background: linear-gradient(90deg, #2196f3 60%, #64b5f6 100%) !important;
        color: #fff !important;
        border: 2px solid #1565c0 !important;
    }
    .fc-event.evento-femenino {
        background: linear-gradient(90deg, #e83e8c 60%, #ffb6e8 100%) !important;
        color: #fff !important;
        border: 2px solid #b10e5d !important;
    }
    .fc-event.evento-otro {
        background: linear-gradient(90deg, #20c997 60%, #b2f7ef 100%) !important;
        color: #fff !important;
        border: 2px solid #097969 !important;
    }
    .fc-event .edad-badge {
        background: rgba(255,255,255,0.86);
        color: var(--primary);
        border-radius: 10px;
        font-size: 0.87em;
        padding: 2px 8px;
        font-weight: 700;
        align-self: flex-start;
        box-shadow: 0 1px 6px rgba(232,62,140,0.09);
        margin-top: 2px;
    }
    .fc-event.evento-hoy {
        border: 2.5px solid var(--today) !important;
        background: linear-gradient(90deg, #28e7b7, #bafc4f 80%) !important;
        color: #222 !important;
        box-shadow: 0 6px 24px 0 rgba(32,201,151,0.13);
    }
    .fc-event.evento-hoy .edad-badge {
        background: var(--today);
        color: #fff;
    }
    .fc-event-main {
        position: relative;
    }
    .fc-event.evento-hoy .fc-event-main {
        padding-top: 8px;
    }
    .badge-hoy {
        position: absolute;
        top: -14px;
        right: 8px;
        background: #ffd700;
        color: #2d2d2d;
        font-size: 0.73em;
        padding: 3px 10px;
        border-radius: 7px;
        font-weight: 800;
        box-shadow: 0 3px 8px rgba(255,215,0,0.13);
        letter-spacing: 0.1em;
        border: 2px solid #fff;
        z-index: 20;
        animation: bounceIn 0.7s;
        pointer-events: none;
    }
    @keyframes fadeInUp {
        0% { opacity: 0; transform: translateY(20px);}
        100% { opacity: 1; transform: translateY(0);}
    }
    @keyframes bounceIn {
        0% { transform: scale(0.8);}
        80% { transform: scale(1.17);}
        100% { transform: scale(1);}
    }
    /* Modal Glass */
    .modal-content {
        border-radius: 25px;
        box-shadow: var(--shadow);
        background: var(--modal-bg);
        backdrop-filter: blur(12px);
        border: 1.5px solid var(--glass-border);
        animation: fadeInUp 0.5s;
        color: inherit;
    }
    .modal-header.masculino {
        background: var(--modal-header-masculino) !important;
        color: #fff !important;
    }
    .modal-header.femenino {
        background: var(--modal-header-femenino) !important;
        color: #fff !important;
    }
    .modal-header.otro {
        background: var(--modal-header-otro) !important;
        color: #fff !important;
    }
    .modal-title {
        font-family: var(--font-title);
        font-weight: 700;
        letter-spacing: 1px;
        color: #fff;
    }
    #btnGenerarImagen {
        background: linear-gradient(90deg, #20c997 60%, #e83e8c 100%);
        color: #fff;
        border: none;
        transition: all 0.3s;
        font-weight: 700;
        border-radius: 10px;
        letter-spacing: 1px;
        box-shadow: 0 1.5px 7px 0 rgba(32,201,151,0.11);
    }
    #btnGenerarImagen:hover {
        transform: scale(1.05);
        opacity: 0.93;
        box-shadow: 0 6px 24px 0 #20c99745;
    }
    .avatar-glass {
        width: 110px;
        height: 110px;
        background: linear-gradient(135deg, #fdf6ff, #e9ffe5 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 15px auto;
        box-shadow: 0 4px 18px 0 rgba(232,62,140,0.14);
        border: 3.5px solid #bfbfbf;
        font-size: 3.7em;
        color: var(--secondary);
        position: relative;
        transition: border-color 0.3s;
    }
    .avatar-masculino { border-color: #1e88e5 !important; }
    .avatar-femenino  { border-color: #e83e8c !important; }
    .avatar-otro      { border-color: #20c997 !important; }
    .empleado-nombre {
        font-family: var(--font-title);
        font-size: 1.25em;
        font-weight: 700;
        color: var(--primary);
    }
    .empleado-cargo {
        font-size: 1em;
        color: var(--secondary);
        font-weight: 600;
    }
    .empleado-depto {
        font-size: 0.97em;
        color: #888;
    }
    [data-theme="dark"] .empleado-depto {
        color: #b5b8c0;
    }
    .badge-info-glass {
        background: #f7e9f8;
        color: #a82b70;
        font-size: 0.92em;
        border-radius: 7px;
        padding: 2px 10px;
        font-weight: 700;
        margin-right: 5px;
        margin-bottom: 3px;
        display: inline-block;
    }
    [data-theme="dark"] .badge-info-glass {
        background: #23242a;
        color: #ff94e8;
    }
    dl.row {
        margin-top: 6px;
    }
    dt {
        font-weight: 700;
        color: var(--primary);
        font-size: 0.97em;
    }
    dd {
        color: #343a40;
        font-size: 0.97em;
        margin-bottom: .7em;
        font-weight: 500;
    }
    [data-theme="dark"] dd {
        color: #f5f6fa;
    }
    @media (max-width: 768px) {
        .modal-dialog { max-width: 98vw; }
        #calendar { padding: 5px;}
    }
    </style>
</head>
<body>
<!-- BOTÓN MODO OSCURO/MODO CLARO -->
<button class="theme-toggle-btn" id="themeToggleBtn" title="Cambiar modo" aria-label="Cambiar modo claro/oscuro">
    <i id="themeToggleIcon" class="bi bi-moon"></i>
</button>

    <div class="container">
        <header class="text-center mb-5">
            <h1 class="display-4 fw-bold mb-3 animate__animated animate__fadeInDown">
                <i class="bi bi-balloon-heart-fill me-2"></i>
                Cumpleaños del Personal
            </h1>
            <p class="lead">Celebremos juntos estos días especiales</p>
        </header>
        <div id="calendar"></div>
    </div>

    <!-- Modal Detalles -->
    <div class="modal fade" id="empleadoModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-person-badge me-2"></i>Detalles del Empleado</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="modalContent">
                    <!-- Dinámico -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn" id="btnGenerarImagen">
                        <i class="bi bi-image me-2"></i>
                        Generar Imagen
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Dependencias JavaScript -->
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.17/index.global.min.js'></script>
    <script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js'></script>
    <script>
        // Botón tema claro/oscuro
        document.addEventListener('DOMContentLoaded', () => {
            // Theme: mantener preferencia y actualizar icono
            var themeToggleBtn = document.getElementById('themeToggleBtn');
            var themeToggleIcon = document.getElementById('themeToggleIcon');
            var htmlTag = document.documentElement;

            // Al cargar, lee la preferencia
            let theme = localStorage.getItem('theme') || 'light';
            htmlTag.setAttribute('data-theme', theme);
            themeToggleIcon.className = theme === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon';

            themeToggleBtn.addEventListener('click', function() {
                let current = htmlTag.getAttribute('data-theme');
                let next = current === 'dark' ? 'light' : 'dark';
                htmlTag.setAttribute('data-theme', next);
                localStorage.setItem('theme', next);
                themeToggleIcon.className = next === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon';
            });

            // FullCalendar (el resto igual)
            const calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
                locale: 'es',
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,dayGridWeek,dayGridDay'
                },
                buttonText: {
                    today: 'Hoy',
                    month: 'Mes',
                    week: 'Semana',
                    day: 'Día',
                    list: 'Lista'
                },
                events: {
                    url: 'birthdays.php',
                    failure: () => console.error('Error al cargar eventos')
                },
                eventContent: (arg) => {
                    const edad = arg.event.extendedProps?.edad ?? '';
                    return {
                        html: `
                            <div class="fc-event-main animate__animated animate__fadeIn">
                                <div class="fc-event-title"><i class="bi bi-cake2 me-1"></i>${arg.event.title}</div>
                                ${edad ? `<div class="edad-badge"><i class="bi bi-gift-fill me-1"></i>${edad} años</div>` : ''}
                            </div>
                        `
                    };
                },
                eventDidMount: (info) => {
                    // Asignar clase por género
                    const genero = (info.event.extendedProps?.genero ?? '').toLowerCase();
                    if (genero === 'masculino') info.el.classList.add('evento-masculino');
                    else if (genero === 'femenino') info.el.classList.add('evento-femenino');
                    else info.el.classList.add('evento-otro');
                    // HOY
                    const hoy = new Date().toLocaleDateString('en-CA');
                    const eventDate = info.event.startStr;
                    if (eventDate === hoy) {
                        info.el.classList.add('evento-hoy');
                        const badge = document.createElement('span');
                        badge.className = 'badge-hoy animate__animated animate__bounceIn';
                        badge.textContent = '¡HOY!';
                        const eventMain = info.el.querySelector('.fc-event-main');
                        if (eventMain) {
                            eventMain.style.position = 'relative';
                            eventMain.appendChild(badge);
                        }
                    }
                },
                eventClick: async (info) => {
                    const modal = new bootstrap.Modal('#empleadoModal');
                    const idPers = info.event.id;
                    try {
                        const response = await fetch(`get_employee_details.php?id_pers=${idPers}`);
                        const data = await response.json();

                        // Determinar clases dinámicas según género
                        const genero = (data.genero ?? '').toLowerCase();
                        let avatarClass = 'avatar-otro', headerClass = 'otro';
                        if (genero === 'masculino') { avatarClass = 'avatar-masculino'; headerClass = 'masculino'; }
                        else if (genero === 'femenino') { avatarClass = 'avatar-femenino'; headerClass = 'femenino'; }

                        // Avatar con clase dinámica
                        const avatarHtml = `
                            <div class="avatar-glass ${avatarClass}">
                                <i class="bi bi-person-circle"></i>
                                <span class="position-absolute bottom-0 end-0 translate-middle badge rounded-pill bg-warning text-dark" style="font-size:1em;box-shadow:0 2px 6px #fff3;">${data.edad ?? ''}</span>
                            </div>
                        `;
                        // Badges destacados (correo, depto, etc.)
                        const badges = [
                            data.correo_institucional ? `<span class="badge-info-glass"><i class="bi bi-envelope"></i> ${data.correo_institucional}</span>` : '',
                            data.departamento ? `<span class="badge-info-glass"><i class="bi bi-diagram-3"></i> ${data.departamento}</span>` : '',
                            data.cargo ? `<span class="badge-info-glass"><i class="bi bi-briefcase"></i> ${data.cargo}</span>` : ''
                        ].join(' ');

                        const contenido = `
                            <div class="row">
                                <div class="col-md-4 text-center mb-4 mb-md-0">
                                    ${avatarHtml}
                                    <div class="empleado-nombre">${data.nombres} ${data.apellidos}</div>
                                    <div class="empleado-cargo">${data.cargo ?? ''}</div>
                                    <div class="empleado-depto">${data.departamento ?? ''}</div>
                                    <div class="mt-3">${badges}</div>
                                </div>
                                <div class="col-md-8">
                                    <dl class="row">
                                        <dt class="col-sm-4">Cédula:</dt>
                                        <dd class="col-sm-8">${data.cedula_identidad ?? 'N/A'}</dd>
                                        <dt class="col-sm-4">Fecha Nac.:</dt>
                                        <dd class="col-sm-8">${data.fecha_nacimiento ?? 'N/A'}</dd>
                                        <dt class="col-sm-4">Edad:</dt>
                                        <dd class="col-sm-8">${data.edad} años</dd>
                                        <dt class="col-sm-4">Género:</dt>
                                        <dd class="col-sm-8">${data.genero ?? 'N/A'}</dd>
                                        <dt class="col-sm-4">Nacionalidad:</dt>
                                        <dd class="col-sm-8">${data.nacionalidad ?? 'N/A'}</dd>
                                        <dt class="col-sm-4">Correo Personal:</dt>
                                        <dd class="col-sm-8">${data.correo_electronico ?? 'N/A'}</dd>
                                        <dt class="col-sm-4">Teléfono:</dt>
                                        <dd class="col-sm-8">${data.telefono_contacto ?? 'N/A'}</dd>
                                        <dt class="col-sm-4">Dirección:</dt>
                                        <dd class="col-sm-8">${data.direccion ?? 'N/A'}</dd>
                                    </dl>
                                </div>
                            </div>
                        `;
                        document.getElementById('modalContent').innerHTML = contenido;
                        const modalHeader = document.querySelector('#empleadoModal .modal-header');
                        modalHeader.classList.remove('masculino','femenino','otro');
                        modalHeader.classList.add(headerClass);
                        modal.show();

                        document.getElementById('btnGenerarImagen').onclick = async () => {
                            try {
                                const imgResponse = await fetch(`generar_imagen.php?id_pers=${idPers}`);
                                if (!imgResponse.ok) throw new Error(`HTTP error! status: ${imgResponse.status}`);
                                const blob = await imgResponse.blob();
                                const url = window.URL.createObjectURL(blob);
                                const a = document.createElement('a');
                                a.href = url;
                                a.download = `cumple_${idPers}.jpg`;
                                document.body.appendChild(a);
                                a.click();
                                window.URL.revokeObjectURL(url);
                                document.body.removeChild(a);
                            } catch (error) {
                                console.error('Error:', error);
                                alert('Error al generar la imagen: ' + error.message);
                            }
                        };
                    } catch (error) {
                        document.getElementById('modalContent').innerHTML = 'Error al cargar los datos';
                        modal.show();
                    }
                }
            });
            calendar.render();
        });
    </script>
</body>
</html>