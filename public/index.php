<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require '../vendor/autoload.php';

$app = new \Slim\App;
date_default_timezone_set('America/Santiago');
// Allow from any origin
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');    // cache for 1 day
}
// Access-Control headers are received during OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {

    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");         

    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
}

require_once('../app/api/lib/dbConnect.php');
require_once('../app/api/lib/tokenController.php');
require_once('../app/api/lib/utilsClass.php');

$authorization = function($request, $response, $next){

	if ($request->isGet()) {
		$token_request = $request->getQueryParam('token', $default = null);
	} elseif ($request->isPost() || $request->isPut()) {
		$token_request = isset($request->getParsedBody()['token']) ? $request->getParsedBody()['token'] : null;
	}

	$token = new token;
	
	if ($token->validate($token_request)) {
		
		$mysqli = getConnection();
		$result = $mysqli->query("SELECT id_bar, scope FROM tbl_access_tokens WHERE token = '$token_request'");
		
		if ($result->num_rows > 0) {
			$row = $result->fetch_assoc();
			$request = $request->withAttribute('id_bar', $row['id_bar']);
			$request = $request->withAttribute('scope', $row['scope']);
			$request = $request->withAttribute('token', $token_request);
			return $next($request, $response);
		}
		
	} else {
		return $response->withJSON(array(
			"status" => 401,
			"message" => "Invalid token or expired"));
	}
	$mysqli->close();
};

require_once('../vendor/phpmailer/phpmailer/PHPMailerAutoload.php');

require_once('../app/api/catalog.php');
require_once('../app/api/load.php');
require_once('../app/api/user.php');
require_once('../app/api/sessions.php');
require_once('../app/api/codes.php');
require_once('../app/api/orders.php');
require_once('../app/api/settings.php');
require_once('../app/api/client.php');

$app->run();