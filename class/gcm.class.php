<?php
require_once 'db.class.php';
require_once 'auth.class.php';
require_once 'config.class.php';
require_once 'logging.class.php';

class gcm {
    private $params;
    private $log;

    function __construct($params) {
        $this->params = $params;
        //$this->log = new logging();
        //$this->log->logRequest(json_encode($params));
    }
    private function check_prereq($optional = array()) {
        $prereq = array (
            "login",
            "passhash"
        );
        $prereq = array_merge ( $prereq, $optional );
        foreach ( $prereq as $key ) {
            if (! isset ( $this->params [$key] )) {
                return false;
            }
        }
        return true;
    }
    function sendBroadcast() {
        //$this->params ['ids'] = $this->getIds ( 0 );
        $gcm = $this->getIds ( 0 );
        if (! $this->check_prereq ()) {
            return 'ERROR PREREQUISITES';
        }
        /*
        $result = $this->sendGroup ();
        */
        $result ="";
        for($i = 0; $i < count ( $gcm ); $i += 20) {
            $result .= $this->send ( array_slice ( $gcm, $i, 20 ) );
        }
        //$this->log->logResponse($result);
        return $result;
    }
    /*
     * function sendOne($id) {
     * return $this->send ( $this->getIds ( $id ) );
     * }
     */
    function sendGroup() {
        if (! $this->check_prereq ( array (
            "ids"
        ) )) {
            return 'ERROR PREREQUISITES';
        }
        $result = "";
        $gcm = $this->getIds ( $this->params ['ids'] );
        for($i = 0; $i < count ( $gcm ); $i += 5) {
            $result .= $this->send ( array_slice ( $gcm, $i, 5 ) );
        }
        //$this->log->logResponse($result);
        return $result;
    }
    private function send($ids) {
        if (! $this->check_prereq ( array (
            "message",
            "title",
            "type",
            "id",
            "lat",
            "lon"
        ) )) {
            return 'ERROR PREREQUISITES GCM';
        }
        /*
        $text = $this->params ['message'];
        $title = $this->params ['title'];
        $id = $this->params ['id'];
        $type = $this->params ['type'];
        $lat = $this->params ['lat'];
        $lng = $this->params ['lng'];

        $data = array (
                'message' => $text,
                'title' => $title,
                'id' => $id,
                'type' => $type,
                'delay_with_idle' => false,
                'lat' => $lat,
                'lon' => $lon
        );
        */
        $data = $this->params;
        if(isset($data['ids'])) {
            unset($data['ids']);
        }
        unset($data['login']);
        unset($data['passhash']);
        $data['delay_with_idle'] = false;
        $data['time_to_live'] = 350;
        $data['lng'] = $data['lon'];
        $data['created'] = date("Y-m-d H:i");
        /*
                $headers = array (
                    'Authorization: key=' . $this->apiKey,
                    'Content-Type: application/json'
                );
        */
        $headers = array (
            'Authorization: key=' . config::get('gcm.key'),
            'Content-Type: application/json'
        );
        $post = array (
            'registration_ids' => $ids,
            'data' => $data
        );
        $ch = curl_init ();
        curl_setopt ( $ch, CURLOPT_URL, config::get('gcm.url') );
        curl_setopt ( $ch, CURLOPT_POST, true );
        curl_setopt ( $ch, CURLOPT_HTTPHEADER, $headers );
        curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt ( $ch, CURLOPT_POSTFIELDS,  json_encode($post));

        $result = curl_exec ( $ch );
        if (curl_errno ( $ch )) {
            $result = 'GCM error: ' . curl_error ( $ch );
        }
        curl_close ( $ch );
        return $result;
    }
    private function getIds($ids_apk = 0) {
        if (! is_array ( $ids_apk )) {
            $ids_apk = array (
                $ids_apk
            );
        }
        $apk = new db ();
        if ($ids_apk [0] == 0) {
            $gcm_raw = $apk->query ( 'SELECT DISTINCT gcm FROM devices WHERE NOT gcm IS NULL;' );
        } else {
            $gcm_raw = $apk->query ( 'SELECT DISTINCT gcm FROM devices WHERE id_user IN(' . implode ( ',', $ids_apk ) . ') AND NOT gcm IS NULL;' );
        }
        $gcm = array ();
        while ( $row = $gcm_raw->fetch_row () ) {
            $gcm [] = $row [0];
        }
        return $gcm;
    }
    function registration() {
        if (! $this->check_prereq ( array (
            "owner_id",
            "gcm_key"
        ) )) {
            return 'ERROR PREREQUISITES';
        }
        $apk = new db ();
        if (isset ( $this->params ['imei'] )) {
            $imei = $apk->real_escape_string ( $this->params ['imei'] );
        } else {
            $imei = "NULL";
        }
        $id = $this->params ['owner_id'];
        $gcm = $apk->real_escape_string ( $this->params ['gcm_key'] );
        $result = implode ( $apk->query ( 'SELECT COUNT(*) FROM devices WHERE id_user = ' . $id . ' AND imei = "' . $imei . '";' )->fetch_row () );
        if ($result == 0) {
            $query = 'INSERT INTO devices ( id_user , imei, gcm ) VALUES (' . $id . ',"' . $imei . '","' . $gcm . '");';
        } else {
            $query = 'UPDATE devices SET gcm="' . $gcm . '", registered=NOW() WHERE id_user=' . $id . ' AND imei="'.$imei.'";';
        }
        $apk->query ($query);
        return 'OK';
    }
}