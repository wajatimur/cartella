<?php
return false;
  
//required files
include("lib/pgsql.php");
include("lib/logger.php");
include("lib/misc.php");
include("lib/xml.php");
include("config/version.php");
include("classes/template.php");

//which step are we on
session_start();

//we made it to here, record there's a setup in process
$_SESSION["installInProgress"] = 1;

//our main theme
define("THEME_PATH","themes/default");

//start at the beginning
if (!$_POST["step"]) 
{

  $_POST["step"] = "0";
  $_POST["nextstep"] = "0";

}

//init our base and app config setup classes
$cf = new TEMPLATE("config");
$af = new TEMPLATE("app");


//setup our possible config classes
$steps = array();
$stepArr[] = "verify";
$stepArr[] = "config";
$stepArr[] = "database";
$stepArr[] = "files";
$stepArr[] = "app";

//get their files
foreach ($stepArr AS $file)
{
  require_once("classes/".$file.".php");
}

//init our current class based on the step we're on
$curClass = $stepArr[$_POST["step"]];
$c = new $curClass();

//if there's a submitted process, handle it
if ($_POST["action"]=="next") 
{

  $c->process();

  //if no errors loads the next step
  $err = $c->getError();
  
  if (!$err) 
  {
    $_POST["step"]++;

    //if there's another class to load, load it.
    if ($stepArr[$_POST["step"]])
    {

      $curClass = $stepArr[$_POST["step"]];
      $c = new $curClass();

    }
    //otherwise bail
    else
    {

      $str = file_get_contents("install/install.php");

      $str = preg_replace("/<\?php\n/","<?php\nreturn false;\n",$str);
      
      file_put_contents("install/install.php",$str);

      $finished = 1;
    
    }

  } else $errorMessage = $err;

}
//go back a page
else if ($_POST["action"]=="back")
{

  $_POST["step"]--;

  $curClass = $stepArr[$_POST["step"]];
  $c = new $curClass();

}

//setup our main form
$siteContent = "
<form name=\"pageForm\" method=\"post\">
<input type=\"hidden\" name=\"step\" id=\"step\" value=\"".$_POST["step"]."\">
<input type=\"hidden\" name=\"action\" id=\"action\" value=\"\">
";

//all done, show the final message
if ($finished) 
{

  //write our files
  $cf->writeFile();
  $af->writeFile();

  $siteContent .= "<div style=\"padding:10px;width:600px;\">
                    <h3>Your setup is complete.</h3>
                    <p>
                      If you want to run setup again, just remove the \"return false;\" 
                      line at the top of the install/install.php file.  You may also
                      safely remove the entire install/ directory if you are done with setup.
                    </p>
                    <p>
                      If this is a new installation, the default username and password is admin/admin.
                    </p>
                    <p>
                      <a href=\"index.php\">Click to login</a>
                    </p>
                  </div>
                  ";

}
//still going, load main config form
else
{

  //toolbar
  $siteContent .= "<div class=\"toolbar\">";

  //give us a back button if not on first page
  if ($_POST["step"]!=0) 
  {
    $siteContent .= "<div class=\"toolbarCell\" onclick=\"document.pageForm.action.value='back';document.pageForm.submit();\">
                      <img src=\"".THEME_PATH."/images/icons/back.png\" align=\"left\"> Back
                     </div>
                     ";
  }

  //next button, end toolbar, show class content
  $siteContent .= "		<div class=\"toolbarCell\" onclick=\"document.pageForm.action.value='next';document.pageForm.submit();\">
                        <img src=\"".THEME_PATH."/images/icons/next.png\" align=\"left\"> Next
                      </div>
                    </div>
                    <div class=\"errorMessage\">".$errorMessage."</div>
                    <div style=\"width:600px;padding-left:10px;\">
                      ".$c->display()."
                    </div>
                    ";
  

}

//end form
$siteContent .= "</form>";

//load our display template
include("normal.php");

//always stop here to prevent the anything else from loading
die;
