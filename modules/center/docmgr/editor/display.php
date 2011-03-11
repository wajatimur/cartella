<?php

//use the old school code if using dso.  mootools crashes dsoframer
if ($editor=="dsoframer") $template = "dso";
else $template = "blank";

//javascript fiels
$modJs .= "ckeditor/ckeditor.js;";
$modJs .= "ckeditor/config.js;";
$modCss .= "ckeditor/stylesheet.css;";
$modJs .= "modules/center/docmgr/editor/js/editor.js;";
$modJs .= "modules/center/docmgr/editor/js/dmeditor.js;";
$modJs .= "modules/center/docmgr/editor/js/textedit.js;";
$modJs .= "modules/center/docmgr/editor/js/dsoframer.js;";
      
//css files
//if ($editor=="dsoframer") $modCss .= "modules/center/docmgr/editor/css/dsoframer.css;";

$modCss .= THEME_PATH."/css/toolbar.css;";

//load the page
$onPageLoad = "loadPage()";

$siteContent ="

<form name=\"pageForm\" method=\"post\">
<input type=\"hidden\" name=\"editor\" id=\"editor\" value=\"".$editor."\">
<input type=\"hidden\" name=\"objectId\" id=\"objectId\" value=\"".$objectId."\">
<input type=\"hidden\" name=\"objectPath\" id=\"objectPath\" value=\"".$objectPath."\">
<input type=\"hidden\" name=\"objectType\" id=\"objectType\" value=\"".$objectType."\">
<input type=\"hidden\" name=\"objectName\" id=\"objectName\" value=\"".$objectName."\">
<input type=\"hidden\" name=\"parentPath\" id=\"parentPath\" value=\"".$parentPath."\">
<input type=\"hidden\" name=\"directPath\" id=\"directPath\" value=\"".$directPath."\">
<input type=\"hidden\" name=\"taskId\" id=\"taskId\" value=\"".$taskId."\">
<input type=\"hidden\" name=\"contactId\" id=\"contactId\" value=\"".$contactId."\">
<input type=\"hidden\" name=\"action\" id=\"action\" value=\"\">
<input type=\"hidden\" name=\"printpreview\" id=\"printpreview\" value=\"\">


<div id=\"editorToolbar\" class=\"toolbar\"></div>
<textarea name=\"editor_content\" id=\"editor_content\"></textarea>
<div id=\"editorDiv\"></div>
<iframe id=\"ddFrame\" style=\"display:none;position:absolute;\"></div>

</form>

";

