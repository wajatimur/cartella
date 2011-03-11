<?php

class VERIFY
{

  private $errorMessage;
  
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

    $content = "<h3>Welcome to the DocMGR Installation Utility</h3>
                  <p>The installer will configure your database, verify minimum system requirements, and make
                  sure all external applications are installed correctly.</p>
                  <p>First we will make sure your system meets the minimum requirements</p>
                  <p>Press Next To Continue</p>
                  ";

    return $content;
    
  }

  /******************************************************
    FUNCTION:	process
    PURPOSE:	writes our submitted values to the
              config file and saves the file
  ******************************************************/  
  public function process()
  {

    $this->checkPerms();
    $this->checkPHP();
      
  }

  /******************************************************
    FUNCTION:	checkPerms
    PURPOSE:	makes sure we have write access to everything
  ******************************************************/  
  protected function checkPerms()
  {

    //can we write to this file to disable when done template
    if (!is_writeable("install/install.php"))
    {
      $this->throwError("install directory is not writeable");
      return false;
    }  

    //can we write to the config directory
    if (!file_put_contents("config/test.php","TEST"))
    {
      $this->throwError("Unable to write to config/ directory.  Please make sure
                        the user apache runs as has write access to this directory");
      return false;
    }
    else unlink("config/test.php");

  }

  protected function checkPHP()
  {
  
    //make sure we have the write versions of php
    $phpver = phpversion();
    
    $arr = explode(".",$phpver);
    
    //must be php 5.2
    if ($arr[0]<5) $prob = 1;
    else if ($arr[0]==5 && $arr[1]<2) $prob = 1;
    else $prob = null;

    //toss an error if it's not    
    if ($prob)
    {
      $this->throwError("DocMGR requires PHP Version 5.2 or later");
      return false;
    }

    //make sure magic quotes is not turned on
    if (get_magic_quotes_gpc()==1)
    {
      $this->throwError("You must turn of magic quotes in your php.ini file");
      return false;
    }      

    if (!extension_loaded("pgsql"))
    {
      $this->throwError("You must enable the pgsql extension for PHP");
      return false;
    }

    if (!extension_loaded("mbstring"))
    {
      $this->throwError("You must enable the mbstring extension for PHP");
      return false;
    }

    if (!extension_loaded("zip"))
    {
      $this->throwError("You must enable the zip extension for PHP");
      return false;
    }

  }
  
}
