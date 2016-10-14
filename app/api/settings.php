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