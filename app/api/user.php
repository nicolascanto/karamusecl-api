<?php

$app->post('/api/login', function($request, $response, $args){
	$email = (isset($request->getParsedBody()['email'])) ? $request->getParsedBody()['email'] : null;
	$password = (isset($request->getParsedBody()['password'])) ? $request->getParsedBody()['password'] : null;
	$arraydata = array(
		"email" => $email);

	if (is_null($email) || is_null($password)) {
		return $response->withJSON(array(
			"status" => 400,
			"message" => "Email y password requeridos"));
	}

	$mysqli = getConnection();
	$result = $mysqli->query("SELECT password FROM tbl_bars WHERE email = '$email'");
	$num_rows = $result->num_rows;

	if ($num_rows > 0) {
		$row = $result->fetch_assoc();
		if (password_verify($password, $row['password'])) {
		    return $response->withJSON(array(
		    	"status" => 200,
		    	"message" => "Usuario verificado correctamente",
		    	"data" => $arraydata));
		} else {
		    return $response->withJSON(array(
		    	"status" => 401,
		    	"message" => "Usuario y/o password incorrectos",
		    	"data" => $arraydata));
		}
	} else {
		return $response->withJSON(array(
			"status" => 404,
			"message" => "Usuario no existe",
			"data" => $arraydata));
	}

});

$app->post('/api/register', function($request, $response, $args){

	$rut = (isset($request->getParsedBody()['rut'])) ? $request->getParsedBody()['rut'] : null;
	$name = (isset($request->getParsedBody()['name'])) ? $request->getParsedBody()['name'] : null;
	$address = (isset($request->getParsedBody()['address'])) ? $request->getParsedBody()['address'] : null;
	$phone = (isset($request->getParsedBody()['phone'])) ? $request->getParsedBody()['phone'] : null;
	$email = (isset($request->getParsedBody()['email'])) ? $request->getParsedBody()['email'] : null;
	$region = (isset($request->getParsedBody()['region'])) ? $request->getParsedBody()['region'] : null;
	$commune = (isset($request->getParsedBody()['commune'])) ? $request->getParsedBody()['commune'] : null;
	$city = (isset($request->getParsedBody()['city'])) ? $request->getParsedBody()['city'] : null;
	$password = (isset($request->getParsedBody()['password'])) ? $request->getParsedBody()['password'] : null;
	$active = (isset($request->getParsedBody()['active'])) ? $request->getParsedBody()['active'] : 0;
	$token = (isset($request->getParsedBody()['token'])) ? $request->getParsedBody()['token'] : null;


	$arraydata = array(
		"email" => $email, 
		"phone" => $phone, 
		"address" => $address, 
		"city" => $city, 
		"commune" => $commune, 
		"rut" => $rut, 
		"region" => $region, 
		"name" => $name,
		"token" => $token,
		"password" => $password);

	$mysqli = getConnection();
	$result = $mysqli->query("SELECT email, phone, active FROM tbl_bars WHERE email='$email'");
	$row_cnt = $result->num_rows;
	$row = $result->fetch_assoc();

	if ($row_cnt > 0) {
		if ($row['active']) {

			return $response->withJSON(array("status" => 201, 
				"message" => "El bar ya existe y se encuentra activo"));

		} elseif(!is_null($rut) && !is_null($name) && !is_null($address) && !is_null($region) && !is_null($commune) && !is_null($city) && !is_null($token) && !is_null($password)) {
			// actualizo los datos del bar
			$active = true;
			$password = password_hash($password, PASSWORD_DEFAULT);
			$query_update_bar = "UPDATE tbl_bars 
			SET rut = ?, name = ?, address = ?, phone = ?, email = ?, region = ?, 
			commune = ?, city = ?, active = ?, password = ? WHERE email = '$email';";
			$stmt1 = $mysqli->prepare($query_update_bar);
			$stmt1->bind_param('ssssssssis', $rut, $name, $address, $phone, 
				$email, $region, $commune, $city, $active, $password);
			$stmt1->execute();

			if (json_encode($stmt1->affected_rows)) {
				$message1 = "Se ha completado el segundo registro del bar";
				
				//invalidar token de registro
				$state = false;
				$query_invalidate_token = "UPDATE tbl_active_tokens SET state = ? WHERE token = ?;";
				$stmt2 = $mysqli->prepare($query_invalidate_token);
				$stmt2->bind_param('is', $state, $token);
				$stmt2->execute();
				if (json_encode($stmt2->affected_rows)) {
					$message2 = "Se ha desactivado el token";
				} else {
					$message2 = "No se ha desactivado el token";
				}

				return $response->withJSON(array("status" => 201, 
						"message1" => $message1, 
						"message2" => $message2, 
						"data" => $arraydata));
			} else {
				return $response->withJSON(array("status" => 402, 
					"message" => "No se ha completado segundo el registro del bar", 
					"data" => $arraydata));
			}
			$stmt1->close();
			$stmt2->close();

		} else {
	
			return $response->withJSON(array("status" => 403, 
				"message" => "No se puede registrar el bar", 
				"data" => $arraydata));
		}
	} else {
		$active = false;
		$query_insert_bar = "INSERT INTO tbl_bars (rut, name, address, phone, email, region, commune, city, active, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?);";
		$stmt1 = $mysqli->prepare($query_insert_bar);
		$stmt1->bind_param('ssssssssis', $rut, $name, $address, $phone, 
			$email, $region, $commune, $city, $active, $password);
		$stmt1->execute();

		if (json_encode($stmt1->affected_rows)) {
			$token = sha1(mt_rand().time().mt_rand().$_SERVER['REMOTE_ADDR']);
			$state = 1;
			$query_active_token = "INSERT INTO tbl_active_tokens (id_bar, token, state) 
			VALUES (LAST_INSERT_ID(), ?, ?);";
			$stmt2 = $mysqli->prepare($query_active_token);
			$stmt2->bind_param('si', $token, $state);
			$stmt2->execute();

			if (json_encode($stmt2->affected_rows)) {
				$message1 = "Se ha creado el token de activación";
			} else {
				$message1 = "No se ha creado el token de activación";
			}

			$mail = new PHPMailer;
			//$mail->SMTPDebug = 2; // Enable verbose debug output
			$mail->CharSet = 'UTF-8';
			$mail->isSMTP(); // Set mailer to use SMTP
			$mail->Host = 'smtp.gmail.com'; // Specify main and backup SMTP servers
			$mail->SMTPAuth = true; // Enable SMTP authentication
			$mail->Username = 'karamuseapp@gmail.com'; // SMTP username
			$mail->Password = 'inspirate2016'; // SMTP password
			$mail->SMTPSecure = 'ssl'; // Enable TLS port(587) encryption, `ssl` port(465) also accepted
			$mail->Port = 465; // TCP port to connect to

			$mail->setFrom('hola@karamuse.cl', 'Karamuse');
			$mail->addAddress($email); // Add a recipient
			$mail->addReplyTo('hola@karamuse.cl', 'Information');
			$mail->addBCC('nicolascanto1@gmail.com');
			$mail->isHTML(true); // Set email format to HTML

			$mail->Subject = '¡Estás a un paso de completar tu registro!';
			$mail->Body    = getHTML($token);
			$mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

			if(!$mail->send()) {
			    $message2 = 'El primer registro está correcto, pero Mailer Error: ' . $mail->ErrorInfo;
			} else {
			    $message2 = "Se ha enviado un email para completar tu registro";
			}

			return $response->withJSON(array("status" => 200, 
				"message1" => $message1, 
				"message2" => $message2,
				"data" => $arraydata));

		} else {
			return $response->withJSON(array("status" => 401, 
				"message" => "No se ha completado el primer registro del bar", 
				"data" => $arraydata));
		}
		$stmt1->close();
		$stmt2->close();
	}

	$mysqli->close();
	$result->close();

});

