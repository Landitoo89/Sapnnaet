<!DOCTYPE html>
<html lang="es" data-theme="light">
<head>
    <meta charset="UTF-8">
    <title>Carga Masiva RRHH</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background: linear-gradient(120deg,#eaf0fc 0%,#f9f9ff 100%) fixed;
            font-family: 'Inter', Arial, sans-serif;
        }
        .panel-carga {
            background: rgba(255,255,255,0.97);
            border-radius: 18px;
            box-shadow: 0 4px 32px rgba(44,62,80,0.13), 0 1.5px 6px rgba(100,100,160,0.09);
            padding: 2.5rem 2.5rem 2rem 2.5rem;
            margin-top: 3rem;
            transition: box-shadow 0.22s, transform 0.22s;
            position: relative;
            overflow: hidden;
            max-width: 870px;
        }
        .panel-carga:hover {
            box-shadow: 0 10px 40px rgba(44,62,80,0.18), 0 3px 16px rgba(100,100,160,0.17);
            transform: translateY(-3px) scale(1.012);
        }
        .panel-titulo {
            color: #4238b5;
            font-weight: 900;
            font-size: 2.1rem;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            letter-spacing: 0.5px;
        }
        .panel-titulo i {
            color: #4f6cf7;
            font-size: 1.7em;
            filter: drop-shadow(0 1px 3px #4f6cf736);
        }
        .form-label {
            font-weight: 700;
            color: #3d3d3d;
            letter-spacing: 0.1px;
        }
        .form-select, .form-control {
            border-radius: 0.85rem;
            font-size: 1.04rem;
            border: 1.7px solid #e0e7ef;
            box-shadow: none;
            transition: border 0.22s;
            background: #fff;
            color: #232323;
        }
        .form-select:focus, .form-control:focus {
            border: 1.7px solid #5f8eff;
            box-shadow: 0 0 0 0.12rem #7f8fff33;
        }
        #btnCargaMasiva {
            background: linear-gradient(90deg,#4238b5 0%,#4f6cf7 100%);
            border: none;
            border-radius: 1.1rem;
            padding: 0.7em 2.1em;
            font-size: 1.09rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 16px 0 rgba(80,100,200,0.13);
            color: #fff;
            margin-top: 0.7em;
            transition: background 0.18s, box-shadow 0.18s, transform 0.19s;
        }
        #btnCargaMasiva:hover, #btnCargaMasiva:focus {
            background: linear-gradient(90deg,#4f6cf7 0%,#4238b5 100%);
            box-shadow: 0 8px 32px 0 rgba(80,100,200,0.21);
            transform: scale(1.016);
        }
        .ejemplo-titulo {
            color: #314a7b;
            font-size: 1.08rem;
            font-weight: 700;
            margin-bottom: 0.2em;
        }
        .ejemplo-csv {
            background: #f7fafd;
            border-left: 5px solid #4f6cf7;
            border-radius: 0 0 13px 13px;
            box-shadow: 0 1px 6px rgba(80,100,200,0.07);
            padding: 1.1em 1.5em 1.2em 1.3em;
            font-size: 1.01rem;
            color: #314a7b;
            margin-bottom: 1.2em;
        }
        @media (max-width: 600px) {
            .panel-carga {padding: 1.2rem 0.5rem;}
            .panel-titulo {font-size: 1.2rem;}
            .ejemplo-csv {font-size: 0.97rem;}
        }
        /* MODO OSCURO */
        [data-theme="dark"] body {
            background: linear-gradient(120deg,#181924 0%,#23242a 100%) fixed;
            color: #e2e7ef;
        }
        [data-theme="dark"] .panel-carga {
            background: rgba(35,36,42,0.98);
            box-shadow: 0 4px 28px rgba(80,100,200,0.14), 0 1.5px 7px rgba(60,60,120,0.19);
        }
        [data-theme="dark"] .panel-titulo,
        [data-theme="dark"] .panel-titulo i {
            color: #ff94e8 !important;
        }
        [data-theme="dark"] .form-label {
            color: #bab8fa;
        }
        [data-theme="dark"] .form-select,
        [data-theme="dark"] .form-control {
            background: #181b20 !important;
            color: #e2e7ef !important;
            border-color: #393b3f !important;
        }
        [data-theme="dark"] .form-select:focus, [data-theme="dark"] .form-control:focus {
            border-color: #8c7be7 !important;
            box-shadow: 0 0 0 0.12rem #5f8eff33;
        }
        [data-theme="dark"] #btnCargaMasiva {
            background: linear-gradient(90deg,#8c7be7 0%,#ff94e8 100%) !important;
            color: #fff !important;
            border: none;
        }
        [data-theme="dark"] #btnCargaMasiva:hover, [data-theme="dark"] #btnCargaMasiva:focus {
            background: linear-gradient(90deg,#ff94e8 0%,#8c7be7 100%) !important;
            color: #fff !important;
        }
        [data-theme="dark"] .ejemplo-csv {
            background: #23242a;
            color: #bab8fa;
            border-left: 5px solid #8c7be7;
        }
    </style>
</head>
<body>
<!-- BOTÓN MODO OSCURO/MODO CLARO -->
<button style="position:fixed;top:18px;right:18px;z-index:1101;background:#fff;border:1px solid #b5b8c0;color:#232323;border-radius:50%;width:44px;height:44px;display:flex;align-items:center;justify-content:center;cursor:pointer;box-shadow:0 2px 10px #2222;transition:background 0.2s,color 0.2s;" id="themeToggleBtn" title="Cambiar modo" aria-label="Cambiar modo claro/oscuro">
    <i id="themeToggleIcon" class="bi bi-moon"></i>
</button>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-9 col-md-11">
            <div class="panel-carga shadow-sm">
                <h2 class="panel-titulo mb-4">
                    <i class="bi bi-upload"></i>
                    Carga Masiva de Datos RRHH
                </h2>
                <form action="procesar_carga_rrhh.php" method="post" enctype="multipart/form-data" autocomplete="off">
                    <div class="mb-3">
                        <label class="form-label" for="tabla"><i class="bi bi-table me-1"></i> Selecciona la tabla</label>
                        <select name="tabla" id="tabla" class="form-select" required>
                            <option value="" selected disabled>-- Elige la tabla --</option>
                            <option value="datos_personales">Datos Personales</option>
                            <option value="datos_socioeconomicos">Datos Socioeconómicos</option>
                            <option value="datos_laborales">Datos Laborales</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="archivo_csv"><i class="bi bi-file-earmark-spreadsheet me-1"></i> Selecciona archivo CSV (Excel)</label>
                        <input type="file" name="archivo_csv" id="archivo_csv" class="form-control" accept=".csv" required>
                    </div>
                    <button type="submit" id="btnCargaMasiva" class="btn btn-primary btn-lg">
                        <i class="bi bi-arrow-up-circle me-2"></i>Cargar Datos
                    </button>
                </form>
                <div class="divider my-4"></div>
                <div>
                    <div class="ejemplo-titulo"><i class="bi bi-info-circle"></i> Ejemplos de formatos CSV requeridos:</div>
                    <div class="ejemplo-csv">
                        <b>Datos Personales:</b><br>
<pre>nombres;apellidos;cedula_identidad;pasaporte;rif_prefijo;rif;genero;fecha_nacimiento;nacionalidad;correo_electronico;telefono_contacto;telefono_contacto_secundario;nombre_contacto_emergencia;apellido_contacto_emergencia;telefono_contacto_emergencia;tiene_discapacidad;detalle_discapacidad;tiene_licencia_conducir;tipo_licencia;licencia_vencimiento;numero_seguro_social;direccion;id_estado;id_municipio;id_parroquia
Juan;Pérez;12345678;NO POSEE;V-;12345678;Masculino;1980-01-01;Venezolano;juan.perez@email.com;04121234567;04127891234;Pedro;Gómez;04129876543;No;No aplica;No;;;123456789;Av. Principal #100;21;14;68</pre>
                        <b>Datos Socioeconómicos:</b><br>
<pre>id_pers;estado_civil;nivel_academico;mencion;tipo_vivienda;servicios_agua;servicios_electricidad;servicios_internet;servicios_gas;tecnologia_computadora;tecnologia_smartphone;tecnologia_tablet;carnet_patria;codigo_patria;serial_patria;carnet_psuv;codigo_psuv;serial_psuv;instituciones_academicas
1;Casado/a;Técnico;;Propia;Sí;Sí;Sí;Sí;Sí;Sí;No;No;;;No;;;UPTTMBI</pre>
                        <b>Datos Laborales:</b><br>
<pre>id_pers;correo_institucional;fecha_ingreso;descripcion_funciones;ficha;id_departamento;id_cargo;id_contrato;id_coordinacion;ha_trabajado_anteriormente;nombre_empresa_anterior;ano_ingreso_anterior;ano_culminacion_anterior;estado;id_tipo_personal;causa_inactivo
1;empleado@empresa.com;2020-02-10;Planificador en el Area de Informatica;12345;1;1;1;1;No;;; ;activo;1;</pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://kit.fontawesome.com/8c6e3f1aeb.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var themeToggleBtn = document.getElementById('themeToggleBtn');
    var themeToggleIcon = document.getElementById('themeToggleIcon');
    var htmlTag = document.documentElement;
    let theme = localStorage.getItem('theme') || 'light';
    htmlTag.setAttribute('data-theme', theme);
    themeToggleIcon.className = theme === 'dark' ? 'bi bi-sun' : 'bi bi-moon';
    themeToggleBtn.addEventListener('click', function() {
        let current = htmlTag.getAttribute('data-theme');
        let next = current === 'dark' ? 'light' : 'dark';
        htmlTag.setAttribute('data-theme', next);
        localStorage.setItem('theme', next);
        themeToggleIcon.className = next === 'dark' ? 'bi bi-sun' : 'bi bi-moon';
    });
});
</script>
</body>
</html>