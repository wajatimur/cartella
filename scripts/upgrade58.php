<?php

die("Disabled\n");

/*****************************************************************************************************
	Instructions:

	This script will transfer your data from a 0.58 database to a 1.0 database.
	Please backup all DocMGR databases and files before beginning, even
	though this script leaves your 0.58 data untouched
	
	1.  Create 1.0 installation and run through wizard.  Do not log in
			after wizard is finished.
	2.	Setup config options below.  the "OLD" options refer to the 0.58
			installation while the "NEW" options refer to the 1.0
	3.  Remove the "die" line at the top of this file
	4.  Run the script by typing "php upgrade58.php" from this directory
	5.  When the script is complete, if there are no errors, run
			"mv /path/to/olddocmgr/files* /path/to/newdocmgr/files/".  You can
			also use "cp" if you don't trust move
	6.  Make sure your apache user owns the fils directory in the new DocMGR 
			"chown -R daemon /path/to/newdocmgr/files"
	7.  Change to the root docmgr directory and run 
			"php bin/docmgr-indexer.php --reindex-all &".  
			If openoffice is throwing segfaults during the conversions, you can safely ignore
			those errors.
	8.  Relax because you're done!

******************************************************************************************************/

/****************************************
  config options
****************************************/

//0.58 server
define("OLDHOST","localhost");
define("OLDUSER","postgres");
define("OLDPASSWORD","secret");
define("OLDPORT","5432");
define("OLDNAME","docmgr58");
define("OLDPATH","/www/doc");

//1.0 server
define("NEWHOST","localhost");
define("NEWUSER","postgres");
define("NEWPASSWORD","secret");
define("NEWPORT","5432");
define("NEWNAME","docmgr");
define("NEWPATH","/www/docmgr");

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
$OLDDB = new POSTGRESQL(OLDHOST,OLDUSER,OLDPASSWORD,OLDPORT,OLDNAME);
$GLOBALS["OLDDB"] = $OLDDB;

$NEWDB = new POSTGRESQL(NEWHOST,NEWUSER,NEWPASSWORD,NEWPORT,NEWNAME);
$GLOBALS["NEWDB"] = $NEWDB;

updateTables();
updateSequences();
updatePerms();
updateKeywords();
updateBookmarks();
updateSearch();
updateParent();

echo "Upgrade complete.  Please move your files directory from the old installation to the new one\n";

function updateSearch()
{

	global $OLDDB,$NEWDB;

	//date_add,date_mod,object_type,account	
	$sql = "SELECT * FROM dm_savesearch";
	$list = $OLDDB->fetch($sql);

	$sql = "TRUNCATE TABLE docmgr.dm_search";
	$NEWDB->query($sql);

	for ($i=0;$i<$list["count"];$i++)
	{

		$str = "{\"command\":\"docmgr_search_search\",\"sort_field\":\"rank\",\"sort_dir\":\"DESC\",\"limit\":10,\"offset\":0,";

		$list[$i]["search_option"] = str_replace("file_name","name",$list[$i]["search_option"]);
		$list[$i]["search_option"] = str_replace("file_contents","content",$list[$i]["search_option"]);

		if ($list[$i]["search_string"]) 
			$str .= "\"search_string\":\"".$list[$i]["search_string"]."\",";
	
		if ($list[$i]["search_option"]) 
			$str .= "\"search_option\":[\"".str_replace("|","\",\"",$list[$i]["search_option"])."\"],";
	
		//collection filter
		if ($list[$i]["col_filter_id"])
			$str .= "\"colfilter\":[\"".str_replace("|","\",\"",$list[$i]["col_filter_id"])."\"],";
	
		//object_type filter
		if ($list[$i]["show_objects"]) 
			$str .= "\"object_type\":[\"".str_replace(",","\",\"",$list[$i]["show_objects"])."\"],";
	
		//mod_option -> mod_option
		//date_option, before after period on
		//date1 and date2 for period.  date2 is only used on period, only date1 used otherwise
	
		//account and object_type should queue up for multiple entries 
		$filterarr = array();
	
		if ($list[$i]["date2"])
		{
	
			if ($list[$i]["mod_option"]=="last") 
			{
				$filterarr[] = "date_mod";
				$filterarr[] = "date_mod";
			} 
			else if ($list[$i]["mod_option"]=="enter") 
			{
				$filterarr[] = "date_add";
				$filterarr[] = "date_add";
			}
			
			$matcharr[] = "after";
			$matcharr[] = "before";
			
			$valuearr[] = $list[$i]["date1"];
			$valuearr[] = $list[$i]["date2"];
		
		}
		else if ($list[$i]["date1"])
		{
	
			if ($list[$i]["mod_option"]=="last") $filterarr[] = "date_mod";
			else if ($list[$i]["mod_option"]=="enter") $filterarr[] = "date_add";
	
			if ($list[$i]["date_option"]=="before") $matcharr[] = "before";
			else if ($list[$i]["date_option"]=="single") $matcharr[] = "on";
			else if ($list[$i]["date_option"]=="after") $matcharr[] = "after";
		
			$valuearr[] = $list[$i]["date1"];
			
		}
	
		if ($list[$i]["account_filter_id"])
		{
		
			$accounts = explode("|",$list[$i]["account_filter_id"]);
			
			foreach ($accounts AS $account)
			{
				$filterarr[] = "account";
				$matcharr[] = "equals";
				$valuearr[] = $account;
			}
		
		}	
	
		
		//now setup for date_add,date_mod,object_type, and account_id
		$str .= "\"filter\":[\"".implode("\",\"",$filterarr)."\"],";
		$str .= "\"match\":[\"".implode("\",\"",$matcharr)."\"],";
		$str .= "\"value\":[\"".implode("\",\"",$valuearr)."\"],";

		$str = substr($str,0,strlen($str)-1)."}";

		$opt = null;
		$opt["object_id"] = $list[$i]["object_id"];
		$opt["params"] = $str;
		$NEWDB->insert("docmgr.dm_search",$opt);

	}		
	
}


