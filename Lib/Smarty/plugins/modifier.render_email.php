<?php

/**
 * Smarty render_email modifier plugin
 *
 * Type:     modifier<br>
 * Name:     render_email<br>
 * Purpose:  link an email
 * @author   Stefan Antonowicz <stefan@reputationdefender.com>
 * @param string
 * @return string
 */
function smarty_modifier_render_email( $email )
{
    $email_out = preg_replace(
        '!(\S+)@([a-zA-Z0-9\.\-]+\.([a-zA-Z]{2,3}|[0-9]{1,3}))!',
        '<a href="mailto:$1@$2">$1@$2</a>',
        $email
    );
    return $email_out;
}
/* vim: set expandtab: */

?>
