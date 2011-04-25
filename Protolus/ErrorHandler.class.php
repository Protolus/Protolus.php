<?php

require_once('Lib/dBug.php');
//require_once('./classes/Encoder.class.php');

class ErrorHandler{

    public static $currentWindowSize = 10;
    public static $historicalWindowSize = 10;
    public static $newErrorTimeoutInHours = 24;
    public static $digestCycleInHours = 24;
    public static $filePath = './Errors/';
    public static $lineEnding = "<br/>\n";
    public static $debug = false;
    public static $enableFatalCatch = true;
    
    public static function startup(){
        global $conf;
        ErrorHandler::$filePath = $conf['debug']['log_directory'];
        //make sure we can clean the buffer later
        ob_start();
        //enable logging to a temp file
        Logger::$logToTempFile = true;
        //catch normal errors
        set_error_handler(array("ErrorHandler", "error"));
        //catch fatal errors
        register_shutdown_function(array("ErrorHandler", "shutdown"));
    }
    
    function shutdown(){
        $error = error_get_last();
        //if the output is fatal, we're still going to catch it
        $page = ob_get_contents();
        if(ErrorHandler::$enableFatalCatch && $error['type'] == 1 && preg_match('~<b>Fatal error</b>:~', $page)){
            ErrorHandler::error($error['type'], $error['message'], $error['file'], $error['line'], $errcontext, false);
        }else{
        //dump the page
        ob_end_flush();
        }
        Logger::shutdown();
    }
    
    public static function getLastDigestTime($current_time){
        $secondsInADay = 24 * 60 * 60;
        $digestCycleTime = ErrorHandler::$digestCycleInHours * 60 * 60;
        $dayStartTime = strtotime(date('Y-m-d', $current_time - $digestCycleTime).' 00:00:00');
        $numberTests = ($secondsInADay/$digestCycleTime) + 1;
        $now = time();
        for($lcv=1; $lcv < $numberTests+1; $lcv++){
            // if the next iteration of the test is past now, return the last one
            $thisPotentialStartTime = $dayStartTime + ($digestCycleTime * $lcv);
            if($thisPotentialStartTime > $now) {
                return $dayStartTime + ($digestCycleTime * ($lcv-1) );
            }
        }
    }
    
    public static function makeDigestEntry($errorObject){
        $hash = $errorObject->get('error_hash');
        $message = 'Error Entry #'.$errorObject->get('id').', <a href="www.reputationdefender.com/optimus/error.php?hash='.$errorObject->get('error_hash').'">link</a>'."\n";
        $message .= ErrorHandler::getErrorText($hash);
        return $message;
    }
    
    public static function buildStackTrace($indent =''){
        $backtrace = debug_backtrace();
        print_r($backtrace);
        $result='';
        for($lcv=0; $lcv<count($backtrace)+1; $lcv++){
            $trace = $backtrace[$lcv];
            $lastTrace = $backtrace[$lcv-1];
            //get class/function information
            if(isset($lastTrace)){
                if($lastTrace['class']) $location = $lastTrace['class'].'.'.$lastTrace['function'];
                else $location = $lastTrace['function'];
            }else{
                $location = 'main script block';
            }
            
            //get the file information
            if(isset($trace)){
                $fileParts = explode('/', $trace['file']);
                $file = $fileParts[count($fileParts)-1].':'.$trace['line'];
            }else{
                $fileParts = explode('/', $_SERVER['SCRIPT_FILENAME']);
                $file = $fileParts[count($fileParts)-1];
            }
            
            //write trace line
            if($trace['class'] != 'ErrorHandler') 
                $result .= $indent.$location.'('.$file.')'.ErrorHandler::$lineEnding;
        }
        return $result;
    }
    
