<?php
$pageAction = $_POST["pageAction"];
$groupId = $_SESSION["groupId"];

if ($_SESSION["groupId"]==NULL) {
	$errorMessage = "No account is specified";
	return false;
}

if ($pageAction=="update") {

	$option = null;
	$opt = null;
	$opt["name"] = $_POST["name"];
	$opt["where"] = "id='$groupId'";
	if (dbUpdateQuery($conn,"auth_groups",$opt)) $successMessage = "Group updated successfully";
	else $errorMessage = "Group update failed";

}

$sql = "SELECT * FROM auth_groups WHERE id='$groupId'";
$groupInfo = single_result($conn,$sql);
