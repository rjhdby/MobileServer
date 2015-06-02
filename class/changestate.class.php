<?php
require_once 'auth.class.php';
require_once 'db.class.php';
require_once 'role.class.php';
class changeState {
	private $params;
	private $user;
	function __construct($params) {
		$this->params = $params;
	}
	private function check_prereq() {
		$prereq = array (
				"login",
				"passhash",
				"id",
				"state" 
		);
		foreach ( $prereq as $key ) {
			if (! isset ( $this->params [$key] )) {
				return false;
			}
		}
		return true;
	}
	public function change_state() {
        $role = new Role($this->params ['login']);
		$state = $this->params ['state'];
		if (! $this->check_prereq ()) {
			return "ERROR";
		}
		$auth = new auth ();
		if (! $auth->check ( $this->params ['login'], $this->params ['passhash'] ))
			return "ERROR";
		$this->user = $auth->get_data ( $this->params ['login'], true );
		if (! $role->isStandart ())
			return "READONLY";
		if (! $role->isModerator () && in_array ( $state, array (
				'acc_status_hide',
				'acc_status_act' 
		) ))
			return "NO RIGHTS";
		if (! $role->isAdmin () && $state == 'acc_status_war')
			return "NO RIGHTS";
		$db = new db ( 'apk' );
		$db->autocommit ( false );
		$db->query ( '
				UPDATE entities 
				SET 
					status = "' . $db->real_escape_string ( $state ) . '",
					modified = NOW()
				WHERE id=' . $db->real_escape_string ( $this->params ['id'] ) . '
				;' );
		if($db->error){
			return $db->error;
		}
		$db->query ( '
				INSERT INTO history 
				(
					id_ent, 
					id_user, 
					action
				) 
				VALUES
				(
					' . $db->real_escape_string ( $this->params ['id'] ) . ',
					' . $db->real_escape_string ( $this->user ['id'] ) . ',
					"'.$db->real_escape_string ( $state ).'"
				);' );
		if($db->error){
			return $db->error;
		}
		$db->commit ();
		$db->close ();
		return "OK";
	}
}