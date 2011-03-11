<?php

//set the include path to work with relative paths
ini_set("include_path",".");

//first call the config file to get our settings, call our base functions, and get our wrapper
include("config/version.php");
include("config/config.php");
include("config/app-config.php");

//include our ldap file if set
if (defined("USE_LDAP")) include("config/ldap-config.php");

//the rest of our includes with our base functions
include("header/callheader.php");

//this gets rid of a hidden session form field which we do not want
ini_set("session.use_trans_sid","0");

//whether cookies should only be sent over secure connections.
if (defined("SECURE_COOKIES")) ini_set("session.cookie_secure",SECURE_COOKIES);

//set to use short tags
ini_set("short_open_tag","1");

//start our session
session_start();

//globalize exempt vars
$GLOBALS["exemptRequest"] = $exemptRequest;

//make our request variables safe
sanitizeRequest($exemptRequest);

//connect
$DB = new POSTGRESQL(DBHOST,DBUSER,DBPASSWORD,DBPORT,DBNAME);
$GLOBALS["DB"] = $DB;
$conn = $DB->getConn();

//we now do our authentications on the local database
$_SESSION["conn"] = $conn;
$GLOBALS["conn"] = $conn;
$auth_conn = $conn;

if (defined("CENTHOST")) {

  $CDB = new POSTGRESQL(CENTHOST,CENTUSER,CENTPASSWORD,CENTPORT,CENTNAME);
  $cent_conn = $CDB->getConn();
  $GLOBALS["cent_conn"] = $cent_conn;
  $GLOBALS["CDB"] = $CDB;

}

//init out logger
$GLOBALS["logger"] = $DB->getLogger();
$logger = $GLOBALS["logger"];

//get the users browser type  
set_browser_info();

//Get our site layout if we have not already
if ($_SESSION["siteModList"] && $_SESSION["siteModInfo"] && !defined("DEV_MODE")) 
{

  $siteModList = &$_SESSION["siteModList"];
  $siteModInfo = &$_SESSION["siteModInfo"];

}
else 
{

  $siteModArr = loadSiteStructure("modules/center/,modules/common/");
  $_SESSION["siteModList"] = $siteModArr["list"];
  $_SESSION["siteModInfo"] = $siteModArr["info"];
  $siteModList = &$_SESSION["siteModList"];
  $siteModInfo = &$_SESSION["siteModInfo"];

}

//set our permission defines
PERM::setDefines("config/permissions.xml");
PERM::setDefines("config/customperm.xml");

//init our communications protocol
$PROTO = new PROTO();
$GLOBALS["PROTO"] = $PROTO;
