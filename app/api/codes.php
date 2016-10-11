<?php

$app->get('/api/codes/verify', function($request, $response, $args){

	$id_bar = $request->getAttribute('id_bar');
	$mysqli = getConnection();
	$session = new session;
	$id_session = $session->id_session($id_bar);

	if (isset($id_session['success']) && $id_session['success']) {
		$id = $id_session['id'];
		$result = $mysqli->query("SELECT code, state FROM tbl_session_codes WHERE id_session = $id");
		
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
		return $response->withJSON($id_session);
	}

})->add($authorization);

$app->post('/api/codes/{lot}', function($request, $response, $args){

	$id_bar = $request->getAttribute('id_bar');

	if (isset($args['lot']) && is_numeric($args['lot'])) {
		$mysqli = getConnection();
		$session = new session;
		$id_session = $session->id_session($id_bar);

		if (isset($id_session['success']) && $id_session['success']) {
			$id = $id_session['id'];
			$count = $args['lot'];
			$codesArr = array();
			$result = $mysqli->query("SELECT count(code) FROM tbl_session_codes WHERE id_session = $id");

			if ($result->num_rows > 0) {
				
				$row = $result->fetch_assoc();
				$count_codes = $row['count(code)'] + $count;
				
				if ($count_codes > 20) {
					return $response->withJSON(array("status" => 201, "message" => "No se pueden generar más códigos"));
				} else {
				
					$stmt = $mysqli->prepare("INSERT INTO tbl_session_codes (id_session, code, state) 
						VALUES (?, ?, 0)");
					$stmt->bind_param('ii', $id, $code);
					for ($i = 0; $i < $count ; $i++) { 	
						$code = mt_rand(1000,9999);
						$stmt->execute();
						$codesArr[] = $code;
					}

					return $response->withJSON(array("status" => 200, "message" => "Se han generado los códigos",
						"total" => $count_codes, "data" => $codesArr, "id_session" => $id));
				}
			}

		} else {
			return $response->withJSON($id_session);
		}
	} else {
		return $response->withJSON(array("status" => 400, "message" => "cantidad requerida"));
	}

})->add($authorization);

$app->put('/api/codes/{code}/state/{state}', function($request, $response, $args){
	
	$id_bar = $request->getAttribute('id_bar');
	$code = $args['code'];
	$state = $args['state'];

	$mysqli = getConnection();
	$session = new session;
	$id_session = $session->id_session($id_bar);

	if (isset($id_session['success']) && $id_session['success']) {
		$id = $id_session['id'];
		$mysqli->query("UPDATE tbl_session_codes SET state = $state 
			WHERE code = $code AND id_session = $id");
		
		if ($mysqli->affected_rows > 0) {
			return $response->withJSON(array("status" => 200, "message" => "Se ha actualizado el código."));
		} else {
			return $response->withJSON(array("status" => 400, "message" => "No se ha actualizado el código."));
		}

	} else {
		return $response->withJSON($id_session);
	}

})->add($authorization);








