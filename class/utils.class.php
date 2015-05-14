<?php
class utils {
	function translit($str) {
		$table = array (
				'А' => 'A',
				'Б' => 'B',
				'В' => 'V',
				'Г' => 'G',
				'Д' => 'D',
				'Е' => 'E',
				'Ё' => 'YO',
				'Ж' => 'ZH',
				'З' => 'Z',
				'И' => 'I',
				'Й' => 'J',
				'К' => 'K',
				'Л' => 'L',
				'М' => 'M',
				'Н' => 'N',
				'О' => 'O',
				'П' => 'P',
				'Р' => 'R',
				'С' => 'S',
				'Т' => 'T',
				'У' => 'U',
				'Ф' => 'F',
				'Х' => 'H',
				'Ц' => 'C',
				'Ч' => 'CH',
				'Ш' => 'SH',
				'Щ' => 'SCH',
				'Ь' => '',
				'Ы' => 'Y',
				'Ъ' => '',
				'Э' => 'E',
				'Ю' => 'YU',
				'Я' => 'YA',
				
				'а' => 'a',
				'б' => 'b',
				'в' => 'v',
				'г' => 'g',
				'д' => 'd',
				'е' => 'e',
				'ё' => 'yo',
				'ж' => 'zh',
				'з' => 'z',
				'и' => 'i',
				'й' => 'j',
				'к' => 'k',
				'л' => 'l',
				'м' => 'm',
				'н' => 'n',
				'о' => 'o',
				'п' => 'p',
				'р' => 'r',
				'с' => 's',
				'т' => 't',
				'у' => 'u',
				'ф' => 'f',
				'х' => 'h',
				'ц' => 'c',
				'ч' => 'ch',
				'ш' => 'sh',
				'щ' => 'sch',
				'ь' => '',
				'ы' => 'y',
				'ъ' => '',
				'э' => 'e',
				'ю' => 'yu',
				'я' => 'ya' 
		);
		$splittedString = $this->str_split_unicode ( $str );
		foreach ( $splittedString as $key => $letter ) {
			if (isset ( $table [$letter] )) {
				$splittedString [$key] = $table [$letter];
			}
		}
		return implode ( '', $splittedString );
	}
	function makeSEO($str) {
		$str = $this->translit ( $str );
		$str = preg_replace ( '/\s/', '-', $str );
		$str = preg_replace ( array (
				'/[^\w-]/',
				'/_/' 
		), '', $str );
		return trim ( strtolower ( $str ), '-' );
	}
	function str_split_unicode($str) {
		$ret = array ();
		$len = mb_strlen ( $str, "UTF-8" );
		for($i = 0; $i < $len; $i ++) {
			$ret [] = mb_substr ( $str, $i, 1, "UTF-8" );
		}
		return $ret;
	}
	function shortURL($url) {
		$key = 'AIzaSyCQ5vAFaPP7KvAdQBQSaCTOoy91uWApCwQ';
		$apiURL = 'https://www.googleapis.com/urlshortener/v1/url?key=' . $key;
		$ch = curl_init ();
		curl_setopt ( $ch, CURLOPT_URL, $apiURL );
		curl_setopt ( $ch, CURLOPT_POST, 1 );
		curl_setopt ( $ch, CURLOPT_POSTFIELDS, json_encode ( array (
				"longUrl" => $url 
		) ) );
		curl_setopt ( $ch, CURLOPT_HTTPHEADER, array (
				"Content-Type: application/json" 
		) );
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
		$result = json_decode ( curl_exec ( $ch ), TRUE );
		curl_close ( $ch );
		return $result ['id'];
	}
	function getStatic($static){
		require_once 'db.class.php';
		$apk = new db();
		$result = implode ( $apk->query ( 'SELECT value FROM static WHERE attribute="' . $apk->real_escape_string ( $static ) . '";' )->fetch_row () );
		return $result;
	}
	function makeYMAPSURL($lon, $lat){
		return "http://maps.yandex.ru/?ll=" . $lon . "," . $lat . "&l=map&z=15&pt=" . $lon . "," . $lat;
	}
}