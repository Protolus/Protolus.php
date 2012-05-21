<?php
/*********************************************************************************//**
 *  WebApplication
 *====================================================================================
 * @author Abbey Hawk Sparrow
 * @file WebApplication.class.php
 * This serves as an abstraction layer to interact with the webserver be it apache,
 *   lighttpd or even direct execution on the commandline.
 *
 * @brief Serves an an abstraction of the application's Webserver
 *************************************************************************************/
class WebApplication{
    public static $configurationStack = array();
    public static $debug = false;
    public static $caches = array();
    public static $cacheLifetime = 2592000; //60 * 60 * 24 * 30 (1 mo)
    public static $currentUser = null;
    private static $checkedForLogin = false;
    private static $localDataLoaded = false;
    public static $configuration_directory = 'Configuration/';
    public static $device = false;
    private function __construct(){} //ugly PHP 'best practices' hack

/*********************************************************************************//**
 *  requireConfiguration loads Application configurations into an overlayed 
 *    environment. It's important to remember subsequent loads will be nested into
 *    previous ones introducing the potential for overwrites.
 *
 * @param[in]     $fileName The filename to load
 * @param[in]     $dir (optional) Directory, defaults to '/Configurations'
 * @return status 
 *************************************************************************************/
    public static function requireConfiguration($fileName, $dir = null){
        if($dir == null) $dir = WebApplication::$configuration_directory;
        try{
            if(file_exists($dir.$fileName.'.private.json')){
                $path = $dir.$fileName.'.private.json';
                Formats::register($path, 'json');
                return true;
            }
            if(file_exists($dir.$fileName.'.conf')){
                $path = $dir.$fileName.'.conf';
                Formats::register($path);
                return true;
            }
            throw new Exception('Configuration\''.$fileName.'\' does not exist!');
        }catch(Exception $ex){
            Logger::log('Conf load error:'.$ex->getMessage());
            return false;
        }
    }
/*********************************************************************************//**
 *  requireResource marks a resource for inclusion when the bundler generates 
 *    resource definitions at the end of the render process, this will also include
 *    any dependent components as a side effect.
 *
 * @param[in]     $name The resource to load
 * @return status 
 *************************************************************************************/
    public static function requireResource($name){
        ResourceBundle::req($name);
    }
/*********************************************************************************//**
 *  domain returns the applications current domain
 *
 * @return this application's domain 
 *************************************************************************************/
    public static function domain(){
        return  $_SERVER['SERVER_NAME'];
    }
/*********************************************************************************//**
 *  url returns the current URL
 *
 * @return the current URL as a string
 *************************************************************************************/
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
/*********************************************************************************//**
 *  url returns the current path
 *
 * @return the current path as a string
 *************************************************************************************/
    public static function path(){
        return($_SERVER['REQUEST_URI']);
    }
/*********************************************************************************//**
 *  url returns the current URL
 *
 * @return the current URL as a string
 *************************************************************************************/
    public static function uploadedFile($name){
        $file = new UploadedFile($name);
        if($file->exists()) return $file;
        return false;
    }
//undocumented
    public static function useLocalData(){
        if(!WebApplication::$localDataLoaded){
            //Autoloader::register_directory('./Core/Classes/Data');
            //todo: loop through all registered DBs
            //$mongo = MongoData::initialize(WebApplication::getConfiguration('mongo')); //init our MongoDB, keep a handle
            //MongoAutoincrement::$db = $mongo;
            WebApplication::$localDataLoaded = true;
        }
    }
/*********************************************************************************//**
 *  Add a cache to the applications pool of caches
 *
 * @param[in]     $cache The cache implementation
 * @param[in]     $name (optional) the cache identifier, defaults to 'memcache'
 *************************************************************************************/
    public static function addCache($cache, $name='memcache'){
        WebApplication::$caches[$name] = $cache;
    }
/*********************************************************************************//**
 *  get a key from a registered cache
 *
 * @param[in]     $key The key to get
 * @param[in]     $cache (optional) the cache identifier, defaults to 'memcache'
 *************************************************************************************/
    public static function getCache($key, $cache='memcache'){
        if($cache == 'memcache'){
            return WebApplication::$caches['memcache']->get($key);
        }
    }
/*********************************************************************************//**
 *  remove a key from a registered cache
 *
 * @param[in]     $key The key to remove
 * @param[in]     $cache (optional) the cache identifier, defaults to 'memcache'
 *************************************************************************************/
    public static function cleanCache($key, $cache='memcache'){
        if($cache == 'memcache'){
            return WebApplication::$caches['memcache']->delete($key);
        }
    }
/*********************************************************************************//**
 *  set a key in a registered cache
 *
 * @param[in]     $key The key to set
 * @param[in]     $cache (optional) the cache identifier, defaults to 'memcache'
 *************************************************************************************/
    public static function setCache($key, $value, $cache='memcache'){
        if($cache == 'memcache'){
            WebApplication::$caches['memcache']->set($key, $value, (time( ) + WebApplication::$cacheLifetime));
        }
    }
/*********************************************************************************//**
 *  handle the current user authentication state
 *
 * @return the current User object or false
 *************************************************************************************/
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
/*********************************************************************************//**
 *  get a configuration value
 *
 * @param[in]     $name The value to get
 *************************************************************************************/
    public static function getConfiguration($name){
        $value = false;
        $namePieces = explode('.', $name);
        $node = null;
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
        foreach(Formats::$registry['json'] as $confName=>$configuration){
            $node = Formats::get($confName, 'json');
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
/*********************************************************************************//**
 *  scan available configurations for a specific prefix
 *
 * @param[in]     $prefix The prefix to scan for
 *************************************************************************************/
    public static function getConfigurationsWithPrefix($prefix){
        $value = false;
        $namePieces = explode('.', $name);
        $node = null;
        $results = array();
        foreach(Formats::$registry['conf'] as $confName=>$configuration){
            foreach($configuration as $index=>$section){
                if(substr($index, 0, strlen($prefix)) == $prefix) $results[substr($index, strlen($prefix), (strlen($index) - strlen($prefix)) )] = $section;
            }
        }
        return $results;
    }
/*********************************************************************************//**
 *  set a session variable
 *
 * @param[in]     $key The key to set
 * @param[in]     $value (optional) the value to set, if not present, it is cleared
 *************************************************************************************/
    public static function setSession($name, $value=null){
        return Session::$instance->set($name, $value);
    }
/*********************************************************************************//**
 *  get a key's value from the session
 *
 * @param[in]     $key The key to get
 * @return the returned value
 *************************************************************************************/
    public static function getSession($name){
        return Session::$instance->get($name);
    }
/*********************************************************************************//**
 *  set a cookie variable
 *
 * @param[in]     $key The key to set
 * @param[in]     $value (optional) the value to set, if not present, it is cleared
 * @param[in]     $expiry (optional) the expiration in seconds, defaults to 1 month
 * @param[in]     $path (optional) the path the cookie is bound to defaults to root
 * @param[in]     $domain (optional) domain this cookie is set to, defaults to the 
 *   cookie domain set in the current configuration
 *************************************************************************************/
    public static function setCookie($name, $value, $expiry = null, $path='/', $domain=null){
        if ($expiry == null) $expiry = time()+60*60*24*30;
        if ($domain == null) $domain = WebApplication::getConfiguration('application.cookie_domain');
        return setcookie($name, $value, $expiry, $path, $domain, false, false);
    }
/*********************************************************************************//**
 *  get a cookie value
 *
 * @param[in]     $key The cookie to retrieve
 * @return the value in this cookie
 *************************************************************************************/
    public static function getCookie($name){
        return $_COOKIE[$name];
    }
/*********************************************************************************//**
 *  add a header
 *
 * @param[in]     $line the value of the added header (a string)
 *************************************************************************************/
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
/*********************************************************************************//**
 *  get a POST value
 *
 * @param[in]     $key The value to retrieve
 * @return the value
 *************************************************************************************/
    public static function getPost($key){
        if(array_key_exists($key, $_POST)){
            return $_POST[$key];
        }else{
            return false;
        }
    }
/*********************************************************************************//**
 *  get a Environment value
 *
 * @param[in]     $key The value to retrieve
 * @return the value
 *************************************************************************************/
    public static function getShell($key){
        return getenv($key);
    }
/*********************************************************************************//**
 *  get a GET value
 *
 * @param[in]     $key The value to retrieve
 * @return the value
 *************************************************************************************/
    public static function getGet($key){
        if(array_key_exists($key, $_GET)){
            return $_GET[$key];
        }else{
            return false;
        }
    }
/*********************************************************************************//**
 *  redirect the browser to a new URL
 *
 * @param[in]     $location The location to redirect to
 *************************************************************************************/
    public static function redirect($location){
        if(!PageRenderer::$dataCall){ //if this is a data call redirecting would be bad form
            if(strpos($location, '://') !== false){ //has protocol
            }else{
                if(substr($location, 0, 1) != '/') $location = '/'.$location;
            }
            WebApplication::addHeader('Location: '.$location);
        }
    }
/*********************************************************************************//**
 *  get a value from one of many sources
 *
 * @param[in]     $key The value to retrieve
 * @param[in]     $include_client_data Should we get values submitted by the user?
 * @return the value
 *************************************************************************************/
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