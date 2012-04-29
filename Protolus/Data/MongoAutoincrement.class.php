<?php
    // ********************************************************************************
    // * PHP Mongo Autoincrement 
    // ********************************************************************************
    // created by      : Abbey Hawk Sparrow
    // initialize with : MongoAutoincrement::$db = $your_MongoDB_object;
    // usage           : new MongoAutoincrement('identifier')
    // inspired by     : http://shiflett.org/blog/2010/jul/auto-increment-with-mongodb
    // ********************************************************************************
    class MongoAutoincrement{
        public static $db = null;
        protected $internalDB = null;
        protected $type = null;
        protected $value = null;
        
        function __construct($type, $db=null){
            //todo: investigate using a backtrace and introspection to try and determine the type (here *or* toString)
            //todo: try to optimize by doing block allocation if we detect a large number of constructions
            if($db != null) $this->internalDB = $db;
            $this->type = $type;
        }
        
        function __toString(){
            if($this->value == null){
                try{
                    $db = null;
                    if(MongoAutoincrement::$db != null) $db = MongoAutoincrement::$db;
                    if($this->internalDB != null) $db = $this->internalDB;
                    if($db == null) throw new Exception('no database linked to MongoAutoincrement! set it to your MongoDB object using MongoAutoincrement::$db');
                    //update and increment atomicly
                    $result = MongoAutoincrement::$db->command(array(
                        'findandmodify' => 'autoincrement_values',
                        'query' => array('_id' => $this->type),
                        'update' => array('$inc' => array(
                            'count' => 1
                        )),
                        'new' => true
                    ));
                    if(array_key_exists('errmsg', $result)) throw new Exception($result['errmsg']);
                    $this->value = $result['value']['count'];
                }catch(Exception $ex){
                    // Initialize this counter
                    if( $ex->getMessage() == 'No matching object found' ){
                        $collection = MongoAutoincrement::$db->autoincrement_values;
                        $collection->insert(array(
                            '_id' => $this->type,
                            'count' => 1
                        ));
                        $this->value = 1;
                    }
                }
            }
            return is_numeric($this->value)?(string)$this->value:'';
        }
    }