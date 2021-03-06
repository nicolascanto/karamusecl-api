<?php

$app->get('/api/client/bars', function($request, $response, $args){
	$mysqli = getConnection();
	$result = $mysqli->query("SELECT tbl_bars.id, tbl_bars.name, tbl_bars.address, tbl_bar_settings.avatar, 
		tbl_bar_settings.banner_ad, tbl_bar_settings.text_ad 
		FROM tbl_bars JOIN tbl_bar_settings ON tbl_bars.id = tbl_bar_settings.id_bar
		WHERE tbl_bars.active = true");

	if ($result->num_rows > 0) {
		$bars = array();
		$settings = array();
		$info = array();
		while ($row = $result->fetch_assoc()) {
			
			$row['settings'] = $settings;
			$info['id'] = $row['id'];
			$info['name'] = $row['name'];
			$info['address'] = $row['address'];
			$info['settings'] = array(
				"avatar" => $row['avatar'],
				"banner_ad" => $row['banner_ad'],
				"text_ad" => $row['text_ad']);

			$bars[] = $info;
		}
		return $response->withJSON(array("status" => 200, "message" => "Listado de bares activos", 
			"data" => $bars));
	} else {
		return $response->withJSON(array("status" => 201, "message" => "No hay bares activos"));
	}
});

$app->post('/api/client/access_token', function($request, $response, $args){
	
	$id_bar = (isset($request->getParsedBody()['id_bar'])) ? $request->getParsedBody()['id_bar'] : null;
	$origin = (isset($request->getParsedBody()['origin'])) ? $request->getParsedBody()['origin'] : null;
	$date = date('Y-m-d H:i:s');

	if (is_null($id_bar) || is_null($origin)) {
		return $response->withJSON(array("status" => 402, "message" => "Debes especificar id_bar y origin"));
	}

	if (!is_numeric($id_bar) || $id_bar <= 0) {
		return $response->withJSON(array("status" => 403, "message" => "El id_bar no es entero válido"));
	}

	$mysqli = getConnection();
	$new_token = getToken();
	$scope = "CLIENT";
	$active = true;
	$result = $mysqli->query("INSERT INTO tbl_access_tokens (id_bar, token, scope, active, origin, created_At, updated_at) 
		VALUES ($id_bar, '$new_token', '$scope', $active, '$origin', '$date', '$date')");

	if ($result) {
		return $response->withJSON(array("status" => 200, "message" => "Token creado", "token" => $new_token));
	} else {
		return $response->withJSON(array("status" => 400, "message" => "Error al crear el token"));
	}

});