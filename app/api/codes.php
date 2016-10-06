<?php

$app->get('/api/codes/verify', function($request, $response, $args){

	$id_bar = $request->getAttribute('id_bar');
	$mysqli = getConnection();
	$result = $mysqli->query("SELECT id FROM tbl_sessions WHERE id_bar = $id_bar AND active = true");
	if ($result->num_rows > 0) {
		$row = $result->fetch_assoc();
		$id_session = $row['id'];

		$result = $mysqli->query("SELECT code, state FROM tbl_session_codes WHERE id_session = $id_session");
		if ($result->num_rows > 0) {

			$codeArr = array();
			while ($row = $result->fetch_assoc()) {
		 		$codeArr[] = $row;
		 	}

			return $response->withJSON(array("status" => 200, "message" => "Sesión tiene códigos", 
			"data" => $codeArr));	
		} else {
			return $response->withJSON(array("status" => 201, "message" => "Sesión no tiene códigos"));	
		}
	} else {
		return $response->withJSON(array("status" => 202, "message" => "No hay sesiones abiertas para este bar"));
	}

})->add($authorization);

$app->post('/api/codes', function($request, $response, $args){

	$id_bar = $request->getAttribute('id_bar');
	return $response->withJSON(array("status" => 200, "message" => "test codes"));

})->add($authorization);