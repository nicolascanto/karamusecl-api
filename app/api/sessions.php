<?php

$app->post('/api/sessions/{type}', function($request, $response, $args){
	
	$id_bar = $request->getAttribute('id_bar');
	$origin = (isset($request->getParsedBody()['origin'])) ? $request->getParsedBody()['origin'] : null;
	$date = date('Y-m-d H:i:s');
	$mysqli = getConnection();

	$result = $mysqli->query("SELECT created_at, origin 
		FROM tbl_sessions WHERE id_bar = $id_bar AND active = true ORDER BY created_at DESC LIMIT 1");

	switch ($args['type']) {
		case 'open':
		if ($result->num_rows > 0) {
			$row = $result->fetch_assoc();
			return $response->withJSON(array("status" => 201, "message" => "Ya existe una sesión abierta",
				"data" => array("created_at" => $row['created_at'], "origin" => $row['origin'])));
		} else {
			$result = $mysqli->query("INSERT INTO tbl_sessions (id_bar, active, origin, created_at, updated_at) 
				VALUES ($id_bar, true, '$origin', '$date', '$date')");
			if ($result) {
				$result = $mysqli->query("SELECT id, created_at, origin FROM tbl_sessions 
					WHERE id_bar = $id_bar AND active = true");
				if ($result->num_rows > 0) {
					$row = $result->fetch_assoc();
					return $response->withJSON(array(
						"status" => 200,
						"message" => "Se ha abierto una sesión",
						"data" => array("id_session" => $row['id'], "created_at" => $row['created_at'],
							"origin" => $row['origin']))); 
				}
			}
		}
			break;
		case 'close':
		$mysqli = getConnection();
		if ($result->num_rows == 0) {
			return $response->withJSON(array(
				"status" => 201,
				"message" => "No hay sesiones abiertas para cerrar"));
		} else {
			$result = $mysqli->query("UPDATE tbl_sessions SET active = false, updated_at = '$date' WHERE id_bar = $id_bar");
			if ($mysqli->affected_rows > 0) {
				$result = $mysqli->query("UPDATE tbl_access_tokens SET active = false, updated_at = '$date' WHERE id_bar = $id_bar");
				if ($mysqli->affected_rows > 0) {
					return $response->withJSON(array("status" => 200, 
					"message" => "Se han cerrado todas las sesiones abiertas"));
				} else {
					return $response->withJSON(var_dump($mysqli));
				}
			}
		}
			break;
	}

})->add($authorization);

