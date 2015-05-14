<?php
require_once 'db.class.php';
require_once 'config.class.php';
class auth {
	private $ident;
	function __construct() {
	}
	function check($login, $passhash) {
		$db = new db ( 'forum' );
		$login = $db->real_escape_string ( $login );
        $query = '
				SELECT
					member_id,
					members_pass_hash,
					members_pass_salt,
					members_display_name
				FROM '.config::get('table.members').'
				WHERE name="' . $login . '";
        ';
		$rs_raw = $db->query ( $query );
		if ($rs_raw->num_rows == 0) {
			return false;
		}
		$rs = $rs_raw->fetch_assoc ();
		$db->close ();
		if (md5 ( md5 ( $rs ['members_pass_salt'] ) . $passhash ) == $rs ['members_pass_hash']) {
			return array (
					"id" => $rs ['member_id'],
					"name" => $rs ['members_display_name'] 
			);
		} else {
			return false;
		}
	}
	function fakeCheck($login) {
		return array (
				"id" => 0,
				"name" => $login 
		);
	}
	function fakeLogin($params) {
		$result = array (
				"id" => 0,
				"name" => "",
				"role" => "readonly",
				"attr" => "" 
		);
		if (! isset ( $params ['login'] ) || ! isset ( $params ['passwordHash'] )) {
			return $result;
		}
		$login = $params ['login'];
		$passhash = $params ['passwordHash'];
		if (isset ( $params ['ident'] )) {
			$this->ident = $params ['ident'];
		} else {
			$ident = 'unknown';
		}
		$forum_data = $this->fakeCheck ( $login );
		if (! $forum_data) {
			return $result;
		}
		$result = $this->get_data ( $login );
		if ($result ['id'] != 0) {
			$db = new db ( 'apk' );
			$db->query ( 'UPDATE users SET lastlogin=NOW() WHERE login="' . $db->real_escape_string ( $login ) . '";' );
			$db->close ();
		}
		return $result;
	}
	function login($params) {
		$result = array (
				"id" => 0,
				"name" => "",
				"role" => "readonly",
				"attr" => "" 
		);
		if (! isset ( $params ['login'] ) || ! isset ( $params ['passwordHash'] )) {
			return $result;
		}
		$login = $params ['login'];
		$passhash = $params ['passwordHash'];
		if (isset ( $params ['ident'] )) {
			$this->ident = $params ['ident'];
		} else {
			$ident = 'unknown';
		}
		$forum_data = $this->check ( $login, $passhash );
		if (! $forum_data) {
			return $result;
		}
		$result = $this->get_data ( $login );
		if ($result ['id'] != 0) {
			$db = new db ( 'apk' );
			$db->query ( 'UPDATE users SET lastlogin=NOW() WHERE login="' . $db->real_escape_string ( $login ) . '";' );
			$db->close ();
		}
		return $result;
	}
	function get_data($login, $force = true) {
		$result = array (
				"id" => 0,
				"name" => $login,
				"role" => "readonly",
				"attr" => "" 
		);
		$db = new db ( 'apk' );
		$login = $db->real_escape_string ( $login );
		$rs_raw = $db->query ( 'SELECT id, role, attr FROM users WHERE login="' . $login . '";' );
		if ($rs_raw->num_rows == 0) {
			if ($force) {
				$ident = $db->real_escape_string ( $this->ident );
				$db->autocommit ( false );
				$db->query ( 'INSERT INTO users (login,register,imei) VALUES ("' . $login . '",NOW(),"' . $ident . '");' );
				$result ['id'] = $db->insert_id;
				$result ['role'] = "standart";
				$db->commit ();
				$db->autocommit ( true );
			}
		} else {
			$rs = $rs_raw->fetch_assoc ();
			$result ['id'] = $rs ['id'];
			$result ['role'] = $rs ['role'];
			$result ['attr'] = $rs ['attr'];
		}
		$db->close ();
		return $result;
	}
}