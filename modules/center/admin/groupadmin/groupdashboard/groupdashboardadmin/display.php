<?php

$modJs .= "javascript/modlet.js;";
$modCss .= THEME_PATH."/css/modlet.css;";

$onPageLoad = "loadPage();";

//load our modlets
$modlets = getModletLayout($conn,"home",$groupId);

$modcol1 = &$modlets["column1"];
$modcol2 = &$modlets["column2"];
$modcont1 = &$modlets["containerid1"];
$modcont2 = &$modlets["containerid2"];

$siteContent = "

<form name=\"pageForm\">
	<input type=hidden name=\"groupId\" id=\"groupId\" value=\"".$groupId."\">
	<input type=hidden name=\"saveModule\" id=\"saveModule\" value=\"home\">
	<input type=hidden name=\"column1\" id=\"column1\" value=\"".@implode(",",$modcol1)."\">
	<input type=hidden name=\"column2\" id=\"column2\" value=\"".@implode(",",$modcol2)."\">
	<input type=hidden name=\"containerid1\" id=\"containerid1\" value=\"".@implode(",",$modcont1)."\">
	<input type=hidden name=\"containerid2\" id=\"containerid2\" value=\"".@implode(",",$modcont2)."\">

	<div class=\"pageHeader\">
	Altering default dashboard for ".$groupInfo["name"]."
	</div>

	<div id=\"dashboard\">
	<div class=\"leftColumn\" id=\"LeftColumn\" dropobj=\"0\">
	</div>
	<div class=\"rightColumn\" id=\"RightColumn\" dropobj=\"0\">
	</div>
	</div>

</form>
";

$opt = null;
$opt["hideHeader"] = 1;
$opt["content"] = $siteContent;
$siteContent = sectionDisplay($opt);
