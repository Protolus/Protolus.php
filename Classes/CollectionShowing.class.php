<?php
    class Collection extends MySQLData{
        public static $fields = array(
            'name',
            'description',
            'opening_date',
            'closing_date',
            'venue_id'
        );

        public static $name = 'collectionshowings';

        function __construct($id = null, $field = null){
            $this->database = 'tarrpitt_mysql';
            $coll = self::$name;
            if($id != null){
//                parent::__construct($id, $field);
                
            } else { //init new one
                
            }
        }
    }
