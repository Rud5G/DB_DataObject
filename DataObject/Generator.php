<?
// +----------------------------------------------------------------------+
// | PHP Version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2002 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.02 of the PHP license,      |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Author:  Alan Knowles <alan@akbkhome.com>
// +----------------------------------------------------------------------+
//
/* generation tools for DB_DataObject
 *
 * @package DB_DataObject
 *
 * Config _$ptions
 * [DB_DataObject_Generator]
 * ; optional default = DB/DataObject.php
 * extends_location =      
 * ; optional default = DB_DataObject
 * extends =           
 * ; leave blank to not generate template stuff.
 * make_template = display,list,edit
 *
 * ; options for Template Generation (using FlexyTemplate
 * [DB_DataObject_Generator_Template_Flexy] 
 * templateDir = /home/xxx/templates 
 * compileDir = /home/xxx/compiled",
 * filters   = php,simpletags,bodyonly
 * forceCompile = 0
 *
 * ; fileds to flags as hidden for template generation(preg_match format)
 * hideFields = password        
 * ; fields to flag as read only.. (preg_match format)
 * readOnlyFields = created|person_created|modified|person_modified                   
 * ; fields to flag as links (from lists->edit/view) (preg_match formate)
 * linkFields = id|username|name
 *
*/

require_once('DB/DataObject.php');
require_once('Config.php');


class DB_DataObject_Generator extends DB_DataObject {

     /* =========================================================== */
    /*  Utility functions - for building db config files           */
    /* =========================================================== */
         
    /**
    * Array of table names
    *
    * @var array
    * @access private
    */  
    var $tables; 
        
    /**
    * associative array table -> array of table row objects
    *
    * @var array
    * @access private
    */
    var $_definitions; 
    /**
    * active table being output 
    *
    * @var string
    * @access private
    */
    
    var $table; // active tablename
    
     
    /**
    * The 'starter' = call this to start the process
    *
    * @access	public
    * @return 	none
    */
    function start() {
        $options = &PEAR::getStaticProperty('DB_DataObject','options');
        $databases = array();
        foreach($options as $k=>$v) {
            if (substr($k,0,9) == 'database_') {
                $databases[] = substr($k,9);
            }
        }
        
        
        if (@$options['database'] && !in_array($options['database'],$databases)) {
            $databases[] = $options['database'];
        }
        
        foreach($databases as $database) {
            if (!$database) continue;
            echo "CREATING FOR $database\n";
            $class = get_class($this);
            $t = new $class;
            $t->_database = $database;
            
            $t->_createTableList();

            foreach(get_class_methods($class) as $method) {
                if (substr($method,0,8 ) != 'generate') {
                    continue;
                }
                $t->$method();
            }
        }
        echo "DONE\n\n";
    }
    /**
    * Output File (Config object)
    * Used to generate the Tables
    *
    * @var object Config
    * @access private
    */
    
    var $_newConfig; 
    
    /**
    * Build a list of tables;
    * Currently this is very Mysql Specific - ideas for more generic stiff welcome
    *
    * @access	private
    * @return 	none
    */
    function _createTableList() {
        $this->query("show tables");
        $database = $this->_database;
        $var = "Tables_in_{$this->_database}";
        while ($this->fetch()) 
            $this->tables[] = $this->$var;
        foreach($this->tables as $table) {
            $t = new DB_DataObject;
            $t->query("describe {$table}");
            $this->_definitions[$table]=array();
            while ($t->fetch()) 
                $this->_definitions[$table][] = $t;
        }
    }
    /**
    * Auto generation of table data.
    *
    * it will output to db_oo_{database} the table definitions
    *
    * @access	private
    * @return 	none
    */
    function generateDefinitions() {
        echo "Generating Definitions file:        ";
        if (!$this->tables) {
            echo "-- NO TABLES -- \n";
            return;
        }
        
        $options = &PEAR::getStaticProperty('DB_DataObject','options');
        
        $this->_newConfig = new Config('IniFile');
        foreach($this->tables as $this->table)
            $this->_generateDefinitionsTable();
        
        $base =  $options['schema_location'];
        $file = "{$base}/{$this->_database}.ini";
        if (!file_exists($base)) 
            mkdir($base,0755);
        echo "{$options['schema_location']}\n";
        touch($file);
        //print_r($this->_newConfig);
        $ret = $this->_newConfig->writeInput($file,false);
        
        if (PEAR::isError($ret) )
            return PEAR::raiseError($ret->message,null,PEAR_ERROR_DIE);
    }
    /**
    * The table geneation part
    * 
    * @access	private
    * @return 	none
    */
    function _generateDefinitionsTable() {
        $defs = $this->_definitions[$this->table];
        foreach($defs as $t) {
            $n=0;
            $ttype = $t->Type;
            if ($p = strpos($t->Type,'(')) {
                $ttype = substr($t->Type,0,$p);
                $n = substr($t->Type,$p+1,strlen($t->Type) - $p -2);
            }
            switch (strtoupper($ttype)) {
            
                case "INT":
                case "INTEGER":
                case "TINYINT":
                case "SMALLINT":
                case "MEDIUMINT":
                case "BIGINT":
                case "REAL":
                case "DOUBLE":
                case "FLOAT":
                case "DECIMAL":
                case "NUMERIC":
                    $type=DB_DATAOBJECT_INT;
                    break;                
                case "CHAR":
                case "VARCHAR":
                case "TINYTEXT":
                case "TEXT":
                case "MEDIUMTEXT":
                case "LONGTEXT":
                case "TINYBLOB":
                case "BLOB":
                case "MEDIUMBLOB":
                case "LONGBLOB":
                case "DATE":
                case "TIME":
                case "TIMESTAMP":
                case "DATETIME":
                case "ENUM":
                case "SET": // not really but oh well
                    $type=DB_DATAOBJECT_STR;
                    break;
            }
            $this->_newConfig->setValue("/{$this->table}",$t->Field, $type);
            
            if ($t->Key == "PRI") 
                $this->_newConfig->setValue("/{$this->table}__keys",$t->Field, $type);
            
            
        }
        
    }    