    public static function error($errno, $errstr, $errfile, $errline, $errcontext, $exitOnFinish=true){
        global $working_directory;
        switch ($errno){
            case E_USER_WARNING:
            case E_USER_NOTICE:
            case E_WARNING:
            case E_NOTICE:
            case E_CORE_WARNING:
            case E_COMPILE_WARNING:
             break;
            case E_USER_ERROR:
            case E_ERROR:
            case E_PARSE:
            case E_CORE_ERROR:
            case E_COMPILE_ERROR:
            case E_RECOVERABLE_ERROR:
            
                // check to see if the error is a MySQL error 
                if (preg_match('~^(sql)$~', $errstr)) {
                    $MYSQL_ERRNO = mysql_errno();
                    $MYSQL_ERROR = mysql_error();
                    $mysqlerror = "Additionally MySQL reported error# $MYSQL_ERRNO : $MYSQL_ERROR";
                }
                
                $fileParts = explode('/', $errfile);
                $file = $fileParts[count($fileParts)-1].':'.$trace['line'];
                
                /* build the error string. */
                $message = "Error #".$errno.": '".$errstr."' in ".$file." on line ".$errline.ErrorHandler::$lineEnding;
                if ($mysqlerror) $message .= $mysqlerror.ErrorHandler::$lineEnding;
                
                // generate a stack trace
                $message .= 'Stack Trace:<ul>'.ErrorHandler::buildStackTrace('<li>').'</ul>'.ErrorHandler::$lineEnding;
                $hash = md5($message);
                
                //get current log
                $log = ErrorHandler::getErrorLogText();
                dBug::$bufferOutput = true;
                $debugger = new dBug($errcontext, '', true);
                $backtrace = debug_backtrace();
                unset($backtrace[0]);
                $trace_debugger = new dBug($backtrace, '', true);
                $scope = 'Context:'.$debugger->buffer."<br/>\n".'Backtrace:'.$trace_debugger->buffer;
                
                
                //eat the current contents of the buffer
                $bufferContent = ob_get_contents();
                ob_clean();
                //dump the error page
                $values['hash'] = $hash;
                $values['message'] = $message;
                if(preg_match('~SQL Error:Table \'datalus\.(.*?)\'~', $message, $matches)){
                    $values['message'] = 'There was an error: your '.$matches[1].' table seems to be missing.';
                    $sqlFileName = $working_directory.'/sql/'.$matches[1].'.sql';
                    if(file_exists($sqlFileName)){
                        $values['message'] .= ' A default SQL definition for this table was found.';
                        $sql = file_get_contents($sqlFileName);
                        try{
                            Database::executeSQL($sql);
                            $values['message'] .= ' The table was successfully added to the database, a subsequent reload should fix the problem';
                        }catch(Exception $ex){
                            $values['message'] .= ' A table load was attempted, but was not successful.';
                        }
                    }else{
                        $values['message'] .= ' A default SQL definition for this table was not found at "'.$sqlFileName.'".';
                    }
                }
                if(!ErrorHandler::$debug){
                    try{
                        if (function_exists('checkLogin') && checkLogin() == "admin") $hashText = '<a href="optimus/error.php">'.$hash.'</a>';
                        else $hashText = $hash;
                        ErrorHandler::registerError($message);
                        ErrorHandler::saveFile($hash, $log, 'log');
                        ErrorHandler::saveFile($hash, $scope, 'scope');
                        ErrorHandler::saveFile($hash, $bufferContent, 'content');
                    }catch(Exception $ex){
                        $message = $ex->getMessage();
                        if(preg_match('~SQL Error:Table~', $message)) $additionalError = '<br /><br />Additionally, The error SQL structure seems to be missing.';
                    }
                    echo substitute($working_directory.'/'.'./Templates/error.template', $values, true);
                }else{
                    echo($message);
                    echo("[Error Log]************************************************************<br/>\n");
                    echo($log);
                    echo("[Error Scope]**********************************************************<br/>\n");
                    echo($scope);
                    echo("[Page Content]*********************************************************<br/>\n");
                    echo($bufferContent);
                }
                if($exitOnFinish) exit();
            default:
             break;
        } // switch
    } // errorHandler
    
