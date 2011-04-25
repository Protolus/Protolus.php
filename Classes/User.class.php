<?php
    class User extends MysqlData{
        public static $fields = array(
            'id',
            'email',
            'facebook_credentials',
            'username',
            'first_name',
            'last_name',
            'gender',
            'birthdate',
            'location'
        );
        public static $name = 'users';
        function __construct($id = null, $service = null){
            $this->database = 'tarrpitt_mysql';
            if($id != null){
                parent::__construct($id);
            }
        }
    }
