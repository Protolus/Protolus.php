<?php
    class Endpoint{
        protected static $registry = array();
        protected $data = array();
        protected $name = '';
        
        public function __construct($name, $silo=null){
            //todo: load endpoint definition
        }
        
        public static function get($name){
            if(!array_key_exists($name, Endpoint::$registry)){
                 Endpoint::$registry[$name] = new Endpoint($name);
            }
            return Endpoint::$registry[$name];
        }
        
        public function has($field, $value=null){ // Search composite field value, -or- test is 'value exists'
            if($value == null){
                return array_key_exists($field, $this->data);
            }else{
                $values = explode($data['output']);
                array_map('trim', $values);
                return in_array($value, $values);
            }
        }
        
        public function is($field, $value){ // Compare full field value
            return isset($this->data[$field]) && $this->data[$field] == $value;
        }
    }
?>