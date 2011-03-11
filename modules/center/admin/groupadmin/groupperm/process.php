<?php
$pageAction = $_POST["pageAction"];
$groupId = $_SESSION["groupId"];

if ($_SESSION["groupId"]==NULL) {
	$errorMessage = "No group is specified";
	return false;
}

if ($pageAction=="update") {

	$p = new PERM($groupId,"group");
	$p->saveGroup($_POST["perm"]);

	$successMessage = "Group updated successfully";

}

