<?php
    class Artist extends MySQLData{
        public $fields = array(
            'name',
            'bio',
            'location',
            'production_location',
            'user_id'
        );

        public static $name = 'artists';

        function __construct($id = null, $field = null){
            $this->database = 'tarrpitt_mysql';
            $this->tableName = self::$name;
            if($id != null){
//                parent::__construct($id, $field);
                
            } else { //init new one
                
            }
        }
    }
