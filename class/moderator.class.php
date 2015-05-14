<?php
require_once 'auth.class.php';
require_once 'db.class.php';
require_once 'role.class.php';
require_once 'history.class.php';
class moderator {
	private $params;
	private $user;
	function __construct($params) {
		$this->params = $params;
		$this->user = new auth ();
	}
	private function check_prereq($optional = array()) {
		$prereq = array (
				"login",
				"passhash",
				"user_id",
				"id" 
		);
		$prereq = array_merge ( $prereq, $optional );
		foreach ( $prereq as $key ) {
			if (! isset ( $this->params [$key] )) {
				return false;
			}
		}
		return true;
	}
	public function ban() {
		// $prereq = array("type","id");
		if (! $this->check_prereq ()) {
			return "ERROR PREREQUISITES";
		}
		if (! $this->user->check ( $this->params ['login'], $this->params ['passhash'] )) {
			return "AUTH ERROR";
		}
		$role = new role ( $this->params ['login'] );
		if (! $role->isModerator ()) {
			return "NO RIGHTS";
		}
		$user_info = $this->get_user_info ();
		if ($user_info == "ERROR") {
			return "NO USER";
		}
		if ($user_info ['role'] == 'moderator' && ! $role->isAdmin ()) {
			return "NO RIGHTS";
		}
		$db = new db ( 'apk' );
		$query = 'UPDATE users SET role="readonly" WHERE id=' . $db->real_escape_string ( $this->params ['user_id'] ) . ';';
		$db->query ( $query );
		$history = array (
				'id_ent' => $this->params ['id'],
				'id_user' => $this->params ['user_id'],
				'action' => 'ban',
				'params' => '{"moderator":"' . $this->params ['login'] . '"}' 
		);
		new history ( $history );
		$db->close ();
		return 'OK';
	}
	private function get_user_info() {
		$db = new db ( 'apk' );
		$result = $db->query ( 'SELECT login, role FROM users WHERE id=' . $db->real_escape_string ( $this->params ['user_id'] ) . ';' );
		if ($result->num_rows == 0) {
			return 'ERROR';
		}
		return $result->fetch_assoc ();
	}
}
?>
