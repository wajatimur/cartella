<?php
/********************************************************************************************/
//
//	Filename:
//		misc.php
//      
//	Summary:
//		this file contains functions common to both prospect and contract applications
//		They should still be somewhat generic
//           
//	Modified:
//             
//		09-02-2004
//			Code cleanup.  Moved functions that don't belong out
//
//       04-19-2006
//          -More consolidation of functions.
//          -merged function.inc.php into file
//          -Created new files for removed functions
//              *file_functions.inc.php
//              *sanitize.inc.php
//              *calc_functions.inc.php                    
//          -Renamed file from common.inc.php to misc.php
//
//
/*********************************************************************************************/
/*********************************************************
//  returns the type of browser the user is using.
//  this function must be passed $HTTP_USER_AGENT.
*********************************************************/
function set_browser_info() {

  //Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10.5; en-US; rv:1.9.0.6) Gecko/2009011912 Firefox/3.0.6 
  //Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_5_6; en-us) AppleWebKit/525.27.1 (KHTML, like Gecko) Version/3.2.1 Safari/525.27.1
  //Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.0; SLCC1; .NET CLR 2.0.50727; .NET CLR 3.0.04506; .NET CLR 3.5.21022) 

  $browser = $_SERVER["HTTP_USER_AGENT"];

  if (stristr($browser,"MSIE")) {

    $arr = explode(";",$browser);
    $version = trim(str_replace(" MSIE ","",$arr[1]));

    define("BROWSER","ie");
    define("BROWSER_VERSION",$version);
      
  } else if (stristr($browser,"KHTML")) {

    $str = substr($browser,strpos($browser,"Version/")+8);
    $version = trim(substr($str,0,strpos($str," ")));

    define("BROWSER","webkit");
    define("BROWSER_VERSION",$version);
      
  } else {

    $version = trim(substr($browser,strrpos($browser,"/")+1));
  
    define("BROWSER","gecko");
    define("BROWSER_VERSION",$version);
      
  } 
 
}


/*********************************************************
*********************************************************/
function login_return($conn,$id) {

	//return anonymous if there is no id
	if ($id=="0" || !$id) return SITE_ADMIN;

	$sql = "SELECT login FROM auth_accounts WHERE id='$id'";

	if ($value = single_result($conn,$sql)) return $value["login"];
	else return false;

}
/*********************************************************
*********************************************************/
function selfClose($url = null) {

	//set to refresh the parent if url is not specified
	if (!$url) $url = "window.opener.location.href";
	else $url = "\"".$url."\"";
	
	echo "<script type=\"text/javascript\">
		var url = ".$url.";
		window.opener.location.href = url;
		self.close();
		</script>
		";

}
/*********************************************************
*********************************************************/
function selfFocus() {

	echo "<script type=\"text/javascript\">\n";
	echo "self.focus();\n";
	echo "</script>\n";

}
/*********************************************************
*********************************************************/
function includeStylesheet($path) {

    //if it has a semicolon in it, it may contain multiples
    $arr = explode(";",$path);

    foreach ($arr AS $css) {

      if (!$css) continue;

      //look for a cached copy, otherwise use the passed one
      if (defined("CSS_COMPRESS") && file_exists("cache/".$css)) $css = "cache/".$css;

      echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"".$css."\">\n";

    }

}
/*********************************************************
*********************************************************/
function includeJavascript($path) {

    //if it has a semicolon in it, it may contain multiples
    $arr = explode(";",$path);

    foreach ($arr AS $js) {

      if (!$js) continue;

      //look for a cached copy, otherwise use the passed one
      if (defined("JS_COMPRESS") && file_exists("cache/".$js)) $js = "cache/".$js;

  	  echo "<script type=\"text/javascript\" src=\"".$js."\"></script>\n";

    }

}
/*********************************************************
*********************************************************/
function includeVBS($path) {

    //if it has a semicolon in it, it may contain multiples
    $arr = explode(";",$path);

    foreach ($arr AS $vbs) {

      if (!$vbs) continue;
  	  echo "<script type=\"text/vbscript\" src=\"".$vbs."\"></script>\n";

    }
  
}
/*********************************************************
*********************************************************/
function preventCache() {

	Header("Cache-control: private, no-cache");
	Header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
	Header("Pragma: no-cache");

}
/*********************************************************
*********************************************************/
function translateHtmlEntities($string) {

	$trans_tbl = get_html_translation_table(HTML_ENTITIES);
	$trans_tbl = array_flip($trans_tbl);
	
	$original = strtr($string,$trans_tbl);

	return $original;
}
/*************************************************************************************
//	checkStrongPassword:
//	checks the password to see if it is considered difficult to crack.
//	Returns a string containing what it thinks is wrong with your password.  
//	If "strong password" is returned, then all is well.  cracklib must be 
//	compiled into php, or this will generate a php error
**************************************************************************************/
function checkStrongPassword($conn,$accountId,$pwd) {

	//if there is no accountId, return false since we can't check against the username
	if (!$accountId) return "no account id specified";

	$info = returnAccountInfo($accountId);
	$login = &$info["login"];
	$fn = &$info["first_name"];
	$ln = &$info["last_name"];

	//make sure the user's first name, last name, and login aren't in the password
	if (stristr($pwd,$login)) return "it contains your username";
	if (stristr($pwd,$fn)) return "it contains your name";
	if (stristr($pwd,$ln)) return "it contains your name";

	// Perform password check with craclib
	$check = crack_check($pwd);

	// Retrieve messages from cracklib
	return crack_getlastmessage();

}

