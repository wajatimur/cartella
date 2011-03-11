<?php
$pageAction = $_POST["pageAction"];

if ($pageAction=="insert") {

	$name = $_POST["name"];

	//see if a group with this name already exists
	$sql = "SELECT id FROM auth_groups WHERE name='$name'";
	$info = single_result($conn,$sql);
	
	if ($info) $errorMessage = "A group with this name already exists";
	else {
	

		$opt = null;
		$opt["name"] = $name;
		$groupId = $DB->insert("auth_groups",$opt,"id");

		if ($groupId) $successMessage = "Group created successfully";
		else $errorMessage = "Unable to create group";

	}

}

//hide the site headers
$hideHeader = 1;



