<?php
class CLUtility{
    //taken from: http://php.net/manual/en/function.parse-ini-file.php
    static function write_php_ini($array, $file){
        $res = array();
        foreach($array as $key => $val){
            if(is_array($val)){
                $res[] = "[$key]";
                foreach($val as $skey => $sval) $res[] = "$skey = ".(is_numeric($sval) ? $sval : '"'.$sval.'"');
            }
            else $res[] = "$key = ".(is_numeric($val) ? $val : '"'.$val.'"');
        }
        safefilerewrite($file, implode("\r\n", $res));
    }
    //////
    static function safefilerewrite($fileName, $dataToSave){    if ($fp = fopen($fileName, 'w'))
        {
            $startTime = microtime();
            do{
                $canWrite = flock($fp, LOCK_EX);
                if(!$canWrite) usleep(round(rand(0, 100)*1000));
            } while ((!$canWrite)and((microtime()-$startTime) < 1000));
    
            //file was locked so now we can store information
            if ($canWrite){
                fwrite($fp, $dataToSave);
                flock($fp, LOCK_UN);
            }
            fclose($fp);
        }
    
    }
    
    /*
    function write_ini_file($assoc_arr, $path, $has_sections=FALSE) { 
        $content = ""; 

        if ($has_sections) { 
            foreach ($assoc_arr as $key=>$elem) { 
                $content .= "[".$key."]\n"; 
                foreach ($elem as $key2=>$elem2) 
                { 
                    if(is_array($elem2)) 
                    { 
                        for($i=0;$i<count($elem2);$i++) 
                        { 
                            $content .= $key2."[] = \"".$elem2[$i]."\"\n"; 
                        } 
                    } 
                    else if($elem2=="") $content .= $key2." = \n"; 
                    else $content .= $key2." = \"".$elem2."\"\n"; 
                } 
            } 
        } 
        else { 
            foreach ($assoc_arr as $key=>$elem) { 
                if(is_array($elem)) 
                { 
                    for($i=0;$i<count($elem);$i++) 
                    { 
                        $content .= $key2."[] = \"".$elem[$i]."\"\n"; 
                    } 
                } 
                else if($elem=="") $content .= $key2." = \n"; 
                else $content .= $key2." = \"".$elem."\"\n"; 
            } 
        } 

        if (!$handle = fopen($path, 'w')) { 
            return false; 
        } 
        if (!fwrite($handle, $content)) { 
            return false; 
        } 
        fclose($handle); 
        return true; 
    }*/
}
?>