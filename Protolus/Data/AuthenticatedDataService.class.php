<?php
    abstract class AuthenticatedDataService extends DataService{
        public $requiresUserToAuthenticate = false;
        public $user = null;
        function __construct($url, &$user){
            parent::__construct($url);
            $this->attachUser($user);
        }
        function attachUser(&$user){
            $this->user = $user;
        }
        abstract function isActive();
        abstract function initialize();
        abstract function isInitialized();
        abstract function authenticate();
        abstract function isAuthenticated();
        function canAuthenticate(){
            if($this->requiresUserToAuthenticate){
                if(
                    $this->user != null &&
                    $user = WebApplication::loggedIn() && 
                    $this->user->get('id') == $user->get('id')
                ) return true;
                return false;
            }else{
                return true;
            }
        }
    }