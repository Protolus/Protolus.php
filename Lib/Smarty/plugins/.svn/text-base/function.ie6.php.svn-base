<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


/**
 * Smarty {ie6} function plugin
 *
 * Type:     function
 * Name:     ie6
 * Purpose:  to deal with a derelict browser that people still use. This can be used in boolean operations (as shown below)... OR you can use an optional parameter "text" to echo out whatever you enter IF IE6 is detected.
 * Examples: 
 * {ie6 text="whatever you want to echo"} 
 * {if ie6}Hello!{/if} UPDATE... this might not work? :: TODO :: TEST THIS
 * @author Steve Peak 
 * @param array
 * @param Smarty
 *   
 */
function smarty_function_ie6($params, &$smarty)
{
	$client = $_SERVER['HTTP_USER_AGENT'];
	
	//check for IE6 in client info
	if($client){
		if(strpos($client, "MSIE 6") !== FALSE && $params['text']){
			return $params['text'];
		}
	}

}


?>