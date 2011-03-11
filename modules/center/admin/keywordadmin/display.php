<?php

//$modJs .= "javascript/tree-complete.js;";
$modJs .= "modules/center/docmgr/js/treeform.js;";
$modCss .= THEME_PATH."/css/toolbar.css;";

$onPageLoad = "loadPage()";

$content = "

<div id=\"toolbar\" class=\"toolbar\"></div>

<div id=\"container\">
<div class=\"leftColumn\" id=\"left\"></div>
<div class=\"rightColumn\" id=\"right\"></div>
<div class=\"cleaner\">&nbsp;</div>
</div>

";

$siteContent = $content;