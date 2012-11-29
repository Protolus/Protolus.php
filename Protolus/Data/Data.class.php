<?php
class WhereParser{
    public function parse($query){
        return $this->parse_where($query);
    }
    
    protected function parse_where($clause){
        $blocks = $this->parse_blocks($clause);
        $parsed = array();
        $this->parse_compound_phrases($blocks, $parsed);
        $parser = $this->parse_discriminant;
        $func = function($value) use(&$func, $parser) {
            if(is_array($value)) return array_map($func, $value);
            else{
                if(in_array($value, array('AND', 'OR', 'and', 'or', '&&', '||'))){
                    return array(
                    'type' => 'conjunction',
                    'value' => $value
                    );
                }else{ //parse statement
                    $key = '';
                    $operator = '';
                    $val = '';
                    $inquote = false;
                    for($lcv = 0; $lcv < strlen($value); $lcv++){
                        $char = $value[$lcv];
                        if($inquote){
                            if($char == $inquote){
                                $inquote = false;
                                continue;
                            }
                            if($operator) $val .= $char;
                            else $key .= $char;
                            continue;
                        }
                        if($char == '\'' || $char == '"'){
                            $inquote = $char;
                            continue;
                        }
                        if(strstr('><=!', $char)){
                            $operator .= $char;
                            continue;
                        }
                        if($operator) $val .= $char;
                        else $key .= $char;
                    }
                    return array(
                        'type' => 'expression',
                        'key' => $key,
                        'operator' => $operator,
                        'value' => $val
                    );
                }
            }
        };

        $result = array_map($func, $parsed);
        return $result;
    }
    
    protected function parse_discriminant($text){
        $key = '';
        $operator = '';
        $value = '';
        $inquote = false;
        for($lcv = 0; $lcv < strlen($text); $lcv++){
            $char = $text[$lcv];
            if($inquote){
                if($char == $inquote){
                    $inquote = false;
                    continue;
                }
            }
            if($char == '\'' || $char == '"'){
                $inquote = $char;
                continue;
            }
            if(strstr('><=!', $char)){
                $operator .= $char;
                continue;
            }
            if($operator) $value .= $char;
            else $key .= $char;
        }
        return array(
            'type' => 'expression',
            'key' => $key,
            'operator' => $operator,
            'value' => $value
        );
    }
    
    protected function parse_blocks($parsableText){
        $env = array();
        $b_open = '(';
        $b_close = ')';
        $t_close = '\'';
        $t_open = '\'';
        $depth = 0;
        $text_mode = false;
        $text = '';
        $root = &$env;
        $stack = array();
        for($lcv = 0; $lcv < strlen($parsableText); $lcv++){
            $char = $parsableText[$lcv];
            if($text_mode){
                $text .= $char;
                if($char == $t_close){
                    $text_mode = false;
                }
                continue;
            }
            if($char == $t_open){
                $text .= $char;
                $text_mode = true;
                continue;
            }
            if($char == $b_open){
                if($text != '') $env[] = $text;
                $nextLevel = array();
                $env[] = &$nextLevel;
                array_push($stack, $env);
                $env = &$nextLevel;
                $text = '';
                continue;
            }
            if($char == $b_close){
                if($text != '') $env[] = $text;
                unset($env);
                $env = array_pop($stack);
                $text = '';
                continue;
            }
            $text .= $char;
        }
        if($text != '') $env[] = $text;
        return $root;
    }
    
    protected function parse_compound_phrases($array, &$result){
        foreach($array as $item){
            if(is_array($item)){
                $results = array();
                $this->parse_compound_phrases($item, $results);
                $result[] = $results;
            }else if(is_string($item)) $result = array_merge($result, $this->parse_compound_phrase($item));
        }
    }
    
