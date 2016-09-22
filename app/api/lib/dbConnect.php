<?php

function getConnection(){

	//Util para cambiar de servidor
	$server = "local";

	if ($server == "local") {
		$host = "localhost";
		$user = "root";
		$pass = "ewinkanka2015";
		$db_name = "karamusecl";
	} elseif ($server == "development") {
		$host = "localhost";
		$user = "";
		$pass = "";
		$db_name = "karamusecl";
	} elseif ($server == "production") {
		$host = "localhost";
		$user = "";
		$pass = "";
		$db_name = "karamusecl";		
	}
	$mysqli = new mysqli($host, $user, $pass, $db_name);
	$mysqli->set_charset("utf8");

	if (mysqli_connect_errno()) {
	    printf("Conexión fallida: %s\n", mysqli_connect_error());
	    exit();
	}
	return  $mysqli;
}