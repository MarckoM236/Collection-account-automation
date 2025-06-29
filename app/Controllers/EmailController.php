<?php

namespace App\Controllers;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailController
{
    public function __construct()
    {
        
    }

    /**
     * Envía un correo usando Gmail SMTP y PHPMailer.
     */
    public function sendEmail($subject,$body,$attachment)
    {
        $mail = new PHPMailer(true);

        try {
            // Configuración del servidor SMTP
            $mail->isSMTP();
            $mail->Host       = $_ENV['SMTP_HOST'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $_ENV['SMTP_USER']; 
            $mail->Password   = $_ENV['SMTP_PASSWORD']; 
            $mail->SMTPSecure = $_ENV['SMTP_SECURE'] ?? 'tls';
            $mail->Port       = (int) $_ENV['SMTP_PORT'] ?? 587;

            // Remitente y destinatario
            $mail->setFrom($_ENV['SMTP_USER'], $_ENV['SMTP_FROMNAME']);
            $mail->addAddress($_ENV['TO'], $_ENV['TONAME']);

            // Contenido del correo
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->addAttachment($attachment);
            //$mail->AltBody = 'Este es un correo de prueba desde el controlador.';

            $mail->send();
            echo 'Correo enviado correctamente.';
        } catch (Exception $e) {
            echo 'Error al enviar el correo: ', $mail->ErrorInfo;
        }
    }
}