    protected function parse_compound_phrase($clause){
        $sentinels = array('and', 'or', '&&', '||');
        $textEscape = array('\'');
        $inText = false;
        $escape = '';
        $current = '';
        $results = Array('');
        for($lcv = 0; $lcv < strlen($clause); $lcv++){
            $ch = $clause[$lcv];
            //echo();
            if($inText){
                $results[count($results)-1] .= $current.$ch;
                $current = '';
                if($ch == $escape){
                    $inText = false;
                }
            }else{
                if(in_array($ch, $textEscape)){
                    $inText = true;
                    $escape = $ch;
                }
                if($ch != ' '){
                    $current .= $ch;
                    if(in_array(strtolower($current), $sentinels)){
                        array_push($results, $current);
                        array_push($results, '');
                        $current = '';
                    }
                }else{
                    $results[count($results)-1] .= $current;
                    $current = '';
                }
            }
        }
        return $results;
        /*
        $parts = preg_split('/(?= [Aa][Nn][Dd] ?| [Oo][Rr] ?|\|\||&&)/', $clause);
        $results = array();
        foreach($parts as $part){
            $results = array_merge($results, preg_split('/(?<= [Aa][Nn][Dd] | [Oo][Rr] |\|\||&&)/', $part));
        }
        foreach($results as $key=>$value){
            if(!trim($value)) unset($results[$key]);
            else $results[$key] = trim($results[$key]);
        }
        return array_values($results);
        */
    }
}

    abstract class Data{
        //protected static function performSearch($subject, $predicate);
        //protected static function initialize($options);
        //static $fields
        
        protected abstract function performLoad($id, $field);
        protected abstract function performSave();
        
        //static implementations
        public static $empty_field_mode = 'null'; //null, notice, exception
        public static $registry = array(); // uuid, integer
        public static $core_fields = array('id', 'record_status', 'modification_time', 'creation_time', 'modified_by');
        public static $where_parser = false;
        public static $core_options = array(
            'id' => array(
                'type' => 'integer',
                'identifier' => 'true'
            ), 
            'record_status', 
            'modification_time' => array(
                'type' => 'instant'
            ), 
            'creation_time' => array(
                'type' => 'instant'
            ),  
            'modified_by' => array(
                'type' => 'integer'
            )
        );
        protected static function parseWhere($whereClause){
            if(!Data::$where_parser) Data::$where_parser = new WhereParser();
            return Data::$where_parser->parse($whereClause);
            /*$results = array();
            $inQuote = false;
            $quoteChar = '';
            $quotation = '';
            $result = array();
            $quoteChars = array('\'', '"');
            $quoteChars = array('\'', '"');
            $breakingChars = array(' ', '=', '>', '<', '!');
            for($lcv=0; $lcv<strlen($whereClause); $lcv++){
                if($inQuote){
                    if($whereClause[$lcv] == $quoteChar){ //close a quote
                        //use gettype to detect where quotes need to get added
                        //$result[sizeof($result)-1] .= '\''.$quotation.'\'';
                        $result[sizeof($result)-1] .= $quotation;
                        $inQuote = false;
                        $quotation = '';
                    }else{
                        $quotation .= $whereClause[$lcv];
                    }
                }else{
                    if(!isset($result[0])) $result[0] = ''; //init a new subject if we have nothing
                    if($whereClause[$lcv] == ' ' ||
                        (!isset($result[1]) && in_array($whereClause[$lcv], $breakingChars)) ||
                        (isset($result[1]) && !isset($result[2]) && !in_array($whereClause[$lcv], $breakingChars))
                    ){
                        if(isset($result[2]) && strtolower(substr($whereClause, $lcv, 4)) == 'and '){
                            $results[] = $result;
                            $result = array();
                            $lcv += 3; //skip the 'and' chars
                            continue;
                        }
                        if(isset($result[0]) && $result[0] == '') continue; //don't advance if we have nothing yet (leading spaces)
                        if(!isset($result[1])){
                            $result[1] = '';
                        }else if(!isset($result[2])){
                            $result[2] = '';
                        }
                    }
                    if(in_array($whereClause[$lcv], $quoteChars)){ //open a quote
                        $quoteChar = $whereClause[$lcv];
                        $inQuote = true;
                        $quotation = '';
                    }else{
                        $result[sizeof($result)-1] .= $whereClause[$lcv];
                    }
                }
                //echo('[q:'.$quotation.']');
            }
            if($quotation != '') $result[2] = $quotation;
            //echo('[QUOTE!]'); exit();
            $results[] = $result;
            //print_r($results); exit();
            return $results;*/
        }
        
        protected static function convertArrayToSearch($datatype, $query){
			return $query;
            //todo: implement... not really a priority
        }
        
        public static function generateUUID(){
            $uuid = array(
                'time_low'  => 0,
                'time_mid'  => 0,
                'time_hi'  => 0,
                'clock_seq_hi' => 0,
                'clock_seq_low' => 0,
                'node'   => array()
            );
            $uuid['time_low'] = mt_rand(0, 0xffff) + (mt_rand(0, 0xffff) << 16);
            $uuid['time_mid'] = mt_rand(0, 0xffff);
            $uuid['time_hi'] = (4 << 12) | (mt_rand(0, 0x1000));
            $uuid['clock_seq_hi'] = (1 << 7) | (mt_rand(0, 128));
            $uuid['clock_seq_low'] = mt_rand(0, 255);
            for ($i = 0; $i < 6; $i++) {
                $uuid['node'][$i] = mt_rand(0, 255);
            }
            $uuid = sprintf('%08x-%04x-%04x-%02x%02x-%02x%02x%02x%02x%02x%02x',
                $uuid['time_low'],
                $uuid['time_mid'],
                $uuid['time_hi'],
                $uuid['clock_seq_hi'],
                $uuid['clock_seq_low'],
                $uuid['node'][0],
                $uuid['node'][1],
                $uuid['node'][2],
                $uuid['node'][3],
                $uuid['node'][4],
                $uuid['node'][5]
            );
            return $uuid;
        }
        
        protected function checkInitialization(){
            //hacking away
            /*if( !isset($this->data[$this->primaryKey]) || empty($this->data[$this->primaryKey]) ){
                switch($this->key_type){
                    //generate a UUID for the object
                    case 'uuid':
                        $this->data[$this->primaryKey] = $this->generateUUID();
                        break;
                    case 'integer': //AKA autoincrement, implemented in the DB implementation class, not here
                        //todo: implement me
                        break;
                }
                $this->firstSave = true;
            }*/
        }
        
        public static function search($datatype, $query=null, $fields=array()){
            $resultSet = Data::query($datatype, $query, $fields);
            $results = array();
            foreach($resultSet as $result){
                $object = new $datatype();
                $object->setData($result);
                $object->firstSave = false;
                $results[] = $object;
            }
            return $results;
        }
        
        public static function query($datatype, $query=null, $fields=array()){
            if(is_string($query)){
                $search = Data::parseWhere($query);
            }else{
                if(is_object($query) && is_a($query, 'Search')){
                    $search = $query->discriminants;
                }else if(is_array($query)){
                    $search = $query; //Data::convertArrayToSearch($query);
                }
            }
            //print_r($search); exit();
            if($query == null) $search = array();
			$dummy = new $datatype();
            if(isset($search)){
                $resultSet = $datatype::performSearch(array(
                    'type'=>$datatype,
                    'object'=>$datatype
                ), $search, Data::$registry[$dummy->database], $fields);
                return $resultSet;
            }else{
                return array();
            }
        }
        
        public static function getFieldOptions($field, $object){
            ($comment = $object->option($field, 'comment'))?$comment:'';
            ($identifier = $object->option($field, 'identifier'))?true:false;
            ($required = $object->option($field, 'required'))?($required == 't'||$required == 'true'):false;
            ($size = (int)$object->option($field, 'size'));
            ($hidden = $object->option($field, 'hidden'));
            ($object_type = $object->option($field, 'object_type'));
            ($query = $object->option($field, 'query'));
            ($readonly = $object->option($field, 'readonly'));
            ($type = $object->option($field, 'type'));
            $options = array(
                'comment' => $comment,
                'identifier' => $identifier,
                'required' => $required,
                'size' => $size,
                'hidden' => $hidden,
                'readonly' => $readonly,
                'object_type' => $object_type,
                'query' => $query,
                'type' => $type
            );
            if($position = $object->option($field, 'position')) $options['position'] = $position;
            return $options;
        }
        
        public static function HTMLField($column, $options, $object){
            $description = $options['description'];
            $identifier = $options['identifier'];
            $default = $options['default'];
            $obfuscated = $options['obfuscated'];
            $hidden = $options['hidden'];
            $readOnly = $options['readonly'];
            $required = $options['required'];
            $size = $options['size'];
            $object_type = $options['object_type']?$options['object_type']:'';
            $query = $options['query']?$options['query']:'1';
            $class = $options['class'];
            $fieldType = strtolower($object->type($column));
            switch($fieldType){
                case 'binary' :
                case 'integer' :
                case 'float' :
                case 'instant' :
                    $class = 'date_select';
                    $type = 'text';
                    break;
                case 'string' :
                case 'link' :
                    $type = 'link';
                    break;
                default :
                    if($size < 256) $type = 'text';
                    else $type = 'textarea';
            }
            if($identifier || $hidden) $type = 'hidden';
            $label = ucwords(preg_replace('~_~', ' ', $column));
            //no label for readonly... 
            if($readOnly) return ($object->get($column)?$object->get($column):$default).'<input ro="'.$column.'|'.$object->get($column).'" name="'.$column.'" value="'.($object->get($column)?$object->get($column):$default).'" type="hidden"></input>';
            //if($readOnly) return '<label for="'.$column.'">'.$label.'</label>'.($object->get($column) || $default).'<input name="'.$column.'" value="'.($object->get($column) || $default).'" type="hidden"></input>';
            if($obfuscated){
                $type = 'password';
                $html = '<label for="'.$column.'">'.$label.'</label><input name="'.$column.'" type="password"'.
                        (($size)?' size="'.$size.'"':'').
                        ' class="'.$class.'"></input>';
            }else{
                $rawValue = (($rawValue = $object->get($column))?$rawValue:($default?$default:''));
                $value = ' value="'.$rawValue.'"';
                switch($type){
                    case 'textarea':
                        $html = '<label for="'.$column.'">'.$label.'</label><textarea name="'.$column.'"'.
                        (($size)?' size="'.$size.'"':'').
                        '>'.(($value = $object->get($column))?' value="'.$value.'"':(($default)?' value="'.$default.'"':'')).'</textarea>';
                        break;
                    case 'readOnly':
                        $html = '<label for="'.$column.'">'.$label.'</label>'.($object->get($column) || $default).'<input name="'.$column.'"'.$value.' type="hidden"></input>';
                        break;
                    case 'link':
                        $opts = Data::query($object_type, $query, 'id,name');
                        $optionText = '';
                        foreach($opts as $option){
                            $optionText .= '<option value="'.$option['id'].'" '.($option['id'] == $rawValue?'selected="true"':'').'>'.$option['name'].'</option>';
                        }
                        $html = '<label for="'.$column.'">'.$label.'</label><select name="'.$column.'">'.$optionText.'</select><a href=""><div class="icon edit"></div> edit</a>';
                        break;
                    default :
                        $html = ($type != 'hidden'?'<label for="'.$column.'">'.$label.'</label>':'').'<input name="'.$column.'" type="'.$type.'"'. $value.
                        (($size)?' size="'.$size.'"':'').
                        ' '.(($fieldType == 'instant' && $class)?'class="'.$class.'"':'').'></input>';
                }
            }
            return $html;
        }
        
        public static function getSelectionOptions($type, $labelField='name', $conditions=array(), $idFieldName='id'){
            
        }
        
        public static function setMetaFields(&$object, $reason = 'object saved'){
	        if(is_array($object)){ // it's an old-style array
	            if($object->isNew) $object['created_at'] = date("Y-m-d H:i:s");
	            //$object['modification_time'] = date("Y-m-d H:i:s");
	            //$object['modified_by'] = MySQLData::$user_id;
	            $object['updated_at'] = date("Y-m-d H:i:s");
	            //$object['last_update_reason'] = $reason;
	        }else if(is_object($object)){ // it's a new-style object
	            if($object->isNew) $object->set('created_at', date("Y-m-d H:i:s"));
	            //$object->set('modification_time', date("Y-m-d H:i:s"));
	            //$object->set('modified_by', MySQLData::$user_id);
	            $object->set('updated_at', date("Y-m-d H:i:s"));
	            //$object->set('last_update_reason', $reason);
	        }else{
	            throw new Exception("object is not an object or array, meta-information cannot be added.");
	        }
	    }

	    public static function isMetaField($name){
	        if(strtolower($name) != 'id' && in_array(strtolower($name), Data::$core_fields)) return true;
	        else return false;
	    }
        
        //instance implementations
        public $cache = null;
        public $isNew = true;
        public $data = array();
        public $types = array(); //if not set, assumption is 'string'
        public $options = array(); //[type][option_name] = value
        public $primaryKey = 'id';
        public $firstSave = false;
        public $key_type = 'uuid'; // uuid, integer, autoincrement
        
        public function __construct($value, $field=null){
            if($value != null && !empty($value)){
                $this->data = $this->load($value, $field);
                //if(empty())
                $class = get_class($this);
                Logger::log('Loading '.($class::$name).' '.$value);
            }
        }
        
        public function type($column){
            if($type = $this->types[strtolower($column)]){
                return $type;
            }else if($type = Data::$core_options[strtolower($column)]['type']){
                return $type;
            }else return false;
        }
        
        public function HTML($separator = '<br/>',  $column = false, $action = false){
            if($action) $action_string = 'action="'.$action.'"';
            if($column){
                return Data::HTMLField($column, self::getFieldOptions($field, $this), $this);
            }else{
                $cn = get_class($this);
                $res = '<form class="form_basic" name="'.$cn.'" method="post" '.$action_string.'>';
                $unordered = array();
                $ordered = array();
                foreach(array_merge(Data::$core_fields, $cn::$fields) as $field){
                    $options = self::getFieldOptions($field, $this);
                    $fieldHTML = Data::HTMLField($field, $options, $this);
                    if(array_key_exists('position', $options)){
                        $ordered[$options['position']] = $fieldHTML;
                    }else{
                        $unordered[] = $fieldHTML;
                    }
                }
                foreach($ordered as $field){
                    $res .= $field.$separator;
                }
                foreach($unordered as $field){
                    $res .= $field.$separator;
                }
                $res .= '<input type="hidden" name="form" value="true" /></form>';
                return $res;
            }
            //todo: implement full form
        }
        
        public function harvest($prefix='', $suffix=''){
            $cn = get_class($this);
            $data = array();
            foreach(array_merge(Data::$core_fields, $cn::$fields) as $field){
                $value = WebApplication::get($prefix.$field.$suffix);
                $data[$field] = $value;
                if($field == $this->primaryKey){
                    $this->isNew = false;
                }
            }
            $this->data = $data;
        }
        
        public function option($column, $name){
            if( ($options = $this->options[strtolower($column)]) && ($option = $options[$name]) ){
                return $option;
            }
            if($option = Data::$core_options[strtolower($column)][$name]){
                return $option;
            }
            return false;
        }
        
        public function load($id, $field=null){
            $res = $this->performLoad($id, $field);
            if($res) $this->isNew = false;
            return $res;
        }
        
        public function save(){
            $this->checkInitialization();
            $result = $this->performSave();
            $this->isNew = false;
            return $result;
        }
        
        public function increment($key, $amount=1){
            $this->set($key, $this->get($key) + $amount);
        }
        
        public function get($key){
            //todo: we should shortcut if there's no descention
            $parts = explode('.', $key);
            $current = $this->data;
            $currentPath = array();
            foreach($parts as $part){
                $currentPath[] = $part;
                if(!is_array($current) && !array_key_exists($part, $current)){
                    $warning_text = 'Value does not exist('.implode('.', $currentPath).')!';
                    switch($empty_field_mode){
                        case 'notice': //issues notice then drops through to null return 
                            trigger_error($warning_text, E_USER_NOTICE);
                        case 'null':
                            return null;
                        case 'empty':
                            return '';
                        case 'exception':
                            throw new Exception($warning_text);
                    }
                }else{
                    $current = &$current[$part];
                }
            }
            return $current;
        }
        
        public function getData(){
            return $this->data;
        }
        
        public function setData($data){
            if($data[$this->primaryKey]) $this->isNew = false;
            return $this->data = $data;
        }
        
        public function set($key, $value){
            //todo: we should shortcut if there's no descention
            $parts = explode('.', $key);
            $parts = array_reverse($parts);
            $current = &$this->data;
            if(sizeof($parts) == 0) throw new Exception('Cannot set an empty value!');
            while(sizeof($parts) > 1){
                $thisPart = array_pop($parts);
                if(!is_array($current) && !array_key_exists($thisPart, $current)){
                    $current[$thisPart] = array();
                }else{
                    $current = &$current[$thisPart];
                }
            }
            $thisPart = array_pop($parts);
            if($value === null){
                unset($current[$thisPart]);
            }else{
                $current[$thisPart] = $value;
            }
        }
    }