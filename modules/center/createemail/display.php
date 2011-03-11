<?php

if ($_REQUEST["hideHeader"]) $template = "solo";

$modJs .= "ckeditor/ckeditor.js;";
$modJs .= "ckeditor/config.js;";
$modCss .= "ckeditor/stylesheet.css;";

$modJs .= "modules/center/createemail/js/template.js;";
$modJs .= "modules/center/createemail/js/suggest.js;";
$modJs .= "modules/center/createemail/js/attach.js;";
$modJs .= "modules/center/createemail/js/addr.js;";

$modCss .= THEME_PATH."/css/toolbar.css;";

$onPageLoad = "loadPage()";

if (BROWSER=="ie") $btnclass = "ieSiteToolbarBtn";
elseif (BROWSER=="safari") $btnclass = "safariSiteToolbarBtn";
else $btnclass = "siteToolbarBtn";

$content = "
<div id=\"templatewin\" style=\"visibility:hidden;position:absolute\"></div>
<div id=\"addrwin\" style=\"visibility:hidden;position:absolute\"></div>
<iframe name=\"uploadframe\" id=\"uploadframe\"  style=\"display:none;width:300px;height:50px;\"></iframe>
<form name=\"uploadForm\" method=\"post\" enctype=\"multipart/form-data\" action=\"index.php?module=attachupload\" target=\"uploadframe\"></form>

<form name=\"pageForm\" method=\"post\" action=\"index.php?module=sendemail\" target=\"uploadframe\">
<input type=hidden name=\"action\" id=\"action\" value=\"\">
<input type=hidden name=\"mode\" id=\"mode\" value=\"".$_REQUEST["mode"]."\">
<input type=hidden name=\"uid\" id=\"uid\" value=\"".$_REQUEST["uid"]."\">
<input type=hidden name=\"taskId\" id=\"taskId\" value=\"".$taskId."\">
<input type=hidden name=\"objectPath\" id=\"objectPath\" value=\"".$objectPath."\">
<input type=hidden name=\"objectType\" id=\"objectType\" value=\"".$objectType."\">
<input type=hidden name=\"objectId\" id=\"objectId\" value=\"".$objectId."\">
<input type=hidden name=\"contactId\" id=\"contactId\" value=\"".@implode(",",$contactId)."\">
<input type=hidden name=\"docmgrAttachments\" id=\"docmgrAttachments\" value=\"".$_REQUEST["docmgrAttachments"]."\">
<input type=hidden name=\"siteTemplate\" id=\"siteTemplate\" value=\"".$template."\">
<textarea style=\"visibility:hidden;position:absolute;left:0px;top:0px;width:0px;height:0px\" id=\"notes\" name=\"notes\">".$_REQUEST["notes"]."</textarea>

<div class=\"main\" id=\"main\">

  <div class=\"toolbar\" id=\"emailToolbar\"></div>
  <div id=\"emailHeader\">
    <div class=\"emailHeaderCell\">
      <div class=\"emailHeaderTitle\">To</div>
      <div class=\"emailHeaderContent\">
        <textarea name=\"to\" id=\"to\" onFocus=\"setFocus('to');\" onKeyUp=\"suggestAddress(event)\">".$email."</textarea>
        <div id=\"tosuggest\" class=\"suggestdiv\"></div>
      </div>
    </div>
    <div class=\"cleaner\">&nbsp;</div>
    <div class=\"emailHeaderCell\" style=\"visibility:hidden;position:absolute;\" id=\"ccCell\">
      <div class=\"emailHeaderTitle\">CC</div>
      <div class=\"emailHeaderContent\">
        <textarea name=\"cc\" id=\"cc\" onFocus=\"setFocus('cc');\" onKeyUp=\"suggestAddress(event)\">".$cc."</textarea>
        <div id=\"ccsuggest\" class=\"suggestdiv\"></div>
      </div>
    </div>
    <div class=\"cleaner\">&nbsp;</div>
    <div class=\"emailHeaderCell\" style=\"visibility:hidden;position:absolute;\" id=\"bccCell\">
      <div class=\"emailHeaderTitle\">BCC</div>
      <div class=\"emailHeaderContent\">
        <textarea name=\"bcc\" id=\"bcc\" onFocus=\"setFocus('bcc');\" onKeyUp=\"suggestAddress(event)\">".$bcc."</textarea>
        <div id=\"bccsuggest\" class=\"suggestdiv\"></div>
      </div>
    </div>
    <div class=\"cleaner\">&nbsp;</div>
    <div class=\"emailHeaderCell\" id=\"subjectCell\">
      <div class=\"emailHeaderTitle\">Subject</div>
      <div class=\"emailHeaderContent\">
        <input type=text name=\"subject\" id=\"subject\" value=\"".$subject."\" autocomplete=\"off\">
        <input type=button value=\"CC\" onClick=\"cycleObject('ccCell')\" class=\"".$btnclass."\">
        <input type=button value=\"BCC\" onClick=\"cycleObject('bccCell')\" class=\"".$btnclass."\">
      </div>
    </div>
    <div class=\"cleaner\">&nbsp;</div>

  </div>
  <div id=\"emailContent\">
  <textarea name=\"editor_content\" id=\"editor_content\">".$emailContent."</textarea>
  </div>

</div>
</form>
";

$opt = null;
$opt["content"] = $content;
$opt["hideHeader"] = 1;
$siteContent = sectionDisplay($opt);