$app->post('/api/register/validate_token', function($request, $response, $args){
		$mysqli = getConnection();
		$token = (isset($request->getParsedBody()['token'])) ? $request->getParsedBody()['token'] : null;
		$query_validate_token = "SELECT tbl_bars.email, tbl_bars.phone, tbl_active_tokens.state 
		FROM tbl_active_tokens JOIN tbl_bars ON tbl_bars.id=tbl_active_tokens.id_bar 
		WHERE tbl_active_tokens.token= '$token';";
		$result = $mysqli->query($query_validate_token);
		$row = $result->fetch_assoc();

    	if ($row['state']) {
    		return $response->withJSON(array(
    			"status" => 200,
    			"message" => "El token es válido",
    			"data" => array(
    				"email" => $row['email'],
    				"phone" => $row['phone'],
    				"token" => $token)));
    	} else {
    		return $response->withJSON(array(
    			"status" => 400,
    			"message" => "El token es inválido",
    			"data" => array(
    				"email" => $row['email'],
    				"phone" => $row['phone'],
    				"token" => $token)));
    	}
});

$app->get('/api/register/gethtml', function($request, $response, $args){
	$token = "333999";
	$fichero = file_get_contents('http://karamuse.cl/karamusecl/html/register.html');
	$fichero = str_replace("mytoken", $token, $fichero);
	var_dump($fichero);
});

function getHTML($token){
	$fichero = file_get_contents('http://karamuse.cl/karamusecl/html/register.html');
	$fichero = str_replace("mytoken", $token, $fichero);
	return $fichero;
}





