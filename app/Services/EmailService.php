<?php

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailService
{
    private PHPMailer $mailer;

    public function __construct()
    {
        $this->mailer = new PHPMailer(true);
        $this->mailer->isSMTP();
        $this->mailer->Host       = $_ENV['MAIL_HOST'] ?? 'smtp.gmail.com';
        $this->mailer->SMTPAuth   = true;
        $this->mailer->Username   = $_ENV['MAIL_USERNAME'] ?? '';
        $this->mailer->Password   = $_ENV['MAIL_PASSWORD'] ?? '';
        $this->mailer->SMTPSecure = $_ENV['MAIL_ENCRYPTION'] ?? 'tls';
        $this->mailer->Port       = (int)($_ENV['MAIL_PORT'] ?? 587);
        $this->mailer->CharSet    = 'UTF-8';
        $this->mailer->isHTML(true);
    }

    /**
     * Envía el email con el enlace de restablecimiento de contraseña.
     *
     * @param string $email Dirección del destinatario.
     * @param string $nombres Nombre del usuario para personalizar el saludo.
     * @param string $resetUrl URL completa con el token de restablecimiento.
     * @return bool True si el email se envió correctamente.
     */
    public function sendResetLink(string $email, string $nombres, string $resetUrl): bool
    {
        try {
            $fromAddress = $_ENV['MAIL_FROM_ADDRESS'] ?? '';
            $fromName    = $_ENV['MAIL_FROM_NAME']    ?? 'Sistema de Ventas';

            $this->mailer->setFrom($fromAddress, $fromName);
            $this->mailer->addAddress($email, $nombres);

            $this->mailer->Subject = 'Restablecimiento de contraseña — Sistema de Ventas';
            $this->mailer->Body    = $this->buildHtmlBody($nombres, $resetUrl);
            $this->mailer->AltBody = "Hola $nombres,\n\nUsa este enlace para restablecer tu contraseña (válido 60 minutos):\n$resetUrl\n\nSi no solicitaste esto, ignora este mensaje.";

            $this->mailer->send();
            return true;
        } catch (Exception $e) {
            error_log('EmailService::sendResetLink — ' . $e->getMessage());
            return false;
        }
    }

    private function buildHtmlBody(string $nombres, string $resetUrl): string
    {
        $year = $this->currentYear();
        return <<<HTML
        <!DOCTYPE html>
        <html lang="es">
        <head>
          <meta charset="UTF-8">
          <meta name="viewport" content="width=device-width, initial-scale=1.0">
          <meta http-equiv="X-UA-Compatible" content="IE=edge">
          <title>Restablecer contraseña</title>
        </head>
        <body style="margin:0;padding:0;background-color:#f4f6f9;font-family:Arial,Helvetica,sans-serif;">
          <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#f4f6f9;">
            <tr>
              <td align="center" style="padding:40px 16px;">

                <!-- Contenedor principal -->
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:580px;background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.1);">

                  <!-- Header -->
                  <tr>
                    <td align="center" style="background-color:#007bff;padding:28px 24px;">
                      <h1 style="margin:0;color:#ffffff;font-size:22px;font-weight:bold;letter-spacing:1px;">SISTEMA DE VENTAS</h1>
                    </td>
                  </tr>

                  <!-- Cuerpo -->
                  <tr>
                    <td style="padding:36px 32px 24px;">
                      <p style="margin:0 0 16px;font-size:16px;color:#333333;">Hola, <strong>$nombres</strong></p>
                      <p style="margin:0 0 28px;font-size:15px;color:#555555;line-height:1.6;">
                        Recibimos una solicitud para restablecer la contraseña de tu cuenta.
                        Haz clic en el botón de abajo para continuar:
                      </p>

                      <!-- Botón CTA -->
                      <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                        <tr>
                          <td align="center" style="padding:0 0 28px;">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                              <tr>
                                <td style="background-color:#007bff;border-radius:6px;">
                                  <a href="$resetUrl"
                                     style="display:inline-block;padding:14px 32px;color:#ffffff;font-size:16px;font-weight:bold;text-decoration:none;border-radius:6px;font-family:Arial,Helvetica,sans-serif;white-space:nowrap;">
                                    Restablecer
                                  </a>
                                </td>
                              </tr>
                            </table>
                          </td>
                        </tr>
                      </table>

                      <!-- Enlace alternativo -->
                      <p style="margin:0 0 8px;font-size:13px;color:#888888;">Si el botón no funciona, copia y pega este enlace en tu navegador:</p>
                      <p style="margin:0 0 24px;font-size:12px;color:#007bff;word-break:break-all;line-height:1.5;">$resetUrl</p>

                      <hr style="border:none;border-top:1px solid #eeeeee;margin:0 0 20px;">

                      <p style="margin:0;font-size:13px;color:#999999;line-height:1.6;">
                        &#9888; Este enlace expirará en <strong>60 minutos</strong>.<br>
                        Si no solicitaste este cambio, puedes ignorar este mensaje con seguridad.
                      </p>
                    </td>
                  </tr>

                  <!-- Footer -->
                  <tr>
                    <td align="center" style="background-color:#f9f9f9;padding:16px 24px;border-top:1px solid #eeeeee;">
                      <p style="margin:0;font-size:12px;color:#aaaaaa;">&copy; $year Sistema de Ventas. Todos los derechos reservados.</p>
                    </td>
                  </tr>

                </table>
                <!-- /Contenedor principal -->

              </td>
            </tr>
          </table>
        </body>
        </html>
        HTML;
    }

    private function currentYear(): string
    {
        return date('Y');
    }
}