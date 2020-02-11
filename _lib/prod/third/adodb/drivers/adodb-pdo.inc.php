<?php
/*
v4.991 16 Oct 2008  (c) 2000-2008 John Lim (jlim#natsoft.com). All rights reserved.
  Released under both BSD license and Lesser GPL library license.
  Whenever there is any discrepancy between the two licenses,
  the BSD license will take precedence.
Set tabs to 4 for best viewing.

  Latest version is available at http://adodb.sourceforge.net

  Requires ODBC. Works on Windows and Unix.

        Problems:
                Where is float/decimal type in pdo_param_type
                LOB handling for CLOB/BLOB differs significantly
*/
// security - hide paths
if (!defined('ADODB_DIR')) die();


/*
enum pdo_param_type {
PDO::PARAM_NULL, 0

/* int as in long (the php native int type).
 * If you mark a column as an int, PDO expects get_col to return
 * a pointer to a long
PDO::PARAM_INT, 1

/* get_col ptr should point to start of the string buffer
PDO::PARAM_STR, 2

/* get_col: when len is 0 ptr should point to a php_stream *,
 * otherwise it should behave like a string. Indicate a NULL field
 * value by setting the ptr to NULL
PDO::PARAM_LOB, 3

/* get_col: will expect the ptr to point to a new PDOStatement object handle,
 * but this isn't wired up yet
PDO::PARAM_STMT, 4 /* hierarchical result set

/* get_col ptr should point to a zend_bool
PDO::PARAM_BOOL, 5


/* magic flag to denote a parameter as being input/output
PDO::PARAM_INPUT_OUTPUT = 0x80000000
};
*/

function adodb_pdo_type($t)
{
        switch($t) {
        case 2: return 'VARCHAR';
        case 3: return 'BLOB';
        default: return 'NUMERIC';
        }
}

/*--------------------------------------------------------------------------------------
--------------------------------------------------------------------------------------*/

////////////////////////////////////////////////



class ADODB_pdo extends ADOConnection {
        var $databaseType = "pdo";
        var $dataProvider = "pdo";
        var $fmtDate = "'Y-m-d'";
        var $fmtTimeStamp = "'Y-m-d, h:i:sA'";
        var $replaceQuote = "''"; // string to use to replace quotes
        var $hasAffectedRows = true;
        var $_bindInputArray = true;
        var $_genSeqSQL = "create table %s (id integer)";
        var $_autocommit = true;
        var $_haserrorfunctions = true;
        var $_lastAffectedRows = 0;

        var $_errormsg = false;
        var $_errorno = false;

        var $dsnType = '';
        var $stmt = false;

        var $debug = false;                 // forcando variavel debug devido a bug do php

        function __construct()
        {
        }

        function Time()
        {
                if (!empty($this->_driver->_hasdual)) $sql = "select $this->sysTimeStamp from dual";
                else $sql = "select $this->sysTimeStamp";

                $rs = $this->_Execute($sql);
                if ($rs && !$rs->EOF) return $this->UnixTimeStamp(reset($rs->fields));

                return false;
        }

    function _pconnect($argDSN, $argUsername = "", $argPassword = "", $argDatabasename = "", $arrExtraArgs = array(), $charset = '')
    {
        return $this->_connect($argDSN, $argUsername, $argPassword, $argDatabasename, $arrExtraArgs, $charset);
    }

        // returns true or false

