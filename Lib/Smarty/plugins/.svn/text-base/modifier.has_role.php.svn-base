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
 * Name:     has_role<br>
 * Date:     July 22, 2008<br>
 * Purpose:  see if a user has a role on the smarty side
 * @author   Stefan Antonowicz <stefan@reputationdefender.com>
 * @version  1.0
 * @param array
 * @param Smarty
 * @return boolean
 */
 
/*** not a modifier, but need it to act like one so
***  we can check against the boolean value
**/
function smarty_modifier_has_role( $role )
{
    if(! $roles = $_SESSION['user_roles'] ) {
        return( FALSE );
    }
    
    if( in_array( 'superuser', $roles ) ) {
        return( TRUE );
    }

	if( in_array( 'me-admin', $roles ) ) {
        return( TRUE );
    }

    
    if( in_array( $role, $roles ) ) {
        return( TRUE );
    }
    
    return( FALSE );
}
/* vim: set expandtab: */

?>
