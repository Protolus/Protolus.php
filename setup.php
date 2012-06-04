<?php
    $start_time = Logger::processing_time(); //let's measure page load time
    //Logger::$logToPHPErrorLog = true; //let's log app events into the PHP error log
    //Logger::$logToUser = true;
    
    
    function currentUser($force = true){
        if(WebApplication::getGet('force_login') || WebApplication::getGet('fl')) $force = true;
        if($user = WebApplication::getSession('user')){ // first, try to get the user from the session
            WebApplication::setSession('user', $user);
            //if we've been bounced, jump to the last place we were bounced from now that we're logged in
            if($location = WebApplication::getSession('bounce_location')){
                WebApplication::setSession('bounce_location');
                WebApplication::redirect($location);
            }
        }else if($force === true){ //if we're in force mode and there's no user, boot em
            WebApplication::setSession('bounce_location', WebApplication::url());
            WebApplication::redirect('/');
        }
        PageRenderer::$core_data['user'] = $user;
        return $user;
    }