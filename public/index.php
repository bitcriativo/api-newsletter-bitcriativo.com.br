<?php

require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Factory\AppFactory;
use GuzzleHttp\Client;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Respect\Validation\Validator;

$dotenv = Dotenv::createImmutable(__DIR__, '.env');
$dotenv->safeLoad();

class CorsMiddleware
{
    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        $response = $handler->handle($request);
        $origin = $request->getHeaderLine('Origin') ?: '*';

        $response = $response
            ->withHeader('Access-Control-Allow-Origin', $origin)
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With')
            ->withHeader('Access-Control-Allow-Methods', 'POST')
            ->withHeader('Access-Control-Allow-Credentials', 'true');

        if ($request->getMethod() === 'OPTIONS') {
            return $response->withStatus(200);
        }

        return $response;
    }
}

$app = AppFactory::create();

$app->addBodyParsingMiddleware();
$app->add(new CorsMiddleware());

$app->post('/newsletter', function (Request $request, Response $response) {
    $data = $request->getParsedBody();

    $validator = Validator::key('email', Validator::allOf(
        Validator::notEmpty(),
        Validator::email()
    ));

    if (!$validator->validate($data)) {
        return createJsonResponse($response, ["status_code" => 400, "message" => "Dados inválidos"], 400);
    }

    try {
        $client = new Client();
        $apiResponse = $client->request('POST', $_ENV['ESPOCRM_URL_LEAD'], [
            'headers' => [
                'Accept' => 'application/json'
            ],
            'json' => [
                'emailAddress' => $data['email']
            ]
        ]);

        $emailService = new EmailService();
        $emailService->sendConfirmationEmail($data['email']);
        $emailService->sendNotificationEmail($data['email']);

        $body = $apiResponse->getBody()->getContents();
        return createJsonResponse($response, ["status_code" => 200, "message" => "Registration completed successfully", "data" => json_decode($body)]);
    } catch (\GuzzleHttp\Exception\RequestException $e) {
        return createJsonResponse($response, ["status_code" => 500, "message" => "Internal Server Error", "error" => $e->getMessage()], 500);
    }
});

$app->run();

function createJsonResponse(Response $response, array $data, int $statusCode = 200): Response
{
    $response->getBody()->write(json_encode($data));
    return $response
        ->withStatus($statusCode)
        ->withHeader('Content-Type', 'application/json');
}

class EmailService
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

    public function sendConfirmationEmail(string $recipientEmail): bool
    {
        try {
            $this->resetMailer();
            $this->mailer->addAddress($recipientEmail);
            $this->mailer->Subject = 'Confirmacao de Inscricao na Newsletter';
            $this->mailer->Body = $this->getConfirmationTemplate();
            $this->mailer->AltBody = 'Obrigado por se inscrever na nossa newsletter!';

            return $this->mailer->send();
        } catch (Exception $e) {
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
        } catch (Exception $e) {
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
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
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
