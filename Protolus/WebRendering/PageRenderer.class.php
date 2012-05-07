<?php

require_once('Lib/Smarty/Smarty.class.php');

class PageRenderer{

    private static $wrapper = null;
    private static $object_registry = array();
    public static $enumeration_registry = array();
    public static $configuration_registry = array();
    private static $wrapper_variable_registry = array();
    public static $component_registry = array();
    private static $ab_test_tracking_registry = array();
    private static $ab_test_conversion_registry = array();
    public static $root_panel = null;
    public static $debug = true;
    public static $dataCall = false;

    public static $template_directory = './Templates';
    public static $compile_directory = './templates_c';
    public static $cache_directory = './templates_ca';
    public static $config_directory = './templates_co';
    public static $root_directory = './';
    public static $panel_directory = './Panels';
    public static $panel_overlay_directory = null;
    public static $controller_overlay_directory = null;
    public static $controller_directory = './Panels';
    public static $wrapper_directory = './App/Wrappers';
    public static $wrapper_controller_directory = './App/Wrappers';
    public static $mimic = null;
	public static $return_mode = false;
	public static $title = "";
    public static $core_data = array();
	public static $panel_extension = '.panel.tpl';
    private static $view_state = null;
    public static $controllers = true;

    private static $initialized = false;

    public static function initialize(){
        //PageRenderer::$enumeration_registry = PageRenderer::scan_directory(dirname(__FILE__) . '/../Enumerations', 'properties');
        //PageRenderer::$configuration_registry = PageRenderer::scan_directory(dirname(__FILE__) . '/../Configurations', array('conf', 'ini'));
        PageRenderer::$initialized = true;
    }

    public static function setWrapper($wrapper){
        $wrapper_path = PageRenderer::$wrapper_directory.'/'.$wrapper.'.wrapper.tpl';
        if(file_exists(realpath($wrapper_path))){
            PageRenderer::$wrapper = $wrapper;
        }
        if(file_exists(realpath(PageRenderer::$wrapper_directory.'/'.$wrapper.'.init.php'))){
        	require PageRenderer::$wrapper_directory.'/'.$wrapper.'.init.php';
        }
    }
    
    //just a demo hack
    public static function panelHas($panel, $field, $value){
        $endpoint = Endpoint::get($panel);
        if($endpoint && $endpoint->has($field, $value)) return true;
        return false;
    }

    public static function track($user, $test, $group){
        PageRenderer::$ab_test_tracking_registry[] = array('user'=>$user, 'test'=>$test, 'group'=>$group);
    }
    public static function convert($user, $test, $group){
        PageRenderer::$ab_test_conversion_registry[] = array('user'=>$user, 'test'=>$test, 'group'=>$group);
    }
    
    public static function objects($identifier){
        if (isset(PageRenderer::$object_registry[$identifier]))
            return PageRenderer::$object_registry[$identifier];
        return false;
    }
    
    public static function registerObjects($identifier, $objects){
        if(is_array($objects)){
            if(isset(PageRenderer::$object_registry[$identifier])){
                throw new Exception('Object '.$identifier.' already registered!');
            }else{
                PageRenderer::$object_registry[$identifier] = $objects;
            }
        }
    }

    public static function enumeration($identifier){
        if(!PageRenderer::$initialized) PageRenderer::initialize();
        return Formats::get($identifier, 'properties');
    }

    public static function configuration($identifier){
        if(!PageRenderer::$initialized) PageRenderer::initialize();
        return Formats::get($identifier, 'conf');
    }

    public static function registerEnumeration($identifier, $enumeration){
        if(is_object($enumeration)){
            Formats::$registry['properties'][$identifier] = $enumeration;
        }
    }

    public static function registerConfiguration($identifier, $configuration){
        Formats::register($identifier);
        if(is_object($configuration)){
            Formats::$registry['conf'][$identifier] = $configuration;
        }
    }
    
    public static function registerConfigurationDirectory($path){
        return Formats::register($path, 'conf');
    }

    public static function log($text){
        Logger::log($text);
    }
    
    public static function data($panel=null, $silo=null){
        //set the correct output level to prevent notices:
        PageRenderer::supressOutput();
        PageRenderer::$dataCall = true;
        if(isset($silo)) $pan = new Panel($panel, $silo);
        else $pan = new Panel($panel);
        $data = $pan->data();
        //return error reporting to it's normal level
        PageRenderer::revertOutput();
        return $data;
    }
    
    public static $default_error_level;
    
