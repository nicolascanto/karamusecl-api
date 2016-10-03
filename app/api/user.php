<?php

$app->post('/api/login', function($request, $response, $args){
	$email = (isset($request->getParsedBody()['email'])) ? $request->getParsedBody()['email'] : null;
	$password = (isset($request->getParsedBody()['password'])) ? $request->getParsedBody()['password'] : null;
	$origin = (isset($request->getParsedBody()['origin'])) ? $request->getParsedBody()['origin'] : null;
	$response_data = array("email" => $email);

	if (is_null($email) || is_null($password)) {
		return $response->withJSON(array(
			"status" => 400,
			"message" => "Email y password requeridos"));
	}

	$mysqli = getConnection();
	$result = $mysqli->query("SELECT password, id FROM tbl_bars WHERE email = '$email' AND active = true");

	if ($result->num_rows > 0) {
		$row = $result->fetch_assoc();
		if (password_verify($password, $row['password'])) {
			
			$id_bar = $row['id'];
			
			// LOGICA CADUCIDAD ACCESS TOKEN
			// $result = $mysqli->query("SELECT token FROM tbl_access_tokens 
			// WHERE id_bar = $id_bar ORDER BY created_at DESC LIMIT 1");

			// if ($result->num_rows > 0) {
			// $row = $result->fetch_assoc();
			// $old_token = $row['token'];
			// $active = false;
			// 	$result = $mysqli->query("UPDATE tbl_access_tokens SET active = $active WHERE token = '$old_token'");
			// }

			$new_token = getToken();
			$active = true;
			$result = $mysqli->query("INSERT INTO tbl_access_tokens (id_bar, token, active, origin) 
				VALUES ($id_bar, '$new_token', $active, '$origin')");

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
					
					$result = $mysqli->query("INSERT INTO tbl_sessions (id_bar, active)
						VALUES ($id_bar, true)");

					if ($result) {
						$last_id = $mysqli->insert_id;
						$stmt = $mysqli->prepare("INSERT INTO tbl_session_codes (id_session, code, state) VALUES (?, ?, 0)");	
						$stmt->bind_param('ii', $last_id, $code);
						for ($i = 0; $i < 50 ; $i++) { 	
							$code = mt_rand(1000,9999);
							$stmt->execute();
						}
					}
					$response_data['session'] = array(
						"active" => false,
						"created_at" => "fecha creacion");
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

$app->post('/api/codes', function($request, $response, $args){
	$count = (isset($request->getParsedBody()['count'])) ? $request->getParsedBody()['count'] : null;
}

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
			password = '$password' WHERE email = '$email'");

			if ($result) {
				$message1 = "Se ha completado el segundo registro del bar";
				
				// INVALIDA TOKEN DE REGISTRO
				$state = false;
				$result = $mysqli->query("UPDATE tbl_active_tokens SET state = $state WHERE token = '$token'");	

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
		$result = $mysqli->query("INSERT INTO tbl_bars (rut, name, address, phone, email, region, province, commune, active, password) VALUES (null, null, null, '$phone', '$email', null, 
			null, null, null, null)");

		if ($result) {
			$token = getToken();
			$active = true;
			$result = $mysqli->query("INSERT INTO tbl_active_tokens (id_bar, token, active) 
			VALUES (LAST_INSERT_ID(), '$token', $active)");

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
			$mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

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
				"data" => $response_data));
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
							$result = $mysqli->query("INSERT INTO tbl_renew_pass (id_bar, token, active) 
								VALUES ($id_bar, '$token', $active)");

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
								SET password = '$new_pass'
								WHERE email = '$email'");

							if ($result) {
								$result = $mysqli->query("UPDATE tbl_renew_pass 
									SET active = false
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

function getHTML_register($token){
	$fichero = file_get_contents('http://karamuse.cl/karamusecl/html/register.html');
	$fichero = str_replace("[token]", $token, $fichero);
	return $fichero;
}

function getHTML_renew_pass($email, $token){
	$fichero = file_get_contents('http://karamuse.cl/karamusecl/html/token_renew_pass.html');
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