//bookmarks, savesearch, keywords are left
function updateBookmarks()
{

	global $OLDDB,$NEWDB;

	$sql = "TRUNCATE TABLE docmgr.dm_bookmark";
	$NEWDB->query($sql);
	
	$sql = "UPDATE auth_accountperm SET setup='f'";
	$NEWDB->query($sql);

	//object_id, account_id, name
	$sql = "SELECT * FROM dm_bookmark";
	$list = $OLDDB->fetch($sql);
	
	for ($i=0;$i<$list["count"];$i++)
	{
	
		$opt = null;
		$opt["object_id"] = $list[$i]["object_id"];
		$opt["account_id"] = $list[$i]["account_id"];
		$opt["name"] = $list[$i]["name"];
		$opt["expandable"] = "t";
		$NEWDB->insert("docmgr.dm_bookmark",$opt);
	
	}
	

}

function updateKeywords()
{

	global $OLDDB,$NEWDB;

	$sql = "TRUNCATE TABLE docmgr.keyword;
					TRUNCATE TABLE docmgr.keyword_collection;
					TRUNCATE TABLE docmgr.keyword_option;
					TRUNCATE TABLE docmgr.keyword_value;
					";
	$NEWDB->query($sql);

	$keywords = xml2array(file_get_contents(OLDPATH."/config/keywords.xml"));
	$kw = $keywords["keyword"];

	for ($i=0;$i<count($kw);$i++)
	{
	
		$k = $kw[$i];
		
		if ($k["type"]=="dropdown")
		{

			$opt = null;
			$opt["name"] = $k["title"];
			$opt["type"] = "select";
			$keyid = $NEWDB->insert("docmgr.keyword",$opt,"id");

			$options = $k["option"];
			if ($options && !is_array($options)) $options = array($options);
	
			//create the options
			for ($c=0;$c<count($options);$c++)
			{
			
				$opt = null;
				$opt["name"] = $options[$c];
				$opt["keyword_id"] = $keyid;
				$optId = $NEWDB->insert("docmgr.keyword_option",$opt,"id");
			
				//find all objects that use this option
				$sql = "SELECT object_id FROM dm_keyword WHERE ".$k["name"]."='".$options[$c]."'";
				$objList = $OLDDB->fetch($sql);
			
				for ($n=0;$n<$objList["count"];$n++)
				{
				
					$opt = null;
					$opt["object_id"] = $objList[$n]["object_id"];
					$opt["keyword_id"] = $keyid;
					$opt["keyword_value"] = $options[$c];
					$NEWDB->insert("docmgr.keyword_value",$opt);
					
				}
			
			}
			
		}
		else
		{
		
			$opt = null;
			$opt["name"] = $k["title"];
			$opt["type"] = "text";
			$keyid = $NEWDB->insert("docmgr.keyword",$opt,"id");

			$field = $k["name"];
			
			//find all objects that use this option
			$sql = "SELECT object_id,".$field." FROM dm_keyword WHERE ".$field." IS NOT NULL";
			$objList = $OLDDB->fetch($sql);

			for ($n=0;$n<$objList["count"];$n++)
			{
				
				$opt = null;
				$opt["object_id"] = $objList[$n]["object_id"];
				$opt["keyword_id"] = $keyid;
				$opt["keyword_value"] = $objList[$n][$field];
				$NEWDB->insert("docmgr.keyword_value",$opt);
					
			}

					
		}
	
		
	
	}
	
	/*
  <keyword>
    <title>Invoice Number</title>
      <name>field1</name>
        <type>text</type>
          </keyword>
    */      
}

