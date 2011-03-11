<?php
$pageAction = $_POST["pageAction"];
$groupId = $_SESSION["groupId"];

if ($_SESSION["groupId"]==NULL) {
	$errorMessage = "No group is specified";
	return false;
}

if ($pageAction=="update") {

	$sql = "DELETE FROM auth_grouplink WHERE groupid='$groupId';";
	
	for ($i=0;$i<count($_POST["accountId"]);$i++) {
	
		$sql .= "INSERT INTO auth_grouplink (accountid,groupid) VALUES ('".$_POST["accountId"][$i]."','".$groupId."');";
	
	}
	
	if ($DB->query($sql)) $successMessage = "Group updated successfully";
	else $errorMessage = "Group Update Failed";

}

