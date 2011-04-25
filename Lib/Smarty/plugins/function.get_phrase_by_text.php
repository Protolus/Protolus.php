<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


/**
 * Smarty {get_phrase_by_text} function plugin
 *
 * Type:     function<br>
 * Name:     get_phrase_by_text<br>
 * Date:     Sept 8th, 2008<br>
 * Purpose:  pull translation from file/database based on the text 
 * @author   Charlie Russ <cruss at reputationdefender dot com > 
 * @version  1.0
 * @param array
 * @param Smarty
 * @return string 
 */
function smarty_function_get_phrase_by_text( $params, &$smarty )
{
    global $translator;
    if(! $params['lang'] ) {
        $params['lang'] = 'en';
    }

  
    $phrase = $translator->getLanguagePhraseByText( $params['text'], $params['lang']);

    return( $phrase );
    
}


?>
