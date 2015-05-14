<?php

require_once('class/db.class.php');
class logging {
    private $id;
    private $table;
    function __construct($table = 'httplog')
    {
        $this->table = $table;
    }
    function logRequest($text){
        $db = new db();
        $db->insert($this->table, array('request' => $text));
        $this->id = $db->insert_id;
        $db->close();
    }
    function logResponse($text){
        $db = new db();
        $db->query("UPDATE ".$this->table."
                    SET response='".$db->real_escape_string($text)."'
                    WHERE id=".$this->id.";");
        $db->close();
    }
}