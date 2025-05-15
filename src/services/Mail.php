<?php

namespace Bitcriativo\ApiNewsletterBitcriativoComBr\services;

use PHPMailer\PHPMailer\PHPMailer;

class Mail
{
  private PHPMailer $mailer;

  public function __construct()
  {
    $this->mailer = new PHPMailer(true);

    $this->mailer->SMTPDebug = (int)$_ENV['SMTP_DEBUG_SERVE'];
    $this->mailer->isSMTP();
    $this->mailer->Host = $_ENV['SMTP_HOST'];
    $this->mailer->Username = $_ENV['SMTP_USER'];
    $this->mailer->Password = $_ENV['SMTP_PASSWORD'];
    $this->mailer->Port = (int)$_ENV['SMTP_PORT'];

    if ($_ENV['MODE'] !== 'development') {
      $this->mailer->SMTPAuth = true;
      $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    }

    $this->mailer->setFrom($_ENV['MAIL_FROM_ADDRESS'] ?? 'no-reply@example.com', $_ENV['MAIL_FROM_NAME'] ?? 'Newsletter');
    $this->mailer->isHTML(true);
  }

  public static function sendConfirmation(string $recipientEmail)
  {
    $mail = new Mail();
    $mail->sendConfirmationEmail($recipientEmail);
    $mail->sendNotificationEmail($recipientEmail);
  }

  public function sendConfirmationEmail(string $recipientEmail): bool
  {
    try {
      $this->resetMailer();
      $this->mailer->addAddress($recipientEmail);
      $this->mailer->Subject = 'Confirmacao de Inscricao na Newsletter';
      $this->mailer->Body = $this->getConfirmationTemplate();
      $this->mailer->AltBody = 'Obrigado por se inscrever na nossa newsletter!';

      return $this->mailer->send();
    } catch (\Exception $e) {
      error_log("Erro ao enviar e-mail de confirmação: {$e->getMessage()}");
      return false;
    }
  }

  public function sendNotificationEmail(string $subscriberEmail): bool
  {
    try {
      $this->resetMailer();

      $adminEmails = explode(',', $_ENV['ADMIN_EMAILS'] ?? 'admin@example.com');
      foreach ($adminEmails as $email) {
        $this->mailer->addAddress(trim($email));
      }

      $this->mailer->Subject = 'Nova Inscricao na Newsletter';
      $this->mailer->Body = $this->getNotificationTemplate($subscriberEmail);
      $this->mailer->AltBody = "Nova inscrição na newsletter: $subscriberEmail";

      return $this->mailer->send();
    } catch (\Exception $e) {
      error_log("Erro ao enviar e-mail de notificação: {$e->getMessage()}");
      return false;
    }
  }

  private function resetMailer(): void
  {
    $this->mailer->clearAddresses();
    $this->mailer->clearCCs();
    $this->mailer->clearBCCs();
    $this->mailer->clearReplyTos();
    $this->mailer->clearAttachments();
  }

  private function getConfirmationTemplate(): string
  {
    $siteUrl = $_ENV['SITE_URL'] ?? 'https://example.com';
    $siteName = $_ENV['SITE_NAME'] ?? 'Nosso Site';

    return <<<HTML
  <!DOCTYPE html>
  <html>

  <head>
    <meta charset="UTF-8">
    <title>Inscrição Confirmada</title>
    <style>
      body {
        font-family: Arial, sans-serif;
        background-color: #f4f4f4;
        margin: 0;
        padding: 0;
      }

      .container {
        max-width: 600px;
        margin: 40px auto;
        background-color: #ffffff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
      }

      h1 {
        color: #333333;
      }

      p {
        font-size: 16px;
        color: #666666;
      }

      .button {
        display: inline-block;
        margin-top: 20px;
        padding: 10px 20px;
        background-color: #007BFF;
        color: white;
        text-decoration: none;
        border-radius: 5px;
      }

      .footer {
        margin-top: 30px;
        font-size: 12px;
        color: #aaaaaa;
      }
    </style>
  </head>

  <body>
    <div class="container">
      <h1>Bem-vindo à nossa Newsletter</h1>
      <p>Obrigado por se inscrever. Agora você receberá atualizações, novidades e conteúdos exclusivos diretamente no seu e-mail.</p>
      <a href="{$siteUrl}" class="button">Visite nosso site</a>
      <div class="footer">
        <p>Se você não se inscreveu, ignore este e-mail.</p>
      </div>
    </div>
  </body>

  </html>
  HTML;
  }

  private function getNotificationTemplate(string $email): string
  {
    $date = date('d/m/Y H:i:s');

    return <<<HTML
    <!DOCTYPE html>
    <html>

    <head>
      <meta charset="UTF-8">
      <title>Nova Inscrição na Newsletter</title>
      <style>
        body {
          font-family: Arial, sans-serif;
          background-color: #f9f9f9;
          margin: 0;
          padding: 20px;
        }

        .container {
          background-color: #ffffff;
          padding: 20px;
          border-radius: 6px;
          border: 1px solid #dddddd;
          max-width: 600px;
          margin: auto;
        }

        h2 {
          color: #333333;
        }

        p {
          font-size: 16px;
          color: #555555;
          margin: 8px 0;
        }

        .label {
          font-weight: bold;
          color: #000;
        }
      </style>
    </head>

    <body>
      <div class="container">
        <h2>Nova Inscrição na Newsletter</h2>
        <p><span class="label">E-mail:</span> {$email}</p>
        <p><span class="label">Data:</span> {$date}</p>
        <p>Você recebeu esta notificação porque um novo usuário se inscreveu na newsletter do site.</p>
      </div>
    </body>

    </html>
    HTML;
  }
}
