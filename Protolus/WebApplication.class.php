<?php
class UploadedFile{
    protected $name;
    public function  __construct($name){
        $this->name = $name;
    }

    public function exists(){
        $res = array_key_exists($this->name, $_FILES);
        //echo('[key: '.$this->name.' : '.($res?'TRUE':'FALSE').']');
        return $res;
    }

    public function saveAs($path){
        move_uploaded_file($_FILES[$this->name]['tmp_name'], $path);
        chmod($newfile , 0755);
        return file_exists($path);
    }

    public function remoteName(){
        return $_FILES[$this->name]['name'];
    }

    public function size(){
        return $_FILES[$this->name]['size'];
    }

    public function type(){
        return $_FILES[$this->name]['type'];
    }

    public function read(){
        return file_get_contents($_FILES[$this->name]['tmp_name']);
    }
}
class WebApplication{
    public static $configurationStack = array();
    public static $debug = false;
    public static $caches = array();
    public static $cacheLifetime = 2592000; //60 * 60 * 24 * 30 (1 mo)
    public static $currentUser = null;
    private static $checkedForLogin = false;
    private static $localDataLoaded = false;
    public static $configuration_directory = 'Configuration/';
    private function __construct(){} //ugly PHP 'best practices' hack

    public static function requireConfiguration($fileName, $dir = null){
        if($dir == null) $dir = WebApplication::$configuration_directory;
        try{
            $path = $dir.$fileName.'.conf';
            //PageRenderer::configuration($fileName); //we're going to skip the lazy-load
            Formats::register($dir.$fileName.'.conf');
            return true;
        }catch(Exception $ex){
            echo('Conf load error:'.$ex->getMessage());
            return false;
        }
    }
    
    public static function url(){
        return 
            ($_SERVER['HTTPS'] == 'on'?'https':'http').
            '://'.
            (
                $_SERVER['SERVER_PORT'] != '80' ?
                $_SERVER['SERVER_NAME'].':'.$_SERVER['SERVER_PORT'].$_SERVER['REQUEST_URI']:
                $_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']
            );
    }

    public static function uploadedFile($name){
        $file = new UploadedFile($name);
        if($file->exists()) return $file;
        return false;
    }

    public static function useLocalData(){
        if(!WebApplication::$localDataLoaded){
            //Autoloader::register_directory('./Core/Classes/Data');
            //todo: loop through all registered DBs
            //$mongo = MongoData::initialize(WebApplication::getConfiguration('mongo')); //init our MongoDB, keep a handle
            //MongoAutoincrement::$db = $mongo;
            WebApplication::$localDataLoaded = true;
        }
    }
    
    public static function addCache($cache, $name='memcache'){
        WebApplication::$caches[$name] = $cache;
    }
    
    public static function getCache($key, $cache='memcache'){
        if($cache == 'memcache'){
            return WebApplication::$caches['memcache']->get($key);
        }
    }
    
    public static function cleanCache($key, $cache='memcache'){
        if($cache == 'memcache'){
            return WebApplication::$caches['memcache']->delete($key);
        }
    }
    
    public static function setCache($key, $value, $cache='memcache'){
        if($cache == 'memcache'){
            WebApplication::$caches['memcache']->set($key, $value, (time( ) + WebApplication::$cacheLifetime));
        }
    }

    public static function loggedIn() {
        if(Session::$instance != null && !WebApplication::$checkedForLogin){ //let's hit up the session and load the user
            $id = WebApplication::getSession('user_id');
            if(!empty($id)){
                try{
                    WebApplication::$currentUser = new User($id);
                }catch(Exception $ex){
                    Logger::log('CRITICAL ERROR: The user_id which appears in the session('.$id.') cannot be found in the DB using:'.MongoData::$lastQuery);
                }
            } else {
                WebApplication::$currentUser = null;
            }
            WebApplication::$checkedForLogin = true;
        }
        if(WebApplication::$currentUser == null) return false;
        else return WebApplication::$currentUser;
    }

