<?php

$pageAction = $_POST["pageAction"];
$includeModule = $_REQUEST["includeModule"];

if ($_REQUEST["groupId"]!=NULL) $groupId = $_REQUEST["groupId"];
elseif ($_SESSION["groupId"]!=NULL) $groupId = $_SESSION["groupId"];

if (!$includeModule)
{
	if ($groupId=="0") $includeModule = "groupperm";
	else if ($groupId) $includeModule = "groupprofile";
}


/********************************************************************
	process our search string
********************************************************************/

//handle the everyone group
if ($groupId=="0")
{

	$_SESSION["groupId"] = $groupId;
	$groupInfo = array();
	$groupInfo["name"] = "Everyone";

} else if ($groupId) {

	$_SESSION["groupId"] = $groupId;
	$sql = "SELECT * FROM auth_groups WHERE id='$groupId'";
	$groupInfo = single_result($conn,$sql);

}

//processing for our sub module.  We cannot call these with a function, or
//we do not get information passed from process to display like we want
$processPath = $siteModInfo["$includeModule"]["module_path"]."process.php";
$functionPath = $siteModInfo["$includeModule"]["module_path"]."function.php";

if (file_exists($functionPath)) include($functionPath);
if (file_exists($processPath)) include($processPath);

$sql = "SELECT * FROM auth_groups ORDER BY name";
$searchResults = total_result($conn,$sql);

//add the everyone group to the dropdown
array_unshift($searchResults["id"],"0");
array_unshift($searchResults["name"],"Everyone");
$searchResults["count"]++;
