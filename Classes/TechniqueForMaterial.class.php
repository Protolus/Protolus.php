<?php
    class TechniqueForMaterial extends MySQLData{
        public static $fields = array(
            'material_id',
            'technique_id',
        );

        public static $name = 'technique_for_materials';

        function __construct($id = null, $field = null){
            $this->database = 'tarrpitt_mysql';
            $coll = self::$name;
            if($id != null){
//                parent::__construct($id, $field);
                
            } else { //init new one
                
            }
        }
    }