    public static function dumpError($hash){
        echo(ErrorHandler::getErrorText($hash,'error'));
        echo("[Error Log]************************************************************<br/>\n");
        echo(ErrorHandler::getErrorText($hash,'log'));
        echo("[Error Scope]**********************************************************<br/>\n");
        echo(ErrorHandler::getErrorText($hash,'scope'));
        echo("[Page Content]*********************************************************<br/>\n");
        echo(ErrorHandler::getErrorText($hash,'content'));
    }

    
    public static function registerError($message, $current_time=null){
        /*if($current_time == null) $current_time = time();
        $hash = md5($message);
        $error = new RDError();
        $errors = $error->getObjects("error_hash='".$hash."'", "initial_date desc", 'limit 1');
        $newErrorTimeout = $current_time - ErrorHandler::$newErrorTimeoutInHours * 60 * 60;
        if(count($errors) == 0 || strtotime($errors[0]->get('initial_date')) < $newErrorTimeout){
            //this is a new error
            ErrorHandler::notify('A new error has occured', '<a href="www.reputationdefender.com/optimus/error.php?hash='.$hash.'">link</a>'."\n".$message);
        }
        //now we check and see if we need to process a digest
        $digestCycleTime = ErrorHandler::$digestCycleInHours * 60 * 60;
        $digestCutoffDate = date( 'Y-m-d H:i:s', $current_time - $digestCycleTime);
        // 1) get 0 hour of current day
        $dayStartTime = strtotime(date('Y-m-d', $digestCutoff).' 00:00:00');
        $digestCycleTime = ErrorHandler::$digestCycleInHours * 60 * 60;
        // 2) get last time we would have expected a digest to be sent
        $lastDigestTime = ErrorHandler::getLastDigestTime($current_time);
        $nextDigestTime = $lastDigestTime + ErrorHandler::$digestCycleInHours * 60 * 60;
        $nextNextDigestTime = $nextDigestTime + ErrorHandler::$digestCycleInHours * 60 * 60;
        // 3) get the most recent error entry
        $errors = $error->getObjects('', "initial_date desc", 'limit 1');
        if(count($errors) > 0) $lastRecordedErrorDate = strtotime($errors[0]->get('initial_date'));
        else $lastRecordedErrorDate = 0;
        // 4) see if the most recent entry date is greater than the last digest moment
        if(count($errors) > 0 && $lastDigestTime > $lastRecordedErrorDate){
        // 5) if it is, create digest and then mail it (select all errors since digest moment before last, to the last one)
            $digestErrors = $error->getObjects("initial_date > '".$digestCutoffDate."'", "initial_date desc");
            $digestBody = '';
            foreach($digestErrors as $digestError){
                //this code does not yet group errors, which it should
                $digestBody .= ErrorHandler::makeDigestEntry($digestError);
            }
            ErrorHandler::notify('[Error Digest]', $digestBody);
        }
        $error->set('initial_date', date( 'Y-m-d H:i:s' ));
        $error->set('error_hash', $hash);
        $error->save();
        ErrorHandler::saveFile($hash, $message);
        ErrorHandler::cleanUp($hash);
        */
    }
    
    public static function notify($subject, $message){
        global $config;
        //send an email here
        global $emailOnError;
        if($emailOnError == 'on'){
            /* To send HTML mail, you can set the Content-type header. */
            $headers  = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/html; charset=iso-8859-1\r\n";
        
            /* additional headers */
            $headers .= "From: ReputationDefender <noreply@reputationdefender.com>\r\n";
            
            $names = explode(',', $config->getProperty('error_notification_email_addresses'));
            foreach($names as $name){
                $email = trim($name);
                mail($email, $subject, $message, $headers);
            }
        }
        //todo: add email code
    }
    
    public static function getErrorText($hash, $type = 'error'){
        $fileName = $hash.'.'.$type;
        $crypto = new Encoder();
        if(!file_exists(ErrorHandler::$filePath.$fileName)) return('file ('.ErrorHandler::$filePath.$fileName.') does not exist.'."<br/>\n");
        $handle = fopen(ErrorHandler::$filePath.$fileName, 'r');
        $contents = fread($handle, filesize(ErrorHandler::$filePath.$fileName));
        fclose($handle);
        return $crypto->decrypt($contents);
    }
    
