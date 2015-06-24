<?php
require_once 'db.class.php';
require_once 'onway.class.php';
class getlist {
	private $params;
	private $db;
	private $lastlogin;
	function __construct($params) {
		$this->params = $params;
	}
	public function get_list() {
		$this->db = new db ( 'apk' );
		$query = 'SELECT
					a.id,
					a.created,
					UNIX_TIMESTAMP(a.created) as uxtime,
					a.address,
					a.type,
					a.description as descr,
					b.value AS status_text,
					a.status,
					c.login AS owner,
					a.owner AS owner_id,
					a.lat,
					a.lon,
					a.attr
				FROM
					entities a,
					static b,
					users c
				WHERE
					1=1
					AND a.type="mc_accident"
					AND b.application="mc_accident"
					AND b.attribute=a.status
					AND a.owner=c.id
					AND a.status != "acc_status_dbl"
					AND NOW() < (DATE_ADD(a.starttime, INTERVAL a.duration HOUR))
				' . $this->make_where () . '
				;';
		if (isset ( $this->params ['debug'] )) {
			echo $query;
		}
		$rs = $this->db->query ( $query );
		if ($rs->num_rows == 0) {
			$out = array (
					array (
							"error" => "no_new" 
					) 
			);
		} else {
			while ( $row = $rs->fetch_assoc () ) {
				$dataset = array ();
				$attr = json_decode ( $row ['attr'], true );
				unset ( $row ['attr'] );
				foreach ( $row as $key => $value ) {
					$dataset [$key] = $value;
				}
				$onway_params = array (
						"id" => $dataset ['id'] 
				);
				$onway = new onway ( $onway_params );
				$out [] = array_merge ( $dataset, $this->parse_attributes ( $attr, $dataset ['id'] ), array (
						"messages" => $this->get_messages ( $dataset ['id'] ),
						"onway" => $onway->getVolunteers (),
						"history" => $this->get_history ( $dataset ['id'] ) 
				) );
			}
		}
		$this->update_user ();
		$this->db->close ();
		return array (
				"list" => $out 
		);
	}
	private function make_where() {
		$where = "";
		if (isset ( $this->params ['lon'] ) && isset ( $this->params ['lat'] ) && isset ( $this->params ['distance'] )) {
			$lon = $this->db->real_escape_string ( deg2rad ( $this->params ['lon'] ) );
			$lat = $this->db->real_escape_string ( deg2rad ( $this->params ['lat'] ) );
			$d = $this->db->real_escape_string ( $this->params ['distance'] / 6371 );
			$where .= ' AND ACOS(COS(' . $lat . ')*COS(RADIANS(lat))*COS(RADIANS(lon)-' . $lon . ')+SIN(' . $lat . ')*SIN(RADIANS(lat))) <' . $d;
		}
		if (isset ( $this->params ['update'] ) && isset ( $this->params ['user'] )) {
			$user = $this->db->real_escape_string ( $this->params ['user'] );
			$this->lastlogin = implode ( "", $this->db->query ( 'SELECT IFNULL(lastgetlist,DATE_SUB(NOW(), INTERVAL 24 HOUR)) FROM users WHERE login="' . $user . '" ' )->fetch_row () );
			/*
			 * $where .= ' AND (a.modified >"' . $this->lastlogin . '"
			 * OR (SELECT COUNT(id) FROM messages WHERE modified>"' . $this->lastlogin . '") > 0
			 * OR (SELECT COUNT(id) FROM onway WHERE timest>"' . $this->lastlogin . '") > 0)
			 * ';
			 */
			$where .= ' AND (a.modified >"' . $this->lastlogin . '")';
		}
		return $where;
	}
	private function update_user() {
		if (isset ( $this->params ['user'] )) {
			$user = $this->db->real_escape_string ( $this->params ['user'] );
			$this->db->query ( 'UPDATE users SET lastgetlist = NOW() WHERE login="' . $user . '";' );
		}
	}
	private function parse_attributes($attr, $id) {
		$out = array ();
		foreach ( $attr as $key => $value ) {
			if (is_numeric ( $value )) {
				$rs = $this->db->query ( 'SELECT value FROM attributes WHERE entity=' . $id . ' AND attribute=' . $value . ' LIMIT 1;' )->fetch_assoc ();
			} else {
				$rs = $this->db->query ( 'SELECT value FROM static WHERE application="mc_accident" AND attribute="' . $value . '" LIMIT 1;' )->fetch_assoc ();
			}
			$out ["mc_accident_" . $key] = $rs ['value'];
			$out ["mc_accident_orig_" . $key] = $value;
		}
		return $out;
	}
	public function get_messages($id) {
		$where = '';
		if (isset ( $this->params ['update'] )) {
			$where = ' AND IFNULL(modified, created)>"' . $this->lastlogin . '"';
		}
		$query = 'SELECT 
				a.id, 
				a.id_user,
				b.login as owner,
				a.modified,
				UNIX_TIMESTAMP(a.modified) as uxtime,
				a.text,
				a.status
				FROM messages a, users b
				WHERE a.id_user=b.id
					AND a.id_ent = ' . $id . '
					' . $where . '
				;';
		$rs = $this->db->query ( $query );
		$out = array ();
		while ( $row = $rs->fetch_assoc () ) {
			$out [] = $row;
		}
		return $out;
	}
	public function get_history($id) {
        /*
		$query = 'SELECT
				a.id,
				a.id_user,
				b.login as owner,
				a.timest as time,
				UNIX_TIMESTAMP(a.timest) as uxtime,
				a.action
				FROM history a, users b
				WHERE a.id_user=b.id
					AND a.id_ent = ' . $id . '
				;';
        */
        $query = 'SELECT
				MAX(a.id) AS id,
				a.id_user,
				b.login AS owner,
				MAX(UNIX_TIMESTAMP(a.timest)) AS uxtime,
				a.action
				FROM history a, users b
				WHERE a.id_user=b.id
					AND a.id_ent = ' . $id . '
                GROUP BY a.id_user, owner, a.action
				;';
		$rs = $this->db->query ( $query );
		$out = array ();
		while ( $row = $rs->fetch_assoc () ) {
			$out [] = $row;
		}
		return $out;
	}
}