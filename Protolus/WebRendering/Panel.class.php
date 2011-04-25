<?php
    class RendererDummy{
        public $data = array();
        public function assign($name, $value){$this->data[$name] = $value;}
        public function get_template_vars($name){return $this->data[$name];}
    }
     
    class Panel{
        public static $callStack = array();
        public static $fileExtension = 'panel.tpl';
        protected $name;
        public $silo;
        protected $rootDirectory = '.';
        function __construct($panel_name, $silo = '_root_'){
            if($silo == '_root_'){
                if(count(Panel::$callStack) > 0){
                    $parentPanel = end(Panel::$callStack);
                    $silo = $parentPanel->silo;
                }else{
                    $silo = '';
                }
            }
            $this->silo = $silo;
            //echo('[panel:'.$panel_name.', '.$silo.' : '.print_r(Panel::$callStack, true).']');
            $this->rootDirectory = Panel::directoryForSilo($silo);
            if(Panel::isDefined($panel_name, $this->rootDirectory.'Panels/')){
                $this->name = $panel_name;
                //echo('[d]');
            }else{
                throw new Exception('Panel does not exist('.$panel_name.')!');
            }
        }
        
        public function data(){
            $renderer = new RendererDummy();
            $controller_location = $this->rootDirectory.'/Controllers/'.$this->name.'.controller.php';
            require($controller_location);
            return json_encode($renderer->data);
        }
        
        public static function extractProfile($panelContent){
            preg_match_all('~{ *?page(.*?)wrapper *= *"(.*?)".*?}~im', $panelContent, $matches);
            if(array_key_exists(0, $matches[2])) PageRenderer::setWrapper($matches[2][0]);
            preg_match_all('~{.*?(\$[A-Za-z0-9\._]*).*?}~im', $panelContent, $matches);
            $vars = array();
            foreach($matches[1] as $name=>$value) if( (!in_array($value, $vars)) && $value != '$content') $vars[] = substr($value, 1);
            $result = array('variables' => $vars);
            preg_match_all('~{ *?panel(.*?)name *= *"(.*?)".*?}~im', $panelContent, $matches);
            if(array_key_exists(0, $matches[2])){
                $result = array_merge_recursive($result, PageRenderer::profile($matches[2][0], null, null, false));
            }
            return $result;
        }
        
        public function profile($type = 'panel'){
            $file = $this->rootDirectory.'Panels/'.$this->name.'.'.$type.'.tpl';
            $template = file_get_contents($file);
            return Panel::extractProfile($template);
        }

        public function render($params = null){
            array_push(Panel::$callStack, $this);
            if($params == null) $params = $this->name;
            $renderer = SmartyUtil::newSmartyInstance();
            $renderer->template_dir = $this->rootDirectory.'Panels/';
            $testing_panel = false;
            if(!is_array($params)){
                $params = array("name"=>$params);
            }
            foreach(PageRenderer::$core_data as $key=>$data){ //assign all the global data
                $renderer->assign($key, $data);
            }
            //I apologize for the ternary, it's just much cleaner this way
            // here we check the instance param, to see if we match instances for connected AND expressions
            $sameInstance = ( array_key_exists('instance', $params)
                              && (
                                  (is_bool($params['instance']) && $params['instance'])
                                  || strtolower($params['instance']) == 'true'
                              )
                            ) ? true : false;
            $groups = array('default' => $params['name']);
            $ratios = isset($params['ratio']) ? array('default' => $params['ratio']) : array('default' => 1);
            $old_name  = $params['name'];
            foreach($params as $index => $param){
                if(substr($index, strlen($index) - 5) == '_test'){ //we found a test
                    $group = substr($index, 0, strlen($index) - 5);
                    $groups[$group] = $param;
                    $testing_panel = true;
                }
                if(substr($index, strlen($index) - 6) == '_ratio'){ //we found a ratio
                    $group = substr($index, 0, strlen($index) - 6);
                    $ratios[$group] = $param;
                    $testing_panel = true;
                }
            }

            if($testing_panel){
                foreach($groups as $index=>$group){
                    if(!isset($ratios[$index])){
                        $ratios[$index] = 1;
                    }
                }
                $assigned_group = PageRenderer::getDisplayGroup($params['name'], $groups, $ratios, $params['prequalifier'], $sameInstance);
                if(trim($assigned_group) != '' && $assigned_group != '*' ){ //we bail because we aren't actually assigned a place in the test
                    if($assigned_group != 'name'){
                        $params['name'] = $assigned_group;
                    }
                    PageRenderer::track('tu1', $old_name, $params['name']);
                }
            }
            // now that we know the name of the panel
            if(
                PageRenderer::$panel_overlay_directory != null &&
                PageRenderer::isPanelDefined($params['name'], PageRenderer::$panel_overlay_directory)
            ){
                $renderer->template_dir = PageRenderer::$panel_overlay_directory;
            }
            //if($smarty!=null) {
              if (isset($params['params'])) {
                foreach($params['params'] as $key=>$param) {
                    $renderer->assign($key, $param);
                }
              }
              if(is_object($smarty)) {
                  foreach($smarty->get_template_vars() as $key=>$param){
                        $renderer->assign($key, $param);
                  }
              }
            //}
            if(is_object($smarty) && $depth = $smarty->get_template_vars('path_depth')){
                $renderer->assign('path_depth', $depth);
                $renderer->assign('path_prefix', $smarty->get_template_vars('path_prefix'));
            }
            if(!isset($params['name'])){
                PageRenderer::log('No panel specified to render!');
            }else{
                $controller_start_time = microtime(true);
                $controller_location = $this->rootDirectory.'/Controllers/'.$params['name'].'.controller.php';
                $panel_dir = $controller_location;
                if(PageRenderer::$controller_overlay_directory != null){
                    $overlay_dir = $controller_location;
                }
                if(isset($overlay_dir) && file_exists($overlay_dir)){
                    require($overlay_dir);
                }else{
                    if(file_exists($panel_dir)){
                        if(PageRenderer::$controllers) require($panel_dir);
                    }else{
                        PageRenderer::log($params['name'].' controller not found.');
                    }
                }
                $panel_location = $params['name'].'.'.Panel::$fileExtension;

                $controller_end_time = microtime(true);
                $controller_run_time = ($controller_end_time - $controller_start_time) * 1000;
                $panel_render_start_time = microtime(true);
                $rendered = '<div id="'.$params['name'].'_panel">'.$renderer->fetch($panel_location).'</div>';

                $panel_render_end_time = microtime(true);
                $panel_render_run_time = ($panel_render_end_time - $panel_render_start_time) * 1000;
                array_pop(Panel::$callStack);
                return $rendered;
            }
            array_pop(Panel::$callStack);
            return null;
        }

        public static function isDefined($panel, $dir=null){
            if($dir == null) $dir = PageRenderer::$panel_directory;
            $panel_location = $dir.$panel.'.'.Panel::$fileExtension;
            PageRenderer::log('Attempting to load panel('.$panel_location.')');
            return file_exists($panel_location);
        }

        public static function directoryForSilo($silo){ //silos were a terrible idea
            if($silo == '_root_' || $silo == ''){
                return PageRenderer::$root_directory;
            }else{
                return PageRenderer::$root_directory.'Silos/'.implode('/Silos/', explode('/', $silo));
            }
        }

        public static function route($panel, $dir=null){ //consult the compiled routes table
            if($panel == '' || strtolower($panel) == 'index') return false;
            if($dir == null) $dir = PageRenderer::$root_directory;
            $routes_location = $dir.'routes.conf';
            $conf = parse_ini_file($routes_location, true);
            if(!$conf) Logger::log('Error loading routing file:\''.$routes_location.'\'');
            else Logger::log('Loaded routing file:\''.$routes_location.'\'');
            foreach($conf as $silo => $rules){
                foreach($rules as $selector => $replacement){
                    //try{
                    if(strtolower($selector) == '%'){
                        if(Panel::isDefined($panel)) continue;
                        else $selector = '*';
                    }
                    $count = 1;
                    if(strpos($selector, ':')){ //if there's a colon, check if we're a range
                        if(preg_match('~^(.*):([ ,0-9]*)$~', $selector, $matches)){
                            //echo('aaa:');
                            $selector = '(['.$matches[1].']{'.$matches[2].'})';
                            //print_r($matches);
                            //exit();
                        }
                    }
                    $matched = false;
                    $selector = preg_replace('~\*~', '(.*)', $selector);
                    if($replacement == '*'){
                        $replacement = '$1';
                    }else{
                        while($pos = strpos($replacement, '*')){
                            $replacement = substr($replacement, 0, $pos).'${'.$count.'}'.substr($replacement, $pos+1, strlen($replacement)-($pos+1));
                            $count++;
                        }
                    }
                    //Logger::log($panel.' > preg_replace(\'~^'.$selector.'$~\', '.$replacement.', '.$panel.'); ->');
                    $selector = preg_replace('~\#~', '([0-9]+)', $selector);
                    if($replacement == '#'){
                        $replacement = '$1';
                    }else{
                        while($pos = strpos($replacement, '#')){
                            $replacement = substr($replacement, 0, $pos).'${'.$count.'}'.substr($replacement, $pos+1, strlen($replacement)-($pos+1));
                            $count++;
                        }
                    }
                    //todo: support '@' for mongo IDs/uuids
                    //}catch
                    if(preg_match('~^'.$selector.'$~', $panel, $matches)){
                        Logger::log('substituted path using routing rule: '.$selector.'<br/>');
                        $panel = preg_replace('~^'.$selector.'$~', $replacement, $panel);
                        $matched = true;
                    }
                    if($matched){
                        return array(
                            'panel' => $panel,
                            'silo' => $silo
                        );
                    }
                }
            }
            return false;
        }
    }
?>
