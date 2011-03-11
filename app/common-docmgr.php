<?php


/*********************************************************************************
  FILE:     common-docmgr.php
  PURPOSE:  contains common functions for docmgr interface
*********************************************************************************/


/*******************************************************************************
  FUNCTION:	callDocmgr
  PURPOSE:	calls the docmgr api and runs the selected command
  INPUTS:		array -> corresponds to parameters of docmgr api
  RETURNS:	array -> api xml results converted to array form

  EXAMPLE:
    $cmd = array();
    $cmd["command"] = "docmgr_object_getinfo";
    $cmd["object_id"] = "1";
    $test = callDocmgr($cmd);

*******************************************************************************/
function callDocmgr($apidata)
{

	return callAPI($apidata);

}

//shortcut function for callDocmgr
function commandDocmgr($cmd,$obj=null)
{

  $arr = array();
  $arr["command"] = $cmd;

  //figure if we're passing object id or path
  if ($obj)
  {
  
    if (is_numeric($obj)) $arr["object_id"] = $obj;
    else $arr["path"] = $obj;
  
  }

  return callDocmgr($arr);

}


function checkAppAvail($app) {

  //if the app is an absolute path, just return true
  if ($app[0]=="/") return true;

  //extract the app from it's command line args
  $app = extractApp($app);

	$str = `which "$app" 2>/dev/null`;

	//if which returns nothing, it couldn't find the app
	if (!$str) return false;

	$pos = strrpos($str,"/");
	$str = trim(substr($str,0,$pos));

	//make sure the app's path is in apache's path
	$pathArr = explode(":",$_SERVER["PATH"]);

	if (in_array($str,$pathArr)) return true;
	else return false;

}

function checkRequiredApp($app) {

        //if the app is an absolute path, just return true
        if ($app[0]=="/") return true;

        //extract the app from it's command line args
        $app = extractApp($app);

	$str = `which "$app" 2>/dev/null`;
	$error = null;

	//if which returns nothing, it couldn't find the app
  	if (!$str) $error = "1";
  	else {
  	  $pos = strrpos($str,"/");
  	  $str = trim(substr($str,0,$pos));

  	  //make sure the app's path is in apache's path
  	  $pathArr = explode(":",$_SERVER["PATH"]);

  	  if (!in_array($str,$pathArr)) $error = "1";;
        }

	if ($error) {
	  $message = "Error!  The application <b>$app</b> could not be found in ".$_SERVER["PATH"]."<br>
	              This application is required by DocMGR to run.<br><br>
	              ";
          die($message);
        }
}

//this function extracts the core app name from an absolute or relative path, and 
//the parameters pass to the app
function extractApp($app) {

  $arr = explode(" ",$app);
  return $arr[0];
  

}

//this function determines if our optional applications are available to docmgr
function getExternalApps() {

  $arr = array();

  //figure out which of our external progs exist
  if (checkAppAvail(APP_OCR)) $arr["ocr"] = 1;
  if (checkAppAvail(APP_WGET)) $arr["wget"] = 1;
  if (class_exists("ZipArchive")) $arr["zip"] = 1;

  if (checkAppAvail(APP_MOGRIFY)) $arr["mogrify"] = 1;
  if (checkAppAvail(APP_CONVERT)) $arr["convert"] = 1;
  if (checkAppAvail(APP_MONTAGE)) $arr["montage"] = 1;
  if ($arr["mogrify"] && $arr["convert"] && $arr["montage"]) $arr["imagemagick"] = 1;

  if (checkAppAvail(APP_PDFTOTEXT)) $arr["pdftotext"] = 1;
  if (checkAppAvail(APP_PDFIMAGES)) $arr["pdfimages"] = 1;
  if ($arr["pdftotext"] && $arr["pdfimages"]) $arr["xpdf"] = 1;  

  if (checkAppAvail(APP_TIFFINFO)) $arr["tiffinfo"] = 1;
  if (checkAppAvail(APP_TIFFSPLIT)) $arr["tiffsplit"] = 1;
  if ($arr["tiffinfo"] && $arr["tiffsplit"]) $arr["libtiff"] = 1;

  if (checkAppAvail(APP_SENDMAIL) || function_exists("imap_8bit")) $arr["email"] = 1;

  if (checkAppAvail(APP_CLAMAV)) $arr["clamav"] = 1;

  return $arr;

}

