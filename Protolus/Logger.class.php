<?php

class Logger{
    public static $logFile = null;
    public static $logToUser = false;
    public static $logToBuffer = false;
    public static $logToTempFile = false;
    public static $logToPHPErrorLog = false;
    public static $logToSystem = false;
    
    //public for static address, but not intended for use
    public static $tempFile = null;
    public static $tempFileName = null;
    public static $lastTime = null;
    public static $buffer = array();
    public static $EOL = "<br/>\n";
    public static $lastLogStack = null;
    public static $fileHandle = null;
    public static $timestamp = null;
    public static $id = null;
    
    public static function getTraceSummary($trace, $index=0){
        $result = "";
        for($lcv=count($trace)-1; $lcv >= $index; $lcv--){
            $distance = 1;
            if(isset($trace[$lcv]['file'])){
                if($result != "") $result .= "/";
                $fileParts = explode('/', $trace[$lcv]['file']);
                $file = $fileParts[count($fileParts)-1];
                $fileParts = explode('.', $file);
                $result .= $fileParts[0].':'.$trace[$lcv]['line'];
            }
            while( (isset($trace[$lcv-1]) && $trace[$lcv-1]['file'] == $trace[$lcv]['file']) || !isset($trace[$lcv]['file'])){
                $lcv--;
                $result .= ':'.$trace[$lcv]['line'];
            }
        }
        return $result;
    }
    
    public static function log($text, $level=0){
        global $start_time;
        $backtrace = debug_backtrace();
        $index = 0;
        if(Logger::$id == null){
            Logger::$id = uniqid();
            Logger::log('+++ '.Logger::$id.' +++');
        }
        //get class/function information
        if(isset($backtrace[$index-1])){
            if($backtrace[$index-1]['class']) $location = $backtrace[$index-1]['class'].'->'.$backtrace[$index-1]['function'];
            else $location = $backtrace[$index-1]['function'];
        }
        
        //get the file information end(explode('',Logger::$logFile))
        if(isset($backtrace[$index])){
            $fileParts = explode('/', $backtrace[$index]['file']);
            $file = $fileParts[count($fileParts)-1].':'.$backtrace[$index]['line'];
        }
        $thisTime = Logger::processing_time($start_time);
        $textPrefix = '['.number_format($thisTime, 3).'s total, '.number_format($thisTime-Logger::$lastTime, 3).'s block]['.Logger::getTraceSummary($backtrace).']';
        if(is_array(Logger::$logFile)){
            print_r(Logger::$logFile);
            exit();
        }
        $phpsucks = explode('.', Logger::$logFile);
        //get the file information end(explode('.', Logger::$logFile))
        if(strtolower(end($phpsucks)) == 'html') $linePrefix = '<h4 style="font-size:10px; margin-bottom:-5px;"><b>'.Logger::getTraceSummary($backtrace).'</b></h4>[<b style="display:inline-block">['.number_format($thisTime, 3).'s total, '.number_format($thisTime-Logger::$lastTime, 3).'s block]</b>]';//$linePrefix = $location.'('.$file.') ';
        else{
            $fileParts = explode('/', $_SERVER['SCRIPT_FILENAME']);
            $script_file = $fileParts[count($fileParts)-1];
            $linePrefix = date('H:i:s,u').' protolus['.Logger::getTraceSummary($backtrace).'] panel['.PageRenderer::$root_panel.'] INFO '.Logger::$id.' ';
        }
        Logger::$lastTime = $thisTime;
        
        if(Logger::$logToBuffer){
            Logger::$buffer['location'] = $location;
            Logger::$buffer['file'] = $file;
            Logger::$buffer['value'] = $text;
        }
        if(Logger::$logToPHPErrorLog){
            error_log($textPrefix.$text, $level);
        }
        if(Logger::$logToSystem){
            syslog($textPrefix.$level, $text);
        }
        if(Logger::$logToUser) $location.'('.$file.') '.$text;
        if(Logger::$logFile != null && Logger::$logFile != -1){ //required because someone randomly stuck in a hardcoded conf value whether it's set or not, if this ever gets found, this check may be removed
            if(Logger::$fileHandle == null){
                if(!is_dir(dirname(Logger::$logFile))){
                    //echo('making '.dirname(Logger::$logFile)); exit();
                    mkdir(dirname(Logger::$logFile), 0777, true);
                }
                Logger::$fileHandle = fopen(Logger::$logFile, 'a');
            }
            if(file_exists(Logger::$logFile)) fwrite(Logger::$fileHandle, $linePrefix.$text.Logger::$EOL);
        }
        if(Logger::$logToTempFile){
            if(Logger::$tempFile == null){
                Logger::$tempFileName = tempnam(sys_get_temp_dir(), 'log-');
                Logger::$tempFile = fopen(Logger::$tempFileName, 'w');
                if( Logger::$tempFile === false || Logger::$tempFile == null) throw new Exception("temp file [".Logger::$tempFileName."] initialized");
            }
            fwrite(Logger::$tempFile, $linePrefix.$text.Logger::$EOL);
            fflush(Logger::$tempFile);
        }
    }
    
    public static function shutdown(){
        if(Logger::$timestamp != null) Logger::log('Timestamp: '.DefaultTable::processing_time(Logger::$timestamp));
        fclose(Logger::$tempFile);
        Logger::log('--- '.Logger::$id.' ---');
        //unlink(Logger::$tempFileName);
    }
    
    public static function getTempLogContent(){
        $contents = file_get_contents(Logger::$tempFileName);
        return $contents;
    }
    
    public static function processing_time($START=false){
        $an = 4;    // How many digits to return after point
        if(!$START) return time() + microtime();
        $END = time() + microtime();
        return round($END - $START, $an);
    }
    
    public static function logTime(){
        if(Logger::$timestamp != null) Logger::log('Timestamp: '.DefaultTable::processing_time(Logger::$timestamp));
    }
    
    public static function dumpBuffer($location = true, $file = true){
        $result = '';
        foreach($buffer as $item){
            $result .= Logger::$buffer['location'];
            $result .= '('.Logger::$buffer['file'].') ';
            $result .= Logger::$buffer['value'].Logger::$EOL;
        }
        return $result;
    }

}

?>