$(document).ready(function() {
    // Cargar datos iniciales
    loadInitialLocation();

    // Manejar cambios en los selectores
    $('#edificio').change(loadPisos);
    $('#piso').change(loadOficinas);
    $('#oficina').change(loadEstantes);
    $('#estante').change(loadCajones);

    function loadInitialLocation() {
        if (currentLocation.edificio_id > 0) {
            loadOptions('edificio', 'get_edificios.php', () => {
                setSelected('edificio', currentLocation.edificio_id, loadPisos);
            });
        }
    }

    function loadPisos() {
        const edificioId = $('#edificio').val();
        if (edificioId > 0) {
            loadOptions('piso', `get_pisos.php?edificio_id=${edificioId}`, () => {
                setSelected('piso', currentLocation.piso_id, loadOficinas);
            });
        }
    }

    function loadOficinas() {
        const pisoId = $('#piso').val();
        if (pisoId > 0) {
            loadOptions('oficina', `get_oficinas.php?piso_id=${pisoId}`, () => {
                setSelected('oficina', currentLocation.oficina_id, loadEstantes);
            });
        }
    }

    function loadEstantes() {
        const oficinaId = $('#oficina').val();
        if (oficinaId > 0) {
            loadOptions('estante', `get_estantes.php?oficina_id=${oficinaId}`, () => {
                setSelected('estante', currentLocation.estante_id, loadCajones);
            });
        }
    }

    function loadCajones() {
        const estanteId = $('#estante').val();
        if (estanteId > 0) {
            loadOptions('cajon', `get_cajones.php?estante_id=${estanteId}`, () => {
                setSelected('cajon', currentLocation.cajon_id);
            });
        }
    }

    function loadOptions(target, url, callback) {
        $(`#${target}`).prop('disabled', true);
        $.get(url)
            .done(data => {
                $(`#${target}`).html(data).prop('disabled', false);
                if (callback) callback();
            })
            .fail(() => {
                $(`#${target}`).html(`<option value="">Error cargando ${target}</option>`);
            });
    }

    function setSelected(selector, value, nextFunction) {
        if (value > 0) {
            $(`#${selector} option[value="${value}"]`).prop('selected', true);
            if (nextFunction) nextFunction();
        }
    }
});