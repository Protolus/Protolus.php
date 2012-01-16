<?php
    class User extends MySQLData{
        public static $fields = array(
            'id',
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
            if($credentials['username'] && $credentials['password']){
                $results = MySQLData::executeSQL('select * from users where username = \''.$credentials['username'].'\' and password =\''.md5($credentials['password']).'\'');
                if(count($results) > 0){
                    $user = new User();
                    $user->data = $results[0];
                    return $user;
                }
            }else if($credentials['email'] && $credentials['password']){
                $results = MySQLData::executeSQL('select * from users where email = \''.$credentials['email'].'\' and password =\''.md5($credentials['password']).'\'');
                if(count($results) > 0){
                    $user = new User();
                    $user->data = $results[0];
                    Session::set('current_user_id', $user->get('id'));
                    return $user;
                }
            }else{
                throw new Exception('Unsupported credentials('.print_r(array_keys($credentials), true).')');
            }
        }
        }

        function __construct($id = null, $field = null){
            $this->database = 'mysql_ad_db';
            $this->tableName = self::$name;
            parent::__construct($id, $field);
        }
    }