function setExternalApps() {

	if (!isset($_SESSION["api"]["setApps"])) {
	
     //check to make sure if we have these required programs.  If not, die
     checkRequiredApp(APP_PHP);

     //make sure they are not using the cgi version of php
     $app = APP_PHP." -v";
     $str = `$app`;
     if (!strstr($str,"(cli)")) die("You are not using the cli version of php.  Please either install php-cli");

     $_SESSION["api"]["setApps"] = getExternalApps();	

	}

	//url download support
	if ($_SESSION["api"]["setApps"]["wget"]) define("URL_SUPPORT","1");

	//zip archive support
	if ($_SESSION["api"]["setApps"]["zip"]) define("ZIP_SUPPORT","1");

	//ocr support
	if (($_SESSION["api"]["setApps"]["ocr"] && 
	  $_SESSION["api"]["setApps"]["libtiff"] && 
	  $_SESSION["api"]["setApps"]["imagemagick"])) define("OCR_SUPPORT","1");

	  if ($_SESSION["api"]["setApps"]["xpdf"]) {
	    define("PDF_SUPPORT","1");
          }

	//thumbnail support
	if ($_SESSION["api"]["setApps"]["imagemagick"]) define("THUMB_SUPPORT","1");

	//tiff handling support
	if ($_SESSION["api"]["setApps"]["libtiff"]) define("TIFF_SUPPORT","1");
	
	//antivirus support
	if ($_SESSION["api"]["setApps"]["clamav"]) define("CLAMAV_SUPPORT","1");

	if ($_SESSION["api"]["setApps"]["email"]) define("EMAIL_SUPPORT","1");

	return true;
	
}


function directViewObject($link)
{

  global $DB,$PROTO;

  //first clear out all expired links
  $sql = "DELETE FROM docmgr.object_link WHERE expires < '".date("Y-m-d H:i:s")."'";
  $DB->query($sql);
  
  $sql = "SELECT object_id,account_id FROM docmgr.object_link WHERE link='$link'";
  $info = $DB->single($sql);
  
  if (!$info) die("This link is no longer valid");
  else
  {

    //get user's account inf
    $a = new ACCOUNT($info["account_id"]);
    $ainfo = $a->getInfo();

    //for the api to use    
    define("USER_ID",$info["account_id"]);
    define("USER_LOGIN",$ainfo["login"]);
    define("USER_PASSWORD",$ainfo["password"]);

		//register our autoload function
		spl_autoload_register('edev_autoload');

		//setup api parameters
		$opt = array();
		$opt["object_id"] = $info["object_id"];

    //log into the api as the links user account and pull the document
    $d = new DOCMGR_OBJECT($opt);
    $d->get();
  
  }
  

}

//setup breadcrumb trail
function setupObjectTrail($objectPath) 
{

	$navarr = array();
	
	$arr = explode("/",$objectPath);
	array_shift($arr);
	$path = "/";
	
	$max = count($arr);
	
	for ($i=0;$i<$max;$i++) 
	{
	
	  if ($path=="/") $path .= $arr[$i];
	  else $path .= "/".$arr[$i];
	
	  if ($i==($max-1)) $navarr[] = $arr[$i];
	  else $navarr[] = "<a href=\"index.php?module=docmgr&objectPath=".urlencode($path)."&objectCeiling=".urlencode($ceiling)."\">".$arr[$i]."</a>";
	
	}
	
	//put the ceiling link on the nav array
	array_unshift($navarr,"<a href=\"index.php?module=docmgr&objectPath=/\">".ROOT_NAME."</a>");
	$nav = implode(" <img style=\"margin-bottom:-1px;margin-left:2px;margin-right:2px\" src=\"".THEME_PATH."/images/navarrow.gif\"> ",$navarr);

	return $nav;
 
} 


function setupIntranetObjectTrail($objectPath,$objectIds) 
{

	$navarr = array();
	
	$arr = explode("/",$objectPath);
	$idArr = explode(",",$objectIds);
	
	$arr = array_slice($arr,3);
	$idArr = array_slice($idArr,3);
	
	$path = INTRANET_PATH;
	
	$max = count($arr);
	
	for ($i=0;$i<$max;$i++) 
	{
	
	  $navarr[] = "<a href=\"index.php?module=intranet&objectId=".$idArr[$i]."\">".$arr[$i]."</a>";
	
	}
	 
	$nav = implode(" <img style=\"margin-bottom:-1px;margin-left:2px;margin-right:2px\" src=\"".THEME_PATH."/images/navarrow.gif\"> ",$navarr);
	
	return $nav;
	 
}

//this can sometimes be set by other apps, this is the default
//in docmgr-only installations
if (!function_exists("checksetup"))
{
	
	function checkSetup() 
	{
	
		global $DB;
		
		//stop here if already done 
		if ($_SESSION["checkSetup"]==1) return true;
		
		$sql = "SELECT setup FROM auth_accountperm WHERE account_id='".USER_ID."'";
		$info = $DB->single($sql);
		
		if ($info["setup"]!="t") 
		{

			include("firstlogin.php");

			//make sure our default folders and bookmarks are made
			checkDocmgr();
			checkDefaultDashboard();

			$opt = null;
			$opt["setup"] = "t";
			$opt["where"] = "account_id='".USER_ID."'";
			$DB->update("auth_accountperm",$opt);
		
		}
		 
		//make sure we don't do it again
		$_SESSION["checkSetup"] = 1; 
		 
	}
	
}
