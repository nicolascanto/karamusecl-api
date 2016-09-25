<?php

$app->post('/api/login/', function($request, $response, $args){
	$email = (isset($request->getParsedBody()['email'])) ? $request->getParsedBody()['email'] : null;
	$password = (isset($request->getParsedBody()['password'])) ? $request->getParsedBody()['password'] : null;

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
		"token" => $token);

	$mysqli = getConnection();
	$result = $mysqli->query("SELECT email, phone, active FROM tbl_bars WHERE email='$email'");
	$row_cnt = $result->num_rows;
	$row = $result->fetch_assoc();

	if ($row_cnt > 0) {
		if ($row['active']) {

			return $response->withJSON(array("status" => 201, 
				"message" => "El bar ya existe y se encuentra activo"));

		} elseif(!is_null($rut) && !is_null($name) && !is_null($address) && !is_null($region) && !is_null($commune) && !is_null($city)) {

			// actualizo los datos del bar
			$active = true;
			$query_update_bar = "UPDATE tbl_bars SET rut = ?, name = ?, address = ?, phone = ?, email = ?, region = ?, commune = ?, city = ?, active = ? WHERE email = '$email';";
			$stmt1 = $mysqli->prepare($query_update_bar);
			$stmt1->bind_param('ssssssssi', $rut, $name, $address, $phone, 
				$email, $region, $commune, $city, $active);
			$stmt1->execute();

			if (json_encode($stmt1->affected_rows)) {
				$message1 = "Se ha completado el segundo registro del bar";
				
				//invalidar token de registro
				$status = false;
				$query_invalidate_token = "UPDATE tbl_active_tokens SET state = ? WHERE token = ?;";
				$stmt2 = $mysqli->prepare($query_invalidate_token);
				$stmt2->bind_param('is', $status, $token);
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
		$query_insert_bar = "INSERT INTO tbl_bars (rut, name, address, phone, email, region, commune, city, active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?);";
		$stmt1 = $mysqli->prepare($query_insert_bar);
		$stmt1->bind_param('ssssssssi', $rut, $name, $address, $phone, 
			$email, $region, $commune, $city, $active);
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

function getHTML($token){
	return '                         <!doctype html>
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
  <title></title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<style type="text/css">
  #outlook a { padding: 0; }
  .ReadMsgBody { width: 100%; }
  .ExternalClass { width: 100%; }
  .ExternalClass * { line-height:100%; }
  body { margin: 0; padding: 0; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
  table, td { border-collapse:collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
  img { border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; -ms-interpolation-mode: bicubic; }
  p { display: block; margin: 13px 0; }
</style>
<!--[if !mso]><!-->
<style type="text/css">
  @media only screen and (max-width:480px) {
    @-ms-viewport { width:320px; }
    @viewport { width:320px; }
  }
</style>
<!--<![endif]-->
<!--[if mso]>
<xml>
  <o:OfficeDocumentSettings>
    <o:AllowPNG/>
    <o:PixelsPerInch>96</o:PixelsPerInch>
  </o:OfficeDocumentSettings>
</xml>
<![endif]-->

<!--[if !mso]><!-->
    <link href="https://fonts.googleapis.com/css?family=Ubuntu:300,400,500,700" rel="stylesheet" type="text/css">
    <style type="text/css">

        @import url(https://fonts.googleapis.com/css?family=Ubuntu:300,400,500,700);

    </style>
  <!--<![endif]--><style type="text/css">
  @media only screen and (min-width:480px) {
    .mj-column-per-100, * [aria-labelledby="mj-column-per-100"] { width:100%!important; }
  }
</style>
</head>
<body>
  <div><!--[if mso | IE]>
      <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="600" align="center" style="width:600px;">
        <tr>
          <td style="line-height:0px;font-size:0px;mso-line-height-rule:exactly;">
      <![endif]--><div style="margin:0 auto;max-width:600px;"><table role="presentation" cellpadding="0" cellspacing="0" style="font-size:0px;width:100%;" align="center" border="0"><tbody><tr><td style="text-align:center;vertical-align:top;font-size:0px;padding:0px;"><!--[if mso | IE]>
      <table role="presentation" border="0" cellpadding="0" cellspacing="0"><tr><td style="vertical-align:top;width:600px;">
      <![endif]--><div aria-labelledby="mj-column-per-100" class="mj-column-per-100" style="vertical-align:top;display:inline-block;font-size:13px;text-align:left;width:100%;"><table role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0"><tbody><tr><td style="word-break:break-word;font-size:0px;padding:10px 25px;" align="center"><table role="presentation" cellpadding="0" cellspacing="0" style="border-collapse:collapse;border-spacing:0px;" align="center" border="0"><tbody><tr><td style="width:300px;"><img alt="" title="" height="auto" src="http://karamuse.cl/karamusecl/images/karamuse-logo2.png" style="border:none;border-radius:;display:block;outline:none;text-decoration:none;width:100%;height:auto;" width="300"></td></tr></tbody></table></td></tr></tbody></table></div><!--[if mso | IE]>
      </td></tr></table>
      <![endif]--></td></tr></tbody></table></div><!--[if mso | IE]>
      </td></tr></table>
      <![endif]-->
      <!--[if mso | IE]>
      <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="600" align="center" style="width:600px;">
        <tr>
          <td style="line-height:0px;font-size:0px;mso-line-height-rule:exactly;">
      <![endif]--><p style="font-size:1px;margin:0 auto;border-top:4px solid #808080;width:100%;"></p><!--[if mso | IE]><table role="presentation" align="center" border="0" cellpadding="0" cellspacing="0" style="font-size:1px;margin:0 auto;border-top:4px solid #808080;width:100%;" width="600"><tr><td style="height:0;line-height:0;"> </td></tr></table><![endif]--><!--[if mso | IE]>
      </td></tr></table>
      <![endif]-->
      <!--[if mso | IE]>
      <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="600" align="center" style="width:600px;">
        <tr>
          <td style="line-height:0px;font-size:0px;mso-line-height-rule:exactly;">
      <![endif]--><div style="margin:0 auto;max-width:600px;"><table role="presentation" cellpadding="0" cellspacing="0" style="font-size:0px;width:100%;" align="center" border="0"><tbody><tr><td style="text-align:center;vertical-align:top;font-size:0px;padding:20px 0px;padding-top:20px;"><!--[if mso | IE]>
      <table role="presentation" border="0" cellpadding="0" cellspacing="0"><tr><td style="vertical-align:top;width:600px;">
      <![endif]--><div aria-labelledby="mj-column-per-100" class="mj-column-per-100" style="vertical-align:top;display:inline-block;font-size:13px;text-align:left;width:100%;"><table role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0"><tbody><tr><td style="word-break:break-word;font-size:0px;padding:10px 25px;" align="center"><div style="cursor:auto;color:#009BCF;font-family:Ubuntu, Helvetica, Arial, sans-serif;font-size:24px;line-height:22px;">¡Gracias por registrarte en Karamuse!</div></td></tr></tbody></table></div><!--[if mso | IE]>
      </td></tr></table>
      <![endif]--></td></tr></tbody></table></div><!--[if mso | IE]>
      </td></tr></table>
      <![endif]-->
      <!--[if mso | IE]>
      <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="600" align="center" style="width:600px;">
        <tr>
          <td style="line-height:0px;font-size:0px;mso-line-height-rule:exactly;">
      <![endif]--><div style="margin:0 auto;max-width:600px;"><table role="presentation" cellpadding="0" cellspacing="0" style="font-size:0px;width:100%;" align="center" border="0"><tbody><tr><td style="text-align:center;vertical-align:top;font-size:0px;padding:0px;"><!--[if mso | IE]>
      <table role="presentation" border="0" cellpadding="0" cellspacing="0"><tr><td style="vertical-align:top;width:600px;">
      <![endif]--><div aria-labelledby="mj-column-per-100" class="mj-column-per-100" style="vertical-align:top;display:inline-block;font-size:13px;text-align:left;width:100%;"><table role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0"><tbody><tr><td style="word-break:break-word;font-size:0px;padding:10px 25px;" align="center"><div style="cursor:auto;color:#4b4b4b;font-family:Ubuntu, Helvetica, Arial, sans-serif;font-size:16px;line-height:22px;">Para completar tu registro, haz click en el siguiente link:</div></td></tr></tbody></table></div><!--[if mso | IE]>
      </td></tr></table>
      <![endif]--></td></tr></tbody></table></div><!--[if mso | IE]>
      </td></tr></table>
      <![endif]-->
      <!--[if mso | IE]>
      <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="600" align="center" style="width:600px;">
        <tr>
          <td style="line-height:0px;font-size:0px;mso-line-height-rule:exactly;">
      <![endif]--><div style="margin:0 auto;max-width:600px;"><table role="presentation" cellpadding="0" cellspacing="0" style="font-size:0px;width:100%;" align="center" border="0"><tbody><tr><td style="text-align:center;vertical-align:top;font-size:0px;padding:0px;"><!--[if mso | IE]>
      <table role="presentation" border="0" cellpadding="0" cellspacing="0"><tr><td style="vertical-align:top;width:600px;">
      <![endif]--><div aria-labelledby="mj-column-per-100" class="mj-column-per-100" style="vertical-align:top;display:inline-block;font-size:13px;text-align:left;width:100%;"><table role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0"><tbody><tr><td style="word-break:break-word;font-size:0px;padding:10px 25px;" align="center"><table role="presentation" cellpadding="0" cellspacing="0" style="border-collapse:separate;" align="center" border="0"><tbody><tr><td style="border-radius:3px;color:white;cursor:auto;padding:10px 25px;" align="center" valign="middle" bgcolor="#009BCF"><a href="http://localhost:9000/#/signup?token=' .$token. '" style="display:inline-block;text-decoration:none;background:#009BCF;color:white;font-family:Helvetica;font-size:13px;font-weight:normal;margin:0px;" target="_blank">
						Completar registro
					</a></td></tr></tbody></table></td></tr></tbody></table></div><!--[if mso | IE]>
      </td></tr></table>
      <![endif]--></td></tr></tbody></table></div><!--[if mso | IE]>
      </td></tr></table>
      <![endif]-->
      <!--[if mso | IE]>
      <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="600" align="center" style="width:600px;">
        <tr>
          <td style="line-height:0px;font-size:0px;mso-line-height-rule:exactly;">
      <![endif]--><div style="margin:0 auto;max-width:600px;"><table role="presentation" cellpadding="0" cellspacing="0" style="font-size:0px;width:100%;" align="center" border="0"><tbody><tr><td style="text-align:center;vertical-align:top;font-size:0px;padding:20px 0px;padding-bottom:0px;padding-top:30px;"><!--[if mso | IE]>
      <table role="presentation" border="0" cellpadding="0" cellspacing="0"><tr><td style="vertical-align:top;width:600px;">
      <![endif]--><div aria-labelledby="mj-column-per-100" class="mj-column-per-100" style="vertical-align:top;display:inline-block;font-size:13px;text-align:left;width:100%;"><table role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0"><tbody><tr><td style="word-break:break-word;font-size:0px;padding:10px 25px;" align="center"><div style="cursor:auto;color:#4b4b4b;font-family:Ubuntu, Helvetica, Arial, sans-serif;font-size:16px;line-height:22px;">O copia esta url en tu navegador:</span></div></td></tr></tbody></table></div><!--[if mso | IE]>
      </td></tr></table>
      <![endif]--></td></tr></tbody></table></div><!--[if mso | IE]>
      </td></tr></table>
      <![endif]-->
      <!--[if mso | IE]>
      <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="600" align="center" style="width:600px;">
        <tr>
          <td style="line-height:0px;font-size:0px;mso-line-height-rule:exactly;">
      <![endif]--><div style="margin:0 auto;max-width:600px;"><table role="presentation" cellpadding="0" cellspacing="0" style="font-size:0px;width:100%;" align="center" border="0"><tbody><tr><td style="text-align:center;vertical-align:top;font-size:0px;padding:0px;"><!--[if mso | IE]>
      <table role="presentation" border="0" cellpadding="0" cellspacing="0"><tr><td style="vertical-align:top;width:600px;">
      <![endif]--><div aria-labelledby="mj-column-per-100" class="mj-column-per-100" style="vertical-align:top;display:inline-block;font-size:13px;text-align:left;width:100%;"><table role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0"><tbody><tr><td style="word-break:break-word;font-size:0px;padding:10px 25px;" align="center"><div style="cursor:auto;color:#4b4b4b;font-family:Ubuntu, Helvetica, Arial, sans-serif;font-size:16px;line-height:22px;">http://localhost:9000/#/signup?token=' .$token. '</span></div></td></tr></tbody></table></div><!--[if mso | IE]>
      </td></tr></table>
      <![endif]--></td></tr></tbody></table></div><!--[if mso | IE]>
      </td></tr></table>
      <![endif]-->
      <!--[if mso | IE]>
      <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="600" align="center" style="width:600px;">
        <tr>
          <td style="line-height:0px;font-size:0px;mso-line-height-rule:exactly;">
      <![endif]--><div style="margin:0 auto;max-width:600px;"><table role="presentation" cellpadding="0" cellspacing="0" style="font-size:0px;width:100%;" align="center" border="0"><tbody><tr><td style="text-align:center;vertical-align:top;font-size:0px;padding:20px 0px;padding-bottom:0px;padding-top:30px;"><!--[if mso | IE]>
      <table role="presentation" border="0" cellpadding="0" cellspacing="0"><tr><td style="vertical-align:top;width:600px;">
      <![endif]--><div aria-labelledby="mj-column-per-100" class="mj-column-per-100" style="vertical-align:top;display:inline-block;font-size:13px;text-align:left;width:100%;"><table role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0"><tbody><tr><td style="word-break:break-word;font-size:0px;padding:10px 25px;" align="center"><div style="cursor:auto;color:#4b4b4b;font-family:Ubuntu, Helvetica, Arial, sans-serif;font-size:16px;line-height:22px;">¡Esperamos que disfrutes las sorpresas que tenemos preparadas para ti!</div></td></tr></tbody></table></div><!--[if mso | IE]>
      </td></tr></table>
      <![endif]--></td></tr></tbody></table></div><!--[if mso | IE]>
      </td></tr></table>
      <![endif]-->
      <!--[if mso | IE]>
      <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="600" align="center" style="width:600px;">
        <tr>
          <td style="line-height:0px;font-size:0px;mso-line-height-rule:exactly;">
      <![endif]--><div style="margin:0 auto;max-width:600px;"><table role="presentation" cellpadding="0" cellspacing="0" style="font-size:0px;width:100%;" align="center" border="0"><tbody><tr><td style="text-align:center;vertical-align:top;font-size:0px;padding:0px;"><!--[if mso | IE]>
      <table role="presentation" border="0" cellpadding="0" cellspacing="0"><tr><td style="vertical-align:top;width:600px;">
      <![endif]--><div aria-labelledby="mj-column-per-100" class="mj-column-per-100" style="vertical-align:top;display:inline-block;font-size:13px;text-align:left;width:100%;"><table role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0"><tbody><tr><td style="word-break:break-word;font-size:0px;padding:10px 25px;" align="center"><div style="cursor:auto;color:#4b4b4b;font-family:Ubuntu, Helvetica, Arial, sans-serif;font-size:16px;line-height:22px;">Saludos,</div></td></tr></tbody></table></div><!--[if mso | IE]>
      </td></tr></table>
      <![endif]--></td></tr></tbody></table></div><!--[if mso | IE]>
      </td></tr></table>
      <![endif]-->
      <!--[if mso | IE]>
      <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="600" align="center" style="width:600px;">
        <tr>
          <td style="line-height:0px;font-size:0px;mso-line-height-rule:exactly;">
      <![endif]--><div style="margin:0 auto;max-width:600px;"><table role="presentation" cellpadding="0" cellspacing="0" style="font-size:0px;width:100%;" align="center" border="0"><tbody><tr><td style="text-align:center;vertical-align:top;font-size:0px;padding:0px;"><!--[if mso | IE]>
      <table role="presentation" border="0" cellpadding="0" cellspacing="0"><tr><td style="vertical-align:top;width:600px;">
      <![endif]--><div aria-labelledby="mj-column-per-100" class="mj-column-per-100" style="vertical-align:top;display:inline-block;font-size:13px;text-align:left;width:100%;"><table role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0"><tbody><tr><td style="word-break:break-word;font-size:0px;padding:10px 25px;" align="center"><div style="cursor:auto;color:#4b4b4b;font-family:Ubuntu, Helvetica, Arial, sans-serif;font-size:16px;line-height:22px;">Team Karamuse</div></td></tr></tbody></table></div><!--[if mso | IE]>
      </td></tr></table>
      <![endif]--></td></tr></tbody></table></div><!--[if mso | IE]>
      </td></tr></table>
      <![endif]--></div>
</body>
</html>';
}




