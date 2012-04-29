<?php
class Codec{
    public static $codecs = array();
    public static $globalKey = 'default';
    
    public static function getCodec($key, $algorithm, $mode){
        if(!isset(Codec::$codecs[$key.$algorithm.$mode])){
            $mcrypt_link = mcrypt_module_open($algorithm, '', $mode, '') ;
            $random_seed = strstr(PHP_OS, "WIN") ? MCRYPT_RAND : MCRYPT_DEV_RANDOM;
            $iv = ($iv === false) ? mcrypt_create_iv(mcrypt_enc_get_iv_size($mcrypt_link), $random_seed) : substr($iv, 0, mcrypt_enc_get_iv_size($mcrypt_link));
            $expected_key_size = mcrypt_enc_get_key_size($mcrypt_link);
            $key = substr(md5($key), 0, $expected_key_size);
            mcrypt_generic_init($mcrypt_link, $key, $iv);
            Codec::$codecs[$key.$algorithm.$mode] = $mcrypt_link;
        }
        return Codec::$codecs[$key.$algorithm.$mode];
    }
    
    public static function encode($encoding, $value, $key=null){
        if($key == null) $key = Codec::$globalKey;
        $mode= 'ecb';
        $iv = false;
        switch(strtoupper($encoding)){
            case 'MD5':
                return md5($value);
                break;
            case 'AES':
                $algorithm = 'rijndael_256';
                break;
            case 'DES':
                $algorithm = 'des';
                break;
            case 'BLOWFISH':
                $algorithm = 'blofish';
                break;
            case 'TRIPLEDES':
                $algorithm = 'tripledes';
                break;
            case 'SERPENT':
                $algorithm = 'serpent_256';
                break;
        }
        $mcrypt_link = Codec::getCodec($key, $algorithm, $mode);
        return base64_encode(mcrypt_generic($mcrypt_link, $value));
    }
    
    public static function decode($encoding, $value, $key='default'){
        $mode= 'ecb';
        $iv = false;
        switch(strtoupper($encoding)){
            case 'MD5':
                return '?';
                break;
            case 'AES':
                $algorithm = 'rijndael_256';
                break;
            case 'DES':
                $algorithm = 'des';
                break;
            case 'BLOWFISH':
                $algorithm = 'blofish';
                break;
            case 'TRIPLEDES':
                $algorithm = 'tripledes';
                break;
            case 'SERPENT':
                $algorithm = 'serpent_256';
                break;
        }
        $mcrypt_link = Codec::getCodec($key, $algorithm, $mode);
        return trim(mdecrypt_generic($mcrypt_link, base64_decode($value)));
    }
}