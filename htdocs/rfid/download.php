<?php
// this script should be as fast as possible as it's called so often...
require_once "./i/config.php";
require_once "./i/funcs.php";

const LATEST_VERS 	= 2;

if(!isset($_GET['ID'], $_GET['APIKEY'], $_SERVER['HTTP_REGISTER'])){
	exit(ERR_MISSING_PARAMS);
}

$client_vers		= $_SERVER['HTTP_REGISTER'];
$client_id			= $_GET['ID'];
$client_key			= $_GET['APIKEY'];

if(!validate_int($client_vers, $client_id)){
	exit(ERR_MISSING_PARAMS);
}

// proceed with validation if the version doesn't match
// the latest number
if((int)$client_vers < LATEST_VERS){
	try {
		require_once "./i/mysqli.php";
		$register = new RegisterAccount($client_id);

		if($register->apikey != $client_key){
			exit(ERR_BAD_KEY);
		}

		$file_url = 'http://www.myremoteserver.com/file.exe';
		header('Content-Type: application/octet-stream');
		header("Content-Transfer-Encoding: Binary"); 
		header("Content-disposition: attachment; filename=\"" . basename($file_url) . "\""); 
		readfile($file_url);
	}
	catch(Exception $e){
		echo $e->getMessage();
	}
}
?>