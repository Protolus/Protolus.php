<?php
    class PieceModifier extends MySQLData{
        public static $fields = array(
            'name',
            'description',
            'price_modifier',
            'material_id_list',
            'technique_id_list',
            'removed_material_id_list',
            'removed_technique_id_list',
        );

        public static $name = 'piece_modifier';

        function __construct($id = null, $field = null){
            $this->database = 'tarrpitt_mysql';
            $coll = self::$name;
            if($id != null){
//                parent::__construct($id, $field);
                
            } else { //init new one
                
            }
        }
    }
