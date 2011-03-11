<?php

if (!$_SESSION["leftModList"]) {
	$arr = loadSiteStructure("modules/left/");
	$_SESSION["leftModList"] = $arr["list"];
}

$leftModList = &$_SESSION["leftModList"];

$leftColumnContent .= "<table border=0>";

$num = count($leftModList["module_name"]);

for ($z=0;$z<$num;$z++) {


	/*********************************************************************************
		check to see if there are any show_module or hide_module restrictions.
	*********************************************************************************/
	$showModArr = &$leftModList["show_module"][$z];
	$hideModArr = &$leftModList["hide_module"][$z];

	//show_module means we only display this column module if our center module
	//is in the show_module array
	if (is_array($showModArr) && !in_array($module,$showModArr)) continue;
		
	//hide_module means we hide this column module if our center module
	//is in the hide_module array
	if (is_array($hideModArr) && in_array($module,$hideModArr)) continue;

	//get the process file, this will tell us if this one is hidden
	$hide_sidemod = null;

	//skip the rest of the loop if there is a perm error
	if ($leftModList["auth_only"][$z]==1 && !defined("USER_ID")) continue;

	$file = $leftModList["module_path"][$z]."process.php";
	if (file_exists($file)) include("$file");

	if ($leftModList["hidden"][$z]) $hide_sidemod = 1;

	if (!$hide_sidemod) {

		$file = $leftModList["module_path"][$z]."display.php";

		if (file_exists($file)) {
			$leftColumnContent .= "<tr><td width=100%>";
			include("$file");
			$leftColumnContent .= "</td></tr><tr><td>&nbsp;</td></tr>";
		}
	}

}

$leftColumnContent .= "</table>";
?>
