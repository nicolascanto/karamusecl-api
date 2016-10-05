<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require '../vendor/autoload.php';

$app = new \Slim\App;
// Access-Control headers are received during OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {

    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");         

    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
}

require_once('../app/api/lib/tokenController.php');

$authorization = function($request, $response, $next){
	$token_request = isset($request->getParsedBody()['token']) ? $request->getParsedBody()['token'] : null;
	$token = new token;
	if ($token->validate($token_request)) {
		return $next($request, $response);
	} else {
		return $response->withJSON(array(
			"status" => 401,
			"message" => "Invalid token or expired"));
	}
};

require_once('../vendor/phpmailer/phpmailer/PHPMailerAutoload.php');
require_once('../vendor/paragonie/random_compat/lib/random.php');
require_once('../app/api/lib/dbConnect.php');
require_once('../app/api/catalog.php');
require_once('../app/api/load.php');
require_once('../app/api/user.php');
require_once('../app/api/sessions.php');

$app->run();