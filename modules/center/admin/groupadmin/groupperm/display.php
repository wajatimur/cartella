<?php

$str = file_get_contents("config/permissions.xml");
$arr = XML::decode($str);
$permArray = $arr["perm"];

if ($groupId!=null) 
{

	$sql = "SELECT bitset,bitmask FROM auth_groupperm WHERE group_id='$groupId'";
	$permInfo = $DB->single($sql);

}

/*************************************************************
	Lay it all out
*************************************************************/

//basic info layout
$siteContent .= "<div class=\"pageHeader\">
		Basic Site Permissions:
		</div>
		<form name=\"pageForm\" method=\"post\">
		<input type=hidden name=\"pageAction\" id=\"pageAction\" value=\"update\">
		<input type=hidden name=\"module\" id=\"module\" value=\"groupadmin\">
		<input type=hidden name=\"includeModule\" id=\"includeModule\" value=\"groupperm\">
		<ul>

		";

foreach ($permArray AS $perm)
{

	$bitPos = $perm["bitpos"];

	//non admins can't see administrator link
	if ($bitPos=="0" && !PERM::check(ADMIN)) continue;

	if ($perm["hidden"]) continue;

	if (PERM::is_set($permInfo["bitmask"],$bitPos)) $checked = " CHECKED ";
	else $checked = null;

	$siteContent .= "<li style=\"list-style-type:none\">
											<input	type=checkbox
													name=\"perm[]\"
													id=\"perm[]\"
													value=\"".$bitPos."\"
													".$checked."
													> ".$perm["name"]."
										</li>\n";

}

$siteContent .= "
		<br><br>
		<input type=submit value=\"Submit Changes\">
		</ul>
		</form>
		";


?>
