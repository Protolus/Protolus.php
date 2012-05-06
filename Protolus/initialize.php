<?php
/*********************************************************************************//**
 *  Protolus Initialize
 *====================================================================================
 * @author Abbey Hawk Sparrow
 *This script assumes it's being included one level up, in the project directory
 *************************************************************************************/
     //things to add:
     //comments in dev && debug mode to show which panel is being rendered
     // try/catch this whole thing

    //require our core loader
    require('./Protolus/bootstrap.php');
    if(file_exists(dirname(__FILE__).'/../init.php')){
        require(dirname(__FILE__).'/../init.php'); //this is our local global environment hook
    }

    // hardcoded memcache interface
    /*Session::$dataMode = 'memcache';
    $mc = new Memcached();
    if (!count($mc->getServerList())) {
        $mc->addServers(array(
            array(WebApplication::getConfiguration('memcache.host'),11211),
        ));
    }
    new Session($mc);
    WebApplication::addCache($mc);*/
    new Session(); //api based session

    //Initialize our datasources (We have no local data)
    //*
    $databases = WebApplication::getConfigurationsWithPrefix('DB:');
    $sessionInitialized = false;
    foreach($databases as $dbID => $database){
        //todo: try/catch to trap errors
        try{
            $database['name'] = $dbID;
            switch(strtolower(trim($database['type']))){
                case 'mongo':
                    $lastDB = MongoData::initialize($database);
                    $auto = strtolower($database['autoincrement']);
                    if($auto == 't' || $auto == 'true'){
                        MongoAutoincrement::$db = $lastDB;
                    }
                    break;
                case 'mysql': //MySQL is the default
                default:
                    $lastDB = MySQLData::initialize($database);
            }
            $sess = strtolower($database['session']);
            //create a session from this DB if the conf calls for it, but only do it once
            if( !$sessionInitialized && ($sess == 't' || $sess == 'true') ){
                Session::$dataMode = $database['type'];
                Session::$appMode = 'custom'; // 'custom' or 'php'
                new Session($lastDB);
                $sessionInitialized = true;
            }
        }catch(Exception $ex){
            Logger::log('There was an error initializing Datasource('.$dbID.': '.$ex->getMessage().')');
        }
    }
    //*/
    //if the app is in debug mode, and has the 'session_debug_enable' set to 'true', then set debug in the session
    if  (   $mode == 'debug' &&
            WebApplication::getGet('debug_mode') &&
            strtolower(WebApplication::getConfiguration('session_debug_enable')) == 'true'
        ){
        if(strtolower(WebApplication::getGet('debug_mode')) == 'false') WebApplication::setSession('debug_mode', false);
        else WebApplication::setSession('debug_mode', true);
    }
    
    if($timezone = strtolower(WebApplication::getConfiguration('application.timezone'))){
        date_default_timezone_set($timezone);
    }
    
    if(!function_exists('defaultPanel')){
        function defaultPanel(){
            return 'index';
        }
    }

    //todo: detect locale: set language, default wrapper based on region + original_referral

    // set the default wrapper (to be rendered if we don't encounter a custom wrapper)
    PageRenderer::setWrapper('default');
    // Now we set the panel passed in
    if (!$panel = WebApplication::getGet('panel')) $panel = defaultPanel();
    $incomingPanel = $panel;
    // if there is a script extension on the panel, ignore it
    if($pos = strpos($panel, '.php')) $panel = substr($panel, 0, $pos);
    if(substr($panel, -1) == '/'){ //pull off a trailing slash if there is one
        $panel = substr($panel, 0, strlen($panel)-1);
    }
    //load combined silo mappings, and substitute the panel, if mapped
    //todo: implement persistent cache for this
    if($routing_info = Panel::route($panel)){
        Logger::log('Substituted path '.$panel.' with '.$routing_info['panel']);
        $panel = $routing_info['panel'];
        $silo = $routing_info['silo'];
        if(strpos($panel, '?')){ //we have some additional args we need to strip off
            $parts = explode('?', $panel);
            $panel = $parts[0];
            $parameters = explode('&', $parts[1]);
            foreach($parameters as $line){
                $words = explode('=', $line);
                $key = $words[0];
                $value = urldecode($words[1]);
                WebApplication::setGet($key, $value);
            }
        }
    }
    if(file_exists(dirname(__FILE__).'/../setup.php')){
        require(dirname(__FILE__).'/../setup.php'); //this is our local global environment hook
    }
    if(!function_exists('defaultPanel')){
        function defaultPanel(){
            return 'index';
        }
    }
