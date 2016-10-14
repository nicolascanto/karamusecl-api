<?php

$app->get('/api/load/{channel}/{part}', function($request, $response, $args){

	
	require_once('lib/webscraping.php');
	$part = $args['part'];
	$channel = $args['channel'];

	$id_channel = getChannelId($channel);
	
	$mysqli = getConnection();
	$json_kara = init($part, $channel, false);

	if (!$json_kara) {
		echo "JSON is false </br>";
	} else {
 		//echo "item: ". $item['url'];
 	
 		// $query = "INSERT INTO tbl_karaokes (title, url, id_channel, time, seen, published, active) 
 		// values ('$title', '$url', $id_channel, '$time', $seen, '$published', $active) 
 		// ON DUPLICATE KEY UPDATE title='$title', time='$time', seen=$seen, published='$published', active=$active";
 		// $result = $mysqli->query($query);

	 	// if ($result) {
	 	// 	echo "Se ha insertado el karaoke a tbl_karaokes del canal " .$channel . " <br/>";
	 	// } else {
	 	// 	echo "Ocurrió un error insertando, ".$mysqli->error . "<br/>";
	 	// }

 		$stmt = $mysqli->prepare("UPDATE tbl_karaokes SET time = ? WHERE url = ?");
	 	$stmt->bind_param('ss', $time, $url);
	 	foreach ($json_kara as $item) {
			$title = $item['title'];
	 		$url = $item['url'];
	 		$time = $item['time'];
	 		$seen = $item['seen'];
	 		$published = $item['published'];
	 		$active = true;
			$stmt->execute();
			if ($mysqli->affected_rows) {
		 		echo "Se ha actualizado el karaoke del canal " .$channel. ", time: " .$time. "<br/>";
		 	} else {
		 		echo "No se actualizó el karaoke ".$mysqli->error . ", time: " .$time. "<br/>";
		 	}
		}
	 	$mysqli->close();
	}

 });

function getChannelId($name){
	$mysqli = getConnection();
	$query_channels = "SELECT id FROM tbl_channels WHERE name='" . $name . "'"; 
	$resul_channels = $mysqli->query($query_channels);
	$mysqli->close();

	$row = $resul_channels->fetch_array(MYSQLI_ASSOC);

	if ($row) {
		return $row['id'];
	}
}
