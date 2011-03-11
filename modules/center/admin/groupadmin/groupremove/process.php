<?php
$pageAction = $_POST["pageAction"];
$groupId = $_SESSION["groupId"];

if ($_SESSION["groupId"]==NULL) {
	$errorMessage = "No group is specified";
	return false;
}

if ($pageAction=="delete") {

	$successMessage = null;

	$option = null;
	$option["conn"] = $conn;
	$option["groupId"] = $_SESSION["groupId"];

	if ($_POST["deleteConfirm"]=="yes") {
	
		$sql = "DELETE FROM auth_groups WHERE id='$groupId';";
		$sql .= "DELETE FROM auth_groupperm WHERE group_id='$groupId';";
		$sql .= "DELETE FROM auth_grouplink WHERE groupid='$groupId';";

		if ($DB->query($sql))
		{
			$groupId = null;
			$successMessage = "Group removed successfully";
		} else {
			$errorMessage = "Group removal failed";
		}
		
	} else $errorMessage = "You must confirm you want to delete this group before removing it";

}

if ($groupId) 
{
	$sql = "SELECT * FROM auth_groups WHERE id='$groupId'";
	$groupInfo = $DB->single($sql);
}