    public static function supressOutput(){
        //set the correct output level to prevent notices: "Smarty!!"
        PageRenderer::$default_error_level = error_reporting();
        $hasNoticesOn = (PageRenderer::$default_error_level ^ E_NOTICE) > PageRenderer::$default_error_level;
        if(!$hasNoticesOn) error_reporting(PageRenderer::$default_error_level ^ E_NOTICE);
    }
    
    public static function revertOutput(){
        error_reporting(PageRenderer::$default_error_level);
    }
    
    public static function profile($panel=null, $silo=null, $data=null, $enable_wrapper=true){
        //set the correct output level to prevent notices:
        PageRenderer::supressOutput();
        $wrapper_controller = PageRenderer::$controller_directory.'/'.PageRenderer::$wrapper.'.controller.php';
        if($panel == null) $panel = 'index';
        if($depth == null){ $depth = substr_count($panel, '/'); }
        if(!isset($path_prefix)) $path_prefix = '';
        for($lcv = 0; $lcv < $depth; $lcv++) $path_prefix .= '../';
        if(isset($silo)) $pan = new Panel($panel, $silo);
        else $pan = new Panel($panel);
        $result = $pan->profile();
        if(!array_key_exists('panels', $result)) $result['panels'] = array();
        $result['panels'][] = $panel;
        if(PageRenderer::$wrapper != null && $enable_wrapper){
            if(!array_key_exists('wrappers', $result)) $result['wrappers'] = array();
            $result['wrappers'][] = PageRenderer::$wrapper;
            $renderer = SmartyUtil::newSmartyInstance();
            $renderer->template_dir = PageRenderer::$wrapper_directory;
            if(file_exists(PageRenderer::$wrapper_directory.'/'.PageRenderer::$wrapper.'.wrapper.tpl')){
                $panelContent = file_get_contents(PageRenderer::$wrapper_directory.'/'.PageRenderer::$wrapper.'.wrapper.tpl');
                $result = array_merge_recursive($result, Panel::extractProfile($panelContent));
            }
        }
        PageRenderer::revertOutput();
        return $result;
    }

    public static function render($panel=null, $silo=null, $data=null){
        //set the correct output level to prevent notices:
        global $start_time;
        PageRenderer::supressOutput();
        $wrapper_controller = PageRenderer::$controller_directory.'/'.PageRenderer::$wrapper.'.controller.php';
        if($panel == null) $panel = 'index';
        if($depth == null){ $depth = substr_count($panel, '/'); }
        if(!isset($path_prefix)) $path_prefix = '';
        for($lcv = 0; $lcv < $depth; $lcv++) $path_prefix .= '../';
        if(isset($silo)) $pan = new Panel($panel, $silo);
        else $pan = new Panel($panel);
        $result = $pan->render();
        if(PageRenderer::$wrapper != null){
            $renderer = SmartyUtil::newSmartyInstance();
            $renderer->template_dir = PageRenderer::$wrapper_directory;
            //$renderer->assign('ab_trackers', PageRenderer::$ab_test_tracking_registry);
            //$renderer->assign('ab_conversions', PageRenderer::$ab_test_conversion_registry);
            foreach(PageRenderer::$core_data as $key=>$data) $renderer->assign($key, $data);
            $renderer->assign('content', '<div id="protolus_root">'.$result.'</div>');
            $wrapper_controller = PageRenderer::$wrapper_controller_directory.'/'.PageRenderer::$wrapper.'.controller.php';
            if(file_exists($wrapper_controller)){
                Logger::log('Loading '.$wrapper_controller);
                require($wrapper_controller);
                Logger::log('Finished loading '.$wrapper_controller);
            }else{
                PageRenderer::log('Wrapper '.PageRenderer::$wrapper.' contoller not found.');
            }
            $head = '';
            if(ResourceBundle::$merge){
                $head = ResourceBundle::combineAllResources();
            }else{
                foreach(PageRenderer::$component_registry as $component){
                    if(!array_key_exists($component, ResourceBundle::$packages)) throw('Required resource(\''.$component.'\') not found!');
                    $head .= (ResourceBundle::$packages[$component]->preloadResources());
                }
            }
            $renderer->assign('head', $head);
            $renderer->assign('render_time', number_format(Logger::processing_time($start_time), 3));
            $renderer->assign('root_panel', PageRenderer::$root_panel);
            if(file_exists(PageRenderer::$wrapper_directory.'/'.PageRenderer::$wrapper.'.wrapper.tpl')){
                $result = $renderer->fetch(PageRenderer::$wrapper.'.wrapper.tpl');
            }else{
               PageRenderer::log('Wrapper '.PageRenderer::$wrapper.' not found.');
            }
        }else{
            $result = '<div id="'.$panel.'">'.$result.'</div>';
        }
        PageRenderer::revertOutput();
		if(!PageRenderer::$return_mode){
			($mode = WebApplication::getConfiguration('application.mode')) || $mode = 'steady';
		    ($machineName = WebApplication::getGet('machineName')) || $mode = 'unknown_machine';
		    ($machine = WebApplication::getGet('environmentType')) || $mode = 'unknown_mode';
			echo $result.'<!-- rendered in '.number_format(Logger::processing_time($start_time), 3).'s on '.$machineName.' in '.$mode.' mode using '.$machine.' settings -->';
		}else{
			if(preg_match("~<title>(.*?)</title>~", $result, $matches)) PageRenderer::$title = $matches[1];
			return $result;
		}
    }

