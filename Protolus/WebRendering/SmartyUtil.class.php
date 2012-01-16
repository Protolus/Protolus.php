<?php
    class SmartyUtil{
    
        public static function newSmartyInstance(){
            $renderer = new Smarty();
            $renderer->template_dir = PageRenderer::$template_directory;
            $renderer->compile_dir = PageRenderer::$compile_directory;
            $renderer->cache_dir = PageRenderer::$cache_directory;
            $renderer->config_dir = PageRenderer::$config_directory;
            $renderer->caching = false;
            $renderer->register_block('ll', array('SmartyUtil','local_link'), false);
            $renderer->register_function('panel', array('SmartyUtil','render_panel'), false);
            $renderer->register_function('enumeration', array('SmartyUtil', 'render_enumeration'), true);
            $renderer->register_function('primitive', array('SmartyUtil', 'render_primitive'), false);
            //$renderer->register_function('ll', array('SmartyUtil', 'local_link'), true);
            $renderer->register_function('txt', array('SmartyUtil', 'render_text'), true);
            $renderer->register_function('language', array('SmartyUtil', 'language'), false);
            $renderer->register_function('language_code', array('SmartyUtil', 'language_code'), false);
            $renderer->register_function('import', array('SmartyUtil', 'component_require'), false);
            $renderer->register_function('page', array('SmartyUtil', 'set_page_data'), false);
            $renderer->register_function('breadcrumb', array('SmartyUtil', 'set_breadcrumb'), false);
            $renderer->register_function('oo', array('SmartyUtil', 'oo'), false);
            $renderer->register_function('convert', array('SmartyUtil', 'perform_conversion'), false);
            $renderer->register_function('assign_view_state', array('SmartyUtil', 'assign_view_state'), false);
            $renderer->register_modifier('view_state', array('SmartyUtil', 'view_state'));
            $renderer->register_modifier('wrapper_variable', array('SmartyUtil', 'assign_wrapper_variable'));
            $renderer->register_function('json', array('SmartyUtil', 'json'));
            $renderer->register_function('require', array('SmartyUtil', 'req'));
            $renderer->register_function('has_role', array('SmartyUtil', 'has_role'), false);
            $renderer->register_function('date', array('SmartyUtil', 'date'), false);
            $renderer->register_function('form', array('SmartyUtil', 'form'), false);
            $renderer->register_function('encode', array('SmartyUtil', 'encode'), false);
            //$renderer->assign('language', Locale::getLanguageCode());
            return $renderer;
        }
    
        public static function modifySmartyInstance($renderer, $config_dir = false){
            if($config_dir){
                $renderer->template_dir = SmartyUtil::$template_directory;
                $renderer->compile_dir = SmartyUtil::$compile_directory;
                $renderer->cache_dir = SmartyUtil::$cache_directory;
                $renderer->config_dir = SmartyUtil::$config_directory;
                $renderer->caching = false;
            }
            $renderer->register_function('panel', array('SmartyUtil','render_panel'), false);
            $renderer->register_function('enumeration', array('SmartyUtil', 'render_enumeration'), true);
            $renderer->register_function('primitive', array('SmartyUtil', 'render_primitive'), false);
            $renderer->register_function('ll', array('SmartyUtil', 'local_link'), true);
            $renderer->register_function('txt', array('SmartyUtil', 'render_text'), true);
            $renderer->register_function('language', array('SmartyUtil', 'language'), false);
            $renderer->register_function('language_code', array('SmartyUtil', 'language_code'), false);
            $renderer->register_function('import', array('SmartyUtil', 'component_require'), false);
            $renderer->register_function('page', array('SmartyUtil', 'set_page_data'), false);
            $renderer->register_function('breadcrumb', array('SmartyUtil', 'set_breadcrumb'), false);
            $renderer->register_function('oo', array('SmartyUtil', 'oo'), false);
            $renderer->register_function('convert', array('SmartyUtil', 'perform_conversion'), false);
            $renderer->register_function('json', array('SmartyUtil', 'json'));
            $renderer->register_modifier('view_state', array('SmartyUtil', 'view_state'));
            $renderer->register_function('date', array('SmartyUtil', 'date'), false);
            $renderer->register_function('form', array('SmartyUtil', 'form'), false);
            $renderer->register_function('encode', array('SmartyUtil', 'encode'), false);
            $renderer->assign('language', Locale::getLanguageCode());
            //return $renderer;
        }

        static function render_panel($params, &$smarty){
            if(!is_array($params)){
                $params = array("name"=>$params);
            }
            $panel = new Panel($params['name']);
            $panel->parent = $smarty;
            return $panel->render($params);
        }
    
        static function render_primitive($params, &$smarty){
            $renderer = new Smarty();
            $renderer->template_dir = PageRenderer::$template_directory;
            $renderer->compile_dir = PageRenderer::$compile_directory;
            $renderer->cache_dir = PageRenderer::$cache_directory;
            $renderer->config_dir = PageRenderer::$config_directory;
            $renderer->register_function('enumeration', array('PageRenderer', 'render_enumeration'), true);
    
    
            if(isset($params['type']) && isset($params['name'])){
                $value = $params['value'];
                $type = PrimitiveType::grab($params['type']);
                if(! is_array($value)){
                    $value = PrimitiveType::contract(PrimitiveType::deconstruct($params['type'], $value));
                }
                $params['value'] = $type->smartyVariables($value, $params['name']);
                if(strtolower($params['null_if_empty']) == "true"){
                    if(is_array($value)){
                        $str_val = PrimitiveType::construct($params['type'], PrimitiveType::expand($value));
                    }else{
                        $str_val = $value;
                    }
                    if(preg_match("~^\|+$~", $str_val)){
                        return "";
                    }
                }
            }
        }
        
        static function render_vars($string, &$smarty){
            if(preg_match_all('~\[\[(.*)\]\]~', $string, $matches)){
                foreach($matches[1] as $match){
                    $parts = explode('.', $match);
                    $parts = array_reverse($parts);
                    $value = $smarty->get_template_vars(array_pop($parts));
                    while(count($parts) > 0){
                        $value = $value[array_pop($parts)];
                    }
                    $string = preg_replace('~\[\['.preg_replace('~\.~', '\.', $match).'\]\]~', $value, $string);
                }
            }
            return $string;
        }
    
        //deprecated
        static function assign_wrapper_variable($params, &$smarty){
            if(!is_array($params)) $params = array('name'=>$params, 'value'=>$smarty);
            if(!isset($params['name']) || !isset($params['value'])) return; // nothing to do, input error!
            PageRenderer::$wrapper_variable_registry[$params['name']] = $params['value'];
        }
    
        //deprecated
        static function assign_view_state($params, &$smarty){
            if(!is_array($params)) $params = array('name'=>$params, 'value'=>$smarty);
            if(!isset($params['name']) || !isset($params['value'])) return; // nothing to do, input error!
            $viewState = PageRenderer::viewState();
            $viewState->$params['name'] = $params['value'];
        }
    
        static function render_enumeration($params, &$smarty){
            $renderer = new Smarty();
            $renderer->template_dir = PageRenderer::$template_directory;
            $renderer->compile_dir = PageRenderer::$compile_directory;
            $renderer->cache_dir = PageRenderer::$cache_directory;
            $renderer->config_dir = PageRenderer::$config_directory;
            if(!isset($params['name'])) throw new Exception('Enumeration must have a name!');
            $enumeration = PageRenderer::enumeration($params['name']);
            $renderer->assign('enumerations', $enumeration->data);
            $renderer->assign('name', $params['identifier']);
            $renderer->assign('value', $params['value']);
            if (isset($params['tabindex']) && !empty($params['tabindex'])) {
                $renderer->assign('tabindex', $params['tabindex']);
            }
            return $renderer->fetch('enumeration.tpl');
        }
        //this formats a link properly... passing in ID and class is optional but 'link' is required
        static function local_link($params, $content, &$smarty){
            //handle variables
            preg_match_all('~\[([^\]]*)?\]~', $params['link'], $matches);
            foreach($matches[1] as $index=>$match){
                $parts = array_reverse(explode('.', $match));
                $p = array_pop($parts);
                $current = $smarty->get_template_vars($p);
                while(count($parts) > 0) $current = $current[array_pop($parts)];
                $params['link'] = preg_replace('~\['.$match.'\]~', $current, $params['link']);
            }
            //$content = $params['link'].print_r($matches, true); //exit();
            if(isset($params['variable'])){
                $parts = array_reverse(explode('.', $params['variable']));
                $current = $smarty->get_template_vars(array_pop($parts));
                while(count($parts) > 0){
                    $current = $current[array_pop($parts)];
                }
                $params['link'] = $current;
            }
            $add_events = '';
            if(isset($params['id'])){
                $add_id = 'id="'.$params['id'].'"';
            }
            if(isset($params['class'])){
                $add_class = 'class="'.$params['class'].'"';
            }
            if(isset($params['script'])){
                $add_class = 'onclick="'.$params['script'].'"';
            }            
            if(isset($params['mouseover'])){
                $add_events .= ' onmouseover="'.$params['mouseover'].'"';
            }
            if(isset($params['mouseout'])){
                $add_events .= ' onmouseout="'.$params['mouseout'].'"';
            }
            if(isset($params['rel'])){
                $add_rel = 'rel="'.$params['rel'].'"';
            }
            if(isset($params['target'])){
                $add_rel = 'target="'.$params['target'].'"';
            }
            return '<a href="/'.$params['link'].'" '.$add_id.' '.$add_class.' '.$add_rel.'>'.$content.'</a>';
        }
    
        static function language($params, &$smarty){
            return Locale::getLanguageName();
        }
    
        static function language_code($params, &$smarty){
            return Locale::getLanguageCode();
        }
    
        static function component_require($params, &$smarty){
            if(!isset($params['target'])) $target = 'bottom';
            else $target = $params['target'];
            if(!isset($params['name'])) return; // nothing to do, input error!
            ExternalComponents::import($params['name'], $target);
        }
    
        static function render_text($params, &$smarty){
            if(isset($params['property'])) {
                $result = Locale::getProperty($params['property']);
            }else if(isset($params['enum'])){
                $enumeration = PageRenderer::enumeration($params['enum']);
                if (!is_object($enumeration)) return '[TXT ERROR:enum '.$params['enum'].' unrecognized]';
                if(isset($params['key'])){
                    $result = $enumeration->getProperty($params['key']);
                }else if(isset($params['value'])){
                    $result = $enumeration->getKey($params['value']);
                }else $result = '';
            }else{
                $result =  Locale::getText($params['key']);
            }
            if(isset($params['substitute'])){
                $subs_environments = explode(',', $params['substitute']);
                $environment = array();
                foreach($subs_environments as $sub){
                    switch (strtolower(trim($sub))){
                        //TODO: optional value substitution by type
                        case '':
                            break;
                    }
                }
                $result = PageRenderer::substitute($result, $environment);
            }
            //echo('|'.$result.'|');
            return $result;
        }
    
        static function insertExternalRenderTargets($page){
            preg_match_all('~<!-- *#\[ *(.*?) *\]# *-->~', $page, $matches);
            foreach($matches[1] as $target){
                $renderedText = ExternalComponents::render($target);
                if(trim($renderedText) == '') $renderedText = '<!-- '.$target.' -->';
                $renderedText = str_replace("\\", "\\\\", $renderedText);
                $page = preg_replace('~<!-- *#\[ *'.$target.' *\]# *-->~', $renderedText, $page);
            }
            return $page;
        }
    
        static function substitute($template, $values, $cleanUnmatched=false){
            preg_match_all('~\[([A-Za-z0-9._-]*?)\]~', $template, $matches);
            $unique_matches = array();
            foreach($matches[1] as $match){
                if(!in_array($match, $unique_matches)) $unique_matches[] = $match;
            }
            foreach($unique_matches as $key){
                if($pos = strpos( $key, '.')){
                    $parts = explode('.', $key);
                    $current_array_spot = $values;
                    foreach($parts as $part){
                        if(isset($current_array_spot[$part])){
                            $current_array_spot = $current_array_spot[$part];
                        }else throw new Exception('|'.$part.'| not found from '.$key.' in environment'.print_r($current_array_spot, true));
                    }
                    $template = preg_replace('~\['.$key.'\]~', $current_array_spot, $template);
                }else{
                    $template = preg_replace('~\['.$key.'\]~', $values[$key], $template);
                }
            }
            //if($cleanUnmatched) $template = preg_replace('~\[.*?\]~', '', $template);
            return $template;//*/
        }
    
        static function set_page_data($params, &$smarty){
            if(isset($params['title'])) PageRenderer::$core_data['page_title'] = SmartyUtil::render_vars($params['title'], $smarty);
            if(isset($params['heading'])) PageRenderer::$core_data['page_heading'] = SmartyUtil::render_vars($params['heading'], $smarty);
            if(isset($params['meta_description'])) PageRenderer::$core_data['page_meta_description'] = SmartyUtil::render_vars($params['meta_description'], $smarty);
            if(isset($params['image'])) PageRenderer::$core_data['page_image'] = SmartyUtil::render_vars($params['image'], $smarty);
            //if(isset($params['meta'])) PageRenderer::$core_data['page_meta'] = array_map(array('SmartyUtil', 'render_vars'), json_decode($params['meta']), array($smarty)); //JSON not legal in macro 
            if(isset($params['wrapper'])) PageRenderer::setWrapper($params['wrapper']);
            
        }
    
        static function perform_conversion($params, &$smarty){
            if(isset($params['group'])){
                $group = PageRenderer::getDisplayGroup($params['group'], null, null, null, false, false);
                if(isset($params['drop'])){
                    return '<!-- AB Test conversion with timer temporarily disabled -->';
            
                    if(preg_match('~^[Tt][Ii][Mm][Ee][Rr] *\( *([0-9]+) *\) *$~', $params['drop'], $matches)){
                        if(is_numeric($matches[1])){
                            return '<script type="text/javascript" src="//static.woopra.com/js/woopra.v2.js"></script><script>setTimeout(\'var testEvent = new WoopraEvent("ABTestConversion"); testEvent.addProperty("abc_user", "'.DefaultTable::$user_id.'"); testEvent.addProperty("abc_test", "'.$params['group'].'"); testEvent.addProperty("abc_group", "'.$group.'"); testEvent.addProperty("abc_user_test", "'.DefaultTable::$user_id.'|'.$params['group'].'"); testEvent.addProperty("abc_test_group", "'.$params['group'].'|'.$group.'"); testEvent.fire();\', '.($matches[1]*1000).');</script>';
                        }else{
                            return '<!-- AB Test conversion with timer not working (test: '.$params['group'].')-->';
                        }
                    }if(strtolower($params['drop']) == 'notag'){
                        return 'setTimeout(\'var testEvent = new WoopraEvent("ABTestConversion"); testEvent.addProperty("abc_user", "'.DefaultTable::$user_id.'"); testEvent.addProperty("abc_test", "'.$params['group'].'"); testEvent.addProperty("abc_group", "'.$group.'"); testEvent.addProperty("abc_user_test", "'.DefaultTable::$user_id.'|'.$params['group'].'"); testEvent.addProperty("abc_test_group", "'.$params['group'].'|'.$group.'"); testEvent.fire();\', '.($matches[1]*1000).');';
                    }else{
                        return '<script type="text/javascript" src="//static.woopra.com/js/woopra.v2.js"></script><script>var testEvent = new WoopraEvent("ABTestConversion");
                    testEvent.addProperty("abc_user", "'.DefaultTable::$user_id.'");
                    testEvent.addProperty("abc_test", "'.$params['group'].'");
                    testEvent.addProperty("abc_group", "'.$group.'");
                    testEvent.addProperty("abc_user_test", "'.DefaultTable::$user_id.'|'.$params['group'].'");
                    testEvent.addProperty("abc_test_group", "'.$params['group'].'|'.$group.'");
                    testEvent.fire();</script>';
                    }
                }else{
                    if($group) PageRenderer::convert('id', $params['group'], $group);
                }
            }
        }
    
        static function set_breadcrumb($params, &$smarty){
            $count = 1;
            while(isset($params['crumb'.$count])){
                if(!isset(PageRenderer::$core_data['breadcrumb_'.$params['name']])) PageRenderer::$core_data['breadcrumb_'.$params['name']] = array();
                PageRenderer::$core_data['breadcrumb_'.$params['name']][$params['crumb'.$count]] = $params['link'.$count];
                $count++;
            }
        }
        
        static function date($params, &$smarty){
            if(!$params['variable'] && !$params['timestamp'] && !$params['format']) return;
            if($params['format']) $format = $params['format'];
            $parts = explode('.', $params['variable']);
            $timestamp = $smarty->get_template_vars(current($parts));
            while(next($parts)){
                if(!$timestamp[current($parts)]) return;
                $timestamp = $timestamp[current($parts)];
            }
            if($params['timestamp']) $timestamp = $params['timestamp'];
            if(!$params['timestamp'] && !$params['variable']){
                $timestamp = time();
                $gmt_offset = 0;
            }else{
                $gmt_offset = timezone_offset_get(date_default_timezone_get());
            }
            return date($format, $timestamp + $gmt_offset);
        }
        
        static function json($params, &$smarty){
            $count = 1;
            if(isset($params['variable'])){
                return json_encode($smarty->get_template_vars($params['variable']));
            }
        }
        
        static function req($params, &$smarty){
            $reqs = explode(',', $params['name']);
            foreach($reqs as $req){
                $req = trim($req);
                ResourceBundle::req($req);
            }
        }
        
        static function form($params, &$smarty){
            if( ($object = $smarty->get_template_vars($params['object'])) && is_object($object)){
                return $object->HTML();
            }
            //echo('ACK!'.print_r($smarty->get_template_vars(), true));
            exit();
        }
        
        static function oo($params, &$smarty){
            $obj = $params['object'];
    
            if(is_string($obj)){ //static call
                $str = $obj."::";
    
                if(isset($params['function'])){
                    $str .= $params['function']."();";
                }else if(isset($params['field'])){
                    $str .= $params['field'].";";
                }else{
                    echo "";
                }
    
                eval("return ".$str);
    
            }else if(is_object($obj)){		//member var or function call
                if(isset($params['function'])){
                    $func = $params['function'];
                    return $obj->$func();
                }else if(isset($params['field'])){
                    $field = $params['field'];
                    return $obj->$field;
                }
    
            }else{
                echo "";
            }
    
        }
        static function encode($params, &$smarty){
            if(!isset($params['value']) || !isset($params['type'])){
                
                 return "";
            }
            preg_match_all('~\[([^\]]*)?\]~', $params['value'], $matches);
            foreach($matches[1] as $index=>$match){
                $parts = array_reverse(explode('.', $match));
                $p = array_pop($parts);
                $current = $smarty->get_template_vars($p);
                while(count($parts) > 0) $current = $current[array_pop($parts)];
                $params['value'] = preg_replace('~\['.$match.'\]~', $current, $params['value']);
            }
            
            
            switch($params['type']){
                case "entity":
                    return htmlentities($params['value']);
                break;
            }
        }
    }
?>