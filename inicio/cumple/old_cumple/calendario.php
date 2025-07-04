<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cumpleaños Empleados</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href='https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.8/main.min.css' rel='stylesheet' />
    <link href='https://cdn.jsdelivr.net/npm/@fullcalendar/daygrid@6.1.8/main.min.css' rel='stylesheet' />
</head>
<body>
    <div class="container mt-4">
        <h2 class="mb-4">Cumpleaños de Empleados</h2>
        <div id="calendar"></div>
    </div>

    <!-- Modal para detalles -->
    <div class="modal fade" id="detallesModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalles del Cumpleaños</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p id="detallesTexto"></p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src='https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.8/main.min.js'></script>
    <script src='https://cdn.jsdelivr.net/npm/@fullcalendar/daygrid@6.1.8/main.min.js'></script>
    <script src='https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.8/locales/es.global.min.js'></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                locale: 'es',
                initialView: 'dayGridMonth',
                events: 'birthdays.php',
                eventClick: function(info) {
                    document.getElementById('detallesTexto').textContent = 
                        `${info.event.title} - ${info.event.start.toLocaleDateString('es-ES')}`;
                    new bootstrap.Modal(document.getElementById('detallesModal')).show();
                },
                eventDidMount: function(info) {
                    info.el.querySelector('.fc-event-title').innerHTML = 
                        '<i class="bi bi-gift"></i> ' + info.event.title;
                }
            });
            calendar.render();
        });
    </script>
</body>
</html>