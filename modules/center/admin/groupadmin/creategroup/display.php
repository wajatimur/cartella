<?php

if ($successMessage) 
{

	$onPageLoad = "loadGroup('".$groupId."');";
	$siteContent = "Please wait...";

} else {

	$siteContent .= "
		<div style=\"padding:10px\">
		<div class=\"pageHeader\">
		Create New Group
		</div>
		<br>
		<form name=\"pageForm\" method=post onSubmit=\"return formCheck();\">	
		<input type=hidden name=\"pageAction\" id=\"pageAction\" value=\"insert\">
		<input type=hidden name=\"module\" id=\"module\" value=\"creategroup\">

		<div>
			<div class=\"formHeader\">Group Name</div>
			<input type=text name=\"name\" id=\"name\" value=\"\">
		</div>
		<div>
			<input type=submit value=\"Submit Changes\">
		</div>
		</form>

		";

}
