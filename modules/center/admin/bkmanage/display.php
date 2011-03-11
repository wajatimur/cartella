<?php

$modJs .= "modules/center/docmgr/js/treeform.js;";
$modCss .= THEME_PATH."/css/toolbar.css;";

$onPageLoad = "loadPage();";

$content = "<form name=\"pageForm\" method=\"post\">
            <input type=\"hidden\" name=\"pageAction\" id=\"pageAction\" value=\"\">
            <div class=\"toolbar\">
              <div id=\"toolbarLeft\">
              </div>
              <div id=\"toolbarRight\">
                ".createAccountSelect()."
              </div>
            </div>  
            <div class=\"cleaner\">&nbsp;</div>
            <br>
            <div class=\"leftColumn\">
              <div class=\"formHeader\">Please select a bookmark to edit</div>
              <div id=\"bkList\">You must select an account first</div>
            </div>
            <div class=\"rightColumn\" id=\"bkInfo\">
            </div>
            <div class=\"cleaner\">&nbsp;</div>  
            ";
            
            
$opt = null;
$opt["hideHeader"] = 1;
$opt["content"] = $content;
$siteContent = sectionDisplay($opt);

