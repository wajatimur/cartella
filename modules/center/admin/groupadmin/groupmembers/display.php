<?php

if ($_REQUEST["filter"]) $filter = $_REQUEST["filter"];
else $filter = "community";

$opt = null;

//get all accounts used by this group
$sql = "SELECT accountid FROM auth_grouplink WHERE groupid='$groupId'";
$linkList = $DB->fetch($sql,1);

$a = new ACCOUNT();
$accountArr = $a->getList();

/*************************************************************
	Lay it all out
*************************************************************/

//account filter

//basic info layout
$siteContent .= "

		<div class=\"pageHeader\">

		Group Members:
		</div>
		<form name=\"pageForm\" method=\"post\">
		<input type=hidden name=\"pageAction\" id=\"pageAction\" value=\"update\">
		<input type=hidden name=\"module\" id=\"module\" value=\"groupadmin\">
		<input type=hidden name=\"includeModule\" id=\"includeModule\" value=\"groupmembers\">
		<div id=\"memList\">

		";

//spit out the account list		
for ($row=0;$row<count($accountArr);$row++) {

		if (!$accountArr[$row]["id"]) continue;

		if (@in_array($accountArr[$row]["id"],$linkList["accountid"])) $checked = " CHECKED ";
		else $checked = null;

		if ($accountArr[$row]["hidden"]) $style = "display:none;";
		else $style = "display:block;";

		$siteContent .= "<div style=\"".$style."\">";
		$siteContent .= "<input 	type=checkbox
															name=\"accountId[]\"
															id=\"accountId[]\"
															value=\"".$accountArr[$row]["id"]."\"
															".$checked."
															>&nbsp;";
		$siteContent .= $accountArr[$row]["login"];
		$siteContent .= "</div>";

}

$siteContent .= "
		</div>
		<br><br>
		<input type=submit value=\"Submit Changes\">
		</form>
		";


?>
