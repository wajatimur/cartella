<?php
$pageAction = $_POST["pageAction"];
$groupId = $_SESSION["groupId"];

if ($_SESSION["groupId"]==NULL) {
	$errorMessage = "No group is specified";
	return false;
}


if ($groupId) {
	$sql = "SELECT * FROM auth_groups WHERE id='$groupId'";
	$groupInfo = single_result($conn,$sql);
} else {
	$groupInfo = array();
	$groupInfo["id"] = "0";
	$groupInfo["name"] = "Everyone";
}

