<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require '../vendor/autoload.php';

$app = new \Slim\App;

require_once('../app/api/lib/dbConnect.php');
require_once('../app/api/catalog.php');
require_once('../app/api/load.php');
require_once('../app/api/user.php');

$app->run();