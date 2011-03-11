<?php

//get our javascript files
$modJs .= "javascript/eform.js;";
$modJs .= "modules/center/admin/accounts/js/profile.js;";
$modJs .= "modules/center/admin/accounts/js/password.js;";
$modJs .= "modules/center/admin/accounts/js/group.js;";
$modJs .= "modules/center/admin/accounts/js/permission.js;";
$modJs .= "modules/center/admin/accounts/js/docmgrsettings.js;";
$modJs .= "modules/center/admin/accounts/js/createuser.js;";

//our stylesheets
$modCss .= "modules/center/admin/accounts/css/profile.css;";
$modCss .= "modules/center/admin/accounts/css/docmgrsettings.css;";
$modCss .= THEME_PATH ."/css/eform.css;";
$modCss .= THEME_PATH."/css/toolbar.css;";

$onPageLoad = "loadPage()";																												//default to blank (loads summary)

$content = "
<form name=\"pageForm\">
<input type=hidden name=\"accountId\" id=\"accountId\" value=\"".$accountId."\">
<input type=hidden name=\"pageMode\" id=\"pageMode\" value=\"".$pageMode."\">

<div id=\"popup\"></div>
<div id=\"container\">

  <!-- toolbar for our module -->
  <div class=\"toolbar\" id=\"toolbar\"></div>
  <!-- main container for our content -->
  <div id=\"content\"></div>
  <div class=\"cleaner\">&nbsp;</div>

</div>

</form>
";

$opt = null;
$opt["hideHeader"] = 1;
$opt["content"] = $content;
$siteContent = sectionDisplay($opt);
