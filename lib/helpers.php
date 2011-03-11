<?php

/********************************************************************************************

	Filename:
		helpers.inc.php
      
        This file contains functions specific to loading helper modules
                        
*********************************************************************************************/

//returns an array of all helpers for this module
function loadHelperList($module) {

	//find our object modules if that hasn't been done already for this object type
	if (!$_SESSION["siteHelperList"][$module] || defined("DEV_MODE")) {

	  	$siteModList = $_SESSION["siteModList"];
	  	$helpArr = array();
  
	  	//get the keys of all modules that are objects
	  	if (!is_array($siteModList["helper"])) return false;

	  	$fields = array_keys($_SESSION["siteModList"]);

	  	//get all our helper modules with entries
	  	$helperCheck = arrayReduce($siteModList["helper"]);
	  	
	  	$num = count($siteModList["link_name"]);
	  	for ($i=0;$i<$num;$i++) {
	  	
	  	  $helper = $siteModList["helper"][$i];

	  	  //stop here if there is no helper entry
	  	  if (!is_array($helper)) continue;

        //if our current module is a helper for the passed module, store it's info
        if (in_array($module,$helper) || $helper[0]=="all") {
          foreach ($fields AS $field) {
            $helpArr[$field][] = $siteModList[$field][$i];
          }
        }
	  	
	  	}

	  	//so they are displayed in the proper order
	  	if (is_array($helpArr["helper_sort"])) {

		    //move unsorted ones to the end of the line
		    $num = count($helpArr["helper_sort"]);
		    for ($i=0;$i<$num;$i++) {
		      if (!$helpArr["helper_sort"][$i]) $helpArr["helper_sort"][$i] = "50";
        }
        //sort by our key
        $helpArr = arrayMultiSort($helpArr,"helper_sort");

      }
      $_SESSION["siteHelperList"][$module] = $helpArr;

	}

	return $_SESSION["siteHelperList"][$module];

}

//this function checks permissions and actually includes
//the helper function file if all is well
function includeHelpers($helperArr,$bitset) {

	$num = count($helperArr["link_name"]);
	$retArr = array();

	for ($i=0;$i<$num;$i++) {

		$modPath = $helperArr["module_path"][$i];
		$modName = $helperArr["link_name"][$i];
		$skipPerm = $helperArr["helper_noperm"][$i];

		//check permissions against this object.  If they don't match (permError is returned), then don't show the icon
		$permError = null;
		if (!$skipPerm) {
			$ret = checkCustomModPerm($modName,$bitset);
			if (is_array($ret)) extract($ret);
			if ($permError) continue;
		}

                //if the function file exists, call it's class				
		$helpfunc_path = $modPath."helper.php";
		if (file_exists($helpfunc_path)) include_once($helpfunc_path);

		$className = $modName."Helper";
		$c = loadClassMethod($className,"loadHelper");
		if (is_object($c)) {
		    $id = $modName.$info["id"];
		    $ret = $c -> loadHelper($bitset);

        //if we return multiple links, load them one at a time
        if ($ret) {
        
          if ($ret[0]) {
          
            foreach ($ret AS $r) {
              $r["id"] = $id;
              $retArr[] = $r;
            }
          
          } else {
            //otherwise return single link
  		      $ret["id"] = $id;
  		      $retArr[] = $ret;
          }
        }
    }

	}

	return $retArr;

}

//this function loads all helper modules and their function files
function loadHelpers($module,$bitset = null) {

        //get our list of helpers
        $helperArr = loadHelperList($module);

        //include the helper files
        $retArr = includeHelpers($helperArr,$bitset);

        $str = null;

        //if we have some entries, loop through and display them
        if (count($retArr) > 0) {
        
          foreach ($retArr AS $entry) {
            $str .= "<div>
                      <img id=\"".$entry["id"]."\" src=\"".$entry["icon"]."\">
                      <a href=\"javascript:".$entry["link"]."\">".$entry["title"]."</a>
                     </div>
                     ";
          }

        //nothing found
        } else {
        
          $str .= "<div>No items to display</div>";
        
        }

        return $str;
        
}

/***********************************************************
  If a class an method exists, this returns the class 	
  object.Otherwise it returns false;
***********************************************************/
function loadClassMethod($className,$methodName) {

  if (!$className) return false;
  if (!$methodName) return false;

  if (!class_exists($className)) return false;
  $c = new $className;

  if (!method_exists($c,$methodName)) return false;
  else return $c;

}