    /*
     * Smarty Extensions
     */
     public static function assignDisplayGroup($group_id, $options, $ratios){
        $group = 'abg_'.$group_id;
        if(!is_array($options) || sizeof($options) == 0) throw new Exception('trying to assign user to group "'.$group.'" but group has no variants!');
        //$r = rand(0, count($options)-1);
        $ratio_size = 0;
        foreach($ratios as $ratio_index => $ratio){
            $ratio_size += $ratio;
        }
        $r = rand(1, $ratio_size);
        $current = 0;
        $selected_index = null;
        foreach($options as $option_index => $option){
            foreach($ratios as $ratio_index => $ratio){
                if($option_index == $ratio_index){
                    $current += $ratio;
                }
            }
            if($current <= $r){
                $selected_index = $option_index;
            }
        }
        $assigned_group = $options[$selected_index];
        setcookie($group, $assigned_group, strtotime("+10 years"), '/');
        if(isset(DefaultTable::$user_id) && DefaultTable::$user_id != 0){
            $group_assignment = new ABUserTestGroup();
            $group_assignment->set('user_id', DefaultTable::$user_id);
            $group_assignment->set('test_group', $group_id);
            $group_assignment->set('test', $assigned_group);
            $group_assignment->save();
        }
        return $assigned_group;
     }

     //get AB group
     public static function getDisplayGroup($group_id, $options = null, $ratios = null, $prequalifier=null, $sameInstance=false, $assign=true){
        $group = $_COOKIE['abg_'.$group_id];
        if(isset($_REQUEST['force_assign']) && isset($_REQUEST['force_test'])){
            if( strtolower(trim($group_id)) == strtolower(trim($_REQUEST['force_test'])) && in_array($_REQUEST['force_assign'], $options)){
                $group = $_REQUEST['force_assign'];
            }
        }
        if(trim($group) == ''){
            //TODO: prequalify test by login status, if they aren't logged in they automatically get a new group if they encounter a test
            $results = DefaultTable::objectSearch('ABUserTestGroup', 'test_group = \''.$group_id.'\' and user_id = \''.DefaultTable::$user_id.'\'');
            $user = new User(DefaultTable::$user_id);
            if(count($results) > 0){
                $group = $results[0]->get('test');
                setcookie('abg_'.$group_id, $group, strtotime("+10 years"), '/'); // we need to put the user back in the group they were in
            }else{
                //if they aren't allowed in this particular test, so we assign them to the passthru group ('*')
                if( ($prequalifier != null && $prequalifier != '' && !PageRenderer::preQualifyUserForTest(DefaultTable::$user_id, $prequalifier, $sameInstance) ||
                    $user->get('user_flag') == 'test' || //exclude test users from tests
                    $user->get('user_flag') == 'vip' //exclude VIP users from tests
                )){
                    setcookie('abg_'.$group_id, '*', strtotime("+10 years"), '/');
                    return '*';
                }
                if($assign) $group = PageRenderer::assignDisplayGroup($group_id, $options, $ratios);
                else return false;
            }
        }
        return $group;
    }

    public static function preQualifyUserForTest($user, $prequalifier, $sameInstance){
        //assemble the user data
        $qualifier = new ExperimentPrequalifier($prequalifier);
        $qualifier->forceSameInstance = $sameInstance;
        $res = $qualifier->qualify($user);
        return $res;
    }

    static function render_panel_ajax($panel_name, $params = array()) {
        $renderParams = array();
        $renderParams['name'] = $panel_name;
        $renderParams['params'] = $params;
        return PageRenderer::render_panel($renderParams);
    }
}

