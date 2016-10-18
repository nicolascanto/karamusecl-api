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


$app->put('/api/settings', function($request, $response, $args){

	$id_bar = $request->getAttribute('id_bar');
	$order_limit = isset($request->getParsedBody()['order_limit']) ? $request->getParsedBody()['order_limit'] : null;
	$avatar = isset($request->getParsedBody()['avatar']) ? $request->getParsedBody()['avatar'] : null;
	$bar_name = isset($request->getParsedBody()['bar_name']) ? $request->getParsedBody()['bar_name'] : null;
	$address = isset($request->getParsedBody()['address']) ? $request->getParsedBody()['address'] : null;
	$mysqli = getConnection();
	
	$updateArr = array(
		array(
			"order_limit" => $order_limit,
			"updated" => false),
		array(
			"avatar" => $avatar,
			"updated" => false),
		array(
			"bar_name" => $bar_name,
			"updated" => false),
		array(
			"address" => $address,
			"updated" => false));

	
	if (!is_null($updateArr[0]['order_limit'])) {
		$result = $mysqli->query("UPDATE tbl_bar_settings SET order_limit = $order_limit WHERE id_bar = $id_bar");
		if ($mysqli->affected_rows > 0) {
			$updateArr[0]['updated'] = true;
		}
	}

	if (!is_null($updateArr[1]['avatar'])) {
		$result = $mysqli->query("UPDATE tbl_bar_settings SET avatar = '$avatar' WHERE id_bar = $id_bar");
		if ($mysqli->affected_rows > 0) {
			$updateArr[1]['updated'] = true;
		}
	}

	if (!is_null($updateArr[2]['bar_name'])) {
		$result = $mysqli->query("UPDATE tbl_bars SET name = '$bar_name' WHERE id = $id_bar");
		if ($mysqli->affected_rows > 0) {
			$updateArr[2]['updated'] = true;
		}
	}

	if (!is_null($updateArr[3]['address'])) {
		$result = $mysqli->query("UPDATE tbl_bars SET address = '$address' WHERE id = $id_bar");
		if ($mysqli->affected_rows > 0) {
			$updateArr[3]['updated'] = true;
		}
	}
	

	return $response->withJSON(array("status" => 200, "message" => "Se han actualizado los datos", 
		"data" => $updateArr));

})->add($authorization);



