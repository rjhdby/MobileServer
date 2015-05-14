<?php
require_once 'db.class.php';
class history {
	function __construct($params) {
		$db = new db('apk');
		$db->insert('history', $params);
		$db->query('UPDATE entities SET modified=NOW() WHERE id='.$params['id_ent'].';');
	}
}