<?php
class UploadedFile{
/*********************************************************************************//**
 *  UploadedFile
 *====================================================================================
 * @author Abbey Hawk Sparrow
 * @file UploadedFile.class.php
 * A container for binary data uploads
 *
 * @brief A container for binary file uploads
 *************************************************************************************/
    protected $name;
    public function  __construct($name){
        $this->name = $name;
    }

    public function exists(){
        $res = array_key_exists($this->name, $_FILES);
        return $res;
    }

    public function saveAs($path){
        move_uploaded_file($_FILES[$this->name]['tmp_name'], $path);
        chmod($newfile , 0755);
        return file_exists($path);
    }

    public function remoteName(){
        return $_FILES[$this->name]['name'];
    }

    public function size(){
        return $_FILES[$this->name]['size'];
    }

    public function type(){
        return $_FILES[$this->name]['type'];
    }

    public function read(){
        return file_get_contents($_FILES[$this->name]['tmp_name']);
    }
}