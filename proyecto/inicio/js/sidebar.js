// sidebar.js - Lógica de la barra lateral
document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.getElementById('sidebar');
    const triggerZone = document.createElement('div');
    triggerZone.className = 'sidebar-trigger';
    document.body.appendChild(triggerZone);

    // Expandir al entrar en la zona de trigger
    triggerZone.addEventListener('mouseenter', () => {
        sidebar.classList.add('sidebar-expanded');
    });

    // Retraer al salir de la barra
    sidebar.addEventListener('mouseleave', () => {
        sidebar.classList.remove('sidebar-expanded');
    });

    // Cerrar al hacer click fuera (mobile)
    document.addEventListener('click', (e) => {
        if (window.innerWidth <= 768 && 
            !sidebar.contains(e.target) && 
            !triggerZone.contains(e.target)) {
            sidebar.classList.remove('sidebar-expanded');
        }
    });
});
