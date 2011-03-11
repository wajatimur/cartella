<?php

$modJs .= "modules/common/pickme/attach.js;";

$onPageLoad = "loadPage();";

$str = "

<!-- our hidden iframe for file uploads -->
<iframe name=\"uploadframe\" id=\"uploadframe\" width=1 height=1 style=\"visibility:hidden;position:absolute;left:0;top:0\"></iframe>
    
    
<form name=\"pageForm\" method=\"post\" enctype=\"multipart/form-data\">
<input type=hidden name=\"path\" id=\"path\" value=\"".$_REQUEST["path"]."\">
<table width=\"100%\" border=\"0\" cellpadding=\"10\" cellspacing=\"0\">
<tr><td width=\"200px\" valign=\"top\">

  <h3>Image Uploader</h3>
  <div class=\"formHeader\">Select Files To Upload</div>
  <br>
  <div id=\"uploadFileForm\">
    <input type=file size=20 class=\"textboxSmall\" onChange=\"addFile()\">
  </div>
  <div id=\"uploadFileText\"></div>	
  <br>
  <input type=button class=\"submitSmall\" onClick=\"uploadFiles()\" value=\"Upload Files\">

</td><td width=\"80%\" valign=\"top\">

  <h3>Available Images</h3>
  <div id=\"resultList\"></div>

</td></tr>
</table>
</form>

";

$siteContent = $str;
