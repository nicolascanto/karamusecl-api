<?php

$app->post('/api/login/', function($request, $response, $args){



});

$app->post('/api/register/', function($request, $response, $args){

	$mysqli = getConnection();
	$email = (isset($request->getParsedBody()['email'])) ? $request->getParsedBody()['email'] : null;
	$result = $mysqli->query("SELECT * FROM `tbl_bars` WHERE email='$email'");
	$row_cnt = $result->num_rows;
	$row = $result->fetch_assoc();

	if ($row_cnt > 0) {

		if ($row['active']) {

			return $response->withJSON(array("status" => 201, "message" => "El bar ya existe y se encuentra activo"));

		} else {
			
			return $response->withJSON(array("status" => 202, "message" => "El bar ya existe pero aún no termina el proceso de registro"));

		}

	} else {

		$query_insert_bar = "INSERT INTO `karamusecl`.`tbl_bars` (`rut`, `name`, `address`, `phone`, `email`, `region`, `commune`, `city`, `active`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?);";
		$stmt = $mysqli->prepare($query_insert_bar);
		$stmt->bind_param('ssssssssi', $rut, $name, $address, $phone, $email, $region, $commune, $city, $active);
		
		$rut = (isset($request->getParsedBody()['rut'])) ? $request->getParsedBody()['rut'] : null;
		$name = (isset($request->getParsedBody()['name'])) ? $request->getParsedBody()['name'] : null;
		$address = (isset($request->getParsedBody()['address'])) ? $request->getParsedBody()['address'] : null;
		$phone = (isset($request->getParsedBody()['phone'])) ? $request->getParsedBody()['phone'] : null;
		$email = (isset($request->getParsedBody()['email'])) ? $request->getParsedBody()['email'] : null;
		$region = (isset($request->getParsedBody()['region'])) ? $request->getParsedBody()['region'] : null;
		$commune = (isset($request->getParsedBody()['commune'])) ? $request->getParsedBody()['commune'] : null;
		$city = (isset($request->getParsedBody()['city'])) ? $request->getParsedBody()['city'] : null;
		$active = (isset($request->getParsedBody()['active'])) ? $request->getParsedBody()['active'] : 0;

		$stmt->execute();
		if (json_encode($stmt->affected_rows)) {
			return $response->withJSON(array("status" => 200, "message" => "Se ha completado el registro del bar", "rut" => $row['rut']));
		} else {
			return $response->withJSON(array("status" => 400, "message" => "No se ha completado el registro del bar", "rut" => $row['rut']));
		}
		$stmt->close();
	}

	$mysqli->close();
	$result->close();

});