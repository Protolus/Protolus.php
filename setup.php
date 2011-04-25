<?php
    $start_time = Logger::processing_time(); //let's measure page load time
    Logger::$logToPHPErrorLog = true; //let's log app events into the PHP error log
    
    function currentUser($force = true){
        if($force == 'approved'){
            $requireApproved = true;
            $force = true;
        }
        if(Furgraph::$testUser) return Furgraph::$testUser;
        if($user = WebApplication::getSession('user')){ // first, try to get the user from the session
            //if we've been bounced, jump to the last place we were bounced from now that we're logged in
            if($location = WebApplication::getSession('bounce_location')){
                WebApplication::setSession('bounce_location');
                WebApplication::redirect($location);
            }
        }else{ //no record of the current user
        }
        if($requireApproved){ //bounce unapproved users
            if(! (
                $user && 
                array_key_exists('flags', $user) && 
                array_key_exists('approved', $user['flags']) && 
                $user['flags']['approved']
            ) ){
                WebApplication::setSession('bounce_location', WebApplication::url());
                WebApplication::redirect('/'); //enjoy the curb
            }
        }
        if($user){
            WebApplication::setSession('user', $user);
            WebApplication::setCache($user['user_id'].'_session', WebApplication::getCookie(Session::$sessionID)); //always set memcache to associate session & user
        }
        else if($force === true){ //if we're in force mode and there's no user, boot em
            WebApplication::setSession('bounce_location', WebApplication::url());
            WebApplication::redirect('/');
        }
        PageRenderer::$core_data['user'] = $user;
        return $user;
    }