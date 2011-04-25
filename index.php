<?php
    /******************************************************
     * Protolus : Render + Logic
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

    //require our core loader
    require('./Protolus/initialize.php');
    if(Panel::isDefined($panel)){
        PageRenderer::render($panel, $silo);
        //if(isset($silo)) $pan = new Panel($panel, $silo);
        //else $pan = new Panel($panel);
        //echo($pan->render());
    }else{
        WebApplication::addHeader("HTTP/1.0 404 Not Found");
        if(Panel::isDefined('error_missing_page')){
            PageRenderer::render('error_missing_page');
        }else{
            echo('<html><head><title>404 Error</title></head><body><center><h1>404 Error!</h1><p>We tried really hard, but we can\'t locate the page you\'re looking for.</p><center></body></html>');
        }
    }
?>
