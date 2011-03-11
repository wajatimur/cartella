<?php

//call the appropriate page template
if ($_REQUEST["hideHeader"]) {
  include("popup.php");				//popup mode for handling task edits only
  $template = "blank";
}
else include("fullpage.php");									//full page mode with the list

//output the content
$opt = null;
$opt["hideHeader"] = 1;
$opt["content"] = $content;
$siteContent = sectionDisplay($opt);
