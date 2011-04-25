<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


/**
 * Smarty {format_url} function plugin
 *
 * @param add - add this to a url query string. EXAMPLE: var=value OR... you may ALSO pass in multiple vars EXAMPLE: var1=test&var2=booyah&so_on=so_forth
 * @param append - if this param is set to "1" then the function will add a "?" or a "&" to it's end in order for values to be appended to the url in plain code. This allows variables to be used.
 * @param ????? - UNKNOWN!! BEAT THE GAME TO UNLOCK THIS PARAMETER!!!!!!111
 *	
 * @version  0.1
 * @author   Steve Peak
 * @param    array
 * @param    Smarty
 * @return   string
 *
 * N.B 'o' and 'd' are reserved for order by and order direction.
 */
function smarty_function_format_url($params, &$smarty)
{

	$script = $_SERVER['SCRIPT_NAME'];
	$uri_data = $_SERVER['QUERY_STRING'];
	$add = $params['add'];
	$remove = $params['remove'];
	$append = $params['append'];
	
	//drop that slash, son!
	//$script = str_replace('/','',$script);
	
	if(!$add && !$remove){
		if(!$uri_data){
			$url = $script;
		}else{
			$url = $script.'?'.$uri_data;
		}
		echo $url;
		return;
	}
	
	if(!$uri_data){
		if($append == 1){
			$link = $script.'?'.$add.'&';
		}else{
			$link = $script.'?'.$add;
		}
		echo $link;
		return;
	}

	parse_str($add, $add_array);	
	parse_str($uri_data, $uri_array);
	if($add){
		foreach($add_array as $add_key => $add_val){
			if($remove != $add_key){
				$uri_array[$add_key] = $add_val;
			}
		}
	}
	if(!$add && $remove){
		$remove_array = array();
		foreach($uri_array as $uri_key => $uri_val){
			if($remove != $uri_key){
				$remove_array[$uri_key] = $uri_val;
			}
		}
		$uri_array = $remove_array;					
	}

	//this is a very specific callout. only accepts 'asc' or 'desc' for tablesorting, but doesn't care about case.
	if(strcasecmp($_GET['o'],$add_array['o']) == 0 ){
		if(strcasecmp($_GET['d'],'asc') == 0){
			$uri_array['d'] = 'desc';
		}else{
			$uri_array['d'] = 'asc';
		}
	}
	
	if($append == 1){
		echo $script.'?'.http_build_query($uri_array).'&';
	}else{
		echo $script.'?'.http_build_query($uri_array);
	}
	return;
	
	
}

?>
