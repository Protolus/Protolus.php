<?php
    class Formats{
        public static $registry = array();
        protected static $formatsDirectory = array();
        public static function get($file, $type='conf'){
            if(!array_key_exists($type, Formats::$registry) || !array_key_exists($file, Formats::$registry[$type])){
                throw new Exception('Cannot return that which does not exist! ('.$file.')');
            }else{
                if(is_string(Formats::$registry[$type][$file])){ //the file has been scanned, but not loaded
                    Formats::$registry[$type][$file] = Formats::loadFile(Formats::$registry[$type][$file]);
                }
                return Formats::$registry[$type][$file];
            }
        }
        
        public static function loadFile($file, $type='conf'){
            if(!array_key_exists($type, Formats::$registry)) Formats::$registry[$type] = array();
            $result = false;
            switch($type){
                case 'conf':
                    $result = parse_ini_file($file, true);
                    break;
                /*case 'sconf': //simple conf AKA: single level conf
                    $result = parse_ini_file($file, true);
                    break;*/
                case 'properties':
                    $result = new Properties($file);
                    break;
                case 'json':
                    $result = json_decode(file_get_contents($file), true);
                    break;
                case 'csv':
                    $result = array();
                    if (($handle = fopen($file, "r")) !== FALSE) {
                        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                            $result[] = $data;
                        }
                        fclose($handle);
                    }
                    break;
            }
            return $result;
        }
        
        public static function register($file, $type='conf'){
            if(!array_key_exists($type, Formats::$registry)) Formats::$registry[$type] = array();
            if(file_exists($file)){
                if(is_dir($file)){ //it's a directory 
                    $files = Formats::scan_directory($file, $type);
                    Formats::$registry[$type] = array_merge(Formats::$registry[$type], $files);
                }else{ //it's a simple file
                    //make a name
                    $temp = explode('/', $file);
                    //$temp = end($temp);
                    $temp = explode('.', end($temp));
                    $name = current($temp);
                    Formats::$registry[$type][$name] = Formats::loadFile($file, $type);
                    return true;
                }
            }else{
                throw new Exception('Cannot load '.$file.' as a directory or a file('.$type.')!');
                return false;
            }
        }
        
        public static function scan_directory($directory, $suffixes=null){
            if(!realpath($directory)) return array();
            $dh  = opendir($directory);
            $files = array();
            while (false !== ($filename = readdir($dh))) {
                $file = realpath($directory.'/'.$filename);
                if(!is_file($file)) continue;
                if(isset($suffixes) && ( is_array($suffixes) || is_string($suffixes) ) ){
                    if(is_string($suffixes)) $suffixes = array($suffixes);
                    foreach($suffixes as $suffix){
                        if($suffix == substr(strtolower($filename), strlen($filename) - strlen($suffix))){ //do we end with the substring?
                            $name = substr(strtolower($filename), 0, strlen($filename) - strlen($suffix)-1);
                            // store the path indexed by the basename, to be loaded as needed
                            $files[$name] = $file;
                            continue;
                        }
                    }
                }
            }
            return $files;
        }
    }