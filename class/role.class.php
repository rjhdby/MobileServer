<?php
require_once 'auth.class.php';
require_once 'db.class.php';
class role {
	public $role;
	function __construct($login) {
		$auth = new auth ();
		$data = $auth->get_data ( $login );
		$this->role = $data ['role'];
	}
	public function isRO() {
		$roles = array (
				'readonly',
				'standart',
				'moderator',
				'admin' 
		);
		return $this->check ( $roles );
	}
	public function isStandart() {
		$roles = array (
				'standart',
				'moderator',
				'admin' 
		);
		return $this->check ( $roles );
	}
	public function isModerator() {
		$roles = array (
				'moderator',
				'admin' 
		);
		return $this->check ( $roles );
	}
	public function isAdmin() {
		$roles = array (
				'admin' 
		);
		return $this->check ( $roles );
	}
	private function check($roles) {
		return in_array ( $this->role, $roles );
	}
}