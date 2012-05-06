<?php
/*********************************************************************************//**
 *  Abbey's Autoloader
 *====================================================================================
 * @author Abbey Hawk Sparrow
 *This autoloader takes care of classloading dynamicly as well as maintaining scope
 *and registration. All you have to do to use it is use the java style _import
 *~~~~~~~~~~~~~{.php}
 * _import('path.to.package.*'); 
 *~~~~~~~~~~~~~
 * or the path based syntax
 *~~~~~~~~~~~~~{.php}
 * Autoloader::register_directory('./path/to/package/'); 
 *~~~~~~~~~~~~~
 *multiple copies of a identically named class can exist, but only one can be
 *loaded during execution, so while this can be helpful when dealing with libraries
 *which have colliding namespaces, it is no panacea.
 *
 *- auto-autoload (other than setting your class folders, you do nothing)
 *- namespace compartmentalization (use the same class name over and over)
 *- shorter class/folder hierarchy traversals (get to the right class faster)
 *- allows inline catching of class load errors (no uncatchable fatals)
 *- maximally optimized awesomeness factor (not really, but it sounds nice)
 *************************************************************************************/

function _import($namespace){ //enforces a naming convention of <class>.classes.php
    $ns_parts = explode('.', $namespace);
    if(end($ns_parts) == '*'){
        unset($ns_parts[key($ns_parts)]);
        $directory = implode('/', $ns_parts);
        Autoloader::register_directory($directory, 1);
    }
}

class Autoloader{
    private static $registry = array();
    private static $file_registry = array();
    private static $file_types = array('.class.php');
    public static $base_dir = '.';
    private static $initialized = false;
    public static $verbose = false;
    
    // the following mode is an experimental setting to shoehorn dummy classes 
    // that can mask multiple collided classes and resolve the linkage by the 
    // calling context, I have personal doubts this will ever be suitable for
    // anything but tests, unless you have a very odd need to sandbox namespaces.
    // it's way inefficient as it is an attempt to autoload *every* instantiation
    // the idea is to define a new class that injects an instance of the fully pathed 
    // class in it's own place, deletes itself and triggers removal of it's own class 
    // definition... sexy, eh?
    private static $class_path_verify_mode = false; //Let me reiterate: this doesn't work yet, DO NOT USE
    
    private static function log($text){
        if(Autoloader::$verbose) echo($text."<br/>\n");
    }
    
    public static function initialize(){
        Autoloader::register_autoloader();
        Autoloader::$base_dir = getcwd();
        Autoloader::$initialized = true;
    }
    
    private static function register_autoloader(){
        spl_autoload_register(array('Autoloader', 'load'));
    }
    
    public static function register_directory($directory, $depth=0){
        if(!Autoloader::$initialized) Autoloader::initialize();
        //examine the stack to get the calling file
        $stacktrace = debug_backtrace();
        if(array_key_exists($depth, $stacktrace) && array_key_exists('file', $stacktrace[$depth])){
            $calling_file = $stacktrace[$depth]['file'];
            Autoloader::log('Registering '.realpath(Autoloader::$base_dir . '/' . $directory).' to '.$calling_file);
            //register the directory to the calling file
            Autoloader::$file_registry[$calling_file][] = $directory;
        }
        //register the directory globally
        if(!in_array($directory, Autoloader::$registry)) Autoloader::$registry[] = $directory;
    }
    
    public static function find_class_definition($directory, $class_name){
        foreach(Autoloader::$file_types as $type){
            $class_path = realpath(Autoloader::$base_dir.'/'.$directory.'/'.$class_name.$type);
            Autoloader::log('Checking for class '.$class_name.' in directory '.$directory.' ('.$class_path.')');
            if(file_exists($class_path)){
                Autoloader::log('Found class '.$class_name.' in directory '.$directory);
                return $class_path;
            }
        }
        return false;
    }

    public static function load($class_name, $depth = 1){
        if(!Autoloader::$initialized) Autoloader::initialize();
        //get the context file we called from
        $stacktrace = debug_backtrace();
        $calling_file = $stacktrace[$depth]['file'];
        //attempt to load from the local context
        $checked_dirs = array();
        if(array_key_exists($calling_file, Autoloader::$file_registry) && is_array(Autoloader::$file_registry[$calling_file])) {
            foreach(Autoloader::$file_registry[$calling_file] as $directory){
                Autoloader::log('Local Seek ['.$directory.']');
                if($definition = Autoloader::find_class_definition($directory, $class_name)){ require_once($definition); return; }
                $checked_dirs[] = $directory;
            }
        }
        //TODO: chain scopes so you have proper scope inheritance (not just local to the calling file)
        // foreach depth we trim one link off the stack, then we walk through the stack. looking for scope
        //attempt to load from the global context
        foreach(Autoloader::$registry as $directory){
            Autoloader::log('Global Seek ['.$directory.']');
            if($definition = Autoloader::find_class_definition($directory, $class_name)){ require_once($definition); return; }
        }
        // uh oh, we can't find the class, we're going to have to return a clean crash-dummy, so we can catch the error
        Autoloader::log('Could not find the class! Creating a dummy.');
        Autoloader::load_text(Autoloader::create_crash_dummy($class_name), $class_name);
    }
    
    public static function load_text($class_text, $class=null, $namespace=null){
        ///*
        eval('?>'.$class_text.'<?php '); // */ 
        //require_once($class_text);
        Autoloader::log('Loaded class definition ['.$class.']');
        if(Autoloader::$class_path_verify_mode && $class != null && $namespace != null){
            $class_with_package_text = preg_replace('~ '.$class.'~', ' '.$namespace.'_'.$class, $class_text);
            eval('?>'.$class_with_package_text.'<?php ');
            Autoloader::log('Loaded package specific class definition ['.$class_with_package_text.']');
        }
    }
    
    public static function create_crash_dummy($class_name){
        return '<?php
            class '.$class_name.'{
                function __construct($a=null, $b=null, $c=null, $d=null, $e=null, $f=null, $g=null, $h=null, $i=null, $j=null, $k=null, $l=null, $m=null, $n=null, $o=null, $p=null){
                    throw new Exception("AUTOLOADER: Class '.$class_name.' not found!");
                }
            }
        ?>';
    }
    
}