    public static function getConfiguration($name){
        $value = false;
        $namePieces = explode('.', $name);
        $node = null;
        //echo('<h2>'.$name.'</h2><textarea>'.print_r(Formats::$registry['conf'], true).'</textarea>');
        foreach(Formats::$registry['conf'] as $confName=>$configuration){
            $node = Formats::get($confName, 'conf');
            $in = false;
            foreach($namePieces as $piece){
                if(is_array($node) && array_key_exists($piece, $node)){
                    $node = &$node[$piece];
                    $in = true;
                }else{
                    continue;
                }
            }
            if($in) $value = $node;
        }
        return $value;
    }

    public static function getConfigurationsWithPrefix($prefix){
        $value = false;
        $namePieces = explode('.', $name);
        $node = null;
        //echo('<h2>'.$name.'</h2><textarea>'.print_r(Formats::$registry['conf'], true).'</textarea>');
        $results = array();
        foreach(Formats::$registry['conf'] as $confName=>$configuration){
            foreach($configuration as $index=>$section){
                if(substr($index, 0, strlen($prefix)) == $prefix) $results[substr($index, strlen($prefix), (strlen($index) - strlen($prefix)) )] = $section;
                //echo($index.':section<br/>');
                //print_r($section);
            }
        }
        return $results;
    }

    public static function setSession($name, $value=null){
        return Session::$instance->set($name, $value);
    }

    public static function getSession($name){
        return Session::$instance->get($name);
    }

    public static function setCookie($name, $value, $expiry = null, $path='/'){
        if ($expiry == null) $expiry = time()+60*60*24*30;
        return setcookie($name, $value, $expiry, $path, "", false, false);
    }

    public static function getCookie($name){
        return $_COOKIE[$name];
    }

    public static function addHeader($line){
        header($line);
    }

    //Hacky override functions
    public static function setPost($key, $value){
        $_POST[$key] = $value;
        $_REQUEST[$key] = $value; //todo: handle value collisions
    }
    public static function setGet($key, $value){
        $_GET[$key] = $value;
        $_REQUEST[$key] = $value; //todo: handle value collisions
    }

    public static function getPost($key){
        if(array_key_exists($key, $_POST)){
            return $_POST[$key];
        }else{
            return false;
        }
    }

    public static function getGet($key){
        if(array_key_exists($key, $_GET)){
            return $_GET[$key];
        }else{
            return false;
        }
    }
    
    public static function redirect($location){
        echo($location."<br/>\n");
        if(strpos($location, '://') !== false){ //has protocol
        }else{
            if(substr($location, 0, 1) != '/') $location = '/'.$location;
        }
        WebApplication::addHeader('Location: '.$location);
    }

    public static function get($key, $include_client_data = true){
        if($include_client_data){ //this isn't secure data because the user can pass you whatever
            if(array_key_exists($key, $_REQUEST)){
                return $_REQUEST[$key];
            }else{
                if(isset($_COOKIE) && array_key_exists($key, $_COOKIE)){ //todo: replace with static reference
                    return $_COOKIE[$key];
                }else{
                    if(isset($_SESSION) && array_key_exists($key, $_SESSION)){ //todo: replace with static reference
                        return $_SESSION[$key];
                    }else{
                        if(array_key_exists($key, $_SERVER)){
                            return $_SERVER[$key];
                        }else{
                            if(array_key_exists($key, $_ENV)){
                                return $_ENV[$key];
                            }else{
                                return false;
                            }
                        }
                    }
                }
            }
        }else{ //this is all server-side data so it's much more safe
            if(isset($_SESSION) && array_key_exists($key, $_SESSION)){
                return $_SESSION[$key];
            }else{
                if(array_key_exists($key, $_SERVER)){
                    return $_SERVER[$key];
                }else{
                    if(array_key_exists($key, $_ENV)){
                        return $_ENV[$key];
                    }else{
                        return false;
                    }
                }
            }
        }
    }
}

?>
