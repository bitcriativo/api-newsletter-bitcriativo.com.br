<?php

namespace Bitcriativo\ApiNewsletterBitcriativoComBr\services;

use GuzzleHttp\Client;

class EspoCRM
{
  public static function createLead(String $email)
  {
    $client = new Client();
    $client->request('POST', $_ENV['ESPOCRM_URL_LEAD'], [
      'headers' => [
        'Accept' => 'application/json'
      ],
      'json' => [
        'emailAddress' => $email
      ],
      'verify' => false
    ]);
  }
}
