<?php
    abstract class MySQLData extends Data{
        public static $lastQuery = '';
		public static $db;
        public static $mysql_iMode = false;
		public static $activeCheck = false;
		public static $debug = true;
		public static $affected_rows = 0;
		public static $user_id= 0;
		protected static $database = 'database';
		public $primaryKey = 'id';
		
        public static function initialize($options){
	//print_r($options); exit;
			$server = $options['host'];
			$login = $options['user'];
			$password = $options['password'];
			$dbname = $options['database'];
			
			if(!isset($options['mode'])) $options['mode'] = 'mysql';
            switch(strtolower($options['mode'])){
                case 'mysql' :
                   $mode = "mysql";
                    break;
                case 'mysqli' :
					$mode = "mysqli";
					MySQLData::$mysql_iMode = true;
				break;
                default : throw new Exception('unsupported MySQL connection mode('.$options['mode'].')!');
            }

	        Logger::log('Creating connection to  '.$server.'@'.$dbname.' with user '.$login.'.<br/>');
	        if($mode == "mysqli"){
	            //$link = mysqli_init();
	            //mysqli_real_connect($link, $server, $login, $password, $dbname);
	            $link=mysqli_connect($server, $login, $password, $dbname);
	            //mysql_select_db($link, $dbname);
	            mysqli_query('set names "utf-8"');
	            mysqli_query('set character set "utf8"');
	            mysqli_query('set character_set_server="utf8"');
	            mysqli_query('set collation_connection="utf8_general_ci"');
	        }else{

	            if(count($_SERVER["argc"]) < 2){ //this is a web request
	                $link = mysql_connect($server, $login, $password, TRUE);
	            }else{ //this is a commandline request
	                $link = odbc_connect($server, $login, $password, TRUE);
	            }
	            if(!mysql_select_db($dbname, $link)){
	                echo('database '.$dbname.' could not be selected!');
	            }
	        }
	        if (!$link){
	            throw new Exception("Error connecting to database!");
	            return false;
	        }
	
		        MySQLData::$db = $link;

            if($options['name']){
                Data::$registry[$options['name']] = $link;
                Logger::log('Initialized the DB('.$options['database'].' @ '.$options['host'].') as \''.$options['name'].'\'');
            }else{
                //todo: warn
                Logger::log('Initialized the DB('.$options['database'].' @ '.$options['host'].') without registering it');
            }
            return $link;
        }

	    public static function getMetaFields($search){
	        $sep='';
	        $result='';
	        $fields = Data::$core_fields;
	        if($search->joinedName) $tableAdd = $search->joinedName.'.';
	        else $tableAdd = $search->tableName.'.';
	        foreach($fields as $name){
	            if($search->metaRenameOverride){
	                $currentName = $name;
	                $newName = $name;
	            }else{
	                $currentName = $search->joinedPrefix.$name;
	                $newName = $search->joinPrefix.$name;
	            }
	            if($currentName != $newName){
	                $result .= $sep.$tableAdd.$currentName.' AS '.$newName;
	            }else{
	                $result .= $sep.$tableAdd.$currentName;
	            }
	            if($sep == '') $sep = ', ';
	        }
	        return $result;
	    }
	
	    public static function setMetaFields(&$object, $reason = 'object saved'){
	        if(is_array($object)){ // it's an old-style array
	            if($object->isNew) $object['creation_time'] = date("Y-m-d H:i:s");
	            $object['modification_time'] = date("Y-m-d H:i:s");
	            $object['modified_by'] = MySQLData::$user_id;
	            //$object['last_update_reason'] = $reason;
	        }else if(is_object($object)){ // it's a new-style object
	            if($object->isNew) $object->set('creation_time', date("Y-m-d H:i:s"));
	            $object->set('modification_time', date("Y-m-d H:i:s"));
	            $object->set('modified_by', MySQLData::$user_id);
	            //$object->set('last_update_reason', $reason);
	        }else{
	            throw new Exception("object is not an object or array, meta-information cannot be added.");
	        }
	    }

	    public static function isMetaField($name){
	        if(strtolower($name) != 'id' && in_array(strtolower($name), Data::$core_fields)) return true;
	        else return false;
	    }
	
	   //TODO: experimental js function to stored procedure translator (func_name + src_hash) to perform a limited sort of distributed search on shards
        protected static function performSearch($subject, $predicate, $db){
			$object = $subject['object'];
            $dummyObject = new $subject['object'];
			$tableName = $dummyObject->tableName;
            $primary_key = $dummyObject->primaryKey;
            try{
                $discriminants = $predicate;
                $discText = array();
                foreach($discriminants as $discriminant){
					$key = $discriminant[0];
					if(!in_array($key, $dummyObject->fieldList) && ! MySQLData::isMetaField($key)) continue;
					$value = $discriminant[2];
					if(is_string($value) && (substr($discriminant[2], 0, 1) != '\'' && substr($discriminant[2], strlen($discriminant[2])-1, 1) != '\'')){
					   $value = "'$value'";
					}
                    $discText[] = $key.' '.trim($discriminant[1]).' '.$value;
                }
				$whereClause = implode(' and ', $discText);
				$sql = MySQLData::buildSelectionStatement( $tableName, $whereClause);
                $res = MySQLData::executeSQL($sql);
				foreach($res as $row){
	                $results[] = $row;	
				}
				return $results;
            }catch(Exception $ex){
                Logger::log('There was a MySQL error['.$ex->getMessage().']');
                echo($ex->getMessage());
            }
        }
        
        function __construct($id, $field=null){
            parent::__construct($id, $field);
            $this->options = array_merge($this->options, Data::$core_options);
        }

        protected function checkInitialization(){
            if( !isset($this->data[$this->primaryKey]) || empty($this->data[$this->primaryKey]) ){
                switch($this->key_type){
                    //generate a UUID for the object
                    case 'autoincrement':
                       	//do stuff
						$next_increment = 0;
						$qShowStatus = "SHOW TABLE STATUS LIKE '".$this->tableName."'";
						//echo $qShowStatus; exit;
						$qShowStatusResult = mysql_query($qShowStatus) or die(mysql_error());
						$row = mysql_fetch_assoc($qShowStatusResult);
						$next_increment = $row['Auto_increment'];
                        $this->newKey = $next_increment;
                        break;
                    default:
                        parent::checkInitialization();
                        break;
                }
                $this->firstSave = true;
            }
        }
        
        public static function getFieldOptions($field, $object){
            ($comment = $object->option($field, 'comment'))?$comment = 'COMMENT \''.$comment.'\'':$comment='';
            ($identifier = $object->option($field, 'identifier'))?true:false;
            ($required = $object->option($field, 'required'))?($required == 't'||$required == 'true'):false;
            ($size = (int)$object->option($field, 'size'));
            $options = array(
                'comment' => $comment,
                'identifier' => $identifier,
                'required' => $required,
                'size' => $size,
            );
            return $options;
        }
        
        public static function SQLType($column, $options, $object){
            $comment = $options['comment'];
            $identifier = $options['identifier'];
            $required = $options['required'];
            $size = $options['size'];
            switch(strtolower($object->type($column))){
                case 'binary' :
                    $type = 'LONGBLOB';
                    break;
                case 'integer' :
                    $type = 'INT';
                    break;
                case 'float' :
                case 'instant' :
                    $type = 'DATETIME';
                    break;
                case 'string' :
                default :
                    if(!$size) $size = 255;
                    if($size < 256) $type = 'VARCHAR('.$size.')';
                    else if($size > 256){
                        $isSmall = false;
                        if($size < 65536){
                            if($size < 16777216){
                                if($size < 4294967296){
                                    $type = 'LONGTEXT';
                                }else throw new Exception('String length is too large('.$size.' bytes)');
                            }else $type = 'MEDIUMTEXT';
                        }else $type = 'TEXT';
                    }else $type = 'TINYTEXT';
            }
            $sql = '`'.$column.'` '.$type.($required?' NOT NULL':'').($identifier?' AUTO_INCREMENT PRIMARY KEY':'').$comment;
            return $sql;
        }
        
        public static function initializeType($type, $object){
            $fields = array_merge(Data::$core_fields, $object->fields);
            $statement = 'CREATE TABLE '.$type." (\n";
            foreach($fields as $index=>$field){
                $statement .= $object->SQLType($field, $object->getFieldOptions($field, $object), $object).
                    ( (($index+1) != sizeOf($fields))?", \n":"\n" );
            }
            $statement .= ' '.")\n";
            Logger::log('SQL table initialized '.$statement);
            MySQLData::executeSQL($statement);
        }
        
        public static function expandType($type, $column, $object){
            $sql = 'ALTER TABLE `'.$type.'` ADD COLUMN '.$object->SQLType($column, $object->getFieldOptions($column, $object), $object);
            Logger::log('SQL table initialized '.$statement);
            MySQLData::executeSQL($sql);
        }

        
        protected function performLoad($id=null, $field='id'){
			if($id == null){
				if(isset($this->data[$this->primaryKey])){
					$id = $this->data[$this->primaryKey];
				}else{
					throw new Exception('MySQLData: No id value set).');
				}
			}
            $this->data[$this->primaryKey] = $id;
			$whereClause = $this->primaryKey . "=" . $id;
			$sql = MySQLData::buildSelectionStatement( $this->tableName, $whereClause);
			
            $res = MySQLData::executeSQL($sql, $this);

			if(count($res) == 1){
				$row = $res[0];
			    $this->data = $row;
            }else{
                unset($this->data[$this->primaryKey]); //if there's no data, this row does not exist -> unset primary key
                throw new Exception('MySQLData: Primary key not valid('.$selector.').');
            }			
			
        }
        
        protected function performSave(){
			MySQLData::setMetaFields($this, 'saved object');
            if(!$this->isNew){
	            //new object
	            MySQLData::executeSQL(MySQLData::buildInsertSQLFromObject($this), $this);
	            $id = mysql_insert_id();
	            $this->set($this->primaryKey, $id);
	        }else{
	            //updated object
	            MySQLData::executeSQL(MySQLData::buildUpdateSQLFromObject($this), $this);
	        }
        }

	    //static selection statement builder for getData, getObjects
	    protected static function buildSelectionStatement( $tableName, $whereClause, $orderClause, $limitClause, $calcRows = false ){
	        if( $calcRows ) {
	            $statement = 'SELECT SQL_CALC_FOUND_ROWS * FROM '.$tableName;
	        } else {
	            $statement = 'SELECT * FROM '.$tableName;
	        }

	        $seperator = ' WHERE ';
	        if(MySQLData::$activeCheck){
	            //we really need to change the record status field to a boolean
	            $statement .= " WHERE record_status='active'";
	            $seperator = ' AND ';
	        }
	        if($whereClause != null && trim($whereClause) != ''){
	            $statement .= $seperator.$whereClause;
	        }
	        if($orderClause != null && trim($orderClause) != ''){
	            $statement .= ' '.$orderClause;
	            //$statement .= 'ORDER BY '.$orderBy.' '.$this->orderDirection;
	        }
	        $statement .= ' '.$limitClause;
	        return $statement;
	    }
	    protected function makeLimitClause($allowLimit){
	        //if the limit argument is a boolean we build a limit, otherwise we pass the argument through
	        if(is_bool($allowLimit)){
	            $limitClause = $this->getLimitClauseFromPageValue();
	        }else{
	            if(is_numeric(substr(trim($allowLimit), 0, 1))){
	                $limitClause = 'LIMIT '.$allowLimit;
	            }else{
	                $limitClause = $allowLimit;
	            }

	        }
	        return $limitClause;
	    }

	    protected function makeOrderClause($orderBy){
	        //if there is an existing ordering, use that, otherwise use the one inside this object
	        $orderClause = null;

	        if( (strpos( strtolower($orderBy), 'asc' ) !== false) || strpos( strtolower($orderBy), 'desc' ) !== false ){
	            $orderClause = 'ORDER BY '.$orderBy;
	        }else{
	            if ( $this->orderDirection != null ) {
	                if(trim($orderBy) != '')$orderClause = 'ORDER BY '.$orderBy.' '.$this->orderDirection;
	                $orderClause = 'ORDER BY '.$this->primaryKey.' '.$this->orderDirection;
	            }
	        }
	        return $orderClause;
	    }
	
	    public static function buildUpdateSQLFromArray($array, $tableName, $fieldList, $primaryKey, $automatic){
	        $update = '';
	        $sep = '';
	        foreach ($fieldList as $name) {
	            if ((isset($array[$name])) && ($primaryKey != $name) && ($automatic != $name)) { // don't update the primary key
	                if (get_magic_quotes_gpc()) {
	                    $value = stripslashes($array[$name]);
	                } else {
	                    $value = $array[$name];
	                }
	                $value = mysql_real_escape_string($value);
	                $update .= $sep. '`' . $name . '`' ."='".$value."' ";
	                if($sep == '') $sep = ', ';
	            }
	        }
	        foreach (MySQLData::$metafields as $name) {
	            if ((isset($array[$name])) && ($primaryKey != $name) && ($automatic != $name)) { // don't update the primary key
	                Logger::log($name);
	                if(!($name == 'creation_time' && isset($array[$primaryKey]))){
	                    if (get_magic_quotes_gpc()) {
	                        $value = stripslashes($array[$name]);
	                    } else {
	                        $value = $array[$name];
	                    }
	                    $value = mysql_real_escape_string($value);
	                    $update .= $sep. '`' . $name . '`' ."='".$value."' ";
	                    if($sep == '') $sep = ', ';
	                }
	            }
	        }
	        $statement = 'UPDATE '.$tableName.' SET '.$update.' WHERE ';
	        $statement .= $primaryKey." = '".$array[$primaryKey]."'";
	//echo "update: " . $statement; exit;
	        return $statement;
	    }

	    public static function buildInsertSQLFromArray($array, $tableName, $fieldList, $primaryKey, $automatic, $key=null){
	        $sep = '';
	        $names = '';
	        $values ='';
			if($key != null){
				$array[$primaryKey] = $key;
			}
	        foreach ($array as $name=>$value) {
	            if( ((in_array($name, $fieldList)) || MySQLData::isMetaField($name)) && ($primaryKey != $name || $key != null) && ($name!=$automatic)){
	                if (get_magic_quotes_gpc()) {
	                    $value = stripslashes($array[$name]);
	                } else {
	                    $value = $array[$name];
	                }
	                $value = mysql_real_escape_string($value);
	                $values .= $sep."'".$value."' ";
	                $names .= $sep . '`' . $name . '`';
	                if($sep == '') $sep = ', ';
	            }
	        }
	

	        $statement = 'INSERT INTO '.$tableName.' ( '.$names.' ) VALUES ( '.$values.' )';
		//echo "insert: " . $statement; exit;
	        return $statement;
	    }
	    public static function buildUpdateSQLFromObject($object){ //convienience method for working with objects
	        return MySQLData::buildUpdateSQLFromArray($object->data, $object->tableName, $object->fields, $object->primaryKey, $object->automatic);
	    }

	    public static function buildInsertSQLFromObject($object){ //convienience method for working with objects
	        return MySQLData::buildInsertSQLFromArray($object->data, $object->tableName, $object->fields, $object->primaryKey, $object->automatic, $object->newKey);
	    }
	
	    public static function executeSQL($statement, $requestingObject, $database=null, $depth=0){
	        // /*  <- uncomment to shut off queries and output SQL
	        if(MySQLData::$debug){
	            Logger::log($statement.'<br/>');
	            $start = Logger::processing_time();
	        }
	        if(!isset(MySQLData::$db)) throw new Exception("SQL Error:" . 'DB Link is not set!');
	        if($database == null) $database = MySQLData::$db;
			/*
	        if(MySQLData::$masterSlaveMode){
	          $isSelect = preg_match('~^[ ]*[sS][eE][lL][eE][cC][tT] ~', $statement);
	          if($isSelect){
	              // we have a select statement so we are using the read-only DB
	              $database = MySQLData::$slaveDB;
	              if(!isset(MySQLData::$db)) throw new Exception("SQL Error:" . 'Slave DB Link is not set!');
	              Logger::log('Using the Slave DB Link');
	          }else{
	              Logger::log('Using the Master DB Link['.print_r($isSelect, true).']');
	          }
	        }
			*/
	        try{
	            $results = array();
	            if(! ( $SQLResult = mysql_query( $statement, $database ) ) ) {
	                throw new Exception("SQL Error:" . mysql_error(MySQLData::$db));
	            }
	            MySQLData::$affected_rows = mysql_affected_rows( MySQLData::$db );
	            if($SQLResult === true){
	                if(MySQLData::$debug) Logger::log('Query has no results<br/><br/>');
	                return array(); //if there are no results return here
	            }	            
	            //get the results (we don't support streaming sets)
		        if(MySQLData::$mysql_iMode) while ($row = mysqli_fetch_assoc($SQLResult) ) $results[] = $row;
		        else while($row = mysql_fetch_assoc($SQLResult) ) $results[] = $row;
	            if(MySQLData::$debug){
	                $time = MySQLData::processing_time($start);
	                Logger::log('Query has '.count($results).' results in '.$time.' seconds.<br/>');
	            }
	            if(MySQLData::$mysql_iMode){

		        }else{
		            mysql_free_result($SQLResult);
		        }
	            return $results;
	        }catch(Exception $ex){
                if(MySQLData::$debug){
			        if(MySQLData::$mysql_iMode){
                        
			        }else{
			            Logger::log(mysql_error($database).'<br/><br/>'.$statement.'<br/><br/>');  
			        }
				}
				$message = $ex->getMessage();
                if(preg_match("~SQL Error:Table '.*?\.dt_link-(.*?)-(.*?)' doesn't exist~", $message, $matches)){   // let's see if we are missing a link table
                    //TODO: implement linking
                    //if(Datasource::$debug) Logger::log('Link Store from '.$matches[1].' to '.$matches[2].' does not exist, the MySQLDatasource is creating one!<br/>');
                    //MySQLDatasource::initializeLink($matches[1], $matches[2]);
                    //MySQLDatasource::executeSQL($statement, $database, true);
                }else if(preg_match("~SQL Error:Table '.*?\.(.*?)' doesn't exist~", $message, $matches)){           // let's see if we are missing a storage table
                    if(MySQLData::$debug) Logger::log('Object Store for '.$matches[1].' does not exist, the MySQLData source is creating one!<br/>');
                    MySQLData::initializeType($matches[1], $requestingObject);
                    MySQLData::executeSQL($statement, $requestingObject, $database, true);
                }else if(preg_match("~SQL Error:Unknown column '(.*?)' in~", $message, $matches)){           // let's see if we are missing a storage table
                    $column = $matches[1];
                    //get the table
                    if($table = (preg_match("~^INSERT INTO ([a-zA-Z0-9_-]*) \\(.*$~", $statement, $matches))? $matches[1] :
                        (preg_match("~.*FROM `([a-zA-Z0-9_-]*)` WHERE.*$~", $statement, $matches)) ? $matches[1] : false
                    ){
                        MySQLData::expandType($table, $column, $requestingObject);
                        MySQLData::executeSQL($statement, $requestingObject, $database, true);
                    }else{
                        //todo: handle nicely
                    }
                }else throw $ex;
	        }
	    }
    }