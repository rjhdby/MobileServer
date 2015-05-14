<?php
require_once 'class/db.class.php';
require_once 'class/config.class.php';
function remove_utf8_bom($text)
{
    $bom = pack('H*', 'EFBBBF');
    $text = preg_replace("/^$bom/", '', $text);
    return $text;
}

function nominationRequest($lon, $lat)
{
    $email = config::get('admin.email');
    $baseUrl = config::get('nomination.url');
    $url = $baseUrl . '?format=json&zoom=18&email=' . $email . '&addressdetails=1&lon=' . $lon . '&lat=' . $lat;
    $header = array(
        'Content-language: ru-RU',
        'Content-type: text/json;charset="iso8859-1"'
    );
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_REFERER, config::get('nomination.refer'));
    curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
    $text = curl_exec($curl);
    $response = json_decode(trim($text, "\x0"), true);
    curl_close($curl);
    $response = $response ['address'];
    $tags = Array(
        'state',
        'city',
        'road',
        'house_number'
    );
    $result = '';
    foreach ($tags as $tag) {
        if (isset ($response [$tag])) {
            $result .= ',' . $response [$tag];
        }
    }
    return trim($result, ",");
}

if (!isset ($HTTP_RAW_POST_DATA))
    $HTTP_RAW_POST_DATA = file_get_contents('php://input');
if (isset ($_GET ['lat']) && isset ($_GET ['lon'])) {
    $_POST ['lon'] = $_GET ['lon'];
    $_POST ['lat'] = $_GET ['lat'];
}

if (isset ($_POST ['lat']) && isset ($_POST ['lon'])) {
    $lon = $_POST ['lon'];
    $lat = $_POST ['lat'];
    $clon = round($lon, 3);
    $clat = round($lat, 3);
    $mysqli = new db('apk');
    $rs = $mysqli->query('SELECT address FROM geocode_cache WHERE lon=' . $clon . ' AND lat=' . $clat . ' LIMIT 1;');
    if ($rs->num_rows == 0) {
        $result = nominationRequest($lon, $lat);
        $mysqli->query('INSERT INTO geocode_cache (lat,lon,address) VALUES (' . $clat . ',' . $clon . ',"' . $result . '");');
    } else {
        $result = implode("", $rs->fetch_row());
    }
    $mysqli->close();
} else {
    $result = "\u0410\u0434\u0440\u0435\u0441 \u043d\u0435 \u043e\u043f\u0440\u0435\u0434\u0435\u043b\u0435\u043d";
}

echo json_encode(Array(
    'address' => trim($result, ',')
));
?>
