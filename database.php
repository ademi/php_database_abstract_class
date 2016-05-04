<?php
/* 
 * Designed by: Mohammed Nasser Al-ademi
 * Date : 4/4/2016
 */
if(!defined('LEGAL_ACCESS')||LEGAL_ACCESS != TRUE)exit(); // you're not suppose to be here!

// Declaration of setting constants 
// this should be declared on a separate .ini file instead
define("DATABASE","db_name");
define("DB_HOST","host");
define("DB_READER","reader");       //user with reading permissions only
define("DB_READER_PASS","123321");
define("DB_WRITER","writer");       //user with absolute privileges 
define("DB_WRITER_PASS","12344321");

class db{
	private $dbh =NULL;         //Object handler
	private $statement;         
	private $start_flag =0;     // a flag to indicate if autoCommit is off (0 is autoCommit is on while 1 is autoCommit is on)
        private $lastId;            // the id of the last changed row
        /*
         * constructor:
         * input: string type (either reader or writer)
         * function: sets up the database handler with setting up the desired flags
         * return : none
         */
	public function __construct($type='r'){
		try{	
			if(trim($type) =='r'){
				 $options = array( PDO::ATTR_PERSISTENT => true,PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION);
				 $this->dbh= new PDO('mysql:host='.DB_HOST.';dbname='.DATABASE.';charset=utf8',DB_READER, DB_READER_PASS,$options);
			}
			else if(trim($type)=='w'){
				 $options = array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION);
				 $this->dbh = new PDO('mysql:host='.DB_HOST.';dbname='.DATABASE.';charset=utf8',DB_WRITER, DB_WRITER_PASS,$options);
			}
			else{
				throw new Exception("Unknown user Type");
			}
		}
		catch(PDOException $e) {$e->getMessage();}
		catch(Exception $e) {$e->getMessage();}
	}
        /*
         * function query:
         * input: string statement to execute
         * functionality: prepares statement to be executed
         * return : none
         */
	public function query($stm){
		$this->statement = $this->dbh->prepare($stm);
	}

        /*
         * function bind:
         * input: associated array where keys hold parameters while values hold the value of the parameters
         * ex: array(":age"=>1,":name"=>"ademi")
         * functionality: prepares statement to be executed
         * return : none
         */
	public function bind($params){
            foreach($params as $param=>$val){
                if( is_numeric($val))		$type=PDO::PARAM_INT;
                else if (is_bool($val))		$type=PDO::PARAM_BOOL;
                else if (is_null($val))		$type=PDO::PARAM_NULL;
                else 				$type=PDO::PARAM_STR;
		
                if(substr(trim($param),0,1)!=":")$param =":".trim($param);
		$this->statement->bindValue($param,$val,$type);
            }
	}
        
        /*
         * function execute:
         * input: None
         * functionality: excutes prepared statements and rolls back commits if execution is unsuccessful
         * return : true if execution is successful else false
         */
	public function execute(){ 
            $result = $this->statement->execute();
            if(!$result)
            {
                if($this->start_flag==1) $this->cancel();
                return FALSE;
            }
            else return TRUE;
            
        }
        /*
         * function get_obj:
         * input: None
         * functionality: return the results as objects
         * return : object
         */
	public function get_obj(){
		$this->execute();
		return $this->statement->fetchAll(PDO::FETCH_OBJ);
	}
        /*
         * function get_assoc_array:
         * input: None
         * functionality: returns results as associated array
         * return : none
         */
	public function get_assoc_array(){
		$this->execute();
		return $this->statement->fetchAll(PDO::FETCH_ASSOC);
	}
        /*
         * function get_array:
         * input: None
         * functionality: returns results as array
         * return : none
         */
        public function get_array(){
		$this->execute();
		return $this->statement->fetchAll();
	}
        /*
         * function rowCoutn()
         * input: None
         * functionality: returns number of effected rows
         * return : none
         */
	public function rowCount()	{return $this->statement->rowCount();}
        /*
         * function get_assoc_array:
         * input: None
         * functionality: returns last effected id of the effected array
         * return : none
         */
	public function lastId()	{return $this->lastId;}
        /*
         * function get_assoc_array:
         * input: None
         * functionality: turns off auto commit to enable rolling back if unsuccessful execution
         * return : none
         */
	public function start()		{
            $this->dbh->beginTransaction();
            $this->start_flag =1;
        }
        /*
         * function commit:
         * input: None
         * functionality: commit changes 
         * return : none
         */
	public function commit(){
            if($this->start_flag==1){
                $this->dbh->commit();
            }
        }
        /*
         * function get_assoc_array:
         * input: None
         * functionality: roll back changes if autoCommit is off
         * return : none
         */
	public function cancel(){
            if($this->start_flag==1){
                $this->dbh->rollback();
                $this->start_flag =0;
            }
            else throw new Exception ("An error occured without rolling back the last query");
        }
}
/*
 * Abstract class which ensures only one database user is initiated throughout the application
 */
class database_factory{
	private static $db_reader = Null;
	private static $db_writer = Null;
	
	public static function get_reader(){
		if(self::$db_reader == Null)self::$db_reader = new db('r');
		return self::$db_reader;
	}
	public static function get_writer(){
		if(self::$db_writer == Null)self::$db_writer = new db('w');
		return self::$db_writer;
	}
}

/** usage example *********************************************************
$d_b =  database_factory::get_writer();
$d_b->start();
$d_b->query("INSERT INTO `TEST` (`NAME`,`AGE`) VALUES(:name , :age)");
$d_b->bind(array(":name"=>"humon","age"=>12);
$d_b->bind(":age","12");
$d_b->execute();
$d_b->commit();
$reader = database_factory::get_reader();
$reader ->query("SELECT * FROM `TEST`");
$array= $reader ->get_assoc_array();
print_r($array);
 * 
 */