        function _connect($argDSN, $argUsername="", $argPassword="", $argDatabasename="", $arrExtraArgs=array(), $charset='')
        {
                //file_put_contents("C:/wwwroot/teste.txt", "CONECTOU: ". $argDSN ."\r\n-----------------\r\n", FILE_APPEND);
                
                if ($argDSN != "") $this->host = $argDSN;
                if ($argUsername != "") $this->user = $argUsername;
                if ($argPassword != "") $this->password = $argPassword; // not stored for security reasons
                if ($argDatabasename != "") $this->database = $argDatabasename;

                $at = strpos($argDSN,':');
                $this->dsnType = substr($argDSN,0,$at);

                if ($argDatabasename) {
                    $argDSN .= ';dbname='.$argDatabasename;
                }
                try {
                        $this->_connectionID = new PDO($argDSN, $argUsername, $argPassword, $arrExtraArgs);

                        switch($this->dsnType)
                        {
                            case 'mysql':
                                    if(!empty($charset))
                                    {
                                        $this->_connectionID->exec("SET NAMES '". $charset ."'");
                                    }
                            break;
                            case 'pgsql':
                                    $this->_connectionID->exec("SET datestyle='ISO'");
                                    $this->_connectionID->exec("SET bytea_output='escape'");
                                    if(!empty($charset))
                                    {
                                            $this->_connectionID->exec("SET CLIENT_ENCODING TO '". $charset ."'");
                                    }
                            break;
                            case 'sqlsrv':
                                    $this->_connectionID->setAttribute(PDO::SQLSRV_ATTR_ENCODING, PDO::SQLSRV_ENCODING_SYSTEM);
                                break;
                        }
                } catch (Exception $e) {
                    
                        $this->_connectionID = false;
                        $this->_errorno = -1;
                        $this->_errormsg = 'Connection attempt failed: '.$e->getMessage();
                        return false;
                }

                if ($this->_connectionID) {
                        switch(ADODB_ASSOC_CASE){
                        case 0: $m = PDO::CASE_LOWER; break;
                        case 1: $m = PDO::CASE_UPPER; break;
                        default:
                        case 2: $m = PDO::CASE_NATURAL; break;
                        }

                        //$this->_connectionID->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_SILENT );
                        $this->_connectionID->setAttribute(PDO::ATTR_CASE,$m);

                        $class = 'ADODB_pdo_'.$this->dsnType;
                        //$this->_connectionID->setAttribute(PDO::ATTR_AUTOCOMMIT,true);
                        switch($this->dsnType)
                        {
                                case 'access':
                                case 'oci':
                                case 'mysql':
                                case 'pgsql':
                                case 'mssql':
                                case 'sqlsrv':
                                case 'sqlite':
                                case 'informix':
                                case 'dblib':
                                case 'odbc':
                                case 'firebird':
                                case 'ibm':
                                        include_once(ADODB_DIR.'/drivers/adodb-pdo_'.$this->dsnType.'.inc.php');
                                break;
                        }
                        
                        if (class_exists($class))
                            $this->_driver = new $class();
                        else
                            $this->_driver = new ADODB_pdo_base();
                        
                        $this->_driver->_connectionID = $this->_connectionID;

                        $this->_driver->host = $this->host;
                        $this->_driver->user = $this->user;
                        $this->_driver->password = $this->password;
                        $this->_driver->database = $this->database;

                        $this->_driver->dsnType = $this->dsnType;

                        $this->_UpdatePDO();
                        return true;
                }
                $this->_driver = new ADODB_pdo_base();
                return false;
        }

        // returns true or false

    function _UpdatePDO()
        {
            $d = &$this->_driver;
            $this->fmtDate = $d->fmtDate;
            $this->fmtTimeStamp = $d->fmtTimeStamp;
            $this->replaceQuote = $d->replaceQuote;
            $this->sysDate = $d->sysDate;
            $this->sysTimeStamp = $d->sysTimeStamp;
            $this->random = $d->random;
            $this->concat_operator = $d->concat_operator;
            $this->nameQuote = $d->nameQuote;

            $this->hasGenID = $d->hasGenID;
            if (isset($d->_genIDSQL)) {
                $this->_genIDSQL = $d->_genIDSQL;
            }
            $this->_genSeqSQL = $d->_genSeqSQL;
            if (isset($d->_dropSeqSQL)) {
                $this->_dropSeqSQL = $d->_dropSeqSQL;
            }

            $d->_init($this);
        }

        /*------------------------------------------------------------------------------*/

        function SelectLimit($sql,$nrows=-1,$offset=-1,$inputarr=false,$secs2cache=0)
        {
                $save = $this->_driver->fetchMode;
                $this->_driver->fetchMode = $this->fetchMode;
                if(isset($this->_driver->debug))
                {
                        $this->_driver->debug= $this->debug;
                }
                $ret = $this->_driver->SelectLimit($sql,$nrows,$offset,$inputarr,$secs2cache);
                $this->_driver->fetchMode = $save;
                return $ret;
        }


