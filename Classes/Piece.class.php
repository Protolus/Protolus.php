<?php
    class Piece extends MySQLData{
        public static $fields = array(
            'name',
            'description',
            'design_date',
            'production_date',
            'design_location',
            'production_location',
            'artist_id',
            'price'
        );

        public static $name = 'materials';

        function __construct($id = null, $field = null){
            $this->database = 'tarrpitt_mysql';
            $coll = self::$name;
            if($id != null){
//                parent::__construct($id, $field);
                
            } else { //init new one
                
            }
        }
    }
