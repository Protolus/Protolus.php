<?php
    class Technique extends MySQLData{
        public static $fields = array(
            'name',
            'wikipedia_name',
            'description',
            'cost_modifier',
            'environmental_impact_text',
            'environmental_impact_rating',
            'non_vegan_flag',
            'allergens',
            'shipping_delay',
        );

        public static $name = 'techniques';

        function __construct($id = null, $field = null){
            $this->database = 'tarrpitt_mysql';
            $coll = self::$name;
            if($id != null){
//                parent::__construct($id, $field);
                
            } else { //init new one
                
            }
        }
    }
