<?php
ini_set ( 'display_errors', 'On' );
ini_set ( 'display_startup_errors', 'On' );
header('Content-Type: text/html; charset=utf-8');
if (isset ( $_GET ['calledMethod'] )) {
    $_POST = $_GET;
}

switch ($_POST ['calledMethod']) {
    case 'getlist' :
        require_once 'class/getlist.class.php';
        $obj = new getlist ( $_POST );
        $result = $obj->get_list ();
        break;
    case 'auth' :
        require_once 'class/auth.class.php';
        $obj = new auth ();
        $result = $obj->login ( $_POST );
        // $result = json_encode ( $obj->fakeLogin ( $_POST ) );
        break;
    case 'createAcc' :
        require_once 'class/createacc.class.php';
        $obj = new createacc ( $_POST );
        $result = array (
            "result" => $obj->create_acc ()
        );
        break;
    case 'changeState' :
        require_once 'class/changestate.class.php';
        $obj = new changeState ( $_POST );
        $result = array (
            "result" => $obj->change_state ()
        );
        break;
    case 'message' :
        require_once 'class/message.class.php';
        $obj = new message ( $_POST );
        $result = array (
            "result" => $obj->create_message ()
        );
        break;
    case 'deltaCreateAcc' :
        require_once 'class/delta.class.php';
        $obj = new delta ( $_POST );
        $result = array (
            "result" => $obj->create_acc ()
        );
        break;
    case 'registerGCM' :
        require_once 'class/gcm.class.php';
        $gcm = new gcm ( $_POST );
        $result = array (
            "result" => $gcm->registration ()
        );
        break;
    case 'onway' :
        require_once 'class/onway.class.php';
        $onway = new onway ( $_POST );
        $result = array (
            "result" => $onway->onway ()
        );
        break;
    case 'inplace' :
        require_once 'class/onway.class.php';
        $onway = new onway ( $_POST );
        $result = array (
            "result" => $onway->inplace ()
        );
        break;
    case 'leave' :
        require_once 'class/onway.class.php';
        $onway = new onway ( $_POST );
        $result = array (
            "result" => $onway->leave ()
        );
        break;
    case 'getOnway' :
        require_once 'class/onway.class.php';
        $onway = new onway ( $_POST );
        $result = array (
            "onway" => $onway->getVolunteers ()
        );
        break;
    case 'ban' :
        require_once 'class/moderator.class.php';
        $moderator = new moderator ( $_POST );
        $result = array (
            "ban" => $moderator->ban ()
        );
        break;
    default :
        $result = '{"error":"wrong_method"}';
}
print_r ( json_encode ( $result ) );
?>