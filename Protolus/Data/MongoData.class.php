<?php
    abstract class MongoData extends Data{
        public static $lastQuery = '';
        public static $functionalMode = true;
        protected static $database = 'database';
        public static function initialize($options){
            if(!isset($options['host']) || strtolower($options['host']) == 'localhost'){
                $connection = new Mongo();
            }else{
                $connection = new Mongo($options['host']);
            }
            if(isset($options['database'])){
                $databaseName = $options['database'];
            }else{
                $databaseName = 'default_db';
            }
            $db = $connection->$databaseName;
            if($options['name']){
                Data::$registry[$options['name']] = $db;
                Logger::log('Initialized the DB('.$db.' @ '.$options['host'].') as \''.$options['name'].'\'');
            }else{
                //todo: warn
                Logger::log('Initialized the DB('.$db.' @ '.$options['host'].') without registering it');
            }
            return $db;
        }
        protected static function performSearch($subject, $predicate, $db){
            //print_r($predicate); exit();
            //$type = $subject['type'];
            $object = $subject['object'];
            $dummyObject = new $subject['object'];
            //print_r($dummyObject);
            //exit();
            $type = $object::$name;
            $primary_key = $dummyObject->primaryKey;
            try{
                $discriminants = $predicate;
                $discText = array();
                if(MongoData::$functionalMode){
                    //deprecated for now
                    /*
                    $operatorMapping = array(
                        '=' => '==',
                        '>' => '>',
                        '<' => '<',
                        '=>' => '=>',
                        '=<' => '=<',
                        '!=' => '!=',
                        '<>' => '!=',
                    );
                    foreach($discriminants as $discriminant){
                        if($discriminant[0] == $primary_key){
                            $discText[] = 'this.'.$primary_key.' '.$operatorMapping[trim($discriminant['operator'])].' '.(is_numeric($discriminant['value']) ? $discriminant['value'] : "'".$discriminant['value']."'");
                        }else{
                            $discText[] = 'this.'.$discriminant[0].' '.$operatorMapping[trim($discriminant[1])].' '.$discriminant[2];
                        }
                    }
                    //this is totally naive, but will work fine for simple selection
                    $array = array();
                    $js = 'function(){ return '.implode(' && ', $discText).'; }';
                    $collection = $db->$type;
                    MongoData::$lastQuery = $js;
                    Logger::log(MongoData::$lastQuery);
                    $cursor = $collection->find(array('$where' => $js)); //*/
                }else{
                    //the new way
                    $where = array();
                    foreach($discriminants as $discriminant){
                        switch($discriminant['operator']){
                            case '=':
                                $where[$discriminant['key']] = $discriminant['value'];
                                break;
                            case '>':
                                $where[$discriminant['key']]['$gt'] = $discriminant['value'];
                                break;
                            case '<':
                                $where[$discriminant['key']]['$lt'] = $discriminant['value'];
                                break;
                            case '>=':
                                $where[$discriminant['key']]['$gte'] = $discriminant['value'];
                                break;
                            case '<=':
                                $where[$discriminant['key']]['$lte'] = $discriminant['value'];
                                break;
                            case '!=':
                            case '<>':
                                $where[$discriminant['key']]['$ne'] = $discriminant['value'];
                                break;
                        }
                    }
                    $collection = $db->$type;
                    MongoData::$lastQuery = '$collection->find('.json_encode($where).')';
                    Logger::log('MongoDB:'.MongoData::$lastQuery);
                    $cursor = $collection->find($where);
                }
                $array = iterator_to_array($cursor);
                return $array;
            }catch(Exception $ex){
                Logger::log('There was a Mongo error['.$ex->getMessage().'] from query :'.MongoData::$lastQuery);
                echo '<PRE>' . __FILE__ . '>' . __LINE__ . ' ' . print_r($ex->getMessage(),true) . '</PRE>';
            }
        }

        function __construct($id, $field=null){
            parent::__construct($id, $field);

        }

        protected function checkInitialization(){
            if( !isset($this->data[$this->primaryKey]) || empty($this->data[$this->primaryKey]) ){
                switch($this->key_type){
                    //generate a UUID for the object
                    case 'mongoid':
                        $id = new MongoID();
                        $id = $id->__toString();
                        $this->data[$this->primaryKey] = $id;
                        break;
                    case 'autoincrement':
                        $id = new MongoAutoincrement($this->name.'-'.$this->primaryKey);
                        $id = $id->__toString();
                        $this->data[$this->primaryKey] = $id;
                        break;
                    default:
                        parent::checkInitialization();
                        break;
                }
                $this->firstSave = true;
            }
        }

        protected function performLoad($id, $field='') {
            //todo: default db;
            if($field == ''){
                //todo: issue a warning because your dumb ass passed in an empty value
                $field = $this->primaryKey;
            }
            $db = Data::$registry[$this->database];
            $class = get_class($this);
            $results = self::performSearch(array(
                'type'=>$class::$name,
                'object'=>$class
            ), array( array($field, '=',$id) ), $db);
            if(count($results) != 1){
                if(count($results) == 0) throw new Exception('Key in collection '.$class::$name.' ('.($field==null?$this->primaryKey:$field).') not found('.$id.') with search('.MongoData::$lastQuery.')!');
                else throw new Exception('Multiple primary keys found, this usually indicates a serious issue('.$id.':'.print_r($results, true).')!');
            }else{
                return current($results);
            }
        }

        protected function performSave() {
            try{
                $entry = $this->data;
                $db = Data::$registry[$this->database];
                if ( isset($entry[$this->primaryKey]) ) {
                    if(!is_numeric($entry[$this->primaryKey])){
                        $query  = array($this->primaryKey => $entry[$this->primaryKey]);
                    }else{
                        $query  = array($this->primaryKey => $entry[$this->primaryKey]);
                    }
                } else {
                    //throw new Exception('Something is wrong, the object has no primary key('.$this->primaryKey.')!');
                }
                $fieldsToUpdate = $this->data;
                if(!$this->firstSave){
                    unset($fieldsToUpdate[$this->primaryKey]);
                }
                unset($fieldsToUpdate['_id']);
                $class = get_class($this);
                $type = $class::$name;
                $collection = $db->$type;
                MongoData::$lastQuery = '$collection->update(
                    '.print_r($query, true).'
                    array(\'$set\' => '.print_r($fieldsToUpdate, true).'),
                    array(\'upsert\' => true, \'fsync\' => true, \'safe\' => true)
                );';
                //Logger::log(MongoData::$lastQuery);
                $res = $collection->update(
                    $query,
                    array('$set' => $fieldsToUpdate),
                    array('upsert' => true,'fsync' => true, 'safe' => true)
                );
                //$db->lastError();
                return $query[$this->primaryKey];
            }catch(Exception $ex){
                echo '<PRE>' . __FILE__ . '>' . __LINE__ . ' ' . ' Error!('.MongoData::$lastQuery.'):'. print_r($ex->getMessage(),true).'</PRE><br/>';
            }
        }

        public function increment($key, $amount=1){
            //do it this way to prevent mongo from getting stupid
            $query = array($this->primaryKey => $this->get($this->primaryKey));
            $update = array('$inc' => array(
                $key => $amount
            ));
            $type = $this::$name;
            $result = MongoAutoincrement::$db->command(array(
                'findandmodify' => $type,
                'query' => $query,
                'update' => $update,
                'new' => true
            ));
            MongoData::$lastQuery = '$collection->findAndModify('.$type.', '.print_r($query, true).', '.print_r($update, true).')';
            if($result['ok']) $this->set($key, $result['count']);
        }
    }