function updateTables()
{

  global $OLDDB,$NEWDB;

	$transfer = array();
	
	//public schema
	$transfer[] = array("from"=>"public.auth_accountperm","to"=>"public.auth_accountperm");
	$transfer[] = array("from"=>"public.auth_accounts","to"=>"public.auth_accounts");
	$transfer[] = array("from"=>"public.auth_grouplink","to"=>"public.auth_grouplink");
	$transfer[] = array("from"=>"public.auth_groupperm","to"=>"public.auth_groupperm");
	$transfer[] = array("from"=>"public.auth_groups","to"=>"public.auth_groups");
	$transfer[] = array("from"=>"public.auth_settings","to"=>"public.auth_settings");
	
	//docmgr schema
	$transfer[] = array("from"=>"public.dm_object","to"=>"docmgr.dm_object");
	
	$transfer[] = array("from"=>"public.dm_alert","to"=>"docmgr.dm_alert");
	$transfer[] = array("from"=>"public.dm_dirlevel","to"=>"docmgr.dm_dirlevel");
	$transfer[] = array("from"=>"public.dm_discussion","to"=>"docmgr.dm_discussion");
	$transfer[] = array("from"=>"public.dm_document","to"=>"docmgr.dm_document");
	$transfer[] = array("from"=>"public.dm_email_anon","to"=>"docmgr.dm_email_anon");
	$transfer[] = array("from"=>"public.dm_file_history","to"=>"docmgr.dm_file_history");
	$transfer[] = array("from"=>"public.dm_object_log","to"=>"docmgr.dm_object_log");
	$transfer[] = array("from"=>"public.dm_object_parent","to"=>"docmgr.dm_object_parent");
	$transfer[] = array("from"=>"public.dm_object_perm","to"=>"docmgr.dm_object_perm");
	$transfer[] = array("from"=>"public.dm_object_related","to"=>"docmgr.dm_object_related");
	$transfer[] = array("from"=>"public.dm_saveroute","to"=>"docmgr.dm_saveroute");
	$transfer[] = array("from"=>"public.dm_saveroute_data","to"=>"docmgr.dm_saveroute_data");
	$transfer[] = array("from"=>"public.dm_subscribe","to"=>"docmgr.dm_subscribe");
	$transfer[] = array("from"=>"public.dm_task","to"=>"docmgr.dm_task");
	$transfer[] = array("from"=>"public.dm_thumb_queue","to"=>"docmgr.dm_thumb_queue");
	$transfer[] = array("from"=>"public.dm_workflow","to"=>"docmgr.dm_workflow");
	$transfer[] = array("from"=>"public.dm_workflow_route","to"=>"docmgr.dm_workflow_route");
	
	
	foreach ($transfer AS $t)
	{
	
	  transferTable($t);
	  
	}

	//some random renaming
	$sql = "UPDATE docmgr.dm_object SET object_type='search' WHERE object_type='savesearch'";
	$NEWDB->query($sql);

}

