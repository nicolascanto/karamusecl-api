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
	$date = date('Y-m-d H:i:s');

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
					$stmt = $mysqli->prepare("INSERT INTO tbl_session_codes (id_session, code, state, created_at, updated_at) 
						VALUES (?, ?, 0, ?, ?)");
					$stmt->bind_param('iiss', $id, $code, $date, $date);
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
	$date = date('Y-m-d H:i:s');

	$mysqli = getConnection();
	$session = new session;
	$id_session = $session->id_session($id_bar);

	if (isset($id_session['success']) && $id_session['success']) {
		$id = $id_session['id'];
		$mysqli->query("UPDATE tbl_session_codes SET state = $state, updated_at = '$date' 
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

$app->get('/api/codes/validate/{code_client}', function($request, $response, $args){

	$id_bar = $request->getAttribute('id_bar');
	$code_client = $args['code_client'];
	$mysqli = getConnection();
	$session = new session;
	$id_session = $session->id_session($id_bar);

	if (isset($id_session['success']) && $id_session['success']) {
		$code = new code;
		$verify = $code->verify($code_client, $id_session['id']);

		if (isset($verify['success']) && $verify['success']) {
			return $response->withJSON(array("status" => 200, "message" => "Código verificado correctamente.", "code_client" => $code_client));
		} else {
			return $response->withJSON(array("status" => 201, "message" => "El código es inválido.", "code_client" => $code_client));
		}
		
	} else {
		return $response->withJSON(array("status" => 202, "message" => "Problemas al identificar la sesión.", "code_client" => $code_client));
	}

})->add($authorization);








