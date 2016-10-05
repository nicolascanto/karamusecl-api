<?php

$app->post('/api/sessions/{type}', function($request, $response, $args){

	switch ($args['type']) {
		case 'open':
			return $response->withJSON(array(
			"status" => 200,
			"message" => "Open session")); 
			break;
		case 'close':
			return $response->withJSON(array(
			"status" => 200,
			"message" => "Close session"));
			break;
	}

})->add($authorization);

$app->post('/api/codes', function($request, $response, $args){
	
	

})->add($authorization);
