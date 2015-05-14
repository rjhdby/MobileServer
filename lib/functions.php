<?php
function prepareAttrArray($app, &$raw) {
	$temp = $raw;
	foreach ( $temp as $key => $value ) {
		$new_key = str_replace ( $app . "_", "", $key );
		$raw [$new_key] = $value;
		unset ( $raw [$key] );
		// var_dump($raw);echo "<br>";
	}
}
function prepareReq(&$d, &$static) {
	$err = false;
	$mysqli = new mysqli ( MYSQL, 'apk', 'hFGgWsMt', 'apkbd' );
	$mysqli->set_charset ( "utf8" );
	$check_app = $mysqli->query ( 'SELECT COUNT(*) FROM types WHERE type="' . $d ['type'] . '";' )->fetch_row ();
	$check_owner = $mysqli->query ( 'SELECT COUNT(*) FROM users WHERE id=' . $d ['owner_id'] . ';' )->fetch_row ();
	$static_raw = $mysqli->query ( 'SELECT attribute FROM static WHERE application="' . $d ['type'] . '";' );
	while ( $row = $static_raw->fetch_row () ) {
		$static [$row [0]] = $row [0];
	}
	
	if ($check_app [0] == 0)
		$err = "ERROR. APPLICATION " . $d ['type'] . " DON'T EXISTS";
	if ($check_owner [0] == 0)
		$err = "ERROR. OWNER " . $d ['owner_id'] . " DON'T EXISTS";
	if (! isset ( $static [$d ['status']] ))
		return "ERROR. WRONG STATUS " . $d ['status'];
	
	isset ( $d ['created'] ) ? $d ['created'] = '"' . $d ['created'] . '"' : $d ['created'] = 'NOW()';
	isset ( $d ['address'] ) ? $d ['address'] = '"' . $d ['address'] . '"' : $d ['address'] = 'NULL';
	isset ( $d ['description'] ) ? $d ['description'] = '"' . $d ['description'] . '"' : $d ['description'] = 'NULL';
	if (! isset ( $d ['coord_lat'] ))
		$d ['coord_lat'] = 0;
	if (! isset ( $d ['coord_lon'] ))
		$d ['coord_lon'] = 0;
	$d ['type'] = '"' . $d ['type'] . '"';
	$d ['status'] = '"' . $d ['status'] . '"';
	$mysqli->close ();
	return $err;
}
function getAttributes(&$row, &$app, &$mysqli) {
	$in = json_decode ( $row ['attr'], true );
	$out = array ();
	foreach ( $in as $key => $value ) {
		if (is_numeric ( $value )) {
			$rs = $mysqli->query ( 'SELECT value FROM attributes WHERE entity=' . $row ['id'] . ' AND attribute=' . $value . ' LIMIT 1;' ) or die ( $mysqli->error );
			$rs = $rs->fetch_assoc ();
		} else {
			$rs = $mysqli->query ( 'SELECT value FROM static WHERE application="' . $app . '" AND attribute="' . $value . '" LIMIT 1;' ) or die ( $mysqli->error );
			$rs = $rs->fetch_assoc ();
		}
		$out [$app . "_" . $key] = $rs ['value'];
		$out [$app . "_orig_" . $key] = $value;
	}
	return $out;
}