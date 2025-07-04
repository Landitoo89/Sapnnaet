// Verificar carga de dependencias
if (typeof FullCalendar === 'undefined') {
    throw new Error('FullCalendar no está cargado correctamente');
}

document.addEventListener('DOMContentLoaded', () => {
    console.log('DOM cargado - Iniciando calendario');
    
    const calendarEl = document.getElementById('calendar');
    
    if (!calendarEl) {
        console.error('❌ Elemento #calendar no encontrado');
        return;
    }

    try {
        const calendar = new FullCalendar.Calendar(calendarEl, {
            locale: 'es',
            plugins: [FullCalendar.dayGridPlugin],
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,dayGridWeek,dayGridDay'
            },
            events: {
                url: 'birthdays.php',
                failure: () => {
                    console.error('Error cargando eventos');
                    // Mostrar eventos de prueba
                    calendar.addEvent({
                        title: 'Evento de prueba',
                        start: new Date(),
                        color: '#ff0000'
                    });
                }
            },
            eventDidMount: function(info) {
                console.log('Evento renderizado:', info.event.title);
            }
        });

        calendar.render();
        console.log('✅ Calendario renderizado:', calendar);

        // Forzar redraw después de 1 segundo
        setTimeout(() => calendar.updateSize(), 1000);

    } catch (error) {
        console.error('🔥 Error crítico:', error);
    }
});

// Verificar carga del archivo
console.log('Archivo calendario.js cargado correctamente');

// Agrega esto al final del script:
setTimeout(() => {
    console.log('Dimensiones calendario:', 
        calendarEl.offsetWidth, 'x', calendarEl.offsetHeight);
    calendar.updateSize();
}, 1000);