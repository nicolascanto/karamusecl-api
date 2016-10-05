<?php

class token {

	function validate($token){

		if (!is_null($token)) {
			
			$mysqli = getConnection();
			$result = $mysqli->query("SELECT token FROM tbl_access_tokens WHERE token = '$token' AND active = true ");
			if ($result->num_rows > 0) {
				return true;
			} else {
				return false;
			}

		} else {
			return false;
		}
	
	}
}