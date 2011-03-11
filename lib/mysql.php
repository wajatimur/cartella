<?php
/*****************************************************************************************
  Fileame: postgresql.php

  Purpose: Contains the functions required for the wrapper to access a PostgreSQL Database

  Created: 11-01-00
  Updated: 05-02-2005 - added db_close function
  Updated: 09-01-2005 - added db_escape_string function

******************************************************************************************/

CLASS MYSQL {

	private $conn;
	private $logger;

	function __construct($dbhost,$dbuser,$dbpassword,$dbport,$dbname) {
	
		$this->conn = mysql_connect($dbhost.":".$dbport,$dbuser,$dbpassword);
		mysql_select_db($dbname);

		//init the error handler
		$this->logger = new logger($this,"file");

	}

	function getErrorHandler() {
		return $this->logger;
	}

	function getConn() {
		return $this->conn;
	}

	function close() {
		mysql_close($this->conn);
	}

	function query($sql) {

		if (!$result = mysql_query($sql)) {
			$this->logger->logerror($sql);
		}

		return $result;
	
	}


	function fetch($sql,$transpose=null) {

		if (!$result = mysql_query($sql)) {
			$this->logger->logerror($sql);
			return false;
		}

		$num = mysql_num_rows($result);

		$arr = array();

		if ($num!=0) {
			for ($i=0;$i<$num;$i++) {
				$arr[$i] = mysql_fetch_assoc($result);
			}
		}
		
		//flip results if asked
		if ($transpose) $arr = transposeArray($arr);

		$arr["count"] = $num;
		return $arr;

	}

	function single($sql) {
	
		if (!$result = mysql_query($sql)) {
			$this->logger->logerror($sql);
			return false;
		}

		$num = mysql_num_rows($result);

		if ($num!=0) {
			return mysql_fetch_assoc($result);
		} else {
			return false;
		}
	
	}

	function error() {
	
		return $this->logger->getLastError();		
	
	}

	function getAllErrors($sep="html") {
		return $this->logger->getAllErrorMsgs($sep);
	}

	function count($sql) {

		if (!$result = mysql_query($this->conn,$sql)) {
			$this->logger->logerror($sql);
			return false;
		}
	
		return mysql_num_rows($result);
	
	}

	//some fun aliases
	function escort($sql,$transpose=null) {
		return $this->fetch($sql,$transpose);
	}
	
	function solo($sql) {
		return $this->single($sql);
	}

	function getId($table,$id,$result) {

			return mysql_insert_id();
	
	}

	//begins a transaction instance
	function begin() {

		return true;

	}
	
	//ends a transaction instance
	function end() {
		
		return false;

	}
	
	//vacuum the database
	function vacuum() {
	
		$sql = "VACUUM FULL ANALYZE";
	
		if (db_query($this->conn,$sql)) $message = "Database Vacuumed Successfully";
		else $message = "Database Vacuum Failed";
	
		return $message;
	
	}
	
	//escape a string to make it safe for db entry
	function escape_string($str) {
	
		return mysql_escape_string($str);
		
	}
	
	function unescape_string($str) {
	
		return str_replace("''","'",$str);
	
	}
	
	//get the last error from the database for this connection
	function last_error() {
	
		return mysql_error($this->conn);
	
	}


	function insert($table,$option,$idField = "id") {
	
		$ignoreArray = array("conn","table","debug","query","_showquery");
	
		$keys = array_keys($option);
	
		$fieldString = null;
		$valueString = null;
	
		for ($row=0;$row<count($keys);$row++) {
	
			$field = $keys[$row];
			$value = $option[$field];
	
			if (!in_array($field,$ignoreArray) && $value!=null) {
	
				$fieldString .= $field.",";
				$valueString .= "'".$value."',";
	
			}	
	
	
		}
	
		if ($fieldString && $valueString) {
	
			$fieldString = substr($fieldString,0,strlen($fieldString) - 1);
			$valueString = substr($valueString,0,strlen($valueString) - 1);
				
			$sql = "INSERT INTO $table (".$fieldString.") VALUES (".$valueString.");";
			if ($option["debug"]) echo $sql."<br>\n";
			if ($option["query"]) return $sql;
			if ($option["_showquery"]) file_put_contents("/tmp/query.sql",$sql);
			
			if ($result = $this->query($sql)) {
	
	      if ($idField) {		
	
	  			$returnId = $this->getId($table,$idField,$result);
	  			if ($returnId) return $returnId;
	  			else return true;
	
	      } else {
	        return true;
	      }
	
			} else return false;
	
		} else return false;
	
	}
	
	
	function update($table,$option,$sanitize = null) {
	
		$ignoreArray = array("conn","table","where","debug","query","_showquery");
	
		$keys = array_keys($option);
	
		$queryString = null;
	
		for ($row=0;$row<count($keys);$row++) {
	
			$field = $keys[$row];
			$value = $option[$field];
	
			if (!in_array($field,$ignoreArray)) {
	
				if ($value!=null) {
					if ($sanitize) 
						$queryString .= $field."='".sanitize($value)."',";
					else
						$queryString .= $field."='".$value."',";
				}
				else $queryString .= $field."=NULL,";
			} 	
	
	
		}
	
		if ($queryString) {
	
			$queryString = substr($queryString,0,strlen($queryString) - 1);
				
			$sql = "UPDATE $table SET ".$queryString." WHERE ".$option["where"];
	
			if ($option["debug"]) echo $sql."<br>\n";
			if ($option["query"]) return $sql;
			if ($option["_showquery"]) file_put_contents("/tmp/query.sql",$sql);
			
			if ($this->query($sql)) return true;
			else return false;
	
		} else return false;
	
	}
	
}
		