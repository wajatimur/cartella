<?php

//get the rss feed url by account id and container id
$sql = "SELECT * FROM modlet.tasks WHERE account_id='".USER_ID."' AND container='".$_REQUEST["modletid"]."'";
$info = single_result($conn,$sql);

$content = "<div id=\"taskList\">Loading...</div>";

$edit = "<img style=\"margin-right:5px\" src=\"".THEME_PATH."/images/icons/edit.png\" title=\"Edit Settings\" onClick=\"manageTasks('".$_REQUEST["modletid"]."');\">";

if (!$info) {
  $info["name"] = "Tasks";
  $info["daterange"] = "week";
}
    
//store current cal info here.  use modletid to suffix field names
$content .= "<input type=\"hidden\" name=\"taskName".$_REQUEST["modletid"]."\" id=\"taskName".$_REQUEST["modletid"]."\" value=\"".$info["name"]."\">\n";
$content .= "<input type=\"hidden\" name=\"taskSpan".$_REQUEST["modletid"]."\" id=\"taskSpan".$_REQUEST["modletid"]."\" value=\"".$info["daterange"]."\">\n";
    
$opt = null;
$opt["header"] = $info["name"];
$opt["rightheader"] = $edit;
$opt["module"] = $module;
$opt["content"] = $content;
$opt["pageload"] = "loadTaskList('".$_REQUEST["modletid"]."')";
$str = createModlet($opt);

//we stop here to avoid overhead of extra processing
die($str);

?>