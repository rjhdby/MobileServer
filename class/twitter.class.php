<?php
require_once 'lib/twitteroauth.php';
require_once 'config.class.php';
class twitter{
    function __construct($status, $lon, $lat) {
        $status = mb_substr($status, 0, 140);
        $ConsumerKey = config::get('twitter.key');
        $ConsumerSecret = config::get('twitter.key.secret');
        $AccessToken = config::get('twitter.token');
        $AccessTokenSecret = config::get('twitter.token.secret');
        $connection = new TwitterOAuth ( $ConsumerKey, $ConsumerSecret, $AccessToken, $AccessTokenSecret );
        $connection->host = "https://api.twitter.com/1.1/";

        $res = ( array ) $connection->post ( 'statuses/update', array (
            'status' => $status,
            'long' => $lon,
            'lat' => $lat
        ) );
        return $res;
    }
}