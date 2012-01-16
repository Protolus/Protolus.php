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
$maintenance = false;
if($maintenance){
    echo('<html><head><title>Down for Maintenance</title></head><body><center><h1>Down for Maintenance</h1><center></body></html>');
    exit();
}
    ini_set('zlib.output_compression', 'On'); //setup compression for lighttpd
    //require our core loader
    require('./Protolus/initialize.php');
    if(Panel::isDefined($panel)){
        //a switch for the JS harness
         if(WebApplication::getGet('dynamic') == 'true'){
         //if(WebApplication::get('dynamic')){
            //if(WebApplication::getGet('dynamic') == 'false'){
                //WebApplication::setCookie('dynamic');
            //}else{
                //if(WebApplication::getGet('dynamic') == 'true') WebApplication::setCookie('dynamic', 'true');\
                PageRenderer::setWrapper('global');
                PageRenderer::render('dynamic');
                exit();
            //}
         }
        PageRenderer::render($panel, $silo);
    }else{
        WebApplication::addHeader("HTTP/1.0 404 Not Found");
        WebApplication::addHeader("Status: 404 Not Found");
        if(Panel::isDefined('error_missing_page')){
            PageRenderer::render('error_missing_page');
        }else{
            echo('<html><head><title>404 Error</title></head><body><center><h1>404 Error!</h1><p>We tried really hard, but we can\'t locate the page you\'re looking for('.$panel.').</p><center></body></html>');
        }
         //*/
    }
?>
