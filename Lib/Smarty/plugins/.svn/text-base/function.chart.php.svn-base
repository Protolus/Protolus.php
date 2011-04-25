<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


/**
 * Smarty {chart} function plugin
 *
 * @param transition - (OPTIONAL) Valid values are dissolve, drop, spin, scale, zoom, blink, slide_right, slide_left
 * slide_up, slide_down, and none. The default is dissolve.
 * @param transition_delay - (OPTIONAL) how long to wait before graph is animated in. In seconds.
 * @param transition_duration - (OPTIONAL) How long does the graph animation last? In seconds.
 * @param transition_order - (OPTIONAL) In what order to render elements in graph. Valid: 
 * series, category, and all. Default is all.
 * @param width - (OPTIONAL) Default is 300
 * @param height - (OPTIONAL) Default is 300
 * @param type - (OPTIONAL) Default is 3d pie. Valid values: line,column (default), stacked column, floating column,
 * 3d column, image column, stacked 3d column, parallel 3d column, pie, 3d pie, image pie, donut, bar,
 * stacked bar, floating bar, area, stacked area, 3d area, stacked 3d area, candlestick, scatter, polar,
 * bubble
 * @param grid_on - (OPTIONAL) true / false
 * @param link_data - (OPT) This determines where data from the graph is processed. 
 * Clicks on the graph take the user to this location EXAMPLE link_data="manager.php"
 * ALL data from the graph is passed through as _GET vars. Defaults to ''.
 *
 * Examples:
 * {chart type="pie" data=$array}
 * {chart type="bar" width="250" height="150" data=$array}
 * {chart type="bar" transition="dissolve" transition_delay="0.5" data=$array}
 *	
 * @version  0.5
 * @author   Steve Peak
 * @author   credits to Abbey Hawk Sparrow (built the charting class / interface for SWFgraph)
 * @author   We paid for SWF Graph... but I'll still mention it.
 * @param    array
 * @param    Smarty
 * @return   string
 */
function smarty_function_chart($params, &$smarty)
{
	//Defaults
	$settings = array(
		'width' => 300,
		'height' => 300,
		'type' => '3d pie',
		'transition_type' => 'none',
		'transition_delay' => '0',
		'transition_duration' => '1',
		'transition_order' => 'all',
		'grid_on' => 'false',
		'link_data' => '',
		'colors' => array(),
		
		'data' => array()
	);
	$setting_keys = array_keys($settings);

	foreach($params as $param => $param_val){
		if(in_array($param, $setting_keys)){
			$settings[$param] = $param_val;
		}
	}
	
	$chartBuilder = new ChartBuilder($settings['width'], $settings['height']);
	$chartBuilder->setGraphBounds(($settings['width']/4), ($settings['height']/4), $settings['width'], $settings['height']);
	$chartBuilder->setType($settings['type']);
	$chartBuilder->addColor('#871919');
	$chartBuilder->setTransition($settings['transition_type'], $settings['transition_delay'], $settings['transition_duration'], $settings['transition_order']);
	$chartBuilder->setLegend('vertical', '#000000', '15', '100', 'false', 'square', 'Arial');
	$chartBuilder->setLegendBounds(0, 0, 20, 20, 1, 'f3f3f3', 0, 'f3f3f3', 0, 1);
	$chartBuilder->setLinkData($settings['link_data']);
	if($settings['data']){
		foreach($settings['data'] as $key => $data){
			$chartBuilder->addData($key, $data);
		}
	}else{
		$arr_smarty['error'] = 'Graph could not be rendered! (No data)';
	}
	if(isset($_REQUEST['get_data'])){
		ob_clean();
		if ($settings['type'] != 'pie' && $settings['type'] != '3d pie'){
			$chartBuilder->renderPieData($settings['transition_type'], $settings['transition_delay'], $settings['transition_duration'], $settings['transition_order'], $settings['grid_on']);
		}else{
			$chartBuilder->renderPieData($settings['transition_type'], $settings['transition_delay'], $settings['transition_duration'], $settings['transition_order'], $settings['grid_on']);
		}
	exit();
	}
	if($settings['data']){
		$chartBuilder->renderGraph();
	}else{
		echo "<b style='color:red'>Graph could not be rendered! (No data)</b>";
	}

}


?>
