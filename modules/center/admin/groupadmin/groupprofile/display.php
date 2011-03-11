<?php

$siteContent .= "<div class=\"pageHeader\">
		Profile Information
		</div>
		<br>
			<form name=\"pageForm\" method=post onSubmit=\"return formCheck();\">	
			<input type=hidden name=\"pageAction\" id=\"pageAction\" value=\"update\">
			<input type=hidden name=\"module\" id=\"module\" value=\"groupadmin\">
			<input type=hidden name=\"includeModule\" id=\"includeModule\" value=\"groupprofile\">
			<div class=\"formHeader\">Group Name</div>
			<input type=text name=\"name\" id=\"name\" value=\"".$groupInfo["name"]."\">
			<br><br>
			<div class=\"formHeader\">Internal ID</div>
			<b>".$groupInfo["id"]."</b>
			<br><br>
			<input type=submit value=\"Submit Changes\">
			</form>
			";

