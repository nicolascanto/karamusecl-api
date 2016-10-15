<?php

 $app->get('/api/catalog/{filter}', function($request, $response, $args){

 	$filter = $args['filter'];
 	$mysqli = getConnection();
 	$result = $mysqli->query("SELECT * FROM tbl_karaokes WHERE title LIKE '%$filter%' 
 	AND active = true");

 	if ($result->num_rows > 0) {
 		while ($row = $result->fetch_assoc()) {
	 		$data[] = $row;
	 	}
	 	return $response->withJSON(array("status" => 200, "data" => $data));
 	} else {
 		return $response->withJSON(array("status" => 404, "message" => "No se encontraron resultados"));
 	}

 	$mysqli->close();

 });