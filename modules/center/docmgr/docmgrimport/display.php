<?php


//if (USER_ID!=1000) die("Import is currently disabled");

$modJs .= "modules/center/docmgr/docmgrimport/js/edit.js;";
if (defined("TEA_ENABLE")) $modJs .= "modules/center/docmgr/docmgrimport/js/import-tea.js;";
else $modJs .= "modules/center/docmgr/docmgrimport/js/import.js;";

$modCss .= "modules/center/docmgr/docmgrimport/css/edit.css;";
$modCss .= "modules/center/docmgr/docmgrimport/css/import.css;";
$modCss .= THEME_PATH."/css/toolbar.css;";

$onPageLoad = "loadPage()";

$content = "

<input type=\"hidden\" name=\"path\" id=\"path\" value=\"".$_REQUEST["path"]."\">
<input type=\"hidden\" name=\"prevpage\" id=\"prevpage\" value=\"".$_REQUEST["prevpage"]."\">
<input type=\"hidden\" name=\"beginbrowse\" id=\"beginbrowse\" value=\"".$_REQUEST["beginbrowse"]."\">
<input type=\"hidden\" name=\"mode\" id=\"mode\" value=\"".$_REQUEST["mode"]."\">

<div class=\"toolbar\" id=\"toolBar\"></div>
<div class=\"cleaner\">&nbsp;</div>
<div id=\"container\"></div>
";

$siteContent = $content;
