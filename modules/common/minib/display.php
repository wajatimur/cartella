<?php

$onPageLoad = "loadPage();";
$str = "

<form name=\"pageForm\">
<input type=hidden name=\"browsePath\" id=\"browsePath\" value=\"".$browsePath."\">
<input type=hidden name=\"objectName\" id=\"objectName\" value=\"".$_REQUEST["objectName"]."\">
<input type=hidden name=\"objectTypeFilter\" id=\"objectTypeFilter\" value=\"".$_REQUEST["objectTypeFilter"]."\">
<input type=hidden name=\"runMode\" id=\"runMode\" value=\"".$_REQUEST["mode"]."\">
<input type=hidden name=\"editor\" id=\"editor\" value=\"".$_REQUEST["editor"]."\">
<input type=hidden name=\"browseFilter\" id=\"browseFilter\" value=\"".$filter."\">
<div id=\"container\">
  <div id=\"mbObjectToolbar\"></div>
  <div id=\"mbObjectList\"></div>
  <div id=\"mbPathDiv\"></div>
  <div id=\"mbTypeDiv\"></div>
  <div id=\"mbPathDisplay\"></div>
  <div id=\"mbObjectControls\"></div>
</div>
</form>

";

$siteContent = $str;
