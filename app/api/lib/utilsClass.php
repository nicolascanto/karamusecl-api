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

	public function check_order ($orderArr, $id_session) {
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
				$orderData['message'] = $order['message'];
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
			$result = $mysqli->query("SELECT * FROM tbl_orders WHERE id = $id_order");

			if ($result->num_rows > 0) {
				$row = $result->fetch_assoc();
				return array("success" => true, "data" => $row);
			} else {
				return array("success" => false, "data" => null);
			}

		} elseif (is_null($params) && $id_session) {

			$result = $mysqli->query("SELECT * FROM tbl_orders WHERE id_session = $id_session");
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
}

class code {

	public function verify ($code, $id_session) {
		$mysqli = getConnection();
		$result = $mysqli->query("SELECT code FROM tbl_session_codes WHERE code = $code 
			AND id_session = $id_session AND state = 1");

		if ($result->num_rows > 0) {
			return array("success" => true);
		} else {
			return array("status" => 403, "message" => "Client forbidden", "code_client_request" => $code, "id_session" => $id_session);
		}

	}
}






