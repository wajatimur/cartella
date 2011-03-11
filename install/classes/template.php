<?php

class TEMPLATE {

  private $templateFile;
  private $configFile;
  private $mode;
  private $errorMessage;
  private $template;
  private $templateSuffix;
        
  /********************************************************************
    FUNCTION:	__construct
    PURPOSE:	init class.  figure our input and output files
  ********************************************************************/  
  function __construct($mode)
  {
  
    $this->mode = $mode;
    
    if ($mode=="app")
    {
      $this->templateFile = "install/templates/app-config.template.php";
      $this->configFile = "config/app-config.php";
    }
    else
    {
      $this->templateFile = "install/templates/config.template.php";
      $this->configFile = "config/config.php";
    }

    
    //store in a session first time out
    if (!$_SESSION[$mode]) 
    {

      //include the file and store its values
      include($this->templateFile);
 
      $_SESSION[$mode] = $template;

      //merge the current config file values in
      $this->mergeConfigFile();

      //store in our class for later access
      $this->template = $_SESSION[$mode];
      
    }
    else
    {
      //snag the template values from the session
      $this->template = $_SESSION[$mode];
    }
    
  }

  function getTemplate()
  {
    return $this->template;
  }

  function throwError($err)
  {
    $this->errorMessage = $err;
  }
  
  function error()
  {
    return $this->errorMessage;
  }
    
  /********************************************************************
    FUNCTION:	buildForm
    PURPOSE:	init class.  figure our input and output files
  ********************************************************************/  
  function buildForm($sectionArr = null)
  {

    $content = null;

    //if no template variable, bail
    if (!$this->template)
    {
      $this->throwError("Error!  Could not find template variable in setup file");
      return false;
    }
    
    if (count($this->template)==0)
    {
      $this->throwError("Error!  No sections found in setup file");
      return false;
    }

    $sitePath = $this->template["Required"]["SITE_PATH"][0];

    //get our site path
    foreach ($this->template AS $sectionName=>$section)
    {

      //skip hidden sections
      if ($section["hidden"]) continue;

      //if passed a section filter, make sure this section is in that filter
      if ($sectionArr && !in_array($sectionName,$sectionArr)) continue;

      $keys = array_keys($section);

      $content .= "<h3>".$sectionName."</h3>\n";
      $content .= "<div style=\"margin-left:10px;margin-bottom:10px\">\n";
                  
      //layout = key => define name then array("value","enabled","description","hidden");    
      foreach ($keys AS $define)
      {
  
        $arr = $section[$define];

        //if hidden or disabled, skip it
        if ($arr[1]==false || $arr[3]==true) continue;

        $content .= "<p>\n";
        $content .= "<div class=\"formHeader\">".$arr[2]."</div>\n";
        $content .= "<input type=\"text\" size=\"60\" name=\"".$define."\" id=\"".$define."\" value=\"".$arr[0]."\">\n";
        $content .= "</p>\n";
      
      }

      $content .= "</div>\n";
      
    }

    return $content;

  }  

  /********************************************************************
    FUNCTION:	processForm
    PURPOSE:	init class.  figure our input and output files
  ********************************************************************/  
  function mergePost($filterArr = null)
  {
  
    //if no filter, use all of them
    if (!$filterArr) $filterArr = array_keys($_SESSION[$this->mode]);

    
    foreach ($filterArr AS $sectionName)
    {

      //now fill in the "Required" sections of our template
      $section = &$_SESSION[$this->mode][$sectionName];

      $keys = array_keys($section);

      foreach ($keys AS $entry)
      {

        //if we aren't submitted an entry, bail here
        if (!$_POST[$entry] && !$section[$entry][3])
        {
          $this->throwError("You did not fill out the \"".$entry."\" field");
          break;
        }
  
        //store the values in the session
        if ($_POST[$entry])   
          $e = $_POST[$entry];
        else
          $e = $section[$entry][0];
  
        $section[$entry][0] = $e;

      }
   
    }
    
  }
  
