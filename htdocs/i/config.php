<?php
// Error codes
const ERR_NOT_NUMERIC 		= "NOT_NUMERIC";
const ERR_NOT_EXIST			= "NOT_EXIST";
const ERR_NOT_VALID_UN		= "NOT_VALID_UN";
const ERR_SITE_NOT_BELONG	= "SITE_NOT_BELONG";
const ERR_SYSTEM			= "SYSTEM_ERROR";
const ERR_MISSING_PARAMS	= "MISSING_PARAMS";
const ERR_ACCESS_DENIED		= "ACCESS_DENIED";
const ERR_USER_NOT_EXIST	= "USER_NOT_EXIST";
const ERR_SITE_NOT_EXIST 	= "SITE_NOT_EXIST";
const ERR_GRP_NOT_BELONG	= "GROUP_NOT_BELONG";
const ERR_NO_UID			= "NO_UID";
const ERR_BAD_KEY			= "BAD_KEY";
const ERR_NO_SESS			= "NO_SESSION";

// Sign statuses
const SIGN_MASTER_SUCCESS 	= "MASTER_SUCCESS";
const SIGN_LOGGED_IN		= "LOGGED_IN";
const SIGN_LOGGED_OUT		= "LOGGED_OUT";

const GENERAL_LOG			= "./logging/general.log";
const SYS_LOG				= "./logging/system.log";
const DBUSER				= "opquaxyz_regie";
const DBPASS				= "[,[(bc{5_clU";
const DBHOST 				= "localhost";
const DB					= "opquaxyz_register";

const ENV					= "dev";

class App{
	public static $db = null;
};

?>