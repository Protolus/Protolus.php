<?php
    class Session{
        public static $instance = null;
        public static $cleanRemoteIP = true;
        public static $lifetime = 2592000; //60 * 60 * 24 * 30 (1 mo)
        public static $sessionID = 'session_id';
        public static $dataMode = 'mysql';
        public static $appMode = 'php'; //php or custom
        public static $link = null;
        public static $internalValues = array();
        protected $session_id = '';
        
        public function __construct($dblink){
            //todo: detect link type
            Session::$link = $dblink;
            if(Session::$instance == null){
                Logger::log(Session::$appMode.' '.Session::$dataMode.' session handler started.');
                switch(Session::$appMode){
                    case 'php':
                        Session::$sessionID = session_name();
                        session_set_save_handler(
                            array( &$this, 'open' ),
                            array( &$this, 'close' ),
                            array( &$this, 'read' ),
                            array( &$this, 'write' ),
                            array( &$this, 'destroy' ),
                            array( &$this, 'gc' )
                        );
                        register_shutdown_function( 'session_write_close' );
                        session_set_cookie_params( (time() + Session::$lifetime), '/', WebApplication::getConfiguration('application.cookie_domain'));
                        session_start();
                        break;
                    case 'api':
                        Session::$link = new APISession();
                        // drop through to custom handler
                    case 'custom':
                        $this->session_id = WebApplication::getCookie(Session::$sessionID);
                        $data = $this->read($this->session_id);
                        //echo('['.$this->session_id.':'.print_r($data, true).']');
                        if(!empty($this->session_id) && $data !== true && is_array($data)){
                            $this->internalValues = $data;
                        }else{
                            $this->session_id = Data::generateUUID();
                            Logger::log('Initialized new Session ID['.$this->session_id.']');
                            WebApplication::setCookie(Session::$sessionID, $this->session_id);
                            //echo('['.Session::$sessionID.' : '.$this->session_id.']');
                        }
                        if(Session::$appMode == 'api' && !Session::$link->get(Session::$sessionID)){
                            Session::$link->set(Session::$sessionID, $this->session_id);
                            //Session::$link->save(); //make sure session sticks
                        }
                        //print_r(array(Session::$sessionID, $this->session_id, Session::$link->data));
                        register_shutdown_function( array($this, 'shutdown') );
                        break;
                }
                Session::$instance = $this;
                Session::set('last_access', date ("Y-m-d H:i:s", time()));
            }else{
                throw new Exception('Session already created, only one session instance at a time!');
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
                case 'api':
                    $data = json_decode(Session::$link->get('data'), true);
                    $data[$name] = $value;
                    Session::$link->set('data', json_encode($data));
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
                case 'api':
                    $data = json_decode(Session::$link->get('data'));
                    return $data[$name];
                    break;
            }
        }

        public function read( $sid ) { //we do a little special juggling to decode the data from JSON
            Logger::log('Loading session['.Session::$dataMode.']: '.$sid);
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
                case 'api':
                    try{
                    Session::$link->load($sid);
                    }catch(Exception $ex){
                        Logger::log('EX: '.$ex->getMessage());
                        echo('[ERROR]');
                    }
                    Logger::log('Loaded API session: '.print_r(Session::$link->data, true));
                    return Session::$link->data;
                    break;
            }
        }

        public function write( $sid, $data ) { //we do a little special juggling to encode the data as JSON
            Logger::log('Saving session['.$sid.']:'.json_encode($data));
            //quick clean of ip so no one can poison the db via GLOBAL overload
            preg_match( '/[\d]{1,3}\.[\d]{1,3}\.[\d]{1,3}\.[\d]{1,3}/', $_SERVER['REMOTE_ADDR'], $match );
            $ip = sizeof( $match ) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
            
            $expiry = time( ) + Session::$lifetime ;
            switch(Session::$dataMode){
                case 'mysql':
                    if(is_array($data)) $data = json_encode($data);
                    $data = mysql_real_escape_string($data);
                    $sql = "INSERT INTO session_table (id, session_data, session_time, session_ip, creation_time, last_update_time )
                            VALUES ('$sid', '$data', $expiry, '$ip', now(), now() ) 
                            ON DUPLICATE KEY UPDATE session_data='$data', session_time = '$expiry', last_update_time = now()";
                    $results = mysql_query($sql,  Session::$link);
                    break;
                case 'mysqli':
                    if(is_array($data)) $data = json_encode($data);
                    $data =  Session::$link->real_escape_string($data);
                    $sql = "INSERT INTO session_table (id, session_data, session_time, session_ip, creation_time, last_update_time )
                            VALUES ('$sid', '$data', $expiry, '$ip', now(), now() ) 
                            ON DUPLICATE KEY UPDATE session_data='$data', session_time = '$expiry', last_update_time = now()";
                    $results =  Session::$link->query($sql);
                    break;
                case 'mongo':
                    $update = $data;
                    unset($update['session_id']); //don't reset the key
                    unset($update['_id']);
                    $collection = Session::$link->session_table;
                    $update['session_time'] = time();
                    $res = $collection->update(
                        array('session_id' => $sid),
                        array('$set' => $update),
                        array('upsert' => true)
                    );
                    break;
                case 'memcache':
                    if(is_array($data)) $data = json_encode($data);
                    Session::$link->set($sid, $data, $expiry);
                    break;
                case 'api':
                    Session::$link->save();
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
    }