<?php
    class ResourceBundle{
        public static $packages = null;
        public static $minify = true;
        public static $integratedMinify = true;
        public static $merge = true;
        public static $hardcodedVersion = '101';
        public static $resourcePackager = false;
                  
        
        public $resources = null;
        public $dependencies = null;
        public $html = null;
        public $initializers = null;
        public $name = '';
        
        public static function startup(){
        
        }
        
        public static function combineAllResources(){
            $allResources = array();
            foreach(ResourceBundle::$packages as $name => $component){
                foreach($component as $item){
                    $allResources[] = '/Resources/'.$name.'/'.$item;
                }
            }
            if(ResourceBundle::$integratedMinify){
                $resourceString = implode('-', ResourceBundle::resources());
                if(ResourceBundle::$minify){
                    return '<link origin="protolus" resource="'.$resourceString.'" rel="stylesheet" href="/style/min/'.$resourceString.'?version='.ResourceBundle::$hardcodedVersion.'" type="text/css" />
    <script origin="protolus" resource="'.$resourceString.'" type="text/javascript" src="/javascript/min/'.$resourceString.'?version='.ResourceBundle::$hardcodedVersion.'"></script>'."\n";
                }else{
                    return '<link origin="protolus" resource="'.$resourceString.'" rel="stylesheet" href="/style/'.$resourceString.'?version='.ResourceBundle::$hardcodedVersion.'" type="text/css" />
    <script origin="protolus" resource="'.$resourceString.'" type="text/javascript" src="/javascript/'.$resourceString.'?version='.ResourceBundle::$hardcodedVersion.'"></script>'."\n";
                }
            }else{
                return ResourceBundle::packageResources(ResourceBundle::allResourceItems(true), ResourceBundle::resources());
            }
        }
        
        public static function resources($fullPath=false){
            return array_keys(ResourceBundle::$packages);
        }
        
        public static function allResourceItems($fullPath=false){
            $allResources = array();
            foreach(ResourceBundle::$packages as $name => $component){
                $allResources = array_merge($allResources, $component->resourceItems($fullPath));
            }
            return $allResources;
        }
        
        public static function packageResources($resources, $names){ //load from head
            if(is_array($names)) $names = implode(',', $names);
            $include = '';
            if(ResourceBundle::$minify){
                $paths = array();
                $styles = array();
                foreach($resources as $resource){
                    $parts = explode('.', $resource);
                    $end = end($parts);
                    $type = strtolower(trim($end));
                    if($type == 'js') $paths[] = $resource;
                    else $styles[] = $resource;
                }
                if(ResourceBundle::$resourcePackager){
                    $names = explode(',', $names);
                    foreach($names as $name){
                        $include .= '<link origin="protolus" resource="'.$name.'" rel="stylesheet" href="/style/min/'.$name.'?version='.ResourceBundle::$hardcodedVersion.'" type="text/css" />
                        <script origin="protolus" resource="'.$resourceString.'" type="text/javascript" src="/javascript/min/'.$name.'?version='.ResourceBundle::$hardcodedVersion.'"></script>'."\n";
                    }
                }else{
                    if(count($paths) == 0) throw new Exception('no resources to include for '.$names.' : '.print_r($resources, true));
                    if(count($paths) == 1){
                        $include .= '<script origin="protolus" resource="'.$names.'" type="text/javascript" src="/min/?f='.$paths[0].'&amp;version='.ResourceBundle::$hardcodedVersion.'"></script>'."\n";
                    }else{
                        $commonPrefix = ResourceBundle::commonPath($paths);
                        $cleanPaths = array();
                        foreach($paths as $path) $cleanPaths[] = substr($path, strlen($commonPrefix));
                        $include .= '<script origin="protolus" resource="'.$names.'" type="text/javascript" src="/min/?f='.implode(',', $paths).'&amp;version='.ResourceBundle::$hardcodedVersion.'"></script>'."\n";
                    }
                    if(count($styles) != 0){
                        if(count($styles) == 1){
                            $include .= '<link origin="protolus" resource="'.$names.'" rel="stylesheet" href="/min/?f='.$styles[0].'&amp;version='.ResourceBundle::$hardcodedVersion.'" type="text/css" />'."\n";
                        }else{
                            $commonPrefix = ResourceBundle::commonPath($styles);
                            $cleanPaths = array();
                            foreach($styles as $path) $cleanPaths[] = substr($path, strlen($commonPrefix));
                            $include .= '<link origin="protolus" resource="'.$names.'" rel="stylesheet" href="/min/?b='.substr($commonPrefix, 1,-1).'&amp;f='.implode(',', $cleanPaths).'&amp;version='.ResourceBundle::$hardcodedVersion.'" type="text/css" />'."\n";
                        }
                    }
                }
            }else{
                foreach($resources as $path){
                    $parts = explode('.', $path);
                    $end = end($parts);
                    $type = strtolower($end);
                    if(file_exists($path.'.min')) $path = $path.'.min'; //todo: handle this in merge mode (above)
                    switch($type){
                        case 'js':
                            $include .= '<script origin="protolus" resource="'.$names.'" type="text/javascript" src="'.$path.'?version='.ResourceBundle::$hardcodedVersion.'"></script>'."\n";
                            break;
                        case 'css':
                            $include .= '<link origin="protolus" resource="'.$names.'" rel="stylesheet" href="'.$path.'?version='.ResourceBundle::$hardcodedVersion.'" type="text/css" />'."\n";
                            break;
                    }
                }
            }
            return $include;
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
        
        public static function commonPath($stringArray){
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
        
        public function resourceItems($fullPath=false){
            $result = array();
            foreach($this->resources as $name => $component){
                //foreach($component as $item){
                    if($fullPath){
                        $result[$name] = '/Resources/'.$this->name.'/'.$component;
                    }else{
                        $result[$name] = $component;
                    }
                //}
            }
            //print_r($result);
            return $result;
        }
        
        public function preloadResources(){ //load from head
            $include = '<!-- [RESOURCE:'.$this->name.'] -->'."\n";
            if($integratedMinify){
                    $include .= '<script origin="protolus" resource="'.$this->name.'" type="text/javascript" src="/javascript/'.$this->name.'?version='.ResourceBundle::$hardcodedVersion.'"></script>'."\n";
            }else{
                $include .= $this->packageResources($this->resourceItems(true), $this->name);
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