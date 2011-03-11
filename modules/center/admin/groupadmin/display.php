<?php

$mainContent = null;

  $mainContent .= "
                <form name=\"searchForm\" method=post>
                <div style=\"float:right\">
                  <a href=\"javascript:createGroup()\">[Create new Group]</a>
                </div>
                <select name=\"groupId\" size=\"1\" onChange=\"selectGroup()\">
                <option value=\"select\">Select A Group
                ";

  for ($row=0;$row<$searchResults["count"];$row++) {

      $id = &$searchResults["id"][$row];
      $name = &$searchResults["name"][$row];

      if ($groupId==$id) $select = " SELECTED ";
      else $select = null;
    
      $mainContent .= "<option value=\"".$id."\" ".$select.">".$name."\n";
    
  }
                
  $mainContent .= "                
                </select>
                </form>
                ";

                  
  $mainContent .= "</div><br>";

/*****************************************************
  This section shows the options for the account
*****************************************************/
if ($groupId!=NULL) {

  $mainContent .= "<div class=\"pageHeader\">
                Now Editing \"".$groupInfo["name"]."\"
                </div>
                ";

  if ($groupId=="0") $filter = "everyone";
  else $filter = null;
  
  //show links for all options here
  $mainContent .= showModLinks($siteModInfo["groupadmin"]["module_path"],"groupadmin",$filter);

}

$siteContent = "

<table width=100% style=\"padding:10px\">
<tr><td width=45% valign=top>
  ".$mainContent."
</td><td width=55% valign=top style=\"padding-left:20px\">
  ";
  
  //there was a perm error accessing the sub module.  Display the error and stop
  if ($permErrorMessage) $siteContent .= "<div class=\"errorMessage\">".$permErrorMessage."</div>\n";
  else {

    //determine our process file and our display file
    $style_path = $siteModInfo["$includeModule"]["module_path"]."stylesheet.css";
    $js_path = $siteModInfo["$includeModule"]["module_path"]."javascript.js";
    $display_path = $siteModInfo["$includeModule"]["module_path"]."display.php";
  
    //these get called by our body.inc.php file
    if (file_exists("$style_path")) includeStylesheet("$style_path");
    if (file_exists("$js_path")) includeJavascript("$js_path");
  
    //define our display module if there is one
    if (file_exists("$display_path")) include("$display_path");;

  }

$siteContent .= "
  </td></tr>
  </table>
";
