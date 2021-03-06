<?php

$app->post('/api/login', function($request, $response, $args){
	$email = (isset($request->getParsedBody()['email'])) ? $request->getParsedBody()['email'] : null;
	$password = (isset($request->getParsedBody()['password'])) ? $request->getParsedBody()['password'] : null;
	$origin = (isset($request->getParsedBody()['origin'])) ? $request->getParsedBody()['origin'] : null;
	$response_data = array("email" => $email);
	$date = date('Y-m-d H:i:s');

	if (is_null($email) || is_null($password)) {
		return $response->withJSON(array(
			"status" => 400,
			"message" => "Email y password requeridos"));
	}

	$mysqli = getConnection();
	$result = $mysqli->query("SELECT tbl_bars.password, tbl_bars.id, tbl_bars.email, tbl_bars.name, 
		tbl_bars.address, tbl_bar_settings.avatar 
		FROM tbl_bars JOIN tbl_bar_settings ON tbl_bar_settings.id_bar = tbl_bars.id 
		WHERE tbl_bars.email = '$email' AND tbl_bars.active = true");

	if ($result->num_rows > 0) {
		$row = $result->fetch_assoc();
		if (password_verify($password, $row['password'])) {
			
			$id_bar = $row['id'];
			$response_data['email'] = $row['email'];
			$response_data['name'] = $row['name'];
			$response_data['address'] = $row['address'];
			$response_data['avatar'] = $row['avatar'];
			
			// LOGICA CADUCIDAD ACCESS TOKEN
			// ...
			// FIN

			$new_token = getToken();
			$scope = "DJ";
			$active = true;
			$result = $mysqli->query("INSERT INTO tbl_access_tokens (id_bar, token, scope, active, origin, created_at, updated_at) 
				VALUES ($id_bar, '$new_token', '$scope', $active, '$origin', '$date', '$date')");

			if ($result) {

				$result = $mysqli->query("SELECT active, created_at FROM tbl_sessions 
					WHERE id_bar = $id_bar AND active = true");

				// VALIDO SI HAY SESIONES ABIERTAS, SI NO CREO UNA SESIÓN
				if ($result->num_rows > 0) {
					$row = $result->fetch_assoc();
					$response_data['token'] = $new_token;
					$response_data['origin'] = $origin;
					$response_data['session'] = array(
						"active" => true,
						"created_at" => $row['created_at']);
					return $response->withJSON(array(
			    	"status" => 200,
			    	"message" => "Usuario verificado correctamente",
			    	"data" => $response_data));
				} else {
					
					$response_data['token'] = $new_token;
					$response_data['session'] = array(
						"active" => false,
						"created_at" => null);
					return $response->withJSON(array(
			    	"status" => 200,
			    	"message" => "Usuario verificado correctamente",
			    	"data" => $response_data));
				}
			}

		} else {
		    return $response->withJSON(array(
		    	"status" => 401,
		    	"message" => "Usuario y/o password incorrectos",
		    	"data" => $response_data));
		}
	} else {
		return $response->withJSON(array(
			"status" => 404,
			"message" => "cuenta no existe o se encuentra desactivada",
			"data" => $response_data));
	}

	$result->close();
	$mysqli->close();

});

$app->post('/api/register', function($request, $response, $args){

	$rut = (isset($request->getParsedBody()['rut'])) ? $request->getParsedBody()['rut'] : null;
	$name = (isset($request->getParsedBody()['name'])) ? $request->getParsedBody()['name'] : null;
	$address = (isset($request->getParsedBody()['address'])) ? $request->getParsedBody()['address'] : null;
	$phone = (isset($request->getParsedBody()['phone'])) ? $request->getParsedBody()['phone'] : null;
	$email = (isset($request->getParsedBody()['email'])) ? $request->getParsedBody()['email'] : null;
	$region = (isset($request->getParsedBody()['region'])) ? $request->getParsedBody()['region'] : null;
	$commune = (isset($request->getParsedBody()['commune'])) ? $request->getParsedBody()['commune'] : null;
	$province = (isset($request->getParsedBody()['province'])) ? $request->getParsedBody()['province'] : null;
	$password = (isset($request->getParsedBody()['password'])) ? $request->getParsedBody()['password'] : null;
	$active = (isset($request->getParsedBody()['active'])) ? $request->getParsedBody()['active'] : 0;
	$token = (isset($request->getParsedBody()['token'])) ? $request->getParsedBody()['token'] : null;
	$date = date('Y-m-d H:i:s');


	$response_data = array(
		"email" => $email, 
		"phone" => $phone, 
		"address" => $address, 
		"province" => $province, 
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

			return $response->withJSON(array(
				"status" => 202, 
				"message" => "El bar ya existe y se encuentra activo",
				"data" => $response_data));

		} elseif(!is_null($rut) && !is_null($name) && !is_null($address) && !is_null($region) && !is_null($province) && !is_null($commune) && !is_null($token) && !is_null($password)) {
			
			// ACTUALIZA LOS DATOS DEL BAR
			$active = true;
			$password = password_hash($password, PASSWORD_DEFAULT);
			$result = $mysqli->query("UPDATE tbl_bars 
			SET rut = '$rut', name = '$name', address = '$address', phone = '$phone', email = '$email', 
			region = '$region', commune = '$commune', province = '$province', active = $active, 
			password = '$password', updated_at = '$date' WHERE email = '$email'");

			if ($result) {
				$message1 = "Se ha completado el segundo registro del bar";
				
				// INVALIDA TOKEN DE REGISTRO
				$state = false;
				$result = $mysqli->query("UPDATE tbl_active_tokens SET state = $state, updated_at = '$date' WHERE token = '$token'");	

				if ($result) {
					$message2 = "Se ha desactivado el token";
				} else {
					$message2 = "No se ha desactivado el token";
				}

				return $response->withJSON(array("status" => 201, 
						"message1" => $message1, 
						"message2" => $message2, 
						"data" => $response_data));
			} else {
				return $response->withJSON(array("status" => 402, 
					"message" => "No se ha completado segundo el registro del bar", 
					"data" => $response_data));
			}

			$result->close();

		} else {
	
			return $response->withJSON(array("status" => 403, 
				"message" => "No se puede registrar el bar", 
				"data" => $response_data));
		}
	} else {
		$result = $mysqli->query("INSERT INTO tbl_bars (rut, name, address, phone, email, region, province, commune, active, password, created_at, updated_at) VALUES (null, null, null, '$phone', '$email', null, 
			null, null, null, null, '$date', '$date')");

		$last_id_bar = $mysqli->insert_id;

		$result = $mysqli->query("INSERT INTO tbl_bar_settings (id_bar, order_limit, avatar, banner_ad, text_ad, created_at, updated_at) 
			VALUES ($last_id_bar, 20, null, null, null, '$date', '$date')");

		if ($result) {
			$token = getToken();
			$active = true;
			$result = $mysqli->query("INSERT INTO tbl_active_tokens (id_bar, token, active, created_at, updated_at) 
			VALUES ($last_id_bar, '$token', $active, '$date', '$date')");

			if ($result) {
				$message1 = "Se ha creado el token de activación";
			} else {
				$message1 = "No se ha creado el token de activación";
			}

			$mail = new PHPMailer;
			//$mail->SMTPDebug = 2; // Enable verbose debug output
			$mail->CharSet = 'UTF-8';
			$mail->isSMTP();
			$mail->Host = 'smtp.gmail.com';
			$mail->SMTPAuth = true;
			$mail->Username = 'karamuseapp@gmail.com';
			$mail->Password = 'inspirate2016';
			$mail->SMTPSecure = 'ssl';
			$mail->Port = 465;

			$mail->setFrom('hola@karamuse.cl', 'Karamuse');
			$mail->addAddress($email);
			$mail->addReplyTo('hola@karamuse.cl', 'Information');
			$mail->addBCC('nicolascanto1@gmail.com');
			$mail->isHTML(true);

			$mail->Subject = '¡Estás a un paso de completar tu registro!';
			$mail->Body    = getHTML_register($token);
			$mail->AltBody = '...';

			if(!$mail->send()) {
			    $message2 = 'El primer registro está correcto, pero Mailer Error: ' . $mail->ErrorInfo;
			} else {
			    $message2 = "Se ha enviado un email para completar tu registro";
			}

			return $response->withJSON(array("status" => 200, 
				"message1" => $message1, 
				"message2" => $message2,
				"data" => $response_data));

		} else {
			return $response->withJSON(array("status" => 401, 
				"message" => "No se ha completado el primer registro del bar", 
				"data" => $response_data,
				"extra" => $last_id_bar));
		}
		
		$result->close();
		
	}

	$mysqli->close();
	$result->close();

});

$app->post('/api/register/validate_token', function($request, $response, $args){
		
		$token = (isset($request->getParsedBody()['token'])) ? $request->getParsedBody()['token'] : null;
		$response_data = array(
			"token" => $token);

		$mysqli = getConnection();
		$result = $mysqli->query("SELECT tbl_bars.email, tbl_bars.phone, tbl_active_tokens.active 
		FROM tbl_active_tokens JOIN tbl_bars ON tbl_bars.id=tbl_active_tokens.id_bar 
		WHERE tbl_active_tokens.token= '$token'");
		$row = $result->fetch_assoc();

		$response_data['email'] = $row['email'];
		$response_data['phone'] = $row['phone'];

    	if ($row['active']) {
    		return $response->withJSON(array(
    			"status" => 200,
    			"message" => "El token es válido",
    			"data" => $response_data));
    	} else {
    		return $response->withJSON(array(
    			"status" => 400,
    			"message" => "El token es inválido",
    			"data" => $response_data));
    	}
});

$app->post('/api/register/renewpass/{step}', function($request, $response, $args){
	$email = (isset($request->getParsedBody()['email'])) ? $request->getParsedBody()['email'] : null;
	$token = (isset($request->getParsedBody()['token'])) ? $request->getParsedBody()['token'] : null;
	$new_pass = (isset($request->getParsedBody()['new_pass'])) ? $request->getParsedBody()['new_pass'] : null;
	$date = date('Y-m-d H:i:s');

	$response_data = array(
		"email" => $email,
		"token" => $token,
		"new_pass" => $new_pass);

	if (is_numeric($args['step'])) {
		$mysqli = getConnection();
			
			switch ($args['step']) {
				case 1: // ENVIAR EMAIL
					if (!is_null($email)) {
						$result = $mysqli->query("SELECT id FROM tbl_bars WHERE email = '$email'");
						$row = $result->fetch_assoc();
						if ($result->num_rows > 0) {
							$id_bar = $row['id'];
							$token = getToken();
							$active = true;
							$result = $mysqli->query("INSERT INTO tbl_renew_pass (id_bar, token, active, created_at, updated_at) 
								VALUES ($id_bar, '$token', $active, '$date', '$date')");

							if ($result) {
								$opts = array(
									"email" => $email,
									"subject" => "Haz solicitado recuperar tu contraseña",
									"body" => getHTML_renew_pass($email, $token),
									"token" => $token);

								 if (sendEmail($opts)) {
								 	return $response->withJSON(array(
									"status" => 200,
									"message" => "Email enviado",
									"data" => $response_data));
								 } else {
								 	return $response->withJSON(array(
									"status" => 405,
									"message" => "Email no enviado",
									"data" => $response_data));
								 }
							} else {
								return $response->withJSON(array(
								"status" => 400,
								"message" => "No se pudo realizar la operación",
								"data" => $response_data));
							}
						} else {
							return $response->withJSON(array(
							"status" => 404,
							"message" => "Bar no encontrado",
							"data" => $response_data));
						}
					} else {
						return $response->withJSON(array(
						"status" => 403,
						"message" => "Email requerido",
						"data" => $response_data));
					}

					break;

				case 2: // VALIDAR CODIGO
					if (!is_null($token) && !is_null($new_pass)) {
						$result = $mysqli->query("SELECT tbl_bars.id, tbl_bars.email FROM tbl_bars 
							JOIN tbl_renew_pass 
							ON tbl_renew_pass.id_bar=tbl_bars.id
							WHERE tbl_renew_pass.token = '$token' 
							AND tbl_renew_pass.active = true AND tbl_renew_pass.created_at >= date_sub(now(), INTERVAL 24 hour)");
						if ($result->num_rows > 0) {
							$row = $result->fetch_assoc();
							$email = $row['email'];
							$id_bar = $row['id'];
							$new_pass = password_hash($new_pass, PASSWORD_DEFAULT);

							$result = $mysqli->query("UPDATE tbl_bars 
								SET password = '$new_pass', updated_at = '$date'
								WHERE email = '$email'");

							if ($result) {
								$result = $mysqli->query("UPDATE tbl_renew_pass 
									SET active = false, updated_at = '$date'
									WHERE id_bar = $id_bar");
								if ($result) {
									return $response->withJSON(array(
									"status" => 200,
									"message" => "Se ha cambiado la contraseña exitosamente",
									"data" => $response_data));
								} else {
									return $response->withJSON(array(
									"status" => 201,
									"message" => "Se ha cambiado la contraseña exitosamente, pero no se desactivó el token",
									"data" => $response_data));
								}
							}
						} else {
							return $response->withJSON(array(
							"status" => 401,
							"message" => "No se pudo verificar el token",
							"data" => $response_data));
						}			
					} else {
						return $response->withJSON(array(
							"status" => 402,
							"message" => "token y/o password requerido",
							"data" => $response_data));
					}

						break;
			}

	} else {
		return $response->withJSON(array(
		"status" => 406,
		"message" => "Step not found",
		"data" => $response_data));
	}
});

/* 
https://apis.modernizacion.cl/dpa/regiones
https://apis.modernizacion.cl/dpa/regiones/{codigo}/provincias
https://apis.modernizacion.cl/dpa/regiones/{codigo}/provincias/{codigo}/comunas                        
para obtener las regiones
para obtener las provincias de una región
para obtener las comunas de una región de una provincia
*/

$app->get('/api/dpa/regiones', function($request, $response, $args){
	
	$url = "https://apis.modernizacion.cl/dpa/regiones";
	$ch = curl_init();  
 
    curl_setopt($ch,CURLOPT_URL,$url);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
 
    $output = curl_exec($ch);
 	$regions = json_decode($output);
    curl_close($ch);
    return $response->withJSON(array("status" => 200, "message" => "Regiones de Chile", "data" => $regions));
});

$app->get('/api/dpa/regiones/{codigo_r}/provincias', function($request, $response, $args){
	$codigo_r = $args['codigo_r'];
	$url = "https://apis.modernizacion.cl/dpa/regiones/" . $codigo_r . "/provincias";
	$ch = curl_init();  
 
    curl_setopt($ch,CURLOPT_URL,$url);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
 
    $output = curl_exec($ch);
 	$provinces = json_decode($output);
    curl_close($ch);
    return $response->withJSON(array("status" => 200, "message" => "Provincias", "data" => $provinces));
});

$app->get('/api/dpa/regiones/{codigo_r}/provincias/{codigo_p}/comunas', function($request, $response, $args){
	$codigo_r = $args['codigo_r'];
	$codigo_p = $args['codigo_p'];
	$url = "https://apis.modernizacion.cl/dpa/regiones/" . $codigo_r . "/provincias/" . $codigo_p . "/comunas";
	$ch = curl_init();  
 
    curl_setopt($ch,CURLOPT_URL,$url);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
 
    $output = curl_exec($ch);
 	$communes = json_decode($output);
    curl_close($ch);
    return $response->withJSON(array("status" => 200, "message" => "Comunas", "data" => $communes));
});

function getHTML_register($token){
	$fichero = file_get_contents('http://dev.karamuse.cl/public/register.html');
	$fichero = str_replace("[token]", $token, $fichero);
	return $fichero;
}

function getHTML_renew_pass($email, $token){
	$fichero = file_get_contents('http://dev.karamuse.cl/public/token_renew_pass.html');
	$fichero = str_replace("[email]", $email, $fichero);
	$fichero = str_replace("[token]", $token, $fichero);
	return $fichero;
}

function getToken(){
	return sha1(mt_rand().time().mt_rand().$_SERVER['REMOTE_ADDR']);
}

function sendEmail($opts) {
	$mail = new PHPMailer;
	//$mail->SMTPDebug = 2; // Enable verbose debug output
	$mail->CharSet = 'UTF-8';
	$mail->isSMTP();
	$mail->Host = 'smtp.gmail.com';
	$mail->SMTPAuth = true;
	$mail->Username = 'karamuseapp@gmail.com';
	$mail->Password = 'inspirate2016';
	$mail->SMTPSecure = 'ssl';
	$mail->Port = 465;

	$mail->setFrom('hola@karamuse.cl', 'Karamuse');
	$mail->addAddress($opts['email']);
	$mail->addReplyTo('hola@karamuse.cl', 'Information');
	$mail->addBCC('nicolascanto1@gmail.com');
	$mail->isHTML(true);

	$mail->Subject = $opts['subject'];
	$mail->Body = $opts['body'];

	if(!$mail->send()) {
	    return false;
	} else {
	    return true;
	}
}





