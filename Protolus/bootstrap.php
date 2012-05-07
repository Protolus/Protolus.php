<?php
// ***********************************************************************************
// * Protolus Initialize by Abbey Hawk Sparrow
// ***********************************************************************************
// * This script assumes it's being included one level up, in the project directory
// ***********************************************************************************

    /* changes:
            add access control to 'Endpoints'
            class based HTML wrapper for 3rd party renderers
            child based folder descention
            level1->children->level2
                -basic feature requirements (SSL, Region, Login, etc. )
            as a convention prefix all ajax with 'get_' or 'set_' or 'access_'?
     */
     //things to add:
     //comments in dev && debug mode to show which panel is being rendered
     // try/catch this whole thing

    //require our core loader
    require('./Protolus/Autoloader.class.php');
    //allow core classes to be loaded
    Autoloader::register_directory('./Protolus');
    Autoloader::register_directory('./Protolus/WebRendering');
    if(file_exists('./Classes')) Autoloader::register_directory('./Classes');
    Autoloader::register_directory('./Protolus/Data');

    $host = preg_replace('~\.~', '_', WebApplication::get('HTTP_HOST'));
    $machine = WebApplication::getShell('PROTOLUS_MACHINE_TYPE')?WebApplication::getShell('PROTOLUS_MACHINE_TYPE'):'production';
    $machineName = exec('hostname');
    WebApplication::setGet('hostID', $host);
    WebApplication::setGet('environmentType', $machine);
    WebApplication::setGet('machineName', $machineName);

    PageRenderer::$template_directory = 'App/Panels/';
    PageRenderer::$wrapper_directory = 'App/Panels/';
    PageRenderer::$wrapper_controller_directory = 'App/Controllers/';
    PageRenderer::$compile_directory = '/tmp/'.$machine.'/'.$host.'/_compile';
    if(!file_exists(PageRenderer::$compile_directory)) mkdir(PageRenderer::$compile_directory, 0777, true);
    PageRenderer::$cache_directory = '/tmp/'.$machine.'/'.$host.'/_cache';
    if(!file_exists(PageRenderer::$compile_directory)) mkdir(PageRenderer::$compile_directory, 0777, true);
    if(!file_exists(PageRenderer::$cache_directory)) mkdir(PageRenderer::$cache_directory, 0777, true);
    PageRenderer::$panel_directory = 'App/Panels/';
    PageRenderer::$root_directory = 'App/';

    //load the application configuration data
    //PageRenderer::registerConfigurationDirectory('Core/Configuration', array('conf'));
    $configDirectory = 'Configuration/';
    WebApplication::requireConfiguration('default', $configDirectory) or die('The Web Application defaults have gone away!');
    WebApplication::requireConfiguration($machine, $configDirectory) or die('The Web Application preferences('.$macine.'.private.json) have gone away!');
    WebApplication::requireConfiguration('panel_mappings', 'App/') or die('The Web Application has no panel map!');
    $mode = WebApplication::getConfiguration('application.mode');

    //if we're in debug mode and the debug variable is set, we set the application level debug var
    if( WebApplication::get('debug_mode') && $mode == 'debug' ) WebApplication::$debug = true;


    //the following code is totally worthless, rewrite
    //if we're debugging or in dev mode, let's turn on local logging 
    //if($logFile = WebApplication::getConfiguration('application.log')) Logger::$logFile = $logFile; 
    //else if( $mode == 'dev' || $mode == 'debug') Logger::$logFile = '../Log/log.html';
    //Logger::log('Application Mode:'.$mode);
    //Logger::$logToTempFile = true;

    ErrorHandler::startup();
    ErrorHandler::$debug = true;
    Codec::$globalKey = 'stuff';
