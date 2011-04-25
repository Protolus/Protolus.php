<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


/**
 * Smarty {is_secure} function plugin
 *
 * Type:     modifier<br>
 * Name:     is_secure<br>
 * Date:     March 13, 2009<br>
 * Purpose:  check to see if we're on a secure page
 * @author   Stefan Antonowicz <stefan@reputationdefender.com>
 * @version  1.0
 * @param array
 * @param Smarty
 * @return boolean
 */
 
/*** not a modifier, but need it to act like one so
***  we can check against the boolean value
**/
function smarty_modifier_is_secure( )
{
    if( $_SERVER['https'] == 'on' && $_SERVER['SERVER_PORT'] == 443 ) {
        return( TRUE );
    }
    
    return( FALSE );
}
/* vim: set expandtab: */

?>
