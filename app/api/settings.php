<?php
$app->put('/api/settings/order_limit/{limit}', function($request, $response, $args){

	$limit = (isset($args['limit'])) ? $args['limit'] : null;
	$id_bar = $request->getAttribute('id_bar');

	if (is_null($limit) || !is_numeric($limit)) {
		return $response->withJSON(array("status" => 400, "message" => "Límite númerico requerido"));
	} else {
		$mysqli = getConnection();
		$result = $mysqli->query("UPDATE tbl_bar_settings SET order_limit = $limit WHERE id_bar = $id_bar");

		if ($mysqli->affected_rows > 0) {
			return $response->withJSON(array("status" => 200, "message" => "Límite de pedidos actualizado"));
		} else {
			return $response->withJSON(array("status" => 402, "message" => "No se ha actualizado el límite de pedidos"));
		}
	}
	
})->add($authorization);

$app->get('/api/settings', function($request, $response, $args){

	$id_bar = $request->getAttribute('id_bar');

	$mysqli = getConnection();
	$result = $mysqli->query("SELECT * FROM tbl_bar_settings WHERE id_bar = $id_bar");

	if ($result->num_rows > 0) {
		$row = $result->fetch_assoc();
		return $response->withJSON(array("status" => 200, "message" => "Configuraciones para el bar", "data" => $row));
	} else {
		return $response->withJSON(array("status" => 404, "message" => "No hay configuraciones para el bar"));
	}
})->add($authorization);