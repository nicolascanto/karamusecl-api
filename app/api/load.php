<?php

// $app->get('/api/load/{channel}/{part}', function($request, $response, $args){

	
// 	require_once('lib/webscraping.php');
// 	$part = $args['part'];
// 	$channel = $args['channel'];

// 	$id_channel = getChannelId($channel);
	
// 	$mysqli = getConnection();
// 	$json_kara = init($part, $channel, false);

// 	if (!$json_kara) {
// 		echo "JSON is false </br>";
// 	} else {
//  		//echo "item: ". $item['url'];
 	
//  		// $query = "INSERT INTO tbl_karaokes (title, url, id_channel, time, seen, published, active) 
//  		// values ('$title', '$url', $id_channel, '$time', $seen, '$published', $active) 
//  		// ON DUPLICATE KEY UPDATE title='$title', time='$time', seen=$seen, published='$published', active=$active";
//  		// $result = $mysqli->query($query);

// 	 	// if ($result) {
// 	 	// 	echo "Se ha insertado el karaoke a tbl_karaokes del canal " .$channel . " <br/>";
// 	 	// } else {
// 	 	// 	echo "Ocurrió un error insertando, ".$mysqli->error . "<br/>";
// 	 	// }

//  		$stmt = $mysqli->prepare("UPDATE tbl_karaokes SET time = ? WHERE url = ?");
// 	 	$stmt->bind_param('ss', $time, $url);
// 	 	foreach ($json_kara as $item) {
// 			$title = $item['title'];
// 	 		$url = $item['url'];
// 	 		$time = $item['time'];
// 	 		$seen = $item['seen'];
// 	 		$published = $item['published'];
// 	 		$active = true;
// 			$stmt->execute();
// 			if ($mysqli->affected_rows) {
// 		 		echo "Se ha actualizado el karaoke del canal " .$channel. ", time: " .$time. "<br/>";
// 		 	} else {
// 		 		echo "No se actualizó el karaoke ".$mysqli->error . ", time: " .$time. "<br/>";
// 		 	}
// 		}
// 	 	$mysqli->close();
// 	}

//  });

// function getChannelId($name){
// 	$mysqli = getConnection();
// 	$query_channels = "SELECT id FROM tbl_channels WHERE name='" . $name . "'"; 
// 	$resul_channels = $mysqli->query($query_channels);
// 	$mysqli->close();

// 	$row = $resul_channels->fetch_array(MYSQLI_ASSOC);

// 	if ($row) {
// 		return $row['id'];
// 	}
// }

$app->post('/api/load/youtube/search', function($request, $response, $args){
	$url_search = isset($request->getParsedBody()['url_search']) ? $request->getParsedBody()['url_search'] : null;
	if (is_null($url_search)) {
		return $response->withJSON(array("status" => 400, "message" => "url_search es null."));
	}
	require_once('lib/webscraping.php');
	$url_video = youtube_search($url_search);
	$url_video = "https://www.youtube.com" . $url_video;
	return $response->withJSON(array("status" => 200, "url_video" => $url_video));
	
});

$app->put('/api/load/karaokes/insert/url', function($request, $response, $args){

	// id_karaoke es donde va a empezar el UPDATE
	$id_karaoke = null;
	$date = date('Y-m-d H:i:s');

	$data = array();
	$mysqli = getConnection();

	$result = $mysqli->query("SELECT max(id) FROM tbl_karaokes_old WHERE url IS NOT NULL ORDER BY id DESC");
	if ($result->num_rows > 0) {
		$row = $result->fetch_assoc();
		$id_karaoke = $row['max(id)'] + 1;
	} else {
		return $response->withJSON(array("message" => "No hay resultados de max(id), id_karaoke es NULL."));
	}

	$result = $mysqli->query("SELECT id, artist, song FROM tbl_karaokes_old WHERE id >= $id_karaoke");
	if ($result->num_rows > 0) {
		while ($row = $result->fetch_assoc()) {
	 		$data[] = $row;
	 	}

	 	$stmt = $mysqli->prepare("UPDATE tbl_karaokes_old SET url = ?, updated_at = ? WHERE id = ?");
 		$stmt->bind_param('ssi', $url_video, $date, $id);

 		require_once('lib/webscraping.php');

	 	foreach ($data as $item) {
	 		$id = $item['id'];
	 		$artist = $item['artist'];
	 		$song = $item['song'];
	 		$full_string = $artist . '+' . $song . '+karaoke';
	 		$search_string = str_replace(" ", "+", $full_string);

	 		$url_search = "https://www.youtube.com/results?search_query=" . $search_string;
			$url_video = youtube_search($url_search);
			$url_video = "https://www.youtube.com" . $url_video;
			// return $response->withJSON(array("full_string" => $full_string, "url_search" => $url_search, "url_video" => $url_video, "data" => $data));
			$stmt->execute();
	 	}

	 	$mysqli->close();

	 	/* Para revisar cuantas url son distintas a NULL
		SELECT * FROM `tbl_karaokes_old` WHERE `url` IS NOT NULL ORDER BY `id` DESC
	 	*/
	}
});









