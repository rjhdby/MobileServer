<?php
require_once 'auth.class.php';
require_once 'db.class.php';
class message {
	private $params;
	private $user;
	function __construct($params) {
		$this->params = $params;
	}
	private function check_prereq($state = array()) {
		$prereq = array (
				"login",
				"passhash",
				"id",
				"text" 
		);
		$prereq = array_merge($prereq, $state);
		foreach ( $prereq as $key ) {
			if (! isset ( $this->params [$key] )) {
				return false;
			}
		}
		return true;
	}
	public function create_message(){
		if (! $this->check_prereq ()) {
			return "ERROR";
		}
		$auth = new auth ();
		if (! $auth->check ( $this->params ['login'], $this->params ['passhash'] )) {
			return "ERROR";
		}
		$this->user = $auth->get_data ( $this->params ['login'], true );
		if ($this->user ['role'] == 'readonly') {
			return "READONLY";
		}
		$db = new db('apk');
		$db->query('
				INSERT INTO messages
				(
					id_ent,
					id_user,
					text,
					modified
				)
				VALUES
				(
					'.$db->real_escape_string($this->params['id']).',
					'.$db->real_escape_string($this->user['id']).',
					"'.$db->real_escape_string($this->params['text']).'",
					NOW()
				)
				;');
		if($db->error){
			return $db->error;
		}
		$db->query('UPDATE entities SET modified = NOW() WHERE id='.$db->real_escape_string($this->params['id']).';');
		$db->close();
		return "OK";
	}
	
	public function change_state(){
		if (! $this->check_prereq (array("state", "id_msg"))) {
			return "ERROR";
		}
		if (!$this->isModerator()){
			return "NO RIGHTS";
		}
		$db = new db('apk');
		$db->query('
				UPDATE messages
				SET
					status = "'.$db->real_escape_string($this->params['state']).'"
					modified = NOW()
				WHERE
					id_ent = '.$db->real_escape_string($this->params['id']).',
					AND id = '.$db->real_escape_string($this->user['id_msg']).'
				;');
		if($db->error){
			return $db->error;
		}
		$db->query('UPDATE entities SET modified = NOW() WHERE id='.$db->real_escape_string($this->params['id']).';');
		$db->close();
		return "OK";
	}
	
	private function isRO() {
		$roles = array (
				'readonly',
				'standart',
				'moderator',
				'admin'
		);
		return in_array ( $this->user ['role'], $roles );
	}
	private function isStandart() {
		$roles = array (
				'standart',
				'moderator',
				'admin'
		);
		return in_array ( $this->user ['role'], $roles );
	}
	private function isModerator() {
		$roles = array (
				'moderator',
				'admin'
		);
		return in_array ( $this->user ['role'], $roles );
	}
	private function isAdmin() {
		$roles = array (
				'admin'
		);
		return in_array ( $this->user ['role'], $roles );
	}
}