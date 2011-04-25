<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


/**
 * Smarty {overdue_action} function plugin
 *
 * Type:     modifier<br>
 * Name:     overdue_action<br>
 * Date:     July 16, 2008<br>
 * Purpose:  check if a date is overdue
 * @author   Stefan Antonowicz <stefan@reputationdefender.com>
 * @version  1.0
 * @param array
 * @param Smarty
 * @return boolean
 */
 
/*** not a modifier, but need it to act like one so
***  we can check against the boolean value
**/
function smarty_modifier_overdue_action( $date )
{
    if(! $date ) {
        return( FALSE );
    }
    
    $timestamp = strtotime( $date );

    if( $timestamp > time( ) ) {
        return( FALSE );
    }
    
    return( TRUE );
    
}

/* vim: set expandtab: */

?>
