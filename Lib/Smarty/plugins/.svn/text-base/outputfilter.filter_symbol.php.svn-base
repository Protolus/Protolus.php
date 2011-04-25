<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */

/**
 * Smarty filter symbol outputfilter plugin
 *
 * File:     outputfilter.filter_symbol.php<br>
 * Type:     outputfilter<br>
 * Name:     filter_symbol<br>
 * Date:     Nov. 12th, 2008<br>
 * Purpose:  Remove any instances of '$' and replace with current currency symbol. 
 *			 This is to replace any '$' symbols that are stored in db.   
 *           
 *           
 * @author   Charlie Russ <cruss at reputationdefender dot com>
 * @version  1.0
 * @param string
 * @param Smarty
 */

function smarty_outputfilter_filter_symbol($source, &$smarty){
	/*if (! isset($cregion)){
		$cregion = 'USA';
	}
	//if the region is USA, we dont need to swap '$' for '$'
		//if no symbol in session, get it from the db
		if(! $_SESSION['symbol']){
			$regionObj = new Region;
		   	$regionResult = $regionObj->getData( "region_3code = '$cregion'" );
		   	$region_id = $regionResult[0]['id'];
			$currency_id = $_SESSION['currency_id'] = $regionResult[0]['currency_id'];
			//create a new currency object
			$currObj = new Currency($currency_id);
			$symbol = $_SESSION['symbol'] = iconv('Windows-1252', 'UTF-8', $currObj->get('symbol') );
		}else{
			$symbol = $_SESSION['symbol'];
		}
	$source = preg_replace('~\$([0-9]+\.[0-9]{2})~', 
							$symbol.'\1', $source);
*/
	return $source;
}
?>