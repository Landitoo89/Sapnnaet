/**
 * Funciones reutilizables para gestión de elementos
 */

// Búsqueda en tiempo real
function setupSearch(inputId, tableBodyId) {
    document.getElementById(inputId)?.addEventListener('input', function() {
        const filter = this.value.toLowerCase();
        const rows = document.querySelectorAll(`#${tableBodyId} tr`);
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
    });
}

// Filtrado por atributo (ej: rol, tipo, categoría)
function setupFilter(buttonsClass, filterAttribute) {
    document.querySelectorAll(`.${buttonsClass}`).forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll(`.${buttonsClass}`).forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            const filter = this.dataset.filter;
            const rows = document.querySelectorAll('#userTableBody tr');
            
            rows.forEach(row => {
                if (filter === 'all') {
                    row.style.display = '';
                } else {
                    row.style.display = row.dataset[filterAttribute] === filter ? '' : 'none';
                }
            });
        });
    });
}

// Confirmación para acciones críticas
function setupFormConfirmations(formSelector) {
    document.querySelectorAll(formSelector).forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!confirm('¿Estás seguro de realizar esta acción?')) {
                e.preventDefault();
            }
        });
    });
}

// Inicialización por defecto (puedes personalizar según necesidad)
document.addEventListener('DOMContentLoaded', function() {
    setupSearch('searchInput', 'userTableBody');
    setupFilter('filter-btn', 'role');
    setupFormConfirmations('form[method="POST"]');
});