<?php

//already loaded, bail 
if (class_exists("APICLIENTSOCK")) return false;

/**********************************************************************
  CLASS: APICLIENTSOCK
  PURPOSE:	A standalone library that code that exists outside
                of the tea module system (like scripts) can use to
                access the api.  uses http sockets via 
                file_get_contents to call the api
**********************************************************************/

class APICLIENTSOCK
{

  private $login;
  private $password;
  private $sessid;
  private $url;
  private $errorMessage;

  /******************************************************************
    FUNCTION:	__construct
    PURPOSE:	class constructor
    INPUTS:	url -> url to the api, including api script name
                login -> api login
                password -> api password
  ******************************************************************/    
  function __construct($url,$login,$password)
  {

    $this->url = $url;

    //we haven't logged in yet, do so
    if (!$_SESSION["api_session_id"])
    {

      //just use a keepalive command  
      $opt = array("command"=>"keepalive");

      //encode it
      $str = json_encode($opt);

      //call the api
      $url = $this->url."?login=".$login."&password=".$password."&apidata=".$str;
      $resp = file_get_contents($url);

      //convert the response
      $data = json_decode($resp,true);
      
      //throw an error if found
      if ($data["error"]) $this->throwError($data["error"]);    

      //all good, store the session for later
      else $_SESSION["api_session_id"] = $data["session_id"];
    
    }
    
  }

  /******************************************************************
    FUNCTION:	throwError
    PURPOSE:	stores errors thrown by this class
    INPUTS:	err (string) -> error message
    RETURNS:	none
  ******************************************************************/    
  function throwError($err)
  {
    $this->errorMessage = $err;
  }
  
  /******************************************************************
    FUNCTION:	error
    PURPOSE:	returns stored class error messages
    INPUTS:	none
    RETURNS:	error (string)
  ******************************************************************/    
  function error()
  {
    return $this->errorMessage;
  }

  /******************************************************************
    FUNCTION:	call
    PURPOSE:	calls the API, passes array as json command
    INPUTS:	opt (array) -> array of command info to pass to api
                  - command (string) -> class to execute in API,
                                        like tea_contact_get
                  - all others depend on class called
    RETURNS:	data (array) -> array of returned info from API
  ******************************************************************/    
  function call($opt)
  {

    //convert to json string
    $str = json_encode($opt);

    //setup our url, use the session from our login to bypass auth        
    $url = $this->url."?PHPSESSID=".$_SESSION["api_session_id"]."&apidata=".urlencode($str);
    $str = file_get_contents($url);

    //decode response to array
    $data = json_decode($str,true);

    //throw error if found    
    if (!is_array($data)) $this->throwError($data);
    else if ($data["error"]) $this->throwError($data["error"]);

    return $data;
  
  }

}

