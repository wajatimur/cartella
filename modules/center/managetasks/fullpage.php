<?php

//additional code to put in the site's head tags
$siteHeadStr = jsCalLoad();
$modJs .= "javascript/eform.js;";
$modJs .= "modules/center/managetasks/js/tasklist.js;";
$modJs .= "modules/center/managetasks/js/edittask.js;";

$modCss .= "modules/center/managetasks/css/edittask.css;";

$onPageLoad = "loadPage()";																												//default to blank (loads summary)

$content = "
<form name=\"pageForm\" action=\"\">
<input type=\"hidden\" name=\"taskId\" id=\"taskId\" value=\"".$taskId."\">
<input type=\"hidden\" name=\"windowMode\" id=\"windowMode\" value=\"full\">

<div id=\"container\">

  <!-- toolbar for our module -->
  <div id=\"toolbar\"></div>

  <!-- main container for our content -->
  <div id=\"content\">
    <div id=\"taskListView\">
      <div id=\"taskListToolbar\">
        <div id=\"taskListBtns\"></div>
        <div id=\"taskListTitle\">Task List</div>
      </div>
      <div id=\"taskList\"></div>
    </div>  
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