    /* 
    * building the class files
    * for each of the tables output a file!
    */
    function generateClasses() {
        echo "Generating Class files:        \n";
        $options = &PEAR::getStaticProperty('DB_DataObject','options');
        $base = $options['class_location'];
        if (!file_exists($base)) 
            mkdir($base,0755);
        $class_prefix  = $options['class_prefix'];
        if ($extends = $options['extends']) {
            $this->_extends = $entends;
            $this->_extendsFile = $options['extends_location'];
        }

        foreach($this->tables as $this->table) {
            $this->_classInclude = str_replace('_','/',$class_prefix)."/" .ucfirst($this->table);
            $this->classname = $class_prefix.ucfirst($this->table);
            $i = '';
            $outfilename = "{$base}/".ucfirst($this->table).".php";
            if (file_exists($outfilename))
                $i = implode('',file($outfilename));
            $out = $this->_generateClassTable($i);
            echo "writing $this->classname\n";
            $fh = fopen($outfilename, "w");
            fputs($fh,$out);
            fclose($fh);
        }
        //echo $out;     
    }
    /**
    * class being extended (can be overridden by [DB_DataObject_Generator] extends=xxxx
    *
    * @var string
    * @access private
    */
    
    var $_extends = "DB_DataObject";
    /**
    * line to use for require('DB/DataObject.php');
    *
    * @var string
    * @access private
    */
    var $_extendsFile = "DB/DataObject.php";
    /**
    * class being generated
    *
    * @var string
    * @access private
    */
    var $_className;
    
    /**
    * The table class geneation part - single file.
    * 
    * @access	private
    * @return 	none
    */
    
    function _generateClassTable($input='') {
        // title = expand me!
        
        $head = "<?\n/*\n* Table Definition for {$this->table}\n*/\n\n";
        // requires
        $head .= "\n\nrequire_once('{$this->_extendsFile}');\n\n";
        // add dummy class header in...
        // class
        $head .= "class {$this->classname} extends {$this->_extends} {\n";
        
        
        
        $body =  "\n    ###START_AUTOCODE\n";
        $body .= "    /* the code below is auto generated do not remove the above tag */\n\n";
        // table
        $padding = (30 - strlen($this->table));
        if ($padding < 2) $padding =2;
        $p =  str_repeat(' ',$padding) ;    
        $body .= "    var \$__table='{$this->table}';  {$p}// table name\n";

        $defs = $this->_definitions[$this->table];
        
        // show nice information!
        $connections = array();
        $sets = array();
        foreach($defs as $t) {
            $padding = (30 - strlen($t->Field));
            if ($padding < 2) $padding =2;
            $p =  str_repeat(' ',$padding) ;    
            $body .="    var \${$t->Field};  {$p}// {$t->Type}\n";
            if (substr($t->Type,0,3) == "set")
                $sets[$t->Field] = "array".substr($t->Type,3);
            
        }
        // simple creation tools ! (static stuff!)
        $body .= "\n\n";
        $body .= "    /* Static get */\n";
        $body .= "    function staticGet(\$k,\$v=NULL) { return DB_DataObject::staticGet('{$this->classname}',\$k,\$v); }\n\n";
        
        /* 
        theoretically there is scope here to introduce 'list' methods 
        based up 'xxxx_up' column!!! for heiracitcal trees..
        
        */
       
       
        // set methods
        foreach ($sets as $k=>$v) {
            $kk = strtoupper($k);
            $body .="    function getSets{$k}() { return {$v}; }\n";
        }
        
        $body .= "\n    /* the code above is auto generated do not remove the tag below */";
        $body .= "\n    ###END_AUTOCODE\n";
        
        $foot .= "}\n?>";
        $full = $head . $body . $foot;
        
        if (!$input) return $full;
        if (!preg_match('/\n\s*###START_AUTOCODE\n/s',$input))  return $full;
        if (!preg_match('/\n\s*###END_AUTOCODE\n/s',$input))  return $full;
       
        $input = preg_replace(
            '/\nclass\s*[a-z_]+\s*extends\s*[a-z_]+\s*\{\n/si',
            "\nclass {$this->classname} extends {$this->_extends} {\n",
            $input);
       
        return preg_replace(
            '/\n    ###START_AUTOCODE\n.*\n    ###END_AUTOCODE\n/s',
            $body,$input);
    }
    
   
     
    
}
?>