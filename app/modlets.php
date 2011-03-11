<?php

/********************************************************************************************

	FILE:	modlets.inc.php
	PURPOSE:	contains functions specific to loading modlets
                        
*********************************************************************************************/

/***************************************************************************************
  FUNCTION:	loadModletList
  PURPOSE:	returns an array of all modlets available for this module
  INPUT:		$module -> name of module the modlets should belong to
  RETURNS:	the list of modlets and all their module data in proper sorted order
***************************************************************************************/
function loadModletList($module) {

	//find our object modules if that hasn't been done already for this object type
	if (!$_SESSION["siteModletList"][$module] || defined("DEV_MODE")) {

	  	$siteModList = $_SESSION["siteModList"];
	  	$modletArr = array();
  
	  	//get the keys of all modules that are objects
	  	if (!is_array($siteModList["modlet"])) return false;

	  	$fields = array_keys($_SESSION["siteModList"]);

	  	//get all our modlet modules with entries
	  	$modletCheck = arrayReduce($siteModList["modlet"]);
	  	
	  	$num = count($siteModList["link_name"]);
	  	for ($i=0;$i<$num;$i++) {
	  	
	  	  $modlet = $siteModList["modlet"][$i];
        $permError = null;
        
	  	  //stop here if there is no modlet entry
	  	  if (!is_array($modlet)) continue;

        //if our current module is a modlet for the passed module, store it's info
        if (in_array($module,$modlet)) {

          //process our module permissions
          $arr = checkModPerm($siteModList["link_name"][$i],BITSET);

          if (is_array($arr)) extract($arr);
          if ($permError) continue;
                                                
          foreach ($fields AS $field) {
            $modletArr[$field][] = $siteModList[$field][$i];
          }
        }
	  	
	  	}
	  	
		//so they are displayed in the proper order
		if (is_array($modletArr["modlet_sort"])) $modletArr = arrayMultiSort($modletArr,"module_sort");
    else $modletArr = arrayMultiSort($modletArr,"module_name");
    
		$_SESSION["siteModletList"][$module] = $modletArr;

	}

	return $_SESSION["siteModletList"][$module];

}

/***************************************************************************************
  FUNCTION:	loadModlets
  PURPOSE:	see getModletLayout
***************************************************************************************/
function loadModlets($conn,$module) {

 //figure out which ones we will display.  this is determined by whether or not they have
 //their settings stored.  If not, then we show the default module for their group
 return getModletLayout($conn,$module);        

}

