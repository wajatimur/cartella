<?php
$path = $_REQUEST["path"];

if (!$path || $path[0]=="/" || strstr($path,"..")) {
  $moduleError = "Invalid path specified";
  return false;
}

$entryArr = scandir($path);