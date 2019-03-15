<?php
class assets {
    public function __construct($aid){
		if(!validate_int($aid))
			throw new Exception(ERR_NOT_NUMERIC);

		$q = App::$db->query("
		SELECT id, username, domain, registered_uid, apikey
		FROM accounts
		WHERE id={$id}") or sql_error();

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

    public function update_asset($assetid, $userid){
        $asset = new asset($assetid);
        $v = $asset->update($userid);
        return $v;
    }
}

class asset {
    public function __construct($assetid){
		if(!validate_int($assetid))
			throw new Exception(ERR_NOT_NUMERIC);

		$q = App::$db->query("
		SELECT id, name, user
		FROM assets
		WHERE id={$assetid}") or sql_error();

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

    public function update($userid){
        if($this->user == NULL){

        }
    }
}
?>