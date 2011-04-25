<?php
    class DataService{
        public static $dumpRequests = false;
        protected $url = null;
        public $minutesDataStaysFresh = 1440; //1 day
        function __construct($url, $user){
            $this->url = $url;
            $this->user = $user;
        }
        public function getAccessKeyName(){
            return $this::$name.'.auth_token';
        }
        function ensureFreshness($object, $location, $id){
            $stale = $object->get($location.'.stale_date');
            //if we haven't ever fetched or this fetch isn't fresh, do it
            if( $stale == '' || ($stale+$service->minutesDataStaysFresh) < time() ){
                $result = $this->fetch();
                if(!empty($result) && !array_key_exists('error', $result)){
                    if(array_key_exists('data', $result)) $object->set($location, $result['data']); //if we can find the data payload, we set that
                    //if not, we're not picky
                    //todo: check for an error state
                    else $object->set($location, $result);
                    $object->set($location.'.stale_date', time() + $service->minutesDataStaysFresh);
                }
            }else{
                //echo('[DATA IS STILL FRESH]');
            }
        }
        function fetch($params = null, $postdata=null){
            if($this->url == null) throw new Exception('No URL for this service!');
            $url = $this->url;
            if($params != null && is_array($params) && count($params) > 0){
                $args = array();
                foreach($params as $name => $value){
                    $args[] = $name.'='.urlencode($value);
                }
                $url = $this->url.'?'.implode('&', $args);
            }
            $result = $this->pull($url, $postdata);
            if(DataService::$dumpRequests) echo('<hr/><b>'.$url.'</b><br/><textarea>'.$result.'</textarea>');
            return $result;
        }
        function pull($url, $postdata=null){
            if (extension_loaded('curl')) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL,$url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_HEADER, FALSE);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, FALSE);
                curl_setopt($ch, CURLOPT_MAXREDIRS, 0);
                if($postdata != null){
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
                }
                $string = curl_exec($ch);
                if ($errNo = curl_errno($ch)) {
                    $x = curl_error($ch);
                    Logger::log('Curl Error['.$errNo.']: '.$x);
                } else {
                    curl_close($ch);
                }
            } else {
                $string = file_get_contents($url);
            }
            return $string;
        }
    }