    public static function buildEnvironmentScope($environment){
    
    }
    
    public static function getErrorLogText(){
        return Logger::getTempLogContent();
    }
    
    public static function saveFile($hash, $message, $type = 'error'){
        $fileName = $hash.'.'.$type;
        $crypto = new Encoder();
        $fileBody = $crypto->encrypt($message);
        $handle = fopen(ErrorHandler::$filePath.$fileName, 'w');
        fwrite($handle, $fileBody);
        fclose($handle);
    }
    
    public static function cleanUp($hash, $now=null){
        if($now==null) $now = time();
        $error = new RDError();
        $errors = $error->getObjects("error_hash='".$hash."'", "initial_date desc", 'limit '.ErrorHandler::$currentWindowSize.', '.(ErrorHandler::$historicalWindowSize+100));
        $numberToEliminate = count($errors)-ErrorHandler::$historicalWindowSize;
        if($numberToEliminate < 0) $numberToEliminate = 0;
        for($lcv = 0; $lcv < $numberToEliminate; $lcv++){
            $bestScore = 0;
            $bestScorePosition = 0;
            for($errorPos = 0; $errorPos < count($errors); $errorPos++){
                if($errorPos+1 < count($errors)){
                    if($errors[$errorPos]==null || $errors[$errorPos+1]==null) {
                        if($errors[$errorPos]==null) unset($errors[$errorPos]);
                        if($errors[$errorPos+1]==null) unset($errors[$errorPos+1]);
                    }else{
                        $score = ErrorHandler::scoreTwoEvents($errors[$errorPos], $errors[$errorPos+1], $now);
                        if($score > $bestScore){
                            $bestScore = $score;
                            $bestScorePosition = $errorPos;
                        }
                    }
                }
            }
            $thisError = $errors[$bestScorePosition];
            $nextError = $errors[$bestScorePosition+1];
            if($thisError->get('conclusion_date') > $nextError->get('conclusion_date') || $nextError->get('conclusion_date') == null){
                if($thisError->get('conclusion_date') != null){
                    $nextError->set('conclusion_date', $thisError->get('conclusion_date'));
                }else{
                    $nextError->set('conclusion_date', $thisError->get('initial_date'));
                }
            }
            if($thisError->get('initial_date') < $nextError->get('initial_date')){
                $nextError->set('initial_date', $thisError->get('initial_date'));
            }
            if($thisError->get('count') > 0) $nextError->set('count', $thisError->get('count'));
            else $nextError->set('count', $nextError->get('count')+1);
            $nextError->save();
            $thisError->delete();
            unset($errors[$bestScorePosition]);
        }
    }
    
    public static function scoreTwoEvents($firstEvent, $secondEvent, $now=null){
        if($now==null) $now = time();
        
        $first_start_date = strtotime( $firstEvent->get('initial_date') );
        $first_stop_date = strtotime( $firstEvent->get('conclusion_date') );
        if($first_stop_date != 0) $first_width = $first_stop_date - $first_start_date;
        else $first_width = 0;
        $first_distance = $now - $first_start_date;
        
        $second_start_date = strtotime( $secondEvent->get('initial_date') );
        $second_stop_date = strtotime( $secondEvent->get('conclusion_date') );
        if($second_stop_date != 0) $second_width = $second_stop_date - $first_start_date;
        else $second_width = 0;
        $second_distance = $now - $second_start_date;
        
        $comparative_distance = (abs($first_distance - $second_distance)+1);
        //echo('comparative_distance:'.$comparative_distance.'first_distance:'.$first_distance.'second_distance:'.$second_distance."<br/>\n");
        
        $final_score = ($first_distance + $second_distance + $first_width + $second_width) / $comparative_distance;
        return $final_score;
    }
}
?>