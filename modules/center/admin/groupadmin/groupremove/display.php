<?php

if ($groupId) {

	$siteContent .= "<div class=\"pageHeader\">
			Delete Group
			</div>
			<br>
			<form name=\"pageForm\" onSubmit=\"return checkPass();\" method=post>
			<input type=hidden name=\"pageAction\" id=\"pageAction\" value=\"delete\">
			<input type=hidden name=\"module\" id=\"module\" value=\"groupadmin\">
			<input type=hidden name=\"includeModule\" id=\"includeModule\" value=\"groupremove\">
			<div class=\"formHeader\">
			Are you sure you want to delete the group <b>".$groupInfo["name"]."</b>?
			</div>
			<input type=radio CHECKED name=deleteConfirm id=deleteConfirm value=\"no\"> No	
			&nbsp;&nbsp;&nbsp;			
			<input type=radio name=deleteConfirm id=deleteConfirm value=\"yes\"> Yes
			<br><br>
			<input type=submit value=\"Delete Group\">
			</form>
			";

}

