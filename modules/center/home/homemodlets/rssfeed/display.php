<?php

//get the rss feed url by account id and container id
$sql = "SELECT name,url FROM modlet.rssfeed WHERE account_id='".USER_ID."' AND container='".$_REQUEST["modletid"]."'";
$info = single_result($conn,$sql);

if ($info["url"]) {
  $str = processRss($info["url"],"10");
} else {
  $str = "Please click edit to pick your rss feed";
}

if ($info["name"]) $name = $info["name"];
else $name = "RSS Feed";

$edit = "<img style=\"margin-right:5px\" src=\"".THEME_PATH."/images/icons/edit.png\" title=\"Edit Feed\" onClick=\"editFeed('".$_REQUEST["modletid"]."');\">";
//$edit = "<div class=\"editRSSLink\"><a href=\"javascript:editFeed('".$_REQUEST["modletid"]."');\">[Edit]</a></div>";

//append a div for editing the feed
$str .= "<div id=\"rssFeedWin\"></div>";

//store current feed info here.  use modletid to suffix field names
$str .= "<input type=\"hidden\" name=\"feedName".$_REQUEST["modletid"]."\" id=\"feedName".$_REQUEST["modletid"]."\" value=\"".$info["name"]."\">\n";
$str .= "<input type=\"hidden\" name=\"feedPath".$_REQUEST["modletid"]."\" id=\"feedPath".$_REQUEST["modletid"]."\" value=\"".$info["url"]."\">\n";

$opt = null;
$opt["header"] = $name;
$opt["rightheader"] = $edit;
$opt["module"] = $module;
$opt["content"] = $str;
$str = createModlet($opt);

die($str);

?>
