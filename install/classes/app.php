<?php

class APP
{

  private $file;
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

    $template = new TEMPLATE("app");
    $content = $template->buildForm();
           
    return $content;
               
  }

  /******************************************************
    FUNCTION:	process
    PURPOSE:	writes our submitted values to the
              config file and saves the file
  ******************************************************/  
  public function process()
  {

    $template = new TEMPLATE("app");
    $content = $template->mergePost(array("Apps"));

    $this->testConfig();
        
  }

  protected function testConfig()
  {

    $apps = array_keys($_SESSION["app"]["Apps"]);

    foreach ($apps AS $entry)
    {

      $entry = strtoupper($entry);
      $arr = $_SESSION["app"]["Apps"][$entry];
      
      //if we aren't submitted an entry, bail here
      if (!$_POST[$entry] && $arr[4])
      {
        $this->throwError("You did not fill out the \"".str_replace("app_","",$entry)."\" field");
        break;
      }

      $app = $_POST[$entry];
      
      //remove any command line arguments
      $arr = explode(" ",$app);
      $app = $arr[0];

      //check for openoffice
      if ($entry=="openoffice_path")
      {

        $python = $app."/program/python";

        //now see if we can find it
        $output = `which $python`;
        $output = trim($output);

        if (!$output)
        {
          $this->throwError("Could not find the python application in the openoffice installation prefix");
        }
      
      }
      else
      {

        //now see if we can find it
        $output = `which $app`;
        $output = trim($output);

        if (!$output && $arr[4])
        {
          $this->throwError("Apache could not find application \"".$app."\" in ".$_SERVER["PATH"]);
        }
    
      }
        
    }

  
  }
      
}


    