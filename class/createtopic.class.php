<?php
require_once 'db.class.php';
require_once 'utils.class.php';
require_once 'config.class.php';
class createtopic {
    private $params;
    private $FORUM_ID = 21;
    private $FORUM_ID_STEAL = 20;
    private $FORUM_ID_TEST = 2;
    private $operator_forum_id = 221;
    private $operator_id = 27;
    private $tid;
    function __construct($params) {
        $this->params = $params;
    }
    function makeTopic() {
//        $apk = new db ();
        $utils = new utils ();
        $lon = $this->params ['lon'];
        $lat = $this->params ['lat'];
        $map_url = $utils->makeYMAPSURL ( $lon, $lat );
        $address = $this->params ['address'];
        $med = $utils->getStatic ( $this->params ['med'] );
        $type = $utils->getStatic ( $this->params ['type'] );
        //echo config::get('operator.id.app');
        if($this->params['owner_id'] == config::get('operator.id.app')){
            $this->params ['login'] = 'Оператор';
        }
        $login = $this->params ['login'];
        //var_dump($this->params);
        if ($address == '') {
            $address = 'Приблизительное местоположение';
        }
        $title = $this->params ['created'] . " " . $type . " " . $med . " " . $address;
        $post = 'Сообщил ' . $login . '<br>[url=' . $map_url . ']' . $address . '[/url]<br>' . $this->params ['descr'];
        $result = $this->APIPost($title, $post, $login);
        return $result;
    }

    private function APIPost($post_title, $post_content, $login){
        //var_dump($this->params);
        $prefix = "../";
        if(mb_strtolower($this->params['type']) == 'угон'){
            //$forum_id = $this->FORUM_ID_STEAL;
            $forum_id = config::get('forum.id.steal');
        } else {
            //$forum_id = $this->FORUM_ID;
            $forum_id = config::get('forum.id.acc');
        }

        define ( 'IN_IPB', true );
        define ( 'IPS_ROOT_PATH', $prefix.'admin/' );
        define ( 'DOC_IPS_ROOT_PATH', $prefix );
        define ( 'IPS_KERNEL_PATH', $prefix.'ips_kernel/' );

        define ( 'IPS_USE_SHUTDOWN', 0 );
        define ( 'IPS_TEMP_CACHE_PATH', DOC_IPS_ROOT_PATH );
        $PATH = '/sources/classes/post/';
        define ( 'IN_PUSH', true );
        define ( 'IN_MOBIQUO', true );
        require_once ($prefix.'initdata.php');
        require_once ($prefix.'conf_global.php');
        require_once (IPS_ROOT_PATH . 'sources/base/ipsRegistry.php');

        try {
            if($this->params['owner_id'] == config::get('operator.id.app')){
                //$member_id = $this->operator_forum_id;
                $member_id = config::get('operator.id.forum');
            } else {
                $db = new db ("godus");
                $query = 'SELECT member_id FROM members WHERE name="' . $db->real_escape_string($login) . '";';
                $member_id = implode('', $db->query($query)->fetch_row());
                $db->close();
            }
            $registry = ipsRegistry::instance ();
            $registry->init ();
            ipsRegistry::getAppClass ( 'forums' );
            $classToLoad = IPSLib::loadLibrary ( IPSLib::getAppDir ( 'forums' ) . '/sources/classes/post/classPost.php', 'classPost', 'forums' );
            $classToLoad = IPSLib::loadLibrary ( IPSLib::getAppDir ( 'forums' ) . '/sources/classes/post/classPostForms.php', 'classPostForms', 'forums' );

            $member = IPSMember::load ( $member_id );

            $post = new $classToLoad ( $registry );
            $post->setBypassPermissionCheck ( true );
            $post->setIsAjax ( false );
            $post->setPublished ( true );
            $post->setForumID ( $forum_id );
            $post->setAuthor ( $member );
            $post->setPostContentPreFormatted ( $post_content );
            $post->setTopicTitle ( $post_title );
            $post->setSettings ( array (
                'enableSignature' => 1,
                'enableEmoticons' => 1,
                'post_htmlstatus' => 0
            ) );

            $post->addTopic ();
            $topic = $post->getTopicData ();
            return $topic['tid'];
        } catch ( Exception $e ) {
            //	print $e->getMessage ();
            return 0;
        }
    }
}