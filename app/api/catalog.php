<?php

 $app->get('/api/catalog/{filter}', function($request, $response, $args){

 	$mysqli = getConnection();
 	$query = "SELECT * FROM tbl_karaokes WHERE title LIKE '%" . $args['filter'] . "%'";
 	$result = $mysqli->query($query);

 	while ($row = $result->fetch_assoc()) {
 		$data[] = $row;
 	}
 
 	if (isset($data)) {
 		return $response->withJSON($data, 200);
 	} else {
 		return $response->withJSON(array("message" => "No se encontraron resultados"), 201);
 	}

 	$mysqli->close();

 });

 $app->get('/api/catalog/', function($request, $response, $args){

 	$mysqli = getConnection();
 	$query = "SELECT * FROM tbl_karaokes limit 200";
 	$result = $mysqli->query($query);

 	while ($row = $result->fetch_assoc()) {
 		$data[] = $row;
 	}

 	if (isset($data)) {
 		return $response->withJSON($data, 200);
 	} else {
 		return $response->withJSON(array("message" => "No se encontraron resultados"), 201);
 	}

 	$mysqli->close();

 });