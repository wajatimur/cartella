<?php
/***********************************************************************
 FILE: error.php

 PURPOSE: File contains functions used for displaying error
          returned by processing in Tea

/***********************************************************************/

//constants for the log levels
define("LOGGER_DEBUG","5");
define("LOGGER_WARNING","3");
define("LOGGER_ERROR","1");

/************************************************************
  categories:
  AUTH
  IMAP
  COMPANY

************************************************************/

class LOGGER {

  private $DB;
  private $logData;
  private $mode;
  private $errorStack = array();
        
  /******************************************************************************
    FUNCTION: construct
    PURPOSE:	our constructor.  inits all required variables and sets up object
  ******************************************************************************/
  public function __construct($dbref) {

    $this->DB = $dbref;
    $this->mode = LOGGER_MODE;
    
  }

  /********************************************************
    FUNCTION: logerror
    PURPOSE:	log sql errors
  ********************************************************/
  public function logerror($sql) {

    //get the last error message from sql  
    $msg = $this->DB->last_error();

    //store it in our array for access
    $this->logData["sql"] = $sql;
    $this->logData["message"] = $msg;
    $this->logData["log_timestamp"] = date("Y-m-d H:i:s");
    $this->logData["ip_address"] = $_SERVER['REMOTE_ADDR'];    
    if (defined("USER_ID")) $this->logData["user_id"] = USER_ID;
    if (defined("USER_LOGIN")) $this->logData["user_login"] = USER_LOGIN;
    if (defined("CUR_CHILD")) $this->logData["child_location_id"] = CUR_CHILD;
    $this->logData["post_data"] = $this->dataToXML($_POST);
    $this->logData["get_data"] = $this->dataToXML($_GET);
    $this->logData["category"] = "DB_ERROR";
    $this->logData["level"] = LOGGER_ERROR;

    //add it to our stack
    $this->errorStack[] = $this->logData;
    
    $this->logMsg();

    if (defined("LOG_ERROR_TASK") && $sql) $this->logToTask();
    
  }

  /******************************************************************************
    FUNCTION: log
    PURPOSE:	logs the specified message
  ******************************************************************************/
  public function log($msg,$level=null,$category=null,$data=null) {

    //if passed a level and we're below the configured logger level, don't do anything
    //if ($level && $level<LOGGER_LEVEL) return false;

    //store it in our array for access
    if (defined("USER_ID")) $this->logData["user_id"] = USER_ID;
    if (defined("USER_LOGIN")) $this->logData["user_login"] = USER_LOGIN;
    if (defined("CUR_CHILD")) $this->logData["child_location_id"] = CUR_CHILD;
    $this->logData["log_timestamp"] = date("Y-m-d H:i:s");
    $this->logData["ip_address"] = $_SERVER['REMOTE_ADDR'];    
    $this->logData["message"] = $msg;
    if ($level) $this->logData["level"] = $level;
    if ($category) $this->logData["category"] = $category;

    if ($data) {
      $this->logData["post_data"] = $this->dataToXML($_POST);
      $this->logData["get_data"] = $this->dataToXML($_GET);
    }    

    //log the error to the file
    $this->logMsg();

  }

  /******************************************************************************
    FUNCTION: logMsg
    PURPOSE:	calls appropriate logging function based on our mode
  ******************************************************************************/
  private function logMsg() {

    //log our error message to our log file
    $arr = $this->logData;

    if ($this->mode=="db") $this->logToDB($arr);
    elseif ($this->mode=="xml") $this->logToXML($arr);
    elseif ($this->mode=="file") $this->logToFile($arr);

  }
  
  /**********************************************************
    write our log into the database
  **********************************************************/
  private function logToDB($arr) {

    $arr = sanitizeArray($arr);

    //insert the info into the database, don't allow logging of any error
    $this->DB->insert("logger.logs",$arr,null,1);    

  }

