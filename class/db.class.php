<?php
require_once 'config.class.php';
class db extends mysqli
{
    function __construct($dbname = 'apk')
    {
        $host = config::get($dbname.'.host');
        $login = config::get($dbname.'.login');
        $password = config::get($dbname.'.password');
        $db = config::get($dbname.'.db');
        parent::__construct($host, $login, $password, $db);
        parent::set_charset("utf8");
    }

    function insert($tablename, $values)
    {
        $this->query('
				INSERT INTO ' . $tablename . '
				(' . implode(',', array_keys($values)) . ')
				VALUES
				(' . $this->makeDBDataset($values) . ')
				;');
    }

    function makeDBDataset($arr)
    {
        $result = '';
        foreach ($arr as $value) {
            if (is_int($value)) {
                $result .= $value . ',';
            } else {
                $result .= '"' . str_replace("\\n", "\n", $this->real_escape_string($value)) . '",';
            }
        }
        return trim($result, ',');
    }
}