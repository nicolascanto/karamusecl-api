<?php

$app->get('/api/load/{part}', function($request, $response, $args){

	
	require_once('lib/webscraping.php');
	$part = $args['part'];
	$channel = 'Super Karaoke Latino';

	$id_channel = getChannelId($channel);
	
	$mysqli = getConnection();
	$json_kara = init($part, $channel, false);

	if (!$json_kara) {
		echo "JSON is false </br>";
	} else {
		foreach ($json_kara as $item) {
	 		//echo "item: ". $item['url'];
	 		$title = $item['title'];
	 		$url = $item['url'];
	 		$time = $item['time'];
	 		$seen = $item['seen'];
	 		$published = $item['published'];
	 		$active = 1;

	 		$query = "INSERT INTO tbl_karaokes (title, url, id_channel, time, seen, published, active) 
	 		values ('$title', '$url', $id_channel, '$time', $seen, '$published', $active) 
	 		ON DUPLICATE KEY UPDATE title='$title', time='$time', seen=$seen, published='$published', active=$active";
		 	$result = $mysqli->query($query);

		 	if ($result == 1) {
		 		echo "Se ha insertado el karaoke a tbl_karaokes del canal " .$channel . " <br/>";
		 	} else {
		 		echo "Ocurrió un error insertando, ".$mysqli->error . "<br/>";
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
