<?php
require_once 'db.class.php';
require_once 'auth.class.php';
class onway {
    private $params;
    function __construct($params) {
        $this->params = $params;
        $apk = new db ();
        if ($this->check_prereq ()) {
            $this->params ['owner_id'] = implode ( $apk->query ( 'SELECT id FROM users WHERE login="' . $apk->real_escape_string ( $this->params ['login'] ) . '";' )->fetch_row () );
        } else {
            $this->params ['owner_id'] = 0;
        }
    }
    private function check_prereq() {
        $prereq = array (
            "id",
            "login"
        );
        foreach ( $prereq as $key ) {
            if (! isset ( $this->params [$key] )) {
                return false;
            }
        }
        return true;
    }
    private function checkAll() {
        if (! $this->check_prereq ()) {
            return "ERROR PREREQUISITES";
        }
/*
        $auth = new auth ();
        if (! $auth->check ( $this->params ['login'], $this->params ['passhash'] )) {
            return "AUTH ERROR";
        }
        $this->user = $auth->get_data ( $this->params ['login'], true );
        if ($this->user ['role'] == 'readonly') {
            return "READONLY";
        }
*/
        return "OK";
    }
    public function onway() {
        return $this->changeStatus("onway");
    }
    public function inplace() {
        return $this->changeStatus("inplace");
    }
    public function leave() {
        return $this->changeStatus("leave");
    }
    public function cancel() {
        return $this->changeStatus("cancel");
    }
    private function changeStatus($status){
        $check = $this->checkAll ();
        if ($check != "OK")
            return $check;
        $id_user = $this->params ['owner_id'];
        $id = $this->params ['id'];
        $apk = new db ();
        $check = implode ( $apk->query ( 'SELECT COUNT(*) FROM onway WHERE id=' . $id . ' AND id_user=' . $id_user . ';' )->fetch_row () );
        if ($check == 0) {
            $insert = array (
                "id" => $id,
                "id_user" => $id_user,
                "status" => $status
            );
            $apk->insert ( "onway", $insert );
        } else {
            $apk->query ( 'UPDATE onway SET status="'.$status.'", timest=NOW() WHERE id=' . $id . ' AND id_user=' . $id_user . ';' );
        }
        $apk->query('UPDATE entities SET modified = NOW() WHERE id='.$id.';');
        $insert = array (
            "id_ent" => $id,
            "id_user" => $id_user,
            "action" => $status
        );
        $apk->insert ( "history", $insert );
        $apk->close ();
        return "OK";
    }
    public function getVolunteers() {
        $apk = new db ();
        $id = $this->params ['id'];
        $result_raw = $apk->query ( '
				SELECT
					a.id_user as id,
					b.login as name,
					a.status as status,
					a.timest as timest,
					UNIX_TIMESTAMP(a.timest) as uxtime
				FROM
					onway a, users b
				WHERE 1=1
					AND a.id = ' . $id . '
					AND a.id_user = b.id
				ORDER BY a.status, a.timest DESC
				;' );
        $result = array ();
        while ( $row = $result_raw->fetch_assoc () ) {
            $result [] = $row;
        }
        return $result;
    }
}