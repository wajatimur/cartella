<?php

class FILES
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

    $content = "<h3>Filesystem Setup</h3>
                <p>The installer will now setup or update your DocMGR files directory</p>
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

    $this->checkFiles();
    
  }

  protected function checkFiles()
  {

    $sitePath = $_SESSION["config"]["Required"]["SITE_PATH"][0];
    $fileDir = str_replace("[[SITE_PATH]]",$sitePath,$_SESSION["config"]["Required"]["FILE_DIR"][0]);

    $dataDir = str_replace("[[FILE_DIR]]",$fileDir,$_SESSION["config"]["Required"]["DATA_DIR"][0]);
    $thumbDir = str_replace("[[FILE_DIR]]",$fileDir,$_SESSION["config"]["Required"]["THUMB_DIR"][0]);
    $previewDir = str_replace("[[FILE_DIR]]",$fileDir,$_SESSION["config"]["Required"]["PREVIEW_DIR"][0]);
    $docDir = str_replace("[[FILE_DIR]]",$fileDir,$_SESSION["config"]["Required"]["DOC_DIR"][0]);
    $tmpDir = str_replace("[[FILE_DIR]]",$fileDir,$_SESSION["config"]["Required"]["TMP_DIR"][0]);
    
    //setup our directories  
    $this->createFileSubDir($dataDir);
    $this->createFileSubDir($thumbDir);
    $this->createFileSubDir($previewDir);
    $this->createFileSubDir($docDir);
    if (!is_dir($tmpDir)) mkdir($tmpDir);
    //if (!is_dir($homeDir)) mkdir($homeDir);
    //if (!is_dir($importDir)) mkdir($importDir);

  }

  //create our subdirectories for storage
  function createFileSubDir($path) 
  {

    $level1num = $_SESSION["config"]["Unchangeable"]["LEVEL1_NUM"][0];
    $level2num = $_SESSION["config"]["Unchangeable"]["LEVEL2_NUM"][0];

    //create our directory if it doesn't exist
    if (!is_dir($path)) mkdir($path);

    //if it's not writable, error out
    if (!is_writable($path)) die("Error!".$path." is not writable by the webserver");

    //check to make sure it doesn't exist already
    if (!is_dir($path."/1")) 
    {

      //make our first level of directories
      for ($i=1;$i<=$level1num;$i++) 
      {

        $level1Data = $path."/".$i;
        @mkdir($level1Data);

        for ($c=1;$c<=$level2num;$c++) 
        {

          $level2Data = $level1Data."/".$c;
          @mkdir($level2Data);

        }

      }
 
    }

  }
  
}
