<?php

$breakOutColumn = null;
$showRightColumnContent = null;

if (!$_SESSION["rightModList"]) {

	$arr = loadSiteStructure("modules/right/");
	$_SESSION["rightModList"] = $arr["list"];
}

$rightModList = &$_SESSION["rightModList"];

$num = count($rightModList["module_name"]);

for ($z=0;$z<$num;$z++) {
	
	$hide_sidemod = null;

        //skip the rest of the loop if there is a perm error
	if ($rightModList["auth_only"][$z]==1 && !defined("USER_ID")) continue;
                                
	$file = $rightModList["module_path"][$z]."process.php";

	if (file_exists($file)) include("$file");

	if ($rightModList["hidden"][$z]) $hide_sidemod = 1;

	if (!$hide_sidemod) {

		$file = $rightModList["module_path"][$z]."display.php";

		$rightColumnContent = null;
		if (file_exists($file)) include("$file");

		if ($rightColumnContent) $showRightColumnContent .= $rightColumnContent."<br>";

		//this prevents anymore processing of column files, like
		//if we loaded a toolbar or something
		if ($breakOutColumn) break;

	}
}

?>