  private function logToTask() {

    global $argc;
    $arr = sanitizeArray($this->logData);
  
    //create our error message
    $msg = "SQL: ".$arr["sql"]."\n\n";
    $msg .= "MESSAGE: ".$arr["message"]."\n\n";
    $msg .= "TIME: ".$arr["log_timestamp"]."\n\n";
    $msg .= "USER_LOGIN: ".$arr["user_login"]."\n\n";
    $msg .= "CHILD_LOC: ".$arr["child_location_id"]."\n\n";
    $msg .= "REQUEST_URI: ".$_SERVER["REQUEST_URI"]."\n\n";
    $msg .= "QUERY_STRING: ".$_SERVER["QUERY_STRING"]."\n\n";
    $msg .= "SCRIPT_FILENAME: ".$_SERVER["SCRIPT_FILENAME"]."\n\n";
    $msg .= "SCRIPT_NAME: ".$_SERVER["SCRIPT_NAME"]."\n\n";
    $msg .= "ARGC: ".implode("-",$argc)."\n\n";
    
    //setup the task    
    $opt = null;
    $opt["accountId"] = ADMIN_USERID;
    $opt["title"] = "Database error detected in ".APP_NAME;
    $opt["notes"] = $msg;
    $opt["taskType"] = "helpme";
    $opt["helpme_type"] = "project";
    $opt["projectId"] = TEA_PROJECTID;
    $opt["helpme_status"] = 1;
        
    if (!class_exists("TASK")) include("app/task.php");
    $t = new TASK();
    $t->save($opt);
  
  }
  
  /**********************************************************
    write our log into an xml file
  **********************************************************/
  private function logToXML($arr) {

    $xml = null;

    $xml .= "<log>\n";

    foreach ($arr AS $key => $data) {

      //skip post and get
      if (is_numeric($key)) continue;
      $xml .= xmlEntry($key,$data);

    }

    $xml .= "</log>\n";

    if (defined("ALT_FILE_PATH")) $path = ALT_FILE_PATH."/".FILE_DIR;
    else $path = FILE_DIR;
    
    $logdir = $path."/logger";
    
    if (!file_exists($logdir)) mkdir($logdir);
    
    file_put_contents($logdir."/log.xml",$xml,FILE_APPEND);
  
  }

  /**********************************************************
    write our message into a log file
  **********************************************************/
  private function logToFile($arr) {

    $str = "=========================== Begin Log ===============================\n";

    foreach ($arr AS $key => $data) {

      //skip post and get
      if (is_numeric($key)) continue;
      $str .= strtoupper($key).": ".$data."\n";

    }

    $str .= "============================= End Log ===============================\n";

    if (defined("ALT_FILE_PATH")) $path = ALT_FILE_PATH."/".FILE_DIR;
    else $path = FILE_DIR;
    
    $logdir = $path."/logger";
    
    if (!file_exists($logdir)) mkdir($logdir);
    
    file_put_contents($logdir."/log.txt",$str,FILE_APPEND);
  
  }

  private function dataToXML($data) {

    $str = null;

    if (count($data) > 0) {

      $xml = null;

      foreach ($data AS $key => $data) {

        //skip numeric keys because they're bad (invalid xml)
        if (is_numeric($key)) continue;
        $xml .= xmlEntry($key,$data);

      }

      $str = base64_encode($xml);
      
    }

    return $str;

  }

  /******************************************************************************
    FUNCTION: getLastError
    PURPOSE:returns error data on the last db error.returns false if there
    is no error to return
  ******************************************************************************/
  public function getLastError() {		
    $num = count($this->errorStack);
    if ($num=="0") return false;
    else return $this->errorStack[$num-1];
  }

  /******************************************************************************
    FUNCTION: getLastErrorMsg
    PURPOSE:returns error data on the last db error.returns false if there
          is no error to return
  ******************************************************************************/
  public function getLastErrorMsg($sep = "html") {

    $num = count($this->errorStack);
    if ($num=="0") return false;
    else {

      if ($sep=="html") $div = "<br>\n";
      else $div = "\n";

      $msg = $this->errorStack[$num-1]["msg"];

      //add the query to the message if in dev mode
      if (defined("DEV_MODE")) $msg .= $div.$this->errorStack[$num-1]["query"];

      return $msg;

    }

  }

  /******************************************************************************
    FUNCTION: getAllErrorMsgs
    PURPOSE:returns error data in the error stack
            is no error to return
  ******************************************************************************/
  public function getAllErrorMsgs($sep = "html") {

    $num = count($this->errorStack);

    if ($sep=="html") $div = "<br>\n";
    else $div = "\n";

    if ($num > 0) {

      $str = null;	

      for ($i=0;$i<$num;$i++) {

        //append our error message and teh query as well if in dev mode
        $msg = $this->errorStack[$i]["msg"];
        if (defined("DEV_MODE")) $msg .= $div.$this->errorStack[$i]["query"];
        $msg .= $div;

        $str .= $msg;

      }
      return $str;

    } else return false;

  }

}

