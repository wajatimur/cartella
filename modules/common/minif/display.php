<?php

$onPageLoad = "loadPage();";

$ceiling = $_REQUEST["ceiling"];

if (!$ceiling) die("No ceiling specified");

if ($ceiling[0]=="/" || strstr($ceiling,"..")) die("Invalid ceiling specified");

$str = "

<form name=\"pageForm\">
<input type=hidden name=\"browseCeiling\" id=\"browseCeiling\" value=\"".stripsan($ceiling)."\">
<input type=hidden name=\"browseFilter\" id=\"browseFilter\" value=\"".$_REQUEST["filter"]."\">
<input type=hidden name=\"browsePath\" id=\"browsePath\" value=\"".$_REQUEST["path"]."\">
<div id=\"container\">
  <div id=\"mbObjectToolbar\"></div>
  <div id=\"mbObjectList\"></div>
  <div id=\"mbPathDiv\"></div>
  <div id=\"mbObjectControls\"></div>
</div>
</form>

";

$siteContent = $str;