function dbInsertQuery($conn,$table,$option,$idField = "id") {

	$ignoreArray = array("conn","table","debug","query");

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
		
		if ($result = db_query($conn,$sql)) {

      if ($idField) {		

  			$returnId = db_insert_id($table,$idField,$conn,$result);
  			if ($returnId) return $returnId;
  			else return true;

      } else {
        return true;
      }

		} else return false;

	} else return false;

}

function dbUpdateQuery($conn,$table,$option,$sanitize = null) {

	$ignoreArray = array("conn","table","where","debug","query");

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

		if (db_query($conn,$sql)) return true;
		else return false;

	} else return false;

}

function debug($level,$msg) {

    if (php_sapi_name()=="cli") $sep = "\n";
    else $sep = "<br>";
    
    if (defined("DEBUG") && DEBUG >= $level) {

    	//if from webdav then use webdav function
    	if (class_exists("webdavfunc")) webdavfunc::checkOutput($msg."\n");
    	else echo $msg.$sep;

    }
    
}
      

function checkSiteTimeout($module=null,$checkModule=null) {

  if (!is_array($checkModule)) $checkModule = array($checkModule);

    //if session timouts are enabled, check to see if the session has expired
    if (defined("SESSION_TIMEOUT")){

      if ( ($_SESSION["timestamp"]!= NULL) && ( $_SESSION["timestamp"] < ( time() - (SESSION_TIMEOUT*60)) )){

        //die("module is ".$module.": ".$_SESSION["siteModInfo"][$module]["template"]);
        //if this module is a background module, show the edev login text so we trigger the session timeout
        if ($_SESSION["siteModInfo"][$module]["template"]=="blank") {

          session_destroy();
          die("<!--EDEV LOGIN-->");          

        //otherwise redirect to login page
        } else {

          session_destroy();
          header("Location: index.php?timeout=true");

        }
        
      }
      else {

        //update the time to the current time, unless the module is one we're supposed to ignore
        if ($module && in_array($module,$checkModule)) return false;
        else $_SESSION["timestamp"] = time();

      }

    }

}

function returnAccountInfo($aid) {

  $a = new ACCOUNT($aid);
  return $a->getInfo();

}

function returnAccountList($opt) {

  $sort = $opt["sort"];
  $filter = $opt["search_filter"];

  $a = new ACCOUNT();
  $ret = $a->getList($filter,$sort);
  
  $num = count($ret);

  //for backwards compatibility
  unset($ret["count"]);
  $ret = transposeArray($ret);

  $ret["count"] = $num;
  return $ret;

}


function includeLibs($dir)
{

  $arr = scandir($dir);

  foreach ($arr AS $file)
  {

    if ($file=="." || $file=="..") continue;
    if (is_dir($dir."/".$file)) continue;
    if (strstr($file,"~")) continue;

    include_once($dir."/".$file);

  }

}


function validMethod($class,$method)
{

  //make sure the method is public
  $valids = get_class_methods($class);
  $num = count($valids);

  for ($i=0;$i<$num;$i++) $valids[$i] = strtolower($valids[$i]);

  if (@in_array($method,$valids)) return true;
  else return false;
 
}

function callAPI($apidata)
{

  require_once("apilib/preauth.php");
 
  global $PROTO,$DB,$logger;

	include("apilib/request.php");
 
	//submit the xml as whatever the browser's encoding should be as set in config file
	$apidata = $PROTO->getData();
	$PROTO->clearData();
 
	return $apidata;
  
}

function uuid($prefix = '')
{  

  $chars = md5(uniqid(mt_rand(), true));
  $uuid = substr($chars,0,8) . '-';
  $uuid .= substr($chars,8,4) . '-';
  $uuid .= substr($chars,12,4) . '-';
  $uuid .= substr($chars,16,4) . '-';
  $uuid .= substr($chars,20,12);

  return strtoupper($prefix . $uuid);

}

function short_uuid($prefix = '')
{  

  $chars = md5(uniqid(mt_rand(), true));
  $uuid = substr($chars,0,8) . '-';
  $uuid .= substr($chars,20,12);

  return strtoupper($prefix . $uuid);

}
