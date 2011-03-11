<?php

//common stuff
$modJs .= "modules/center/docmgr/proplib/properties.js;";
$modJs .= "modules/center/docmgr/proplib/logs.js;";
$modJs .= "modules/center/docmgr/proplib/permissions.js;";
$modJs .= "modules/center/docmgr/proplib/parents.js;";
$modJs .= "modules/center/docmgr/js/treeform.js;";
$modJs .= "modules/center/docmgr/proplib/discussion.js;";
$modJs .= "modules/center/docmgr/js/subscriptions.js;";

//local
$modJs .= "modules/center/docmgr/fileprop/js/history.js;";

//our stylesheets
$modCss .= "modules/center/docmgr/proplib/properties.css;";
$modCss .= "modules/center/docmgr/proplib/discussion.css;";
$modCss .= "modules/center/docmgr/proplib/permissions.css;";
$modCss .= "modules/center/docmgr/proplib/logs.css;";
$modCss .= "modules/center/docmgr/fileprop/css/history.css;";
$modCss .= THEME_PATH."/css/toolbar.css;";

//editor
$modJs .= "ckeditor/ckeditor.js;";
$modJs .= "ckeditor/config.js;";
$modCss .= "ckeditor/stylesheet.css;";
          

if ($_REQUEST["pageLoad"]) $onPageLoad = "loadPage('".$_REQUEST["pageLoad"]."')";
else $onPageLoad = "loadPage()";

if ($_REQUEST["callingModule"]=="intranet") $nav = setupIntranetObjectTrail($objInfo["display_path"],$objInfo["objectid_path"]);
else $nav = setupObjectTrail($objInfo["object_path"]);

$content = "
".jsCalLoad()."
<form name=\"pageForm\" method=\"post\" enctype=\"multipart/form-data\" action=\"\">
<input type=hidden name=\"objectId\" id=\"objectId\" value=\"".$objectId."\">
<input type=hidden name=\"parentPath\" id=\"parentPath\" value=\"".$parentPath."\">
<input type=hidden name=\"parentId\" id=\"parentId\" value=\"".$parentId."\">
<input type=hidden name=\"pageLoad\" id=\"pageLoad\" value=\"".$_REQUEST["pageLoad"]."\">
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
<iframe id=\"uploadframe\" name=\"uploadframe\" style=\"display:none\" src=\"\"></iframe>

</form>
";

$opt = null;
$opt["hideHeader"] = 1;
$opt["content"] = $content;
$siteContent = sectionDisplay($opt);
