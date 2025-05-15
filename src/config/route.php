<?php

use Bitcriativo\ApiNewsletterBitcriativoComBr\controller\NewsletterController;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

$app = AppFactory::create();

$app->add(function (Request $request, $handler) {
  $response = $handler->handle($request);
  return $response
    ->withHeader('Access-Control-Allow-Origin', '*')
    ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Accept, Origin')
    ->withHeader('Access-Control-Allow-Methods', 'GET, POST');
});

$app->addBodyParsingMiddleware();

$app->addRoutingMiddleware();

$errorMiddleware = $app->addErrorMiddleware(true, true, true);

$app->get('/', function (Request $request, Response $response, $args) {
  $response->getBody()->write("Hello, World!");
  return $response;
});

$app->post('/newsletter', NewsletterController::class . ':subscriber');

$app->run();
