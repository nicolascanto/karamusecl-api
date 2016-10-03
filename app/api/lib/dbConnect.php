<?php

// La contraseña para el nuevo usuario FTP u643273191.dev ha sido establecida a ig6aiTFqTZ
function getConnection(){

	//Util para cambiar de servidor
	$server = "development";
	//$server = "development";
	//$server = "production";

	switch ($server) {
		case 'local':
			$host = "localhost";
			$user = "root";
			$pass = "ewinkanka2015";
			$db_name = "karamusecl";
			break;
		case 'development':
			$host = "mysql.hostinger.es";
			$user = "u643273191_dev";
			$pass = "etZdEI8zSW";
			$db_name = "u643273191_dev";
			break;
		case 'production':
			$host = "";
			$user = "";
			$pass = "";
			$db_name = "";
			break;
	}
	$mysqli = new mysqli($host, $user, $pass, $db_name);
	$mysqli->set_charset("utf8");

	if (mysqli_connect_errno()) {
	    printf("Conexión fallida: %s\n", mysqli_connect_error());
	    exit();
	}
	return  $mysqli;
}