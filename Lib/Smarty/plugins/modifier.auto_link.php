<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


/**
 * Smarty auto_link modifier plugin
 *
 * Type:     modifier<br>
 * Name:     auto_link<br>
 * Purpose:  automatically link http:// and https:// urls
 * @author   Stefan Antonowicz <stefan@reputationdefender.com>
 * @param string
 * @return string
 */
function un_url( $matches ) {
    $url = html_entity_decode( $matches[1] );
    str_replace( ' ', '_', $url );
    return( "<a href=\"$url\">$url</a>" );
}

function smarty_modifier_auto_link( $string )
{
    return( preg_replace_callback( '/(http:\/\/[^\s<]+)/', "un_url", $string ) );
}


?>