<?php
require __DIR__ . '/conexion/conexion_db.php';

function limpiar($valor) {
    return trim(mb_convert_encoding($valor, 'UTF-8', 'auto'));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_FILES['archivo_csv'])
    && isset($_POST['tabla'])
) {
    $tabla = $_POST['tabla'];
    $archivo = $_FILES['archivo_csv']['tmp_name'];
    $resultados = [
        'exitos' => 0,
        'fallos' => [],
        'total' => 0
    ];

    $handle = fopen($archivo, 'r');
    if ($handle === false) {
        die("No se pudo abrir el archivo.");
    }
    $encabezados = fgetcsv($handle, 0, ';');
    if (!$encabezados) { die("El archivo CSV está vacío."); }

    // DATOS PERSONALES
    if ($tabla == 'datos_personales') {
        $campos_bd = [
            'nombres','apellidos','cedula_identidad','pasaporte','rif_prefijo','rif','genero','fecha_nacimiento',
            'nacionalidad','correo_electronico','telefono_contacto','telefono_contacto_secundario',
            'nombre_contacto_emergencia','apellido_contacto_emergencia','telefono_contacto_emergencia',
            'tiene_discapacidad','detalle_discapacidad','tiene_licencia_conducir','tipo_licencia','licencia_vencimiento',
            'numero_seguro_social','direccion','id_estado','id_municipio','id_parroquia'
        ];
        foreach ($campos_bd as $campo) {
            if (!in_array($campo, $encabezados)) {
                die("El campo '$campo' es obligatorio en el archivo CSV.");
            }
        }
        $sql = "INSERT INTO datos_personales (
            nombres, apellidos, cedula_identidad, pasaporte, rif, genero, fecha_nacimiento, nacionalidad,
            correo_electronico, telefono_contacto, telefono_contacto_secundario, nombre_contacto_emergencia,
            apellido_contacto_emergencia, telefono_contacto_emergencia, tiene_discapacidad, detalle_discapacidad,
            carnet_discapacidad_imagen, tiene_licencia_conducir, tipo_licencia, licencia_vencimiento,
            licencia_imagen, numero_seguro_social, direccion, id_estado, id_municipio, id_parroquia
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, ?, ?, ?, NULL, ?, ?, ?, ?, ?)";
    }
    // DATOS SOCIOECONOMICOS
    elseif ($tabla == 'datos_socioeconomicos') {
        $campos_bd = [
            'id_pers','estado_civil','nivel_academico','mencion','tipo_vivienda',
            'servicios_agua','servicios_electricidad','servicios_internet','servicios_gas',
            'tecnologia_computadora','tecnologia_smartphone','tecnologia_tablet',
            'carnet_patria','codigo_patria','serial_patria',
            'carnet_psuv','codigo_psuv','serial_psuv','instituciones_academicas'
        ];
        foreach ($campos_bd as $campo) {
            if (!in_array($campo, $encabezados)) {
                die("El campo '$campo' es obligatorio en el archivo CSV.");
            }
        }
        $sql = "INSERT INTO datos_socioeconomicos (
            id_pers, estado_civil, nivel_academico, mencion, tipo_vivienda,
            servicios_agua, servicios_electricidad, servicios_internet, servicios_gas,
            tecnologia_computadora, tecnologia_smartphone, tecnologia_tablet,
            carnet_patria, codigo_patria, serial_patria,
            carnet_psuv, codigo_psuv, serial_psuv, instituciones_academicas
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    }
    // DATOS LABORALES
    elseif ($tabla == 'datos_laborales') {
        $campos_bd = [
            'id_pers','correo_institucional','fecha_ingreso','descripcion_funciones','ficha',
            'id_departamento','id_cargo','id_contrato','id_coordinacion',
            'ha_trabajado_anteriormente','nombre_empresa_anterior','ano_ingreso_anterior','ano_culminacion_anterior',
            'estado','id_tipo_personal','causa_inactivo'
        ];
        foreach ($campos_bd as $campo) {
            if (!in_array($campo, $encabezados)) {
                die("El campo '$campo' es obligatorio en el archivo CSV.");
            }
        }
        $sql = "INSERT INTO datos_laborales (
            id_pers, correo_institucional, fecha_ingreso, descripcion_funciones, ficha,
            id_departamento, id_cargo, id_contrato, id_coordinacion,
            ha_trabajado_anteriormente, nombre_empresa_anterior, ano_ingreso_anterior, ano_culminacion_anterior,
            estado, id_tipo_personal, causa_inactivo
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    }
    else {
        die("Tabla no soportada.");
    }

    $stmt = $pdo->prepare($sql);

    while (($datos = fgetcsv($handle, 0, ';')) !== false) {
        if (count($datos) == 1 && trim($datos[0]) === '') continue;
        if (count($datos) != count($encabezados)) {
            $resultados['fallos'][] = "Fila {$resultados['total']} ignorada: columnas no coinciden con encabezados.";
            continue;
        }
        $resultados['total']++;
        $fila = array_combine($encabezados, $datos);

        // Validaciones mínimas (puedes ampliar según la tabla)
        if ($tabla == 'datos_personales') {
            $cedula = limpiar($fila['cedula_identidad'] ?? '');
            $email = limpiar($fila['correo_electronico'] ?? '');
            if (!$cedula || !$email) {
                $resultados['fallos'][] = "Falta cédula o email en la fila {$resultados['total']}";
                continue;
            }
            $verifica = $pdo->prepare("SELECT id_pers FROM datos_personales WHERE cedula_identidad = ? OR correo_electronico = ?");
            $verifica->execute([$cedula, $email]);
            if ($verifica->fetch()) {
                $resultados['fallos'][] = "Duplicado: $cedula o $email ya existe (fila {$resultados['total']})";
                continue;
            }
            $rif_completo = limpiar($fila['rif_prefijo'] ?? '') . limpiar($fila['rif'] ?? '');
            $datos_para_insertar = [
                limpiar($fila['nombres'] ?? ''),
                limpiar($fila['apellidos'] ?? ''),
                $cedula,
                limpiar($fila['pasaporte'] ?? ''),
                $rif_completo,
                limpiar($fila['genero'] ?? ''),
                limpiar($fila['fecha_nacimiento'] ?? ''),
                limpiar($fila['nacionalidad'] ?? ''),
                $email,
                limpiar($fila['telefono_contacto'] ?? ''),
                limpiar($fila['telefono_contacto_secundario'] ?? ''),
                limpiar($fila['nombre_contacto_emergencia'] ?? ''),
                limpiar($fila['apellido_contacto_emergencia'] ?? ''),
                limpiar($fila['telefono_contacto_emergencia'] ?? ''),
                limpiar($fila['tiene_discapacidad'] ?? 'No'),
                limpiar($fila['detalle_discapacidad'] ?? 'No aplica'),
                limpiar($fila['tiene_licencia_conducir'] ?? 'No'),
                limpiar($fila['tipo_licencia'] ?? ''),
                limpiar($fila['licencia_vencimiento'] ?? ''),
                limpiar($fila['numero_seguro_social'] ?? ''),
                limpiar($fila['direccion'] ?? ''),
                limpiar($fila['id_estado'] ?? ''),
                limpiar($fila['id_municipio'] ?? ''),
                limpiar($fila['id_parroquia'] ?? '')
            ];
        } elseif ($tabla == 'datos_socioeconomicos') {
            $datos_para_insertar = [];
            foreach ($campos_bd as $campo) {
                $datos_para_insertar[] = limpiar($fila[$campo] ?? '');
            }
        } elseif ($tabla == 'datos_laborales') {
            $datos_para_insertar = [];
            foreach ($campos_bd as $campo) {
                $datos_para_insertar[] = limpiar($fila[$campo] ?? '');
            }
        }

        $exito = $stmt->execute($datos_para_insertar);
        if ($exito) {
            $resultados['exitos']++;
        } else {
            $resultados['fallos'][] = "Error en la fila {$resultados['total']}: " . implode('; ', $fila);
        }
    }
    fclose($handle);

    // Resultados
    echo "<h3>Resultados de la carga masiva</h3>";
    echo "<b>Total de filas procesadas:</b> {$resultados['total']}<br>";
    echo "<b>Exitosas:</b> {$resultados['exitos']}<br>";
    echo "<b>Fallidas:</b> " . count($resultados['fallos']) . "<br>";
    if ($resultados['fallos']) {
        echo "<ul>";
        foreach ($resultados['fallos'] as $f) {
            echo "<li>$f</li>";
        }
        echo "</ul>";
    }
    echo "<a href='carga_masiva_rrhh.html'>Volver</a>";
} else {
    echo "No se recibió un archivo CSV.";
}
?>