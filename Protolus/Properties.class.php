<?php
/*********************************************************************************//**
 *  Properties
 *====================================================================================
 * @author Abbey Hawk Sparrow
 *Parse and use .properties files
 *************************************************************************************/
    class Properties{
        var $data;
        var $fileName;
        var $lineEnding;
        
        function Properties($fileName, $platform="Unix"){
            $this->fileName = $fileName;
            if($fileName){
                switch($platform){
                    case "mac":
                        $lineEnding = chr(13);
                        break;
                    case "windows":
                        $lineEnding = chr(13).chr(10);
                        break;
                    default:
                        $lineEnding = chr(10);
                        break;
                }
                //$lineEnding = "\n";
                $this->lineEnding = $lineEnding;
                $this->read();
            }
        }
        
        function read(){
            if($this->fileName){
                $handle = fopen ($this->fileName, "r");
                $contents = fread ($handle, filesize ($this->fileName));
                fclose ($handle);
                $lines = explode($this->lineEnding, $contents);
                for ($i = 0; $i < count($lines); $i++){
                    $values = explode("=", $lines[$i]);
                    if(count($values) > 2) $pred = $this->predicate($values);
                    else if(count($values) > 1) $pred = $values[1];
                    else $pred = '';
                    $this->data[trim($values[0])] = trim($pred);
                }
            }else{
                //empty property
                echo("I/O ERROR: Properties file \"$this->fileName\" not found.<br />");
                echo("--DIRECTORY---<br />".$this->printDirectory($this->fileName)."---------");
                exit();
            }
        }
        
        function predicate($array){
            $count = 0;
            $result = "";
            foreach($array as $value){
                if($count > 1) $result .= "=";
                $result .= $value;
                $count++;
            }
            return $result;
        }
        
        function loadString($string){
           $lines = explode($this->lineEnding, $string);
            for ($i = 0; $i < count($lines); $i++){
                $values = explode("=", $lines[$i]);
                $this->data[trim($values[0])] = trim($values[1]);
            }
        }
        
        function getKey($target_value){
            foreach($this->data as $key=>$value){
                if($value === $target_value) return $key;
            }
            return null;
        }
    
        function getProperty($name){
            if(isset($this->data[$name]) && $this->data[$name] != ""){
                return $this->data[$name];
            }
            else return null;
        }
        
        function setProperty($name, $value){
            $this->data[$name] = $value;
        }
        
        function hasProperty($name){
            return isset($this->data[$name]);
        }
        
        function write(){
            //$this->fileName = $this->store.$object->getName()."/".$id.".properties";
            $handle = fopen($this->fileName, "w");
            flock($handle, LOCK_EX);
            foreach($this->data as $name => $value){
                if($name != '') fwrite($handle, $name."=".$value.$this->lineEnding);
            }
            flock($handle, LOCK_UN);
            fclose($handle);
        }
        
        function getData(){
            return $this->data;
        }
        
        function printDirectory($name){
            $directory = opendir(".");
            $result = "";
            while($file = readdir($directory)){
                $result .= $file."<br />";
            }
            return $result;
        }
        
        function getKeys(){
            $result = array();
            foreach($this->data as $key=>$value){
                $result[] = $key;
            }
            return $result;
        }
        
        function printKeys(){
            $out = "<BR><TABLE><TR><TD><B>Properties</B></TD></TR><TR><TD>";
            for(reset($this->data); $key = key($this->data); next($this->data)){
                $out .= "$key</TD></TR><TR><TD>";
            }
            $out .= "</TD></TR></TABLE><BR>";
            return $out;
        }
        
        function dump(){
            $out = "<BR><TABLE><TR><TD><B>Properties</B></TD></TR><TR><TD>";
            foreach($this->data as $key=>$value){
                $out .= "$key</TD><TD>$value</TD></TR><TR><TD>";
            }
            for(reset($this->data); $key = key($this->data); next($this->data)){
                $out .= "$key</TD></TR><TR><TD>";
            }
            $out .= "</TD></TR></TABLE><BR>";
            return $out;
        }

    }