<?php

$app->post('/api/login/', function($request, $response, $args){
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
	$active = (isset($request->getParsedBody()['active'])) ? $request->getParsedBody()['active'] : 0;

	$arraydata = array("email" => $email, "phone" => $phone, "address" => $address, "city" => $city, "commune" => $commune, "rut" => $rut, "region" => $region, "name" => $name);

	$mysqli = getConnection();
	$result = $mysqli->query("SELECT email, phone, active FROM `tbl_bars` WHERE email='$email'");
	$row_cnt = $result->num_rows;
	$row = $result->fetch_assoc();

	if ($row_cnt > 0) {

		if ($row['active']) {

			return $response->withJSON(array("status" => 201, "message" => "El bar ya existe y se encuentra activo"));

		} elseif(!is_null($rut) && !is_null($name) && !is_null($address) && !is_null($region) && !is_null($commune) && !is_null($city)) {

			// El bar ya existe pero aún no termina el proceso de registro
			$active = true;
			$query_insert_bar = "UPDATE tbl_bars SET rut = ?, name = ?, address = ?, phone = ?, email = ?, region = ?, commune = ?, city = ?, active = ? WHERE email = '$email';";
			$stmt = $mysqli->prepare($query_insert_bar);
			$stmt->bind_param('ssssssssi', $rut, $name, $address, $phone, $email, $region, $commune, $city, $active);

			$stmt->execute();
			if (json_encode($stmt->affected_rows)) {
				return $response->withJSON(array("status" => 200, "message" => "Se ha completado segundo el registro del bar", "data" => $arraydata));
			} else {
				return $response->withJSON(array("status" => 402, "message" => "No se ha completado segundo el registro del bar", "data" => $arraydata));
			}
			$stmt->close();

		} else {
	
			return $response->withJSON(array("status" => 403, "message" => "No se puede registrar el bar", "data" => $arraydata));
		}

	} else {

		$active = false;
		$query_insert_bar = "INSERT INTO tbl_bars (rut, name, address, phone, email, region, commune, city, active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?);";
		$stmt = $mysqli->prepare($query_insert_bar);
		$stmt->bind_param('ssssssssi', $rut, $name, $address, $phone, $email, $region, $commune, $city, $active);

		$stmt->execute();

		if (json_encode($stmt->affected_rows)) {
			$mail = new PHPMailer;
			$mail->SMTPDebug = 2;                               // Enable verbose debug output
			$mail->isSMTP();                                      // Set mailer to use SMTP
			$mail->Host = 'smtp.gmail.com';  // Specify main and backup SMTP servers
			$mail->SMTPAuth = true;                               // Enable SMTP authentication
			$mail->Username = 'karamuseapp@gmail.com';                 // SMTP username
			$mail->Password = 'inspirate2016';                           // SMTP password
			$mail->SMTPSecure = 'ssl';            // Enable TLS port(587) encryption, `ssl` port(465) also accepted
			$mail->Port = 465;                                    // TCP port to connect to

			$mail->setFrom('karamuseapp@gmail.com', 'Mailer');
			$mail->addAddress($email);     // Add a recipient
			$mail->addReplyTo('hola@karamuse.cl', 'Information');
			$mail->addBCC('nicolascanto1@gmail.com');
			$mail->isHTML(true);                                  // Set email format to HTML

			$mail->Subject = '¡Estás a un paso de completar tu registro en Karamuse!';
			$mail->Body    = $email .'!, '. 'This is the HTML message body <b>in bold!</b>';
			$mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

			if(!$mail->send()) {
			    $message = 'El primer registro está correcto pero... Mailer Error: ' . $mail->ErrorInfo;
			} else {
			    $message = "Se ha enviado un email para completar tu registro";
			}

			return $response->withJSON(array("status" => 200, "message" => $message, "data" => $arraydata));

		} else {
			return $response->withJSON(array("status" => 401, "message" => "No se ha completado el primer registro del bar", "data" => $arraydata));
		}
		$stmt->close();
	}

	$mysqli->close();
	$result->close();

});

function sendEmail($email, $name) {

}




