<?php

class CONFIG 
{

  private $file;
  private $errorMessage;
  private $template;
  
  /******************************************************
    FUNCTION:	getError
    PURPOSE:	returns an existing class error
  ******************************************************/  
  public function getError()
  {
    return $this->errorMessage;
  }
  
  /******************************************************
    FUNCTION:	throwError
    PURPOSE:	throws a class error
  ******************************************************/  
  public function throwError($err)
  {
    $this->errorMessage = $err;
  }
  
  /******************************************************
    FUNCTION:	display
    PURPOSE:	displays the form for entering config
              information
  ******************************************************/  
  public function display()
  {

    $template = new TEMPLATE("config");
    $content = $template->buildForm(array("Required"));

    return $content;
    
  }

  /******************************************************
    FUNCTION:	process
    PURPOSE:	writes our submitted values to the
              config file and saves the file
  ******************************************************/  
  public function process()
  {

    $template = new TEMPLATE("config");
    $content = $template->mergePost(array("Required"));

    $this->testConfig();
    
  }

  /******************************************************
    FUNCTION:	testConfig
    PURPOSE:	verifies the given db parameters
  ******************************************************/  
  protected function testConfig()
  {

    $DB = new POSTGRESQL( $_POST["DBHOST"],
                          $_POST["DBUSER"],
                          $_POST["DBPASSWORD"],
                          $_POST["DBPORT"],
                          $_POST["DBNAME"]);

    if (!$DB->getConn())
    {
      $this->throwError("There was an error with your database configuration");
      return false;
    }

    $version = $DB->version();

    $arr = explode(".",$version);
    
    if ($arr[0] < 8) $prob = 1;
    else if ($arr[0] == 8 && $arr[1] < 4) $prob = 1;
    else $prob = null;

    if ($prob)
    {
      $this->throwError("You must be using Postgresql 8.4 or later.  Halting setup.");
      return false;
    }
    
  }
      
}


