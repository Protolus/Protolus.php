<?php
    class Material extends MySQLData{
        public static $fields = array(
            'name',
            'wikipedia_name',
            'category', //metals, gemstones, etc..
            'jewelry_origin',
            'processing_origin',
            'environmental_impact_text',
            'environmental_impact_rating',
            'cultural_impact_text',
            'cultural_impact_rating',
            'non_vegan_flag',
            'allergens',
            'stock_quantity',
            'shipping_delay',
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
