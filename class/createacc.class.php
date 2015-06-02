<?php
require_once 'auth.class.php';
require_once 'db.class.php';
require_once 'config.class.php';
require_once 'role.class.php';

class createacc
{
    private $params;
    private $user;
    private $is_test;

    function __construct($params)
    {
        $this->params = $params;
        if ($this->params['owner_id'] == 8) {
            $this->params['test'] = 1;
            $this->params['owner_id'] = 1;
        }
    }

    private function check_prereq()
    {
        $prereq = array(
            "owner_id",
            "type",
            "med",
            "lat",
            "lon",
            "created",
            "address",
            "descr",
            "login",
            "passhash"
        );
        foreach ($prereq as $key) {
            if (!isset ($this->params [ $key ])) {
                return false;
            }
        }

        return true;
    }

    private function check_last_created($timeout = 1800)
    {
        $db = new db ('apk');
        $timestamp = implode('', $db->query('SELECT IFNULL(UNIX_TIMESTAMP(MAX(created)), 0) FROM entities WHERE owner=' . $this->params ['owner_id'] . ';')->fetch_row());
        if ((time() - $timestamp) < $timeout) {
            return false;
        }

        return true;
    }

    public function create_acc()
    {
        $this->is_test = false;
        $role = new Role($this->params ['login']);
        $operator_id = config::get('operator.id.app');
        $delta_id = config::get('operator.id.delta');
        if (!$this->check_prereq()) {
            return "ERROR PREREQUISITES";
        }
        if ($this->params ['owner_id'] != $delta_id) {
            $auth = new auth ();
            if (!$auth->check($this->params ['login'], $this->params ['passhash'])) {
                return "AUTH ERROR";
            }
            $this->user = $auth->get_data($this->params ['login'], true);
            if ($this->user ['role'] == 'readonly') {
                return "READONLY";
            }
        } else {
            $this->params ['owner_id'] = $operator_id;
            if ($this->params ['type'] == 'acc_o') {
                return "PROBABLY SPAM";
            }
        }
        if ($this->user ['role'] == 'standart' && $this->params ['owner_id'] != $operator_id) {
            if (!$this->check_last_created()) {
                return "PROBABLY SPAM";
            }
        }
        if (!$this->check_last_created(60)
            && $role->isModerator()
            && $this->params ['owner_id'] != $operator_id) {
            return "PROBABLY SPAM";
        }
        if($role->isDeveloper()){
            $this->is_test = true;
        }
        $db = new db ('apk');
        $db->autocommit(false);
        $starttime = $db->real_escape_string($this->params ['created']);
        $owner = $db->real_escape_string($this->params ['owner_id']);
        $lat = $db->real_escape_string($this->params ['lat']);
        $lon = $db->real_escape_string($this->params ['lon']);
        $address = $db->real_escape_string($this->params ['address']);
        $description = $db->real_escape_string($this->params ['descr']);
        $attr = $db->real_escape_string(json_encode(array(
            "type" => $this->params ['type'],
            "med"  => $this->params ['med']
        )));
        //if (!isset($this->params['test'])) {
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
					attr,
					is_test
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
					"' . $attr . '",
					'.$this->is_test.'
				);';

        $db->query($query);

        if ($db->error) {
            return $db->error;
        }
        $id = $db->insert_id;
        $hparams = $db->real_escape_string(json_encode(array(
            "lon"     => $this->params ['type'],
            "lat"     => $this->params ['med'],
            "address" => $this->params ['address']
        )));
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
        $db->query($query);
        if ($db->error) {
            return $db->error;
        }
        $db->commit();
        // }
        $result = 0;
        $gcm_result = 'GCM ERROR';
        require_once 'utils.class.php';
        require_once 'gcm.class.php';
        $utils = new utils();
        $med = $utils->getStatic($this->params ['med']);
        $title = $utils->getStatic($this->params ['type']);
        if ($med != '') {
            $title .= ', ' . $med;
        }
        if (!$this->is_test) {
            $gcm_array = array(
                'login'                 => $this->params ['login'],
                'passhash'              => $this->params ['passhash'],
                'message'               => $address . ", " . $description,
                'title'                 => $title,
                'type'                  => $this->params ['type'],
                'id'                    => $id,
                'lat'                   => $lat,
                'lon'                   => $lon,
                'mc_accident_orig_med'  => $this->params ['med'],
                'mc_accident_orig_type' => $this->params ['type'],
                'status'                => 'acc_status_act',
                'address'               => $address,
                'owner_id'              => $owner,
                'owner'                 => $this->params ['login'],
                'descr'                 => $description
            );
            $gcm = new gcm ($gcm_array);
            $gcm_result = $gcm->sendBroadcast();
        }

        if ($this->params ['type'] != 'acc_o') {
            require_once 'createtopic.class.php';

            require_once 'logging.class.php';
            $log = new logging();
            $log->logRequest(json_encode($this->params));

            $topic = new createtopic ($this->params);

            $tid = $topic->makeTopic();
            $log->logResponse(json_encode($tid));
            if (is_int($tid)) {
                $db->query('UPDATE entities SET forum_id=' . $tid . ' WHERE id=' . $id . ';');
                $db->commit();
                $result = $tid;
            }
            require_once 'twitter.class.php';
            new twitter($title . ', ' . $address . ", " . $description, $lon, $lat);
        }
        $db->close();

        return array('ID' => $result);
    }
}