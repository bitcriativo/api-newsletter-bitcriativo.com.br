<?php

namespace Bitcriativo\ApiNewsletterBitcriativoComBr\helpers;

use Psr\Http\Message\ResponseInterface as Response;

class ResponseFormat
{
  public static function json(Response $response, array $data, int $statusCode = 200): Response
  {
    $response->getBody()->write(json_encode($data));
    return $response
      ->withStatus($statusCode)
      ->withHeader('Content-Type', 'application/json');
  }
}
