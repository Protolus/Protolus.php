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

    require('./Protolus/initialize.php');
    if(Panel::isDefined($panel)){
        PageRenderer::$controllers = false;
        //PageRenderer::$core_data = array_map('filterIncomingUserData', $_REQUEST);
        PageRenderer::$core_data = $_REQUEST;
        if($HTTP_RAW_POST_DATA != ''){
            try{
                $payload = json_decode($HTTP_RAW_POST_DATA, true);
                foreach($payload as $index=>$value){
                    PageRenderer::$core_data[$index] = $value;
                }
            }catch(Exception $ex){
                echo('Error decoding post payload (this endpont expects a JSON endpoint )!');
            }
        }
        PageRenderer::render($panel, $silo);
    }else{
        WebApplication::addHeader("HTTP/1.0 404 Not Found");
        if(Panel::isDefined('error_missing_page')){
            PageRenderer::render('error_missing_page');
        }else{
            echo('Nothing to render!');
        }
    }
?>
