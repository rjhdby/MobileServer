<?php
require_once 'auth.class.php';
require_once 'db.class.php';
class delta {
	private $params;
	private $user;
	function __construct($params) {
		$this->params = $params;
	}
	private function check_prereq() {
		$prereq = array (
				"owner_id",
				"type",
				"med",
				"lat",
				"lon",
				"created",
				"address",
				"descr"
		);
		foreach ( $prereq as $key ) {
			if (! isset ( $this->params [$key] )) {
				return false;
			}
		}
		return true;
	}
	public function create_acc() {
		if (! $this->check_prereq ()) {
			return "ERROR";
		}
		/*
		$auth = new auth ();
		if (! $auth->check ( $this->params ['login'], $this->params ['passhash'] )) {
			return "ERROR";
		}
		$this->user = $auth->get_data ( $this->params ['login'], true );
		if ($this->user ['role'] == 'readonly') {
			return "READONLY";
		}
		*/
		$db = new db ( 'apk' );
		$db->autocommit ( false );
//		$starttime = $db->real_escape_string ( $this->params ['created'] );
//		$starttime = preg_replace("/(\d\d).(\d\d).(\d\d\d\d)(.*)/", "\3-\2-\1 \4", $starttime);
//		$starttime = preg_replace("/ (\d):/", " 0\1:", $starttime);
		$owner = $db->real_escape_string ( $this->params ['owner_id'] );
		$lat = $db->real_escape_string ( $this->params ['lat'] );
		$lon = $db->real_escape_string ( $this->params ['lon'] );
		$address = $db->real_escape_string ( $this->params ['address'] );
		$description = $db->real_escape_string ( $this->params ['descr'] );
		$attr = $db->real_escape_string ( json_encode ( array (
				"type" => $this->params ['type'],
				"med" => $this->params ['med'] 
		) ) );
		$query = '
				INSERT INTO entities 
				(
					created,
					starttime,
					modified,
					owner,
					type,
					lat,
					lon,
					address,
					description,
					status,
					attr
				) VALUES (
					NOW(),
					NOW(),
					NOW(),
					' . $owner . ',
					"mc_accident",
					' . $lat . ',
					' . $lon . ',
					"' . $address . '",
					"' . $description . '",
					"acc_status_act",
					"' . $attr . '"
				);';
		
		$db->query ( $query );
		$id = $db->insert_id;
		$hparams = $db->real_escape_string ( json_encode ( array (
				"lon" => $this->params ['type'],
				"lat" => $this->params ['med'],
				"address" => $this->params ['address'] 
		) ) );
		$query = 'INSERT INTO history 
				(
					id_ent,
					id_user,
					action,
					params
				) VALUES (
					' . $id . ',
					' . $owner . ',
					"create_mc_acc",
					"' . $hparams . '"
				);';
		$db->query ( $query );
		if ($db->error) {
			return $db->error;
		}
		$db->commit ();
		if($this->params ['type'] != 'acc_o'){
			require_once 'createtopic.class.php';
			$topic = new createtopic($this->params);
			$tid = $topic->makeTopic();
			if(is_int($tid)){
				$db->query('UPDATE entities SET forum_id='.$tid.' WHERE id='.$id.';');
				$db->commit ();
			}
			require_once 'sms.class.php';
			require_once 'utils.class.php';
			$utils = new utils();
			$med = $utils->getStatic($this->params ['med']);
			$type = $utils->getStatic($this->params ['type']);
			$sms = new sms();
			$text = $type.",".$med.",".$address.",".$utils->shortURL($utils->makeYMAPSURL($lon, $lat));
			$sms->translit = true;
			$sms->sendOne("79035333639", $text);
		}
		$db->close ();
		return "OK";
	}
}
