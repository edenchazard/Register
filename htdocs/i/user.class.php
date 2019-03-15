<?php
require_once "config.php";
require_once "funcs.php";
require_once "register.class.php";

if (!isset($_SESSION)) session_start();

class User {
	public function __construct($id, $account = null){ 

		// IDs are numeric
		if(!validate_int($id)){
			throw new Exception(ERR_NOT_NUMERIC);
		}

		// We can find the user by their uid or id.
		// default is id, but if account is specified, we can do 
		// it by uid too.
		// remember that UIDs aren't unique across the database
		// only per account
		// in the case of searching by uid, the $id acts as $uid
		// and you must know the account

		// Maybe we should remove the join and sitename, as it's only used
		// for view purposes. We never index via it, perhaps a query outside would
		// be better.
		$sql = "
		SELECT users.id, uid, users.name, `group`, timeIn, site, sites.name AS sitename, users.organisation
		FROM users
		LEFT JOIN `sites` ON `users`.`site` = `sites`.`id`";

		if($account !== null){
			if(!validate_int($account)){
				throw new Exception(ERR_NOT_NUMERIC);
			}
			
			$sql .= "WHERE users.UID = {$id} AND users.organisation = {$account}";
		}
		else{
			$sql .= "WHERE users.id = {$id}";
		}

		//echo $sql;
		$q = App::$db->query($sql) or sql_error;

		// Validate user exists
		if(!$row = $q->fetch_assoc()){
			throw new Exception(ERR_USER_NOT_EXIST);
		}
		else{
			// collect data for our class
			foreach($row as $property => $value){
				$this->$property = $value;
			}
		}
	}

	public function reset_uid($to){
		if(!validate_int($to))
			throw new Exception(ERR_NOT_NUMERIC);

		//todo protect from uid collisions
		//App::$db->begin_transaction();
		App::$db->query("UPDATE users SET uid='{$to}' WHERE id={$this->id}") or sql_error();
		//App::$db->commit();
	}

	
	public function change_group($to_id){
		// IDs are numeric
		if(!validate_int($to_id)){
			throw new Exception(ERR_NOT_NUMERIC);
		}

		if(Register::group_belongs_to_account($to_id, $this->organisation)){
			App::$db->query("UPDATE `users` SET `group` = {$to_id} WHERE id={$this->id}") or sql_error();
		}
		else{
			throw new Exception(ERR_GRP_NOT_BELONG);
		}
	}

	private function logged_in(){
		return !!$this->timeIn;
	}

	public function sign($site = null){
		if($this->logged_in()){
			$v = $this->sign_out();
			return $v;
		}
		else{
			if(!validate_int($site)){
				throw new Exception(ERR_NOT_NUMERIC);
			}
			$v = $this->sign_in($site);
			return $v;
		}
	}

	private function sign_in($site){
		$reg = new Register(App::$db);
		if($reg->site_belongs_to_account($site, $this->organisation)){
			App::$db->query("
			UPDATE `users`
			SET `timeIn` = NOW(),
			`site` = {$site}
			WHERE `id` = {$this->id}") or sql_error();
			return SIGN_LOGGED_IN;
		}
		else{
			throw new Exception(ERR_SITE_NOT_BELONG);
		}
	}

	private function sign_out(){
		// Mastercards must be handled differently, hardcoded
		if($this->group == 3){
			// build a query to sign everyone out
			$users = false;
			$q = App::$db->query("SELECT id, timeIn, site FROM users WHERE timeIn != NULL") or sql_error();

			$sql = "INSERT INTO `sessions` (userid, timeIn, timeOut, site, notes) VALUES ";
			while($user = $q->fetch_assoc()){
				$users = true;
				$sql .= "('{$user['id']}', '{$user['timeIn']}', NOW(), '{$user['site']}', 'mc'),";
			}
			$sql = substr($sql, 0, -1);

			if($users){
				App::$db->begin_transaction();
				App::$db->query("UPDATE `users` SET `timeIn` = NULL, `site`=NULL") or sql_error();
				App::$db->query($sql) or sql_error();
				App::$db->commit();
			}
			
			return SIGN_MASTER_SUCCESS;
		}
		else{
			App::$db->begin_transaction();
			App::$db->query("
			INSERT INTO `sessions` (userid, timeIn, timeOut, site)
			VALUES ({$this->id}, '{$this->timeIn}', NOW(), {$this->site})") or sql_error();
			App::$db->query("UPDATE `users` SET `timeIn` = NULL, `site`=NULL WHERE `id` = {$this->id}") or sql_error();
			App::$db->commit();
			
			return SIGN_LOGGED_OUT;
		}
	}
	
	public function get_activity($since = null){
		// todo maybe sites.name needs separating
		//sessions.timeIn >= SUBDATE(NOW(), INTERVAL 7 DAY) AND
		$q = App::$db->query("
		SELECT sessions.timeIn, sessions.timeOut, sites.name, sessions.notes
		FROM `sessions`
		LEFT JOIN sites ON sessions.site = sites.id
		WHERE sessions.userid={$this->id}
		ORDER BY sessions.timeIn DESC") or sql_error();

		return $q->fetch_all();
	}
	
	public function belongs_to_account($against){
		return $this->organisation == $against;
	}

	public function get_data(){
		return array(
			'id' 			=> $this->id,
			'uid'	 		=> $this->uid,
			'name'			=> $this->name,
			'timeIn'		=> $this->timeIn,
			'sitename'		=> $this->sitename,
			'group'			=> $this->group,
			'organisation'	=> $this->organisation
		);
	}

	public function is_deactivated(){
		return $this->group == 2;
	}

	public function asset($id){
		
	}
}
?>