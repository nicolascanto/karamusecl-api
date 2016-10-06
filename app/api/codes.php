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

			$codesArr = array();
			while ($row = $result->fetch_assoc()) {
		 		$codesArr[] = $row;
		 	}

			return $response->withJSON(array("status" => 200, "message" => "Sesión tiene códigos", 
			"data" => $codesArr));	

		} else {
			return $response->withJSON(array("status" => 201, "message" => "Sesión no tiene códigos"));	
		}
	} else {
		return $response->withJSON(array("status" => 202, "message" => "No hay sesiones abiertas para este bar"));
	}

})->add($authorization);

$app->post('/api/codes/{lot}', function($request, $response, $args){

	$id_bar = $request->getAttribute('id_bar');

	if (isset($args['lot']) && is_numeric($args['lot'])) {
		$mysqli = getConnection();
		$result = $mysqli->query("SELECT id FROM tbl_sessions WHERE id_bar = $id_bar AND active = true");

		if ($result->num_rows > 0) {
			
			$row = $result->fetch_assoc();
			$id_session = $row['id'];
			$count = $args['lot'];
			$codesArr = array();

			$result2 = $mysqli->query("SELECT count(code) FROM tbl_session_codes WHERE id_session = $id_session");

			if ($result2->num_rows > 0) {
				$row = $result2->fetch_assoc();
				$count_codes = $row['count(code)'] + $count;
				if ($count_codes > 20) {
					return $response->withJSON(array("status" => 201, "message" => "No se pueden generar mas códigos"));
				} else {
					$stmt = $mysqli->prepare("INSERT INTO tbl_session_codes (id_session, code, state) VALUES (?, ?, 0)");
					$stmt->bind_param('ii', $id_session, $code);
					for ($i = 0; $i < $count ; $i++) { 	
						$code = mt_rand(1000,9999);
						$stmt->execute();
						$codesArr[] = $code;
					}

					return $response->withJSON(array("status" => 200, "message" => "Se han generado los códigos",
						"total" => $count_codes, "data" => $codesArr));
				}
			}

		}
	} else {
		return $response->withJSON(array("status" => 400, "message" => "cantidad requerida"));
	}

})->add($authorization);

$app->put('/api/codes/{code}/state/{state}', function($request, $response, $args){
	$code = $args['code'];
	$state = $args['state'];
	$id_bar = $request->getAttribute('id_bar');
	return $response->withJSON(array("status" => 200, "message" => "test codes",
		"code" => $code, "state" => $state));

})->add($authorization);