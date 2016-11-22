<?php

$app->get('/api/catalog/{filter}', function($request, $response, $args){

 	$filter = $args['filter'];
 	$sizePage = $request->getQueryParam('sizePage', $default = null);
 	$numPage = $request->getQueryParam('numPage', $default = null);
 	$mysqli = getConnection();
 	$result = $mysqli->query("SELECT id FROM tbl_karaokes_old WHERE artist LIKE '%$filter%' 
 		OR song LIKE '%$filter%' AND active = true");

 	if ($result->num_rows > 0) {
 		while ($row = $result->fetch_assoc()) {
	 		$dataPurity[] = $row;
	 	}
	 	
	 	$totalResults = count($dataPurity);
	 	$paging = new paging;
	 	$dataResult = $paging->compute($sizePage, $numPage, $totalResults);
	 	$start = $dataResult['start'];
	 	
	 	$result = $mysqli->query("SELECT * FROM tbl_karaokes_old WHERE artist LIKE '%$filter%' 
 			OR song LIKE '%$filter%' AND active = true ORDER BY song DESC LIMIT $start, $sizePage");

	 	if ($result->num_rows > 0) {
	 		while ($row = $result->fetch_assoc()) {
		 		$dataPaging[] = $row;
		 	}
		 	return $response->withJSON(array(
	 		"status" => 200, 
	 		"totalResults" => $totalResults,
	 		"totalPages" => $dataResult['totalPages'], 
		 	"data" => $dataPaging));
		 }

 	} else {
 		return $response->withJSON(array("status" => 404, "message" => "No se encontraron resultados"));
 	}

 	$mysqli->close();

})->add($authorization);

$app->post('/api/catalog', function($request, $response, $args){

	$artist = isset($request->getParsedBody()['artist']) ? $request->getParsedBody()['artist'] : null;
	$song = isset($request->getParsedBody()['song']) ? $request->getParsedBody()['song'] : null;
	$url = isset($request->getParsedBody()['url']) ? $request->getParsedBody()['url'] : null;

	if (is_null($artist) || is_null($song) || is_null($url)) {
		return $response->withJSON(array("status" => 400, "message" => "Debes especificar artista, canción y url"));
	} else {
		$mysqli = getConnection();
		$result = $mysqli->query("INSERT INTO tbl_karaokes_old (artist, song, url, active) VALUES ('$artist', '$song', '$url', false)");
		if ($result) {
			return $response->withJSON(array("status" => 200, "message" => "Karaoke enviado", 
				"data" => array("artist" => $artist, "song" => $song, "url" => $url)));
		} elseif ($mysqli->error_list[0]["errno"] === 1062) {
			return $response->withJSON(array("status" => 201, "message" => "El karaoke ya fue enviado anteriormente",
				"error" => $mysqli->error_list[0]["error"],
				"errno" => $mysqli->error_list[0]["errno"]));
		} else {
			return $response->withJSON(array("status" => 400, "message" => "No se pudo enviar el karaoke"));
		}
	}

})->add($authorization);