        function ServerInfo()
        {
                return $this->_driver->ServerInfo();
        }

        function MetaTables($ttype=false,$showSchema=false,$mask=false)
        {
                return $this->_driver->MetaTables($ttype,$showSchema,$mask);
        }

        function MetaIndexes($table, $primary = FALSE, $owner=false)
        {
                return $this->_driver->MetaIndexes($table, $primary, $owner);
        }

        function MetaColumns($table,$normalize=true)
        {
                return $this->_driver->MetaColumns($table,$normalize);
        }

        function InParameter(&$stmt,&$var,$name,$maxLen=4000,$type=false)
        {
                $obj = $stmt[1];
                if ($type) $obj->bindParam($name,$var,$type,$maxLen);
                else $obj->bindParam($name, $var);
        }

        function ErrorMsg()
        {
                if ($this->_errormsg !== false) return $this->_errormsg;
                if (!empty($this->_stmt)) $arr = $this->_stmt->errorInfo();
                else if (!empty($this->_connectionID)) $arr = $this->_connectionID->errorInfo();
                else return 'No Connection Established';


                if ($arr) {
                         if (sizeof($arr)<2) return '';
                        if ((integer)$arr[1]) return $arr[2];
                        else return '';
                } else return '-1';
        }


        function ErrorNo()
        {
                if ($this->_errorno !== false) return $this->_errorno;
                if (!empty($this->_stmt)) $err = $this->_stmt->errorCode();
                else if (!empty($this->_connectionID)) {
                        $arr = $this->_connectionID->errorInfo();
                        if (isset($arr[0])) $err = $arr[0];
                        else $err = -1;
                } else
                        return 0;

                if ($err == '00000') return 0; // allows empty check
                return $err;
        }

        function BeginTrans()
        {
                //file_put_contents("C:/wwwroot/teste.txt", "BEGIN TRANS \r\n-----------------\r\n", FILE_APPEND);
                if (!$this->hasTransactions) return false;
                if ($this->transOff) return true;
                $this->transCnt += 1;
                $this->_autocommit = false;
                try {
                  $this->_connectionID->setAttribute(PDO::ATTR_AUTOCOMMIT,false);
                } catch (Exception $e) {
                }
                return $this->_connectionID->beginTransaction();
        }

        function CommitTrans($ok=true)
        {
                //file_put_contents("C:/wwwroot/teste.txt", "COMMIT TRANS \r\n-----------------\r\n", FILE_APPEND);
                if (!$this->hasTransactions) return false;
                if ($this->transOff) return true;
                if (!$ok) return $this->RollbackTrans();
                if ($this->transCnt) $this->transCnt -= 1;
                $this->_autocommit = true;

                $ret = $this->_connectionID->commit();
                try {
                  $this->_connectionID->setAttribute(PDO::ATTR_AUTOCOMMIT,true);
                } catch (Exception $e) {
                }
                return $ret;
        }

        function RollbackTrans()
        {
                if (!$this->hasTransactions) return false;
                if ($this->transOff) return true;
                if ($this->transCnt) $this->transCnt -= 1;
                $this->_autocommit = true;

                $ret = $this->_connectionID->rollback();
                try {
                  $this->_connectionID->setAttribute(PDO::ATTR_AUTOCOMMIT,true);
                } catch (Exception $e) {
                }
                return $ret;
        }

        function Prepare($sql)
        {
                $this->_stmt = $this->_connectionID->prepare($sql);
                if ($this->_stmt) return array($sql,$this->_stmt);

                return false;
        }

        function PrepareStmt($sql)
        {
                $stmt = $this->_connectionID->prepare($sql);
                if (!$stmt) return false;
                $obj = new ADOPDOStatement($stmt,$this);
                return $obj;
        }


