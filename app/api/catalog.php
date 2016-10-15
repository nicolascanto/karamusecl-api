<?php

 $app->get('/api/catalog/{filter}', function($request, $response, $args){

 	$filter = $args['filter'];
 	$sizePage = $request->getQueryParam('sizePage', $default = null);
 	$numPage = $request->getQueryParam('numPage', $default = null);
 	$mysqli = getConnection();
 	$result = $mysqli->query("SELECT * FROM tbl_karaokes WHERE title LIKE '%$filter%' 
 		AND active = true");

 	if ($result->num_rows > 0) {
 		while ($row = $result->fetch_assoc()) {
	 		$dataPurity[] = $row;
	 	}
	 	
	 	$totalResults = count($dataPurity);
	 	$paging = new paging;
	 	$dataResult = $paging->compute($sizePage, $numPage, $totalResults);
	 	$start = $dataResult['start'];
	 	
	 	$result = $mysqli->query("SELECT * FROM tbl_karaokes WHERE title LIKE '%$filter%' 
 			AND active = true ORDER BY seen DESC LIMIT $start, $sizePage");

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

 });