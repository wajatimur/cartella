<?php
$pageAction = $_POST["pageAction"];
$groupId = $_SESSION["groupId"];

if ($_SESSION["groupId"]==NULL) {
	$errorMessage = "No group is specified";
	return false;
}
