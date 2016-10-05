<?php

$app->post('/api/sessions/{type}', function($request, $response, $args){
	
	$id_bar = $request->getAttribute('id_bar');
	// $active = ($args['type'] == "open") ? true : false;


	$mysqli = getConnection();
	$result = $mysqli->query("SELECT created_at FROM tbl_sessions WHERE id_bar = $id_bar AND active = true 
		ORDER BY created_at DESC LIMIT 1");


	switch ($args['type']) {
		case 'open':
		if ($result->num_rows > 0) {
			$row = $result->fetch_assoc();
			return $response->withJSON(array(
				"status" => 201,
				"message" => "Ya existe una sesión abierta",
				"created_at" => $row['created_at']));
		} else {
			$result = $mysqli->query("INSERT INTO tbl_sessions (id_bar, active) 
				VALUES ($id_bar, true)");
			if ($result) {
				$result = $mysqli->query("SELECT id, created_at 
					FROM tbl_sessions WHERE id_bar = $id_bar AND active = true");
				if ($result->num_rows > 0) {
					$row = $result->fetch_assoc();
					$id_session = $row['id'];
					$created_at = $row['created_at'];

					return $response->withJSON(array(
						"status" => 200,
						"message" => "Session opened",
						"id_session" => $id_session,
						"created_at" => $created_at)); 
				}
			}
		}
			break;
		case 'close':
		$mysqli = getConnection();
		if ($result->num_rows == 0) {
			return $response->withJSON(array(
				"status" => 201,
				"message" => "No hay sesiones abiertas para cerrar",
				"created_at" => null));
		} else {
			$result = $mysqli->query("UPDATE tbl_sessions SET active = false 
				WHERE id_bar = $id_bar");
			if ($result) {
				return $response->withJSON(array(
					"status" => 200,
					"message" => "Se han cerrado todas las sesiones abiertas",
					"created_at" => null));
			}
		}
			break;
	}

})->add($authorization);

$app->post('/api/codes', function($request, $response, $args){
	
	

})->add($authorization);
