<?php
require_once "../i/config.php";
require_once "../i/mysqli.php";
require_once "../i/funcs.php";
require_once "../i/user.class.php";
require_once "../i/register.class.php";

try{
	if(!isset($_GET['uid'], $_GET['id'], $_GET['apikey'], $_GET['site'])){
		throw new Exception(ERR_MISSING_PARAMS);
		exit;
	}

	$uid		= $_GET['uid'];
	$account	= $_GET['id'];
	$site		= $_GET['site'];
	$apikey		= $_GET['apikey'];

	$register = new RegisterAccount($account);

	// validate entry.
	if($register->apikey !== $apikey){
		throw new Exception(ERR_ACCESS_DENIED);
	}

	// confirm we are valid
	// exceptions will be thrown if: not in database (id) or
	// site doesn't belong to this account
	$entry_point = $register->get_site($site);

	// Acquire user account details based on UID and the ID of the 
	// register referenced by IP access
	$user = new User($uid, $entry_point['organisation']);

	// deactivated
	if($user->is_deactivated()){
		// todo perhaps could be DEACTIVATED
		echo "NOT_EXIST";
		exit;
	}

	// handle sign in/out functionality
	$sign = $user->sign($entry_point['id']);

	switch($sign){
	 	case SIGN_MASTER_SUCCESS: echo "MASTER_SUCCESS"; break;
		case SIGN_LOGGED_IN: echo "LOGGED_IN"; break;
		case SIGN_LOGGED_OUT: echo "LOGGED_OUT"; break;
	}
}

catch(Exception $e){
	// Add additional functionality hooks
	switch($e->getMessage()){
		case ERR_USER_NOT_EXIST:
			// todo temp workaround for now, to set the captured uid
			// until there's a better way of doing
			$register->set_uid($uid);
			break;
	}

	echo $e->getMessage();
}

App::$db->close();
exit;
?>