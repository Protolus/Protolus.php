<?php
    class Session{
        public static $instance = null;
        public static $cleanRemoteIP = true;
        public static $lifetime = 2592000; //60 * 60 * 24 * 30 (1 mo)
        public static $sessionID = 'session_id';
        public static $dataMode = 'default';
        public static $appMode = 'php'; //php or custom
        public static $link = null;
        public static $internalValues = array();
        protected $session_id = '';
        
        public function __construct($dblink){
            //todo: detect link type
            Session::$link = $dblink;
            Logger::log('Session['.Session::$appMode.':'.Session::$dataMode.']');
            if(Session::$appMode == 'php' && Session::$dataMode == 'default'){
                session_start();
            }else{
                if(Session::$instance == null){
                    switch(Session::$appMode){
                        case 'php':
                            Session::$sessionID = session_name();
                            $this->session_id = WebApplication::getCookie(Session::$sessionID);
                            session_set_save_handler(
                                array( &$this, 'open' ),
                                array( &$this, 'close' ),
                                array( &$this, 'read' ),
                                array( &$this, 'write' ),
                                array( &$this, 'destroy' ),
                                array( &$this, 'gc' )
                            );
                            register_shutdown_function( 'session_write_close' );
                            session_start();
                            break;
                        case 'custom':
                            $this->session_id = WebApplication::getCookie(Session::$sessionID);
                            $data = $this->read($this->session_id);
                            if(!empty($this->session_id) && $data !== true && is_array($data)){
                                //echo('read something!'.print_r($data, true));
                                $_SESSION = $data;
                                $this->internalValues = $data;
                            }else{
                                $this->session_id = Data::generateUUID();
                                Logger::log('Initialized new Session ID['.$this->session_id.']');
                                //echo('Data:'.print_r($data, true));
                                WebApplication::setCookie(Session::$sessionID, $this->session_id);
                            }
                            register_shutdown_function( array($this, 'shutdown') );
                            break;
                    }
                    Session::$instance = $this;
                }else{
                    throw new Exception('Session already created, only one session instance at a time!');
                }
            }
        }
        
        public static function erase(){
            switch(Session::$appMode){
                    case 'php':
                        session_destroy();
                        break;
                    case 'custom':
                        //todo: implement
                }

        }   
        
        public function shutdown(){
            Logger::log('Saving Session');
            $this->write($this->session_id, $this->internalValues);
            Logger::log('Saved Session['.$this->session_id.']:'.print_r($this->internalValues, true));
        }

        public function open( $path, $name ) {
            return true;
        }

        public function close( ) {
            return true;
        }

        public function set($name, $value){
            switch(Session::$appMode){
                case 'php':
                    if($value === null) unset($_SESSION[$name]);
                    else $_SESSION[$name] = $value;
                    break;
                case 'custom':
                    if($value === null) unset($this->internalValues[$name]);
                    else $this->internalValues[$name] = $value;
                    break;
            }
        }
        
        public function get($name){
            switch(Session::$appMode){
                case 'php':
                    if(array_key_exists($name, $_SESSION)) return $_SESSION[$name];
                    else return false;
                    break;
                case 'custom':
                    if(array_key_exists($name, $this->internalValues)) return $this->internalValues[$name];
                    else return false;
                    break;
            }
        }

        public function read( $sid ) { //we do a little special juggling to decode the data from JSON
            Logger::log('Loading session: '.$sid);
            switch(Session::$dataMode){
                case 'mysql':
                    $sql = 'SELECT * FROM session_table WHERE id = \''.$sid.'\' AND session_time > ' . time( );
                    $results = mysql_query($sql,  Session::$link);
                    if( sizeof($results) == 1 ) return serialize(json_decode($results[0]['session_data']));
                    return true;
                    break;
                case 'mysqli':
                    $sql = 'SELECT * FROM session_table WHERE id = \''.$sid.'\' AND session_time > ' . time( );
                    $results =  Session::$link->query($sql);
                    if( sizeof($results) == 1 ) return serialize(json_decode($results[0]['session_data']));
                    return true;
                    break;
                case 'mongo':
                    $collection = Session::$link->session_table;
                    $cursor = $collection->find(array(
                        'session_id' => $sid,
                        /*'session_time' =>array(
                            '$gt' => (time() - Session::$lifetime)
                        )*/
                    ));
                    if($cursor->hasNext()) return $cursor->getNext();
                    else return true;
                    break;
                case 'memcache':
                    return Session::$link->get($sid);
                    break;
            }
        }

        public function write( $sid, $data ) { //we do a little special juggling to encode the data as JSON
            Logger::log('Saving session: '.$sid);
            Logger::log(print_r($data, true));
            //quick clean of ip so no one can poison the db via GLOBAL overload
            preg_match( '/[\d]{1,3}\.[\d]{1,3}\.[\d]{1,3}\.[\d]{1,3}/', $_SERVER['REMOTE_ADDR'], $match );
            $ip = sizeof( $match ) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
            
            $expiry = time( ) + Session::$lifetime ;
            switch(Session::$dataMode){
                case 'mysql':
                    if(is_array($data)) $data = json_encode($data);
                    $data = mysql_real_escape_string($data);
                    $sql = "INSERT INTO session_table (id, session_data, session_time, session_ip )
                            VALUES ('$sid', '$data', $expiry, '$ip') 
                            ON DUPLICATE KEY UPDATE session_data='$data', session_time = '$expiry'";
                    $results = mysql_query($sql,  Session::$link);
                    break;
                case 'mysqli':
                    if(is_array($data)) $data = json_encode($data);
                    $data =  Session::$link->real_escape_string($data);
                    $sql = "INSERT INTO session_table (id, session_data, session_time, session_ip)
                            VALUES ('$sid', '$data', $expiry, '$ip') 
                            ON DUPLICATE KEY UPDATE session_data='$data', session_time = '$expiry'";
                    $results =  Session::$link->query($sql);
                    break;
                case 'mongo':
                    $update = $data;
                    unset($update['session_id']); //don't reset the key
                    unset($update['_id']);
                    $collection = Session::$link->session_table;
                    $update['session_time'] = time();
                    //echo('update['.$this->session_id.']: '.print_r($update, true));
                    $res = $collection->update(
                        array('session_id' => $sid),
                        array('$set' => $update),
                        array('upsert' => true)
                    );
                    //echo('COL['.print_r(Session::$link->lastError(), true).']('.Session::$link->session_table.'):'); print_r($collection);
                    /*echo('$res = $collection->update(
                        array(\'session_id\' => '.print_r($this->session_id, true).'),
                        array(\'$set\' => '.print_r($update, true).'),
                        array(\'upsert\' => true)
                    );'); //*/
                    break;
                case 'memcache':
                    if(is_array($data)) $data = json_encode($data);
                    //echo('inserting '.$sid.' into '.$data);
                    Session::$link->set($sid, $data, $expiry);
                    break;
            }
        }

        public function destroy( $sid ) {
            switch(Session::$dataMode){
                case 'mysql':
                    $sql = "DELETE FROM session_table WHERE id = '$sid'";
                    $results = mysql_query($sql,  Session::$link);
                    //if( isset( $_COOKIE[session_name()] ) ) { //todo: make this reference WebApplication
                      //  setcookie( session_name( ), '', time() - 42000, '/' );
                        WebApplication::setCookie(Session::$sessionID, '', time() - 42000);
                    //}
                    break;
                case 'mysqli':
                    $sql = "DELETE FROM session_table WHERE id = '$sid'";
                    Session::$link->query($sql);
                    //if( isset( $_COOKIE[session_name()] ) ) { //todo: make this reference WebApplication
                        WebApplication::setCookie(Session::$sessionID, '', time() - 42000);
                    //}
                    break;
                case 'mongo':
                    break;
                case 'memcache':
                    Session::$link->delete($sid);
                    break;
            }
        }

        public function gc( ) {
            switch(Session::$dataMode){
                case 'mysql':
                    $sql = 'DELETE FROM session_table WHERE session_time < \''.(time( ) - Session::$lifetime).'\'';
                    $results = mysql_query($sql,  Session::$link);
                    return;
                    break;
                case 'mysqli':
                    $sql = 'DELETE FROM session_table WHERE session_time < \''.(time( ) - Session::$lifetime).'\'';
                    $results =  Session::$link->query($sql);
                    return;
                    break;
                case 'mongo':
                    break;
                case 'memcache': //todo: implement me!
            }
        }
        
        public function initializeResources( ) {
            $sql = 'CREATE TABLE `session_table` ( `id` varchar(32) NOT NULL, `session_data` longtext NOT NULL, `session_time` int(11) NOT NULL DEFAULT \'0\', `session_ip` varchar(32) NOT NULL, `session_status` enum(\'active\',\'passive\') NOT NULL, PRIMARY KEY (`id`) ) ENGINE=MyISAM DEFAULT CHARSET=latin1;';
            switch(Session::$dataMode){
                case 'mysql':
                    $results = mysql_query($sql,  Session::$link);
                    return;
                    break;
                case 'mysqli':
                    $results =  Session::$link->query($sql);
                    return;
                    break;
                case 'mongo':
                    break;
                case 'memcache': //todo: implement me!
            }
        }
    }
?>