<?php
session_start();

// Habilitar la visualización de errores para depuración.
error_reporting(E_ALL);
ini_set('display_errors', 1);

$id_vacacion = isset($_GET['id_vacacion']) ? (int)$_GET['id_vacacion'] : 0;
$periodos_ids = isset($_GET['periodos_ids']) ? $_GET['periodos_ids'] : ''; // Obtener los IDs de los períodos

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redirigiendo...</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            background-color: #f0f2f5;
            color: #333;
            text-align: center;
            padding: 20px;
        }
        .message-box {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            padding: 30px;
            max-width: 500px;
            width: 100%;
        }
        .message-box h2 {
            color: #28a745;
            margin-bottom: 15px;
        }
        .message-box p {
            margin-bottom: 20px;
            line-height: 1.6;
        }
        .message-box a {
            color: #007bff;
            text-decoration: none;
            font-weight: bold;
        }
        .message-box a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="message-box">
        <h2>¡Registro Exitoso!</h2>
        <p>Se ha registrado la vacación correctamente.</p>
        <p>El reporte PDF se está generando y debería abrirse en una nueva pestaña.</p>
        <p>Si el PDF no se abre automáticamente, por favor, 
           <a id="pdfLink" href="generar-reporte-vacacion.php?id_vacacion=<?= $id_vacacion ?>&periodos_ids=<?= urlencode($periodos_ids) ?>" target="_blank">haz clic aquí para abrirlo manualmente</a> 
           o revisa tu bloqueador de pop-ups.
        </p>
        <p>Serás redirigido a la gestión de vacaciones en breve.</p>
    </div>

    <script type="text/javascript">
        const idVacacion = <?= $id_vacacion ?>;
        const periodosIds = "<?= $periodos_ids ?>"; // Pasa los IDs a JavaScript

        if (idVacacion > 0) {
            // Abrir generar-reporte-vacacion.php en una nueva pestaña
            // Se añade un pequeño retraso antes de intentar abrir el pop-up
            setTimeout(function() {
                const pdfWindow = window.open("generar-reporte-vacacion.php?id_vacacion=" + idVacacion + "&periodos_ids=" + encodeURIComponent(periodosIds), "_blank");
                if (pdfWindow) {
                    pdfWindow.focus(); // Intenta enfocar la nueva ventana
                } else {
                    // Si window.open devuelve null, el pop-up fue bloqueado.
                    console.warn("El pop-up del PDF fue bloqueado. Por favor, revisa tu configuración de navegador.");
                }
            }, 100); // Pequeño retraso antes de intentar abrir el pop-up
        }

        // Redirigir la página actual a gestion-vacaciones.php después de un retraso más largo
        setTimeout(function() {
            window.location.href = "gestion-vacaciones.php";
        }, 2000); // Retraso de 2 segundos (2000 milisegundos)
    </script>
</body>
</html>
<?php exit; // Asegurarse de que no se envíe más contenido ?>