  function mergeConfigFile()
  {

    //loop through and replace defaults with our current config
    //file values
    if (file_exists($this->configFile))
    {

      $config = file_get_contents($this->configFile);
      
      include($this->configFile);

      $keys = array_keys($_SESSION[$this->mode]);

      foreach ($keys AS $key)
      {

        if ($key=="Suffix") continue;

        $section = &$_SESSION[$this->mode][$key];
    
        $forms = array_keys($section);
        
        //now we have all the forms for the section.  if already defined
        //use the previously defined values
        foreach ($forms AS $form)
        {

          if ($form=="hidden") continue;

          $arr = $this->extractValue($config,$form);
          
          //disable if disabled in the config file
          if ($arr["enabled"]==false) $section[$form][1] = false;
          else
          {
          
            $section[$form][0] = $arr["value"];
            $section[$form][1] = true;
          
          }

        }      
      
      }            
    
    }  

  //end func  
  }

  function extractValue($str,$field)
  {
  
    $arr = explode("\n",$str);
    $ret = array();

    foreach ($arr AS $line)
    {
    
      $line = trim($line);
      $check = "define(\"".$field."\",";
      
      //if we have the line with the define, stope here
      if (stristr($line,$check))
      {
      
        $worker = str_replace($check,"",$line);
        
        if (@constant($field))
        {

          $ret["enabled"] = true;

          //remove the ");" from the end
          $worker = substr($worker,0,strlen($worker)-2);
  
          //remove trailing and leading quotes
          if ($worker[0]=="\"") $worker = substr($worker,1);
          if ($worker[strlen($worker)-1]=="\"") $worker = substr ($worker,0,strlen($worker)-1);
          
          //replace any defines
          $worker = str_replace("SITE_PATH","[[SITE_PATH]]",$worker);
          $worker = str_replace("FILE_DIR","[[FILE_DIR]]",$worker);
          $worker = str_replace("SITE_URL","[[SITE_URL]]",$worker);

          //beginning and ending of defines included in a config option
          $worker = str_replace(".\"","",$worker);
          $worker = str_replace("\".","",$worker);
  
          $ret["value"] = $worker;
  
        }
        else
        {
          $ret["enabled"] = false;
        }
        
        break;          
        
      }
    
    }
  
    return $ret;
  
  }

  function writeFile()
  {

    $keys = array_keys($_SESSION[$this->mode]);
    
    $fileName = str_replace("config/","",$this->configFile);

    $output = "<?php\n\n";
    $output .= "/**************************************************************************\n\n";
    $output .= "\t".$fileName."\n\n";
    $output .= "\tThis file was automatically created by the installer.  You may \n";
    $output .= "\tedit it at any time.  The installer will migrate your settings \n";
    $output .= "\tinto the new config file at upgrade time.  Be sure to backup   \n";
    $output .= "\tthis file before attempting upgrades.  Non-standard config     \n";
    $output .= "\toptions should go in the custom-config.php file.               \n\n";
    $output .= "**************************************************************************/\n\n\n";
    
    foreach ($keys AS $key)
    {

      if ($key=="Suffix") continue;

      $section = &$_SESSION[$this->mode][$key];
    
      $output .= "/**********************************************************\n";
      $output .= "	".$key."\n";
      $output .= "***********************************************************/\n\n";
    
      $forms = array_keys($section);
      
      //now we have all the forms for the section.  if already defined
      //use the previously defined values
      foreach ($forms AS $form)
      {

        if ($form=="hidden") continue;

        $val = $section[$form][0];
        $enabled = $section[$form][1];
        $comments = $section[$form][2];
        
        $str = "//".$comments."\n";
        if (!$enabled) $str .= "//";

        //if val has [[ ]] in it, it's a define instead of a standard value
        if (strstr($val,"]]") && strstr($val,"[[")) 
        {

          $pos1 = strpos($val,"[[");
          $pos2 = strpos($val,"]]");
          $constant = substr($val,$pos1+2,$pos2-$pos1-2);

          $arr = explode("[[".$constant."]]",$val);

          $prefix = $arr[0];
          $suffix = $arr[1];

          $val = $constant;
          if ($prefix) $val = "\"".$prefix."\".".$val;
          if ($suffix) $val .= ".\"".$suffix."\"";

          $str .= "define(\"".$form."\",".$val.");\n";
        
        }
        else
        {
          $str .= "define(\"".$form."\",\"".addslashes($val)."\");\n";
        }
              
        $output .= $str."\n";

      }    
    
    }        

    //add any fixed portion of the config file template to our output
    $output .= $this->template["Suffix"];
   
    file_put_contents($this->configFile,$output);   
      
  }
  
  
  
}


