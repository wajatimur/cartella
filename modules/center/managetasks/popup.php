<?php

//additional code to put in the site's head tags
$siteHeadStr = jsCalLoad();

$modJs .= "javascript/eform.js;";
$modJs .= "modules/center/managetasks/js/edittask.js;";
$modCss .= "modules/center/managetasks/css/edittask.css;";

$onPageLoad = "loadEditPage()";																												//default to blank (loads summary)

$content = "
<form name=\"pageForm\" action=\"\">
<input type=\"hidden\" name=\"taskId\" id=\"taskId\" value=\"".$_REQUEST["taskId"]."\">
<input type=\"hidden\" name=\"windowMode\" id=\"windowMode\" value=\"popup\">

<div id=\"container\">

  <!-- main container for our content -->
  <div id=\"content\">
    <div id=\"editTask\">
      <div id=\"taskToolbar\">
        <div id=\"taskToolbarBtns\"></div>
        <div id=\"taskToolbarTitle\"></div>
        <div class=\"cleaner\"></div>
      </div>
      <div id=\"taskDiv\"></div>
      <div id=\"infoDiv\"></div>
    </div>
    <div class=\"cleaner\"></div>
  </div>

</div>

</form>
";
