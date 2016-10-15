<?php

class session {

	public function id_session ($id_bar) {
		$mysqli = getConnection();
		$result = $mysqli->query("SELECT id FROM tbl_sessions WHERE id_bar = $id_bar AND active = true");

		if ($result->num_rows > 0) {
			$row = $result->fetch_assoc();
			return array("success" => true, "id" => $row['id']);
		} else {
			return array("status" => 402, "message" => "No hay sesiones abiertas.");
		}
	}
}

class ticket {

	public function last_ticket ($id_session) {
		$mysqli = getConnection();
		$result = $mysqli->query("SELECT max(ticket) FROM tbl_orders WHERE id_session = $id_session");

		if ($result->num_rows > 0) {
			$row = $result->fetch_assoc();
			return array("success" => true, "last" => $row['max(ticket)']);
		} else {
			return array("status" => 405, "message" => "Ocurrió un error al obtener el último ticket");
		}
	}
}

class order {

	public function check_order ($params) {
		$id_session = $params['id_session'];
		$orderArr = $params['orderArr'];
		$newOrderArr = array();
		$orderData = array();
		$mysqli = getConnection();
		$stmt = $mysqli->prepare("SELECT id_karaoke FROM tbl_orders 
			WHERE id_karaoke = ? AND id_session = $id_session AND state <> 2");
		$stmt->bind_param('i', $id_karaoke);

		if ($orderArr && count($orderArr) > 0) {
			
			foreach ($orderArr as $order) {
				$id_karaoke = $order['id_karaoke'];
				$stmt->execute();
				if ($stmt->fetch()) {
					$orderData['add_order'] = false;
				} else {
					$orderData['add_order'] = true;
				}
				$orderData['id_karaoke'] = $order['id_karaoke'];
				$orderData['origin'] = $order['origin'];
				$newOrderArr[] = $orderData;

			}

			return array("success" => true, "data" => $newOrderArr);

		} else {
			return array("status" => 404, "message" => "El pedido no se puede verificar, puede ser que el arreglo del pedido esté vacío o es inválido.");
		}
	}

	public function getOrders ($params, $id_session = false) {

		$mysqli = getConnection();

		if (isset($params['id_order']) && is_numeric($params['id_order'])) {

			$id_order = $params['id_order'];
			$result = $mysqli->query("SELECT tbl_orders.id, tbl_orders.ticket, tbl_orders.origin, tbl_orders.code_client, tbl_karaokes.title, tbl_karaokes.url, tbl_karaokes.time, tbl_orders.state, tbl_orders.created_at 
				FROM tbl_orders JOIN tbl_karaokes ON tbl_orders.id_karaoke = tbl_karaokes.id 
				WHERE tbl_orders.id = $id_order AND tbl_orders.state <> 2");

			if ($result->num_rows > 0) {
				$row = $result->fetch_assoc();
				return array("success" => true, "data" => $row);
			} else {
				return array("success" => false, "data" => null);
			}

		} elseif (is_null($params) && $id_session) {

			$result = $mysqli->query("SELECT tbl_orders.id, tbl_orders.ticket, tbl_orders.origin, tbl_orders.code_client, tbl_karaokes.title, tbl_karaokes.url, tbl_karaokes.time, tbl_orders.state, tbl_orders.created_at 
				FROM tbl_orders JOIN tbl_karaokes ON tbl_orders.id_karaoke = tbl_karaokes.id 
				WHERE id_session = $id_session AND state <> 2 ORDER BY tbl_orders.state ASC, tbl_orders.created_at DESC");

			$dataResponse = array();
			if ($result->num_rows > 0) {
				while ($row = $result->fetch_assoc()) {
					$dataResponse[] = $row;
				}
				return array("success" => true, "data" => $dataResponse);
			} else {
				return array("success" => false, "data" => null);
			}

		} else {

			return array("success" => false);

		}
	}

	public function maxOrdersNow ($params) {

		$id_session = $params['id_session'];
		$id_bar = $params['id_bar'];

		$mysqli = getConnection();
		$result = $mysqli->query("SELECT order_limit, (SELECT COUNT(*) FROM tbl_orders 
			WHERE id_session = $id_session) AS num_orders FROM tbl_bar_settings WHERE id_bar = $id_bar");
		
		if ($result->num_rows > 0) {	
			$row = $result->fetch_assoc();
			$maxOrdersNow = $row['order_limit'] - $row['num_orders'];
			return $maxOrdersNow;
		} else {
			return false;
		}

	}
}

class code {

	public function verify ($code_client, $id_session) {
		$mysqli = getConnection();
		$result = $mysqli->query("SELECT code FROM tbl_session_codes WHERE code = $code_client 
			AND id_session = $id_session AND state = 1");

		if ($result->num_rows > 0) {
			return array("success" => true);
		} else {
			return array("status" => 403, "message" => "Client forbidden", "code_client_request" => $code_client, "id_session" => $id_session);
		}

	}
}






