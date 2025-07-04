<?php
session_start();
require 'conexion_archivero.php';

// Carga de PHPMailer (ajusta según si usas Composer o descarga manual)
require 'phpmailer/PHPMailer.php';
require 'phpmailer/SMTP.php';
require 'phpmailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Función para generar un CÓDIGO numérico de 6 dígitos
function generarCodigoRecuperacion() {
    return str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    if (empty($email)) {
        $_SESSION['reset_message'] = ['text' => 'Por favor, introduce tu correo electrónico.', 'type' => 'danger'];
        header('Location: forgot_password.php');
        exit;
    }

    $stmt = $conexion->prepare("SELECT id, nombre FROM usuarios WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($usuario = $result->fetch_assoc()) {
        $user_id = $usuario['id'];
        $user_nombre = $usuario['nombre']; // Opcional, para personalizar el correo
        $codigo = generarCodigoRecuperacion();
        $expires = date('Y-m-d H:i:s', strtotime('+15 minutes')); // Código válido por 15 minutos

        // Almacenar el código en la base de datos
        // Asegúrate de que la columna token_reset es VARCHAR(255) o similar
        $stmt_update = $conexion->prepare("UPDATE usuarios SET token_reset = ?, token_expira = ? WHERE id = ?");
        $stmt_update->bind_param("ssi", $codigo, $expires, $user_id);
        $stmt_update->execute();

        // Enviar correo electrónico con el código
        $mail = new PHPMailer(true);
        try {
            // Configuración del servidor (TU CONFIGURACIÓN DE GMAIL)
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'sapnnaetinformatica@gmail.com'; // Tu dirección de correo de Gmail
            $mail->Password   = 'yfcwiowamawsghnk';    // La contraseña de aplicación que generaste
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            // Destinatarios
            $mail->setFrom('sapnnaetinformatica@gmail.com', 'Sistema de Archivo');
            $mail->addAddress($email);

            // Contenido
            $mail->isHTML(true);
            $mail->Subject = 'Tu Código de Recuperación de Contraseña';
            $mail->Body    = 'Hola ' . htmlspecialchars($user_nombre) . ',<br><br>'
                           . 'Has solicitado restablecer tu contraseña. Tu código de recuperación es:<br><br>'
                           . '<h2 style="color:#007bff; text-align:center;">' . $codigo . '</h2><br>'
                           . 'Por favor, introduce este código en la página de recuperación para continuar. <br>'
                           . 'Puedes acceder a la página de verificación aquí: <a href="http://' . $_SERVER['HTTP_HOST'] . '/verify_code.php">Haz clic aquí para verificar tu código</a><br><br>' // Enlace a la nueva página
                           . 'Este código expirará en 15 minutos.<br><br>'
                           . 'Si no solicitaste un restablecimiento de contraseña, ignora este correo electrónico.';
            $mail->AltBody = 'Hola ' . htmlspecialchars($user_nombre) . ', Has solicitado restablecer tu contraseña. Tu código de recuperación es: ' . $codigo . '. Este código expirará en 15 minutos. Si no solicitaste un restablecimiento de contraseña, ignora este correo electrónico.';

            $mail->send();
            $_SESSION['reset_message'] = ['text' => 'Se ha enviado un código de recuperación a tu correo electrónico. Por favor, revisa tu bandeja de entrada y spam. El código expirará en 15 minutos.', 'type' => 'success'];
            // Redirigir a la nueva página de verificación de código, o a la misma página para mostrar el mensaje
            header('Location: verify_code.php'); // O a verify_code.php si quieres redirigir directamente
            exit;

        } catch (Exception $e) {
            error_log("Error al enviar el correo de código de recuperación: {$mail->ErrorInfo}");
            $_SESSION['reset_message'] = ['text' => 'Hubo un problema al enviar el correo electrónico del código de recuperación. Inténtalo de nuevo más tarde.', 'type' => 'danger'];
            header('Location: forgot_password.php');
            exit;
        }
    } else {
        // Por seguridad, mensaje genérico
        $_SESSION['reset_message'] = ['text' => 'Si tu correo electrónico está registrado, recibirás un código de recuperación en breve.', 'type' => 'success'];
        header('Location: verify_code.php');
        exit;
    }
} else {
    header('Location: forgot_password.php');
    exit;
}
?>