<?php

$objectId = $_REQUEST["objectId"];

//common stuff
$modJs .= "modules/center/docmgr/proplib/properties.js;";
$modJs .= "modules/center/docmgr/proplib/logs.js;";
$modJs .= "modules/center/docmgr/proplib/permissions.js;";
$modJs .= "modules/center/docmgr/proplib/parents.js;";
$modJs .= "modules/center/docmgr/js/treeform.js;";
$modJs .= "modules/center/docmgr/js/subscriptions.js;";

//our stylesheets
$modCss .= "modules/center/docmgr/proplib/properties.css;";
$modCss .= "modules/center/docmgr/proplib/permissions.css;";
$modCss .= "modules/center/docmgr/proplib/logs.css;";
$modCss .= THEME_PATH."/css/toolbar.css;";

if ($_REQUEST["callingModule"]=="intranet") $nav = setupIntranetObjectTrail($objInfo["display_path"],$objInfo["objectid_path"]);
else $nav = setupObjectTrail($objInfo["object_path"]);

$onPageLoad = "loadPage()";

$content = "
<form name=\"pageForm\">
<input type=hidden name=\"objectId\" id=\"objectId\" value=\"".$objectId."\">
<input type=hidden name=\"parentPath\" id=\"parentPath\" value=\"".$parentPath."\">
<input type=hidden name=\"parentId\" id=\"parentId\" value=\"".$parentId."\">

<div id=\"objectNav\" style=\"display:none\">".$nav."</div>
<div id=\"popup\"></div>
<div id=\"container\">

  <!-- toolbar for our module -->
  <div class=\"toolbar\">
    <div id=\"toolbarBtns\"></div>
    <div id=\"toolbarTitle\" class=\"toolbarTitle\"></div>
  </div>
  <div class=\"cleaner\">&nbsp;</div>
  <!-- main container for our content -->
  <div id=\"content\"></div>
  <div class=\"cleaner\">&nbsp;</div>

</div>

</form>
";

$siteContent = $content;
