<?php

namespace Bitcriativo\ApiNewsletterBitcriativoComBr\controller;

use Bitcriativo\ApiNewsletterBitcriativoComBr\helpers\ResponseFormat;
use Bitcriativo\ApiNewsletterBitcriativoComBr\services\EspoCRM;
use Bitcriativo\ApiNewsletterBitcriativoComBr\services\Mail as ServicesMail;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Respect\Validation\Validator;

class NewsletterController
{
  function __construct() {}

  public function subscriber(Request $request, Response $response)
  {
    $data = $request->getParsedBody();

    $validator = Validator::key('email', Validator::allOf(
      Validator::notEmpty(),
      Validator::email()
    ));

    if (!$validator->validate($data)) {
      return ResponseFormat::json($response, ["status_code" => 400, "message" => "Dados inválidos"], 400);
    }

    try {
      EspoCRM::createLead($data['email']);
      ServicesMail::sendConfirmation($data['email']);

      return ResponseFormat::json($response, ["status_code" => 200, "message" => "Registration completed successfully", "data" => json_decode("body")]);
    } catch (\GuzzleHttp\Exception\RequestException $e) {
      return ResponseFormat::json($response, ["status_code" => 500, "message" => "Internal Server Error", "error" => $e->getMessage()], 500);
    }
  }
}
