<?php

die("Disabled\n");

//pick the files you need.  start at the version you currently have and uncomment everything below it
$upgrade = array();

//$upgrade[] = "upgrade-from-rc1.sql";
//$upgrade[] = "upgrade-from-rc2.sql";
//$upgrade[] = "upgrade-from-rc3.sql";
//$upgrade[] = "upgrade-from-rc4.sql";

//this one has to be here
$upgrade[] = "upgrade-from-rc6.sql";


/****************************************
  don't modify anything below here
****************************************/

ini_set("include_path",".");
include("../config/config.php");
include("../lib/postgresql.php");
include("../lib/misc.php");
include("../lib/arrays.php");
include("../lib/pgsql.php");
include("../lib/logger.php");
include("../lib/xml.php");
include("../lib/calc.php");
include("../lib/perm.php");
include("../config/ldap-config.php");
include("../lib/account/account.php");

//connect to database
$DB = new POSTGRESQL(DBHOST,DBUSER,DBPASSWORD,DBPORT,DBNAME);
$GLOBALS["DB"] = $DB;

//upgrade the database
foreach ($upgrade AS $u)
{

  $sql = file_get_contents("upgrade-sql/".$u);
  $DB->query($sql);

}

/************************************************************
  upgrade account permissions
************************************************************/

$blank = null;
for ($i=0;$i<32;$i++) $blank .= "0";

$perms = xml2array(file_get_contents("../config/permissions.xml"));

$sql = "SELECT account_id,bitset FROM auth_accountperm ORDER BY account_id";
$list = $DB->fetch($sql);

for ($i=0;$i<$list["count"];$i++)
{

  $permstring = $blank;

  //loop through the original permissions.  If set, set in the new string
  foreach ($perms["perm"] AS $perm)
  {

    $pos = $perm["bitpos"];
    $val = bitCal($pos);

    if (bit_comp($list[$i]["bitset"],$val))
    {
      $permstring = PERM::bit_set($permstring,$pos);
    }

  }  

  $opt = null;
  $opt["bitmask"] = $permstring;
  $opt["where"] = "account_id='".$list[$i]["account_id"]."'";
  $DB->update("auth_accountperm",$opt);

}


/************************************************************
  upgrade group permissions
************************************************************/
$sql = "SELECT group_id,bitset FROM auth_groupperm ORDER BY group_id";
$list = $DB->fetch($sql);

for ($i=0;$i<$list["count"];$i++)
{

  $permstring = $blank;

  //loop through the original permissions.  If set, set in the new string
  foreach ($perms["perm"] AS $perm)
  {

    $pos = $perm["bitpos"];
    $val = bitCal($pos);

    if (bit_comp($list[$i]["bitset"],$val))
    {
      $permstring = PERM::bit_set($permstring,$pos);
    }

  }  

  $opt = null;
  $opt["bitmask"] = $permstring;
  $opt["where"] = "group_id='".$list[$i]["group_id"]."'";
  $DB->update("auth_groupperm",$opt);

}



/************************************************************
  upgrade object permissions
************************************************************/


$blank = null;
for ($i=0;$i<8;$i++) $blank .= "0";

$perms = xml2array(file_get_contents("../config/customperm.xml"));

$sql = "SELECT * FROM docmgr.dm_object_perm ORDER BY object_id";
$list = $DB->fetch($sql);

for ($i=0;$i<$list["count"];$i++)
{

  $permstring = "00000000";

  //loop through the original permissions.  If set, set in the new string
  foreach ($perms["perm"] AS $orig)
  {

    if (strlen($permstring) > 8)
    {
    
      $permstring = strrev($permstring);
      $permstring = substr($permstring,0,8);
      $permstring = strrev($permstring);
          
    }
  
    $val = bitCal($orig["bitpos"]);  
    if (bit_comp($list[$i]["bitset"],$val))
    {
  
      $pos = $orig["bitpos"];
      $permstring = PERM::bit_set($permstring,$pos);

    }

  }  

  $permstring = "0".substr($permstring,0,strlen($permstring)-1);

  //echo $permstring."\n";
  $opt = null;
  $opt["bitmask"] = $permstring;
  

  if ($list[$i]["group_id"]) $opt["where"] = "object_id='".$list[$i]["object_id"]."' AND group_id='".$list[$i]["group_id"]."'";
  else if ($list[$i]["account_id"])  $opt["where"] = "object_id='".$list[$i]["object_id"]."' AND account_id='".$list[$i]["account_id"]."'";
  else continue;
  
  $DB->update("docmgr.dm_object_perm",$opt);

}

echo "Upgrade complete.  Remember you have to reset your password before you can access files via webdav\n";
