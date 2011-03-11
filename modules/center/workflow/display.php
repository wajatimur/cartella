<?php

//css files
$modCss .= THEME_PATH."/css/toolbar.css;";
$modCss .= "modules/center/workflow/css/tasks.css;";
$modCss .= "modules/center/workflow/css/workrecipient.css;";
$modCss .= "modules/center/workflow/css/workflow.css;";

//js files
$modJs .= "modules/center/workflow/js/tasks.js;";
$modJs .= "modules/center/workflow/js/workflow.js;";
$modJs .= "modules/center/workflow/js/workrecipient.js;";
$modJs .= "modules/center/workflow/js/history.js;";

$onPageLoad = "loadPage();";

$siteContent = "
".jsCalLoad()."
<input type=\"hidden\" name=\"object_id\" id=\"object_id\" value=\"".$_REQUEST["objectId"]."\">
<input type=\"hidden\" name=\"route_id\" id=\"route_id\" value=\"".$_REQUEST["routeId"]."\">
<input type=\"hidden\" name=\"workflow_id\" id=\"workflow_id\" value=\"".$_REQUEST["workflowId"]."\">
<input type=\"hidden\" name=\"action\" id=\"action\" value=\"".$_REQUEST["action"]."\">
<div class=\"toolbar\">
  <div id=\"toolbarBtns\"></div>
  <div id=\"toolbarTitle\" class=\"toolbarTitle\"></div>
</div>
<div id=\"container\"></div>
";
