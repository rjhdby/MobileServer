<?php
class Config {
    const CONFIG = "config.php";
    private static $params;

    public static function get($key){
        if(isset(Config::$params[$key])){
            return Config::$params[$key];
        }else {
            Config::readConfig();
        }
        if(isset(Config::$params[$key])){
            return Config::$params[$key];
        }
        return "";
    }

    private static function readConfig() {
        $content = preg_grep("/.*=.*/", file(Config::CONFIG));
        foreach ($content as $row) {
            list($key, $value) = explode("=", $row, 2);
            $key = trim($key);
            $value = trim(preg_replace("/#.*/", "", $value));
            Config::$params[$key] = $value;
        }
    }
}