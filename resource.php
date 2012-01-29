<?php
    /******************************************************
     * Protolus : Render + Data
     ******************************************************
     * @created 09/09/09
     * @author  Abbey Hawk Sparrow
     * @name root index
     ******************************************************
     * This script provides an entry point for all URLS to
     * map to a panel, redirection, or to location failure
     * It also performs all needed App initialization
     * todo: replace this header block with YAML
     ******************************************************/

    require('./Protolus/bootstrap.php');
    $resources = explode('-', WebApplication::getGet('resources'));
    //echo('minify: '.WebApplication::getGet('minify')); exit();
    if(WebApplication::getGet('minify')){
       $minString = '.min';
    }
    $v = WebApplication::getGet('version');
    $fileName = '/tmp/ResourceCache/'.WebApplication::getGet('resources').$minString.'.'.WebApplication::getGet('type');
    $componentConfName = '/tmp/ResourceCache/'.WebApplication::getGet('resources').$minString.'.'.WebApplication::getGet('type');
    if(!file_exists(dirname($fileName))) mkdir(dirname($fileName));
    $mostRecentTime = 0;
    foreach($resources as $resourceName){
        $time = filemtime('Resources/'.$resourceName.'/component.conf');
        if($mostRecentTime < $time) $mostRecentTime = $time;
    }
    $rootDir = getcwd();
    if(file_exists($fileName) && filemtime($fileName) > $mostRecentTime){
        echo(file_get_contents($fileName));
        exit();
    }else{
        chdir('min/lib');
        require('min/utils.php');
        require('Minify.php');
        require('JSMin.php');
        require('Minify/CSS.php');
        require_once('Minify/CSS/Compressor.php');
        //Minify_CSS::minify('');
        //chdir('../..');
        $items = array();
        $code = '';
        $allItems = array();
        foreach($resources as $resourceName){
            chdir('../..');
            $resource = new ResourceBundle($resourceName, Formats::loadFile('Resources/'.$resourceName.'/component.conf', 'conf'));
            chdir('min/lib');
            $items = $resource->resourceItems(true);
            foreach($items as $index=>$item){
                switch(strtolower(WebApplication::getGet('type'))){
                    case 'js' :
                        if(strtolower(substr($item, -3)) != '.js') unset($items[$index]);
                        break;
                    case 'css' :
                        //echo('Style: '.strtolower(substr($item, -4))); exit();
                        if(strtolower(substr($item, -4)) != '.css') unset($items[$index]);
                        break;
                }
            }
            $allItems = array_merge($allItems, $items);
        }
        //file_put_contents($fileName, $code);
    }
    //chdir('../..');
    //echo($rootDir); exit();
    $mapper = function($string) { global $rootDir; return $rootDir.$string; };
    $allItems = array_map($mapper, $allItems);
    //print_r($resources); print_r($allItems); exit();
    if(count($allItems) == 0){
        echo('');
        exit();
    }
    //*
    switch(strtolower(WebApplication::getGet('type'))){
        case 'js' :
            WebApplication::addHeader("Content-type: text/javascript");
            break;
        case 'css' :
            WebApplication::addHeader("Content-type: text/css");
            break;
    }//*/
    if(strtolower(WebApplication::getGet('minify')) == 'true'){
        Minify::setCache('/tmp/ResourceCache');
        Minify::serve('Files', array(
            'files'  => $allItems,
            'maxAge' => 86400
        ));
    }else{
        $res = '';
        foreach($allItems as $item){
            $res .= file_get_contents($item).';';
        }
        echo($res);
    }
    //echo($code);*/
    exit();
?>
