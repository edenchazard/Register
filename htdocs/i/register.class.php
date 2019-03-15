<?php
require_once "config.php";
require_once "funcs.php";

if (!headers_sent() && !isset($_SESSION)) session_start();

/*
	Base class for the Register. It can handle operations that
	don't require an attached account id
*/
class Register {
	public static function validate_username($name){
		return (strlen($name) > 0 && strlen($name) <= 30);
	}

	public static function is_signed_in(){
		return isset($_SESSION['bx2']);
	}
	
	public static function get_session(){
		if(!self::is_signed_in())
			throw new Exception(ERR_NO_SESS);
		
		return $_SESSION['bx2'];
	}

	// These will be overriden in the RegisterAccount class
	public static function site_belongs_to_account($siteid, $accountid){
		return self::__site_belongs_to_account($siteid, $accountid);
	}

	public static function group_belongs_to_account($groupid, $accountid){
		return self::__group_belongs_to_account($groupid, $accountid);
	}

	// creates the session if valid
	// returns true or false upon successful sign in
	public static function attempt_sign_in($username, $password){
		if(!self::validate_username($username))
			return false;

		$salt = "*Mmo!HZn6G^grk";
		$hash = hash("sha256", $salt.$password);

		// No need to safe the pw as we hashed it
		$q = App::$db->query("
		SELECT id
		FROM `accounts`
		WHERE `username` = '{$username}'
		AND `password` = '{$hash}'
		");
		
		if($user = $q->fetch_assoc()){
			// Set the session
			$_SESSION['bx2'] = $user['id'];
		}

		return !!$user;
	}

	//  returns true if logout was successful
	public static function log_out(){
		if(isset($_SESSION['bx2'])){
			unset($_SESSION['bx2']);
			return true;
		}
		else
			return false;
	}

	// To be used by both the static general Register class
	// and the RegisterAccount class (which only requires a siteid/groupid)
	public static function __site_belongs_to_account($siteid, $accountid){
		if(!validate_int(array($siteid, $accountid)))
			throw new Exception(ERR_NOT_NUMERIC);

		$q = App::$db->query("
		SELECT 1
		FROM sites
		WHERE id={$siteid}
		AND organisation={$accountid}");

		return !!$q->fetch_assoc();
	}

	public static function __group_belongs_to_account($groupid, $accountid){
		if(!validate_int(array($groupid, $accountid)))
			throw new Exception(ERR_NOT_NUMERIC);

		// range group ids 1 to 9 are system reserved and every account has one
		// of these. it's hardcoded
		if($groupid <= 9)
			return true;

		$q = App::$db->query("
		SELECT 1
		FROM `groups`
		WHERE id={$groupid}
		AND organisation={$accountid}");

		return !!$q->fetch_assoc();
	}
}






/*
	Class for handling registers with an attached account
*/
class RegisterAccount {
	public function __construct($id){
		if(!validate_int($id))
			throw new Exception(ERR_NOT_NUMERIC);

		$q = App::$db->query("
		SELECT id, username, domain, registered_uid, apikey
		FROM accounts
		WHERE id={$id}");

		// Validate account exists
		if(!$row = $q->fetch_assoc()){
			throw new Exception(ERR_NOT_EXIST);
		}
		else{
			// Add the data
			foreach($row as $property => $value){
				$this->$property = $value;
			}
		}
	}

	public function site_belongs_to_account($siteid){
		return Register::__site_belongs_to_account($siteid, $this->id);
	}
	
	public function group_belongs_to_account($groupid){
		return Register::__group_belongs_to_account($groupid, $this->id);
	}

	// Unassigned is 1
	public function add_user($uid, $name, $group = 1){
		// validate the format
		if(!Register::validate_username($name))
			throw new Exception(ERR_NOT_VALID_UN);

		if(!validate_int($uid, $group))
			throw new Exception(ERR_NOT_NUMERIC);

		// Make sure the group being applied belongs to this register
		if(!$this->group_belongs_to_account($group))
			throw new Exception(ERR_GRP_NOT_BELONG);

		$stmt = App::$db->prepare("
		INSERT INTO `users` (`UID`, `name`, `group`, `organisation`)
		VALUES ('{$uid}', ?, '{$group}', '{$this->id}')");
		$stmt->bind_param('s', $name);
		$stmt->execute();
		$stmt->close();

		return true;
	}
	
	public function set_uid($to){
		App::$db->query("
		UPDATE `accounts`
		SET `registered_uid` = {$to}
		WHERE `id`={$this->id}");
	}

	// Remove stored uid from database
	public function unset_uid(){
		// Clean the UID from the account row
		App::$db->query("
		UPDATE `accounts`
		SET `registered_uid` = NULL
		WHERE `id`={$this->id}");
	}

	public function get_groups(){
		$q = App::$db->query("SELECT id, name FROM `groups`");
		return $q->fetch_all(MYSQLI_ASSOC);
	}

	public function get_site($siteid){
		if(!validate_int($siteid)){
			throw new Exception(ERR_NOT_NUMERIC);
		}

		$q = App::$db->query("
		SELECT `id`, organisation, `name`
		FROM `sites`
		WHERE `id`={$siteid}");

		// Could possibly be removed to the Register class if 
		// checking the organisation isn't important...
		if(!$row = $q->fetch_assoc()){
			throw new Exception(ERR_SITE_NOT_EXIST);
		}
		else{
			if($row['organisation'] != $this->id){
				throw new Exception(ERR_SITE_NOT_BELONG);
			}
			return $row;
		}
	}
	
	public function get_sites(){
		$q = App::$db->query("SELECT `id`, `name` FROM `sites` WHERE `organisation`={$this->id} ORDER BY `name`");
		return $q->fetch_all(MYSQLI_ASSOC);
	}
	
	public function get_data(){
		return array(
			'id'				=> $this->id,
			'username' 			=> $this->username,
			'domain' 			=> $this->domain,
			'registered_uid'	=> $this->registered_uid
		);
	}

	public function get_whos_signed_in($siteid = 0){
		if(!validate_int($siteid)){
			throw new Exception(ERR_NOT_NUMERIC);
		}

		// query builder
		// don't list deactivated users (group 2)
		// list only signed in users (timein not null)
		$sql = "
		SELECT users.name, users.timeIn
		FROM users
		WHERE ";
		if($siteid > 0){
			// We are asking for users at a particular site
			$sql .=  "users.site={$siteid}
			AND ";
		}

		$sql .= "
		timeIn IS NOT NULL
		AND users.group != 2
		ORDER BY users.name";

		//echo $sql;
		$q = App::$db->query($sql);
		return $q->fetch_all();
	}

	public function get_users_data_in_group($id){
		if(!validate_int($id))
			throw new Exception(ERR_NOT_NUMERIC);

		$q = App::$db->query("
		SELECT users.id, users.UID, users.name, users.timeIn, sites.name AS sitename
		FROM `users`
		LEFT JOIN `sites` ON `users`.`site` = `sites`.`id`
		WHERE `users`.`group` ='{$id}'
		AND users.`organisation` = {$this->id}
		ORDER BY users.name");

		return $q->fetch_all(MYSQLI_ASSOC);
	}

	public function mass_sign_in($ids, $siteid){
		if(!validate_int($siteid))
			throw new Exception(ERR_NOT_NUMERIC);

		if(empty($ids)){
			return true;
		}

		// Confirm the site given belongs to the org
		if(!$this->site_belongs_to_account($siteid)){
			throw new Exception(ERR_SITE_NOT_BELONG);
		}

		// Check each id
		foreach($ids as $id){
			if(!validate_int($id)){
				throw new Exception(ERR_NOT_NUMERIC);
			}
		}

		// Only sign in those without an active session
		// this will also validate that the user belongs to the org
		$sql = "
		UPDATE `users` SET `timeIn` = NOW(), site={$siteid}
		WHERE ID IN(".implode(',', $ids).") AND site IS NULL";

		$sql .= " AND organisation='{$this->id}'";

		$q = App::$db->query($sql);

		return true;
	}

	// 0: sign them out at the site they're at
	// > 0: Sign them out at the site selected
	public function mass_sign_out($ids){
		// if(!validate_int($siteid))
		// 	throw new Exception(ERR_NOT_NUMERIC);

		if(empty($ids)){
			return true;
		}

		/*if($siteid > 0){
			// If we're not signing them out of whatever site they're at,
			// Confirm the site given belongs to the org
			if(!$this->site_belongs_to_account($siteid)){
				throw new Exception(ERR_SITE_NOT_BELONG);
			}
		}*/
		// Check each id
		foreach($ids as $id){
			if(!validate_int($id)){
				throw new Exception(ERR_NOT_NUMERIC);
			}
		}

		$idstring = implode(',', $ids);

		// this will also validate that the user belongs to the org
		// and has a currently active session
		$sql = "
		SELECT id, timeIn, site
		FROM users
		WHERE id IN($idstring)
		AND organisation='{$this->id}'
		AND site IS NOT NULL";

		$q = App::$db->query($sql);

		$sql = "
		INSERT INTO `sessions` (userid, timeIn, timeOut, site, notes)
		VALUES ";

		$users = false;
		while($user = $q->fetch_assoc()){
			$users = true;
			//$site = ($siteid == 0 ? $user['site'] : $siteid);
			$sql .= "('{$user['id']}', '{$user['timeIn']}', NOW(), {$user['site']}, 'manual'),";
		}

		$sql = substr($sql, 0, -1);

		// Only process if we returned users
		if($users){
			App::$db->begin_transaction();
			// this will also validate that the user belongs to the org
			App::$db->query("
			UPDATE `users`
			SET `timeIn` = NULL, `site`=NULL
			WHERE id IN({$idstring})
			AND organisation='{$this->id}'");

			App::$db->query($sql);
			App::$db->commit();
		}

		return true;
	}

	public function auto_sign_out(){
		$sql = "
		SELECT id, timeIn, site
		FROM users
		WHERE organisation='{$this->id}'
		AND site IS NOT NULL";

		$q = App::$db->query($sql);

		$sql = "
		INSERT INTO `sessions` (userid, timeIn, timeOut, site, notes)
		VALUES ";

		$users = false;
		while($user = $q->fetch_assoc()){
			$users = true;
			$sql .= "('{$user['id']}', '{$user['timeIn']}', NOW(), {$user['site']}, 'auto'),";
		}
		$sql = substr($sql, 0, -1);

		// Only process if we returned users
		if($users){
			App::$db->begin_transaction();
			App::$db->query("
			UPDATE `users`
			SET `timeIn` = NULL, `site`=NULL
			WHERE organisation='{$this->id}'");
			App::$db->query($sql);
			App::$db->commit();
		}
	}
}
?>