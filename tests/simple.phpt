--TEST--
DB::DataObject test
--SKIPIF--
<?php if (!@include(dirname(__FILE__)."/../DataObject.php")) print "skip"; ?>
--FILE--
<?php // -*- C++ -*-

// Test for: DB::parseDSN()
include_once dirname(__FILE__)."/../DataObject.php";
include_once 'PEAR.php';

$options = &PEAR::getStaticProperty('DB_DataObject','options');
$options['schema_location'] = dirname(__FILE__);
$options['database'] = 'mysql://@localhost/test';
$options['debug_force_updates'] = TRUE;
 
DB_DataObject::debugLevel(3);
// create a record
class test extends DB_DataObject {
	var $__table = 'test';
	 
    function doTests() {
        $this->createDB();
        $this->test1();
    }
    
    function createDB() {
        $this->query('DROP TABLE IF EXISTS test');
    
    
        $this->query(
            "CREATE TABLE test (
              id int(11) NOT NULL auto_increment PRIMARY KEY,
              name varchar(255) NOT NULL default '',
              username varchar(32) NOT NULL default '',
              password varchar(13) binary NOT NULL default '',
              firstname varchar(255) NOT NULL default '',
              lastname varchar(255) NOT NULL default '' 
            )"); 
    }
    
	function test1() {
        $this->createRecordWithName('test');
        $this->dumpTest(); 
        $t = new test;
        //$t->id = 1;
        /* delete everything with test and 'username'; */
        $t->name = 'test';
        $t->username = 'username';
        $t->delete();
        $this->dumpTest(); 
        /* update everything with username to firstname = 'fred' */
        $this->createRecordWithName('test');
        $t = new test;
        $t->whereAdd("username = 'username'");
        $t->firstname='fred';
        $t->update(TRUE);
        $this->dumpTest(); 
        /* now update based on key */
        $t= new test;
        $t->get(2);
        $t->firstname='brian';
        $t->update();
         $this->dumpTest();  
        
        
        
    }
    
    function createRecordWithName($name) {
        $t = new test;
        $t->name = $name;
        $t->username = 'username';
        $r= $t->insert(); 
        echo "INSERT got $r\n";
    }
    
    function dumpTest() {
        $t = new test;
        $t->find();
        if (!$t->N)  {
            echo "NO RESULTS!\n";
            return;
        }
        while ($t->fetch()) {
           $t->debugPrint();
        }
    }
    
    function debugPrint() {
        $t = $this;
        foreach(get_object_vars($t) as $k=>$v) {
            if ($k{0}== '_') {
                unset($t->$k);
            }
        }
        print_r($t);
    }
    
    
}



$t = new test;
$t->doTests();


?>
--GET--
--POST--
--EXPECT--