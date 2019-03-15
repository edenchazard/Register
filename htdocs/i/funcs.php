<?php
function e_log($message, $log){
	$date = date("Y-m-d H:i:s");
	// Error check if given an invalid log
	error_log("{$date} {$message}\r\n", 3, $log);
}
/*
function send_json($data){
	App::$db->close();

	// Send JSON
	header('Content-type: application/json');
	echo json_encode($data);
	exit;
}*/

//filter validate_int will return false when int = 0
function validate_int($val){
	if(gettype($val) == 'array'){
		foreach($val as $v){
			if(!preg_match('/^\d+$/', $v)){
				return false;
			}
		}
		return true;
	}

	return preg_match('/^\d+$/', $val);
}
?>