        /* returns queryID or false */
        function _query($sql,$inputarr=false)
        {
                //file_put_contents("C:/wwwroot/teste.txt", $sql ."\r\n-----------------\r\n", FILE_APPEND);

                if (is_array($sql)) {
                        $stmt = $sql[1];
                } else {
                        $stmt = $this->_connectionID->prepare($sql);
                }
                #adodb_backtrace();
                #var_dump($this->_bindInputArray);
                $ok = false;
                if ($stmt) {
                        if(isset($this->_driver->debug))
                        {
                                $this->_driver->debug = $this->debug;
                        }

                        if ($inputarr) $ok = $stmt->execute($inputarr);
                        else $ok = $stmt->execute();
                }


                $this->_errormsg = false;
                $this->_errorno = false;

                if ($ok) {
                        $this->_stmt = $stmt;
                        return $stmt;
                }

                if ($stmt) {

                        $arr = $stmt->errorinfo();
                        if ((integer)$arr[1]) {
                                $this->_errormsg = $arr[2];
                                $this->_errorno = $arr[1];
                        }

                } else {
                        $this->_errormsg = false;
                        $this->_errorno = false;
                }
                return false;
        }

        // returns true or false
        function _close()
        {
                $this->_stmt = false;
                return true;
        }

        function _affectedrows()
        {
                return ($this->_stmt) ? $this->_stmt->rowCount() : 0;
        }

        function _insertid()
        {
                return ($this->_connectionID) ? $this->_connectionID->lastInsertId() : 0;
        }

        function BlobDecode($blob,$maxsize=false,$hastrans=true, $blobtype='BLOB')
        {
                return $this->_driver->BlobDecode($blob,$maxsize,$hastrans, $blobtype);
        }

        function UpdateBlob($table,$column,$val,$where,$blobtype='BLOB')
        {
                return $this->_driver->UpdateBlob($table,$column,$val,$where,$blobtype);
        }

    function qstr($s,$magic_quotes=false)
    {
     if (!$magic_quotes) {
      if ($this->dsnType != 'odbc' && ADODB_PHPVER >= 0x4300) {
       if ($this->_connectionID) {
        return trim($this->_connectionID->quote($s));
                   }
      }
      if ($this->replaceQuote[0] == '\\'){
       $s = adodb_str_replace(array('\\',"\0"),array('\\\\',"\\\0"),$s);
      }
      return  "'".str_replace("'",$this->replaceQuote,$s)."'";
     }

     // undo magic quotes for "
     $s = str_replace('\\"','"',$s);
     return "'$s'";
    }
}



class ADODB_pdo_base extends ADODB_pdo {

        var $sysDate = "'?'";
        var $sysTimeStamp = "'?'";


        function _init($parentDriver)
        {
            $parentDriver->_bindInputArray = true;
            #$parentDriver->_connectionID->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY,true);
        }

        function ServerInfo()
        {
                return ADOConnection::ServerInfo();
        }

        function SelectLimit($sql,$nrows=-1,$offset=-1,$inputarr=false,$secs2cache=0)
        {
                $ret = ADOConnection::SelectLimit($sql,$nrows,$offset,$inputarr,$secs2cache);
                return $ret;
        }

        function MetaTables($ttype=false,$showSchema=false,$mask=false)
        {
                return false;
        }

        function MetaColumns($table,$normalize=true)
        {
                return false;
        }
}


class ADOPDOStatement {

        var $databaseType = "pdo";
        var $dataProvider = "pdo";
        var $_stmt;
        var $_connectionID;

        function __construct($stmt,$connection)
        {
                $this->_stmt = $stmt;
                $this->_connectionID = $connection;
        }

        function Execute($inputArr=false)
        {
                $savestmt = $this->_connectionID->_stmt;
                $rs = $this->_connectionID->Execute(array(false,$this->_stmt),$inputArr);
                $this->_connectionID->_stmt = $savestmt;
                return $rs;
        }

        function InParameter(&$var,$name,$maxLen=4000,$type=false)
        {

                if ($type) $this->_stmt->bindParam($name,$var,$type,$maxLen);
                else $this->_stmt->bindParam($name, $var);
        }

        function Affected_Rows()
        {
                return ($this->_stmt) ? $this->_stmt->rowCount() : 0;
        }

        function ErrorMsg()
        {
                if ($this->_stmt) $arr = $this->_stmt->errorInfo();
                else $arr = $this->_connectionID->errorInfo();

                if (is_array($arr)) {
                        if ((integer) $arr[0] && isset($arr[2])) return $arr[2];
                        else return '';
                } else return '-1';
        }

        function NumCols()
        {
                return ($this->_stmt) ? $this->_stmt->columnCount() : 0;
        }

