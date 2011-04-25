<?php
    /******************************************************
     * Protolus : Logic
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
        echo PageRenderer::data($panel, $silo);
    }else{
        WebApplication::addHeader("HTTP/1.0 404 Not Found");
        echo(json_encode(array(
            'error'=>array(
                'code'=>'-1',
                'message'=>'No data for this panel.'
            )
        )));
    }
?>
