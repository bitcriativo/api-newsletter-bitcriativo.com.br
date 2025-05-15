<?php

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__, '/../../.env');
$dotenv->safeLoad();