/***************************************************************************************
  FUNCTION:	getModletLayout
  PURPOSE:	determines modlet layout on page for this module. also loads all modlets
            and gets their information stored in each appropriate column and order.
            this array is later passed to createModlet to be displayed on the screen
  INPUTS:		$conn -> db connection resoure
            $module -> module modlets must belnog to
            $groupId -> group to load modlets from if there are none
  RETURNS: 	md array containing information for actually loading a modlet
***************************************************************************************/
function getModletLayout($conn,$module,$groupId = null) {

  //dont query accounts if we have a groupId
  if ($groupId==null) {
  
    $sql = "SELECT * FROM dashboard WHERE account_id='".USER_ID."' AND module='$module' ORDER BY sort_order";
    $list = total_result($conn,$sql);

  }

  //if results, arrange them accordingly
  if ($list["count"] > 0) 
  {

    //split into the two separate columns
    $col1keys = array_keys($list["display_column"],"1");
    $col2keys = array_keys($list["display_column"],"2");

    //merge our keys with our values
    $col1arr = arrayCombine($col1keys,$list["modlet"]);
    $col2arr = arrayCombine($col2keys,$list["modlet"]);;

    $cont1arr = arrayCombine($col1keys,$list["container_id"]);
    $cont2arr = arrayCombine($col2keys,$list["container_id"]);
  
  }
  //otherwise, get default layout for this person's group
  else {

    //get our group name.  Use the first one in the list (although there should only be one);
    if ($groupId!=null) $groupArr[0] = $groupId;
    else $groupArr = explode(",",USER_GROUPS);

    if (count($groupArr) > 0 && $groupArr[0]!=null) 
    {
    
      $sql = "SELECT * FROM group_dashboard WHERE group_id='".$groupArr[0]."' AND module='$module' ORDER BY sort_order";
      $list = total_result($conn,$sql);
  
      //if results, arrange them accordingly
      if ($list["count"] > 0) 
      {
  
        //split into the two separate columns
        $col1keys = array_keys($list["display_column"],"1");
        $col2keys = array_keys($list["display_column"],"2");

        //merge our keys w/ our values
        $col1arr = arrayCombine($col1keys,$list["modlet"]);
        $col2arr = arrayCombine($col2keys,$list["modlet"]);

        $cont1arr = arrayCombine($col1keys,$list["container_id"]);
        $cont2arr = arrayCombine($col2keys,$list["container_id"]);
  
      }

    }
    
  }
  
  //setup javascript files
  $modJs = null;
  $modCss = null;

  for ($i=0;$i<count($col1arr);$i++) 
  {

    $mod = $col1arr[$i];
    $modpath = $_SESSION["siteModInfo"][$mod]["module_path"];

    if (file_exists($modpath."javascript.js")) $modJs .= $modpath."javascript.js;";
    if (file_exists($modpath."stylesheet.css")) $modCss .= $modpath."stylesheet.css;";

  }

  for ($i=0;$i<count($col2arr);$i++) 
  {

    $mod = $col2arr[$i];
    $modpath = $_SESSION["siteModInfo"][$mod]["module_path"];
    if (file_exists($modpath."javascript.js")) $modJs .= $modpath."javascript.js;";
    if (file_exists($modpath."stylesheet.css")) $modCss .= $modpath."stylesheet.css;";

  }

  //assemble into an array for returning
  $ret = array();
  $ret["column1"] = $col1arr;
  $ret["column2"] = $col2arr;
  $ret["containerid1"] = $cont1arr;
  $ret["containerid2"] = $cont2arr;
  $ret["js"] = $modJs;
  $ret["css"] = $modCss;
  
  return $ret;    

}

/***************************************************************************************
  FUNCTION:	createModlet
  PURPOSE:	called by the modlet itself.  takes the modlet data and converts it to
            xml 
  INPUTS:		$opt -> array containing module data;
  RETURNS: 	string -> xml data for a modlet
***************************************************************************************/
function createModlet($opt) {

  extract($opt);
  if (!$module) return false;
    
  //default to the modules' name if not specified
  if (!$header) $header = $_SESSION["siteModInfo"][$module]["module_name"];
  $path = $_SESSION["siteModInfo"][$module]["module_path"];

  $jsfile = $path."javascript.js";
  $cssfile = $path."stylesheet.css";

  //if passed a jsfile or css file, add them back to be called later
  //if ($jsfile && file_exists($jsfile)) $GLOBALS["modletJs"] .= $jsfile.";";
  //if ($cssfile && file_exists($cssfile)) $GLOBALS["modletCss"] .= $cssfile.";";

  //if the calling modlet was passed a container id, use it.  otherwise use the modletname
  $contId = $_SESSION["siteModletList"][$module]["container_id"];
  
  $p = new PROTO();
  $p->add("modlet",$module);
  $p->add("modletid",$_REQUEST["modletid"]);
  $p->add("header",$header);
  $p->add("content",$content);
  $p->add("pageload",$pageload);
  $p->add("rightheader",$rightheader);
  $p->add("containerid",$contId);
  if ($jsfile && file_exists($jsfile)) 		$p->add("javascript",$jsfile);
  if ($cssfile && file_exists($cssfile)) 	$p->add("stylesheet",$cssfile);
  $p->output();

}

