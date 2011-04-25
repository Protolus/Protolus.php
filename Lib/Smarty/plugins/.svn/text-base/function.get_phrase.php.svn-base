<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


/**
 * Smarty {get_phrase} function plugin
 *
 * Type:     function<br>
 * Name:     get_phrase<br>
 * Date:     August 25, 2008<br>
 * Purpose:  pull translation from file/databawse
 * @author   Stefan Antonowicz <stefan at reputationdefender dot com>
 * @version  1.0
 * @param array
 * @param Smarty
 * @return string 
 */
function smarty_function_get_phrase( $params, &$smarty )
{
    global $translator;
    if(! $params['lang'] ) {
        $params['lang'] = 'en';
    }
    
    if( $params['trans'] ) {
        $translation_array = $params['trans'];
    } else {
        $translation_array = array( );
    }
    
    $phrase = $translator->getLanguagePhraseByID( $params['uuid'], $params['lang'], $translation_array );
    
    if( $params['format'] ) {
        return( nl2br( $phrase ) );
    } 
    
    return( $phrase );
    
}

/* vim: set expandtab: */

?>
