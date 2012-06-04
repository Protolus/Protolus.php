<?php
    class User extends MySQLData{
        public static $fields = array(
            'email',
            'password',
            'company',
            'first_name',
            'last_name',
            'phone',
            'address',
            'city',
            'state',
            'zip',
            'country'
        );

        public static $name = 'users';
        
        public static function login($credentials){
             
        }

        function __construct($id = null, $field = null){
            $this->database = 'mysql_ad_db';
            $this->tableName = self::$name;
            parent::__construct($id, $field);
        }
    }