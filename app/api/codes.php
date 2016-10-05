<?php
$app->get('/api/codes', function($request, $response, $args){

	$id_bar = $request->getAttribute('id_bar');
	return $response->withJSON(array("status" => 200, "message" => "test codes"));

})->add($authorization);