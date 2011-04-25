<?php
    class PieceComposedOfMaterial extends MySQLData{
        public static $fields = array(
            'piece_id',
            'material_id',
        );

        public static $name = 'technique_composed_of_materials';

        function __construct($id = null, $field = null){
            $this->database = 'tarrpitt_mysql';
            $coll = self::$name;
            if($id != null){
//                parent::__construct($id, $field);
                
            } else { //init new one
                
            }
        }
    }
