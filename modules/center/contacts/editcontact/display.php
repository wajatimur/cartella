<?php

//get our javascript files
$modJs .= "javascript/eform.js;";
$modJs .= "modules/center/contacts/editcontact/js/editcontact.js;";
$modJs .= "modules/center/contacts/editcontact/js/editaccount.js;";

//our stylesheets
$modCss .= "modules/center/contacts/editcontact/css/editcontact.css;";
$modCss .= "modules/center/contacts/editcontact/css/editaccount.css;";

$modCss .= THEME_PATH."/css/eform.css;";
$modCss .= THEME_PATH."/css/toolbar.css;";


$onPageLoad = "loadPage()";																												//default to blank (loads summary)

$content = "
<form name=\"pageForm\">
<input type=hidden name=\"contactId\" id=\"contactId\" value=\"".$contactId."\">
<input type=hidden name=\"pageMode\" id=\"pageMode\" value=\"".$pageMode."\">

<div id=\"popup\"></div>
<div id=\"container\">

  <!-- toolbar for our module -->
  <div id=\"toolbar\" class=\"toolbar\"></div>
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
