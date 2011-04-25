<?php
    class Venue extends MySQLData{
        public static $fields = array(
            'name',
            'description',
            'location',
            'email',
            'phone_number',
            //'vendor_id'
        );

        public static $name = 'collections';

        function __construct($id = null, $field = null){
            $this->database = 'tarrpitt_mysql';
            $coll = self::$name;
            if($id != null){
//                parent::__construct($id, $field);
                
            } else { //init new one
                
            }
        }
    }