function updateSequences()
{

  global $OLDDB,$NEWDB;

	//set the sequences
	$sequences = array();
	$sequences[] = "docmgr.dm_alert_id_seq";
	$sequences[] = "docmgr.dm_document_id_seq";
	$sequences[] = "docmgr.dm_file_history_id_seq";
	$sequences[] = "docmgr.dm_object_id_seq";
	$sequences[] = "docmgr.dm_saveroute_id_seq";
	$sequences[] = "docmgr.dm_thumb_queue_id_seq";
	$sequences[] = "docmgr.dm_workflow_id_seq";
	$sequences[] = "docmgr.dm_workflow_route_id_seq";
	$sequences[] = "docmgr.dm_workflow_id_seq";
	$sequences[] = "docmgr.dm_workflow_route_id_seq";
	$sequences[] = "docmgr.level1";
	$sequences[] = "docmgr.level2";
	$sequences[] = "auth_accounts_id_seq";
	$sequences[] = "auth_groups_id_seq";
	
	foreach ($sequences AS $sequence)
	{
	
	  $oldsequence = str_replace("docmgr.","",$sequence);
	
	  $val = $OLDDB->increment_seq($oldsequence);
	  $NEWDB->set_seq($sequence,$val);
	
	}

}


function transferTable($t)
{

  global $OLDDB,$NEWDB;
  
  $fromTable = $t["from"];
  $toTable = $t["to"];

  //remove eventually
  $sql = "TRUNCATE TABLE ".$toTable." CASCADE";
  $NEWDB->query($sql);

  //db compatible way to get names of columns
  $sql = "SELECT * FROM $fromTable";
  $list = $OLDDB->fetch($sql);

  for ($i=0;$i<$list["count"];$i++)
  {
  
    $keys = array_keys($list[$i]);
    $opt = array();
    
    foreach ($keys AS $key)
    {
    
      $opt[$key] = addslashes($list[$i][$key]);
      
    }

    $NEWDB->insert($toTable,$opt);
  
  }

}

/************************************************************
  upgrade object parents
************************************************************/

function updateParent()
{

  global $NEWDB;

  $sql = "UPDATE docmgr.dm_object_parent SET account_id=(SELECT object_owner FROM docmgr.dm_object WHERE id=dm_object_parent.object_id);";
  $NEWDB->query($sql);
  
}

/************************************************************
  upgrade account permissions
************************************************************/

function updatePerms()
{

  global $NEWDB;
	
	$blank = null;
	for ($i=0;$i<32;$i++) $blank .= "0";
	
	$perms = xml2array(file_get_contents("../config/permissions.xml"));
	
	$sql = "SELECT account_id,bitset FROM auth_accountperm ORDER BY account_id";
	$list = $NEWDB->fetch($sql);
	
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
	  $NEWDB->update("auth_accountperm",$opt);
	
	}
	
	
	/************************************************************
	  upgrade group permissions
	************************************************************/
	$sql = "SELECT group_id,bitset FROM auth_groupperm ORDER BY group_id";
	$list = $NEWDB->fetch($sql);
	
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
	  $NEWDB->update("auth_groupperm",$opt);
	
	}
	
	
	
	/************************************************************
	  upgrade object permissions
	************************************************************/
	$blank = null;
	for ($i=0;$i<8;$i++) $blank .= "0";
	
	$perms = xml2array(file_get_contents("../config/customperm.xml"));
	
	$sql = "SELECT * FROM docmgr.dm_object_perm ORDER BY object_id";
	$list = $NEWDB->fetch($sql);
	
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
	  
	  $NEWDB->update("docmgr.dm_object_perm",$opt);
	
	}

	//now we have to make a permissions entry for all object owners that don't have permissions
	$sql = "SELECT id,object_owner FROM docmgr.dm_object WHERE id NOT IN 
					(SELECT object_id FROM docmgr.dm_object_perm WHERE object_id=dm_object.id AND account_id=dm_object.object_owner)";
	$list = $NEWDB->fetch($sql);

	for ($i=0;$i<$list["count"];$i++)
	{
 
		$opt = null;
		$opt["object_id"] = $list[$i]["id"];
		$opt["account_id"] = $list[$i]["object_owner"];
		$opt["bitmask"] = "00000001";
		$NEWDB->insert("docmgr.dm_object_perm",$opt);
 
	}
 

}
	