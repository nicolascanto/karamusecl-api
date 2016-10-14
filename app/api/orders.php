<?php

$app->post('/api/orders', function($request, $response, $args){

	$id_bar = $request->getAttribute('id_bar');
	$scope = $request->getAttribute('scope');
	$orderArr = (isset($request->getParsedBody()['order'])) ? $request->getParsedBody()['order'] : array();
	$message = (isset($request->getParsedBody()['message'])) ? $request->getParsedBody()['message'] : null;
	$session = new session;
	$id_session = $session->id_session($id_bar);

	if (isset($id_session['success']) && $id_session['success']) {
		$id = $id_session['id'];

		// Verifica si el scope es CLIENT ó DJ
		if ($scope == "CLIENT") {
			$code_client = (isset($request->getParsedBody()['code_client'])) ? $request->getParsedBody()['code_client'] : null;
			$code = new code;
			$verify = $code->verify($code_client, $id_session['id']);

			if (!isset($verify['success']) && !$verify['success']) {
				return $response->withJSON($verify);
			} 
		} else {
			$code_client = "DJ";
		}

		$ticket = new ticket;
		$last_ticket = $ticket->last_ticket($id_session['id']);

		if (isset($last_ticket['success']) && $last_ticket['success']) {
			$last = $last_ticket['last'];
			$ticket = $last + 1;

			$order = new order;
			
			$maxOrdersNow = $order->maxOrdersNow(array("id_session" => $id, "id_bar" => $id_bar));
			if ($maxOrdersNow < count($orderArr)) {
				return $response->withJSON(array("status" => 406, "message" => "Cupos limitados", "capacity" => $maxOrdersNow));
			}

			$order_verified = $order->check_order(array("orderArr" => $orderArr, 
				"id_session" => $id_session['id'], "message" => $message));

			if (isset($order_verified['success']) && $order_verified['success']) {
				$verified = $order_verified['data'];
				$mysqli = getConnection();
				$stmt = $mysqli->prepare("INSERT INTO tbl_orders (id_bar, id_session, origin, code_client, ticket, 
				id_karaoke, message, state) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
				$stmt->bind_param('iissiisi', $id_bar, $id, $origin, $code_client, $ticket, $id_karaoke, $message, $state);

				foreach ($verified as $_order) {
					$origin = $_order['origin'];
					$id_karaoke = $_order['id_karaoke'];
					$state = 0;
					if ($_order['add_order']) {
						$stmt->execute();
					}
				}

				return $response->withJSON(array("status" => 200, "message" => "Pedidos verificados", 
					"message_client" => $message, "data" => $verified, "scope" => $scope));	

			} else {
				return $response->withJSON($order_verified);
			}

		
		} else {
			return $response->withJSON($last_ticket);
		}

	} else {
		return $response->withJSON($id_session);
	}

})->add($authorization);

$app->put('/api/orders/{id_order}', function($request, $response, $args){

	$state = (isset($request->getParsedBody()['state'])) ? $request->getParsedBody()['state'] : null;

	if ((isset($args['id_order']) && is_numeric($args['id_order'])) && (!is_null($state) && is_numeric($state))) {
	
		$id_order = $args['id_order'];
		$mysqli = getConnection();
		$result = $mysqli->query("UPDATE tbl_orders SET state = $state WHERE id = $id_order");

		if ($mysqli->affected_rows > 0) {
			return $response->withJSON(array("status" => 200, "message" => "Se ha actualizado el estado del pedido"));
		} else {
			return $response->withJSON(array("status" => 400, "message" => "No se ha actualizado el estado del pedido"));
		}

	} else {
		return $response->withJSON(array("status" => 402, "message" => "Debes especificar ID y estado del pedido"));
	}

})->add($authorization);

$app->get('/api/orders/{id_order}', function($request, $response, $args){

	$order = new order;
	$getOrders = $order->getOrders(array("id_order" => $args['id_order']));

	if (isset($getOrders['success']) && $getOrders['success']) {
		return $response->withJSON(array("status" => 200, "message" => "Se ha obtenido el pedido", "data" => $getOrders['data']));
	} else {
		return $response->withJSON(array("status" => 404, "message" => "No hay pedidos para ID especificado"));
	}

})->add($authorization);


$app->get('/api/orders', function($request, $response, $args){

	$id_bar = $request->getAttribute('id_bar');
	$session = new session;
	$id_session = $session->id_session($id_bar);

	if (isset($id_session['success']) && $id_session['success']) {
		$order = new order;
		$getOrders = $order->getOrders(null, $id_session['id']);

		if (isset($getOrders['success']) && $getOrders['success']) {
			return $response->withJSON(array("status" => 200, "message" => "Se han obtenido los pedidos", "data" => $getOrders['data']));
		} else {
			return $response->withJSON(array("status" => 404, "message" => "No hay pedidos en la sesión"));
		}
	} else {
		return $response->withJSON($id_session);
	}

})->add($authorization);


