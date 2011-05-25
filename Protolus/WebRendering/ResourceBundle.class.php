<?php
    class ResourceBundle{
        public static $packages = null;
        public static $minify = true;
        
        public $resources = null;
        public $dependencies = null;
        public $html = null;
        public $initializers = null;
        public $name = '';
        
        public static function startup(){
        
        }
        
        public function __construct($name, $options){
            $this->resources = array();
            $this->initializers = array();
            $this->dependencies = array();
            $this->resources = array();
            if(array_key_exists('initialize', $options)) foreach($options['initialize'] as $option){
                $this->initializers[] = $option;
            }
            if(array_key_exists('resource', $options)) foreach($options['resource'] as $option){
                $this->resources[] = $option;
            }
            if(array_key_exists('dependency', $options)) foreach($options['dependency'] as $option){
                $this->dependencies[] = $option;
                //try{
                    if(!array_key_exists($option, ResourceBundle::$packages)){
                        new ResourceBundle($option, Formats::loadFile('Resources/'.$option.'/component.conf', 'conf'));
                        PageRenderer::$component_registry[] = $option;
                    }
                //}catch(Exception $ex){
                //}
            }
            if(array_key_exists('html', $options)) foreach($options['html'] as $option){
                $this->html[] = $option;
            }
            $this->name = $name;
            ResourceBundle::$packages[$this->name] = $this;
        }
        
        public function commonPath($stringArray){
            $longest = '';
            if(count($stringArray) == 0) return false;
            $parts = explode('/', $stringArray[0]);
            $combinedParts = '';
            foreach($parts as $part){
                $combinedParts .= $part.'/';
                foreach($stringArray as $item) if($combinedParts != substr($item, 0, strlen($combinedParts))) break(2);
                $longest = $combinedParts;
            }
            return $longest;
        }
        
        public function preloadResources(){ //load from head
            $include = '<!-- [RESOURCE:'.$this->name.'] -->'."\n";
            if(ResourceBundle::$minify){
                $paths = array();
                foreach($this->resources as $resource) $paths[] = '/Resources/'.$this->name.'/'.$resource;
                //print_r($this->resources); print_r($paths); echo('['.$this->name.']'); exit();
                if(count($paths) == 0) throw new Exception('no resources to include for '.$this->name.' : '.print_r($this, true));
                if(count($paths) == 1){
                    $include .= '<script origin="protolus" type="text/javascript" src="/min/?f='.$paths[0].'&amp;version=3"></script>'."\n";
                }else{
                    $commonPrefix = $this->commonPath($paths);
                    $cleanPaths = array();
                    foreach($paths as $path) $cleanPaths[] = substr($path, strlen($commonPrefix));
                    $include .= '<script origin="protolus" type="text/javascript" src="/min/?b='.substr($commonPrefix, 1,-1).'&amp;f='.implode(',', $cleanPaths).'&amp;version=1"></script>'."\n";
                }
            }else{
                foreach($this->resources as $resource){
                    $parts = explode('.', $resource);
                    $end = end($parts);
                    $type = strtolower($end);
                    $path = '/Resources/'.$this->name.'/'.$resource;
                    if(file_exists($path.'.min')) $path = $path.'.min';
                    switch($type){
                        case 'js':
                            $include .= '<script origin="protolus" type="text/javascript" src="'.$path.'?version=1"></script>'."\n";
                            break;
                        case 'css':
                            $include .= '<link origin="protolus" rel="stylesheet" href="'.$path.'?version=1" type="text/css" />'."\n";
                            break;
                    }
                }
            }
            $include .= '<!-- [/RESOURCE:'.$this->name.'] -->'."\n";
            return $include;
        }
        public function postloadResources(){ //load from body
            foreach($this->resources as $resource){
                $include = '';
                $type = strtolower(end(explode('.', $resource)));
                $path = '/Resources/'.$type.'/'.$resource;
                if(file_exists($path.'.min')) $path = $path.'.min';
                switch($type){
                    case 'js':
                        $include .= 'Asset.javascript(\''.$path.'\');'."\n"; //todo: handle completion
                        break;
                    case 'css':
                        $include .= 'Asset.css(\''.$path.'\');'."\n";
                        break;
                }
            }
            return '<!-- [RESOURCE:'.$this->name.'] --><script>'.$include.'</script>';
        }
        public function inlineResources(){ //push into body
            
        }
        public function inlineInit(){
            
        }
        public static function req($component){
            try{
                if(ResourceBundle::$packages == null) ResourceBundle::$packages = array();
                if(!array_key_exists($component, ResourceBundle::$packages)){
                    $load = Formats::loadFile('Resources/'.$component.'/component.conf', 'conf');
                    new ResourceBundle($component, $load);
                    PageRenderer::$component_registry[] = $component;
                }
            }catch(Exception $ex){
                return false;
            }
        }
    }
?>