        function ErrorNo()
        {
                if ($this->_stmt) return $this->_stmt->errorCode();
                else return $this->_connectionID->errorInfo();
        }
}

/*--------------------------------------------------------------------------------------
         Class Name: Recordset
--------------------------------------------------------------------------------------*/

class ADORecordSet_pdo extends ADORecordSet {

        var $bind = false;
        var $databaseType = "pdo";
        var $dataProvider = "pdo";

/*  PHP7      function __construct($id,$mode=false) */
        function __construct($id,$mode=false)
        {
            $this->ADORecordSet_pdo($id,$mode);
        }
        
        function ADORecordSet_pdo($id,$mode=false)
        {
            if ($mode === false) {
                global $ADODB_FETCH_MODE;
                $mode = $ADODB_FETCH_MODE;
            }
            $this->adodbFetchMode = $mode;
            switch($mode) {
            case ADODB_FETCH_NUM: $mode = PDO::FETCH_NUM; break;
            case ADODB_FETCH_ASSOC:  $mode = PDO::FETCH_ASSOC; break;

            case ADODB_FETCH_BOTH:
            default: $mode = PDO::FETCH_BOTH; break;
            }
            $this->fetchMode = $mode;

            $this->_queryID = $id;
            $this->ADORecordSet($id);
        }
        
        function Init()
        {
                if ($this->_inited) return;
                $this->_inited = true;
                if ($this->_queryID) @$this->_initrs();
                else {
                        $this->_numOfRows = 0;
                        $this->_numOfFields = 0;
                }
                if ($this->_numOfRows != 0 && $this->_currentRow == -1) {
                        $this->_currentRow = 0;
                        if ($this->EOF = ($this->_fetch() === false)) {
                                $this->_numOfRows = 0; // _numOfRows could be -1
                        }
                } else {
                        $this->EOF = true;
                }
        }

        function _initrs()
        {
        global $ADODB_COUNTRECS;

                $this->_numOfRows = ($ADODB_COUNTRECS) ? @$this->_queryID->rowCount() : -1;
                if (!$this->_numOfRows) $this->_numOfRows = -1;
                $this->_numOfFields = $this->_queryID->columnCount();
        }

        // returns the field object

    function _fetch()
    {
        if (!$this->_queryID) return false;


        $this->fields = $this->_queryID->fetch($this->fetchMode);
        return !empty($this->fields);
    }

    function _seek($row)
    {
        return false;
    }

    function _close()
    {
        $this->_queryID = false;
    }

    function Fields($colname)
    {
        if ($this->adodbFetchMode != ADODB_FETCH_NUM) return @$this->fields[$colname];

        if (!$this->bind) {
            $this->bind = array();
            for ($i = 0; $i < $this->_numOfFields; $i++) {
                $o = $this->FetchField($i);
                $this->bind[strtoupper($o->name)] = $i;
            }
        }
        return $this->fields[$this->bind[strtoupper($colname)]];
    }

    function FetchField($fieldOffset = -1)
    {
        $off=$fieldOffset+1; // offsets begin at 1

        $o= new ADOFieldObject();
        
        $arr = false;        
        try {
            $arr = @$this->_queryID->getColumnMeta($fieldOffset);
        } catch (Exception $e) {            
        }

        if (!$arr) {
                $o->name = 'bad getColumnMeta()';
                $o->max_length = -1;
                $o->type = 'VARCHAR';
                $o->precision = 0;
#                $false = false;
                return $o;
        }
        //adodb_pr($arr);
        $o->name = $arr['name'];
        if (isset($arr['sqlsrv:decl_type']) && $arr['sqlsrv:decl_type'] <> "null") {
                $o->type = $arr['sqlsrv:decl_type'];
        }
        elseif (isset($arr['native_type']) && $arr['native_type'] <> "null") {
                $o->type = $arr['native_type'];
        }
        else {
                $o->type = adodb_pdo_type($arr['pdo_type']);
        }
        $o->max_length = $arr['len'];
        $o->precision = $arr['precision'];

        if (ADODB_ASSOC_CASE == 0) $o->name = strtolower($o->name);
        else if (ADODB_ASSOC_CASE == 1) $o->name = strtoupper($o->name);
        return $o;
    }
}

?>