<?php
/*****************************************************************************************************

	body.inc.php

	This file displays the site including all side columns and logos

*****************************************************************************************************/
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>
<?php
if ($siteTitle) echo $siteTitle;
else echo SITE_TITLE;
?>
</title>

<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<!-- Mimic Internet Explorer 7 -->
<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7" />

<?php
/****************************************************
	our stylesheets and javascript files
****************************************************/
$css = THEME_PATH."/css/core.css;";
$css .= THEME_PATH."/css/sitenav.css;";
$css .= THEME_PATH."/css/popup.css;";
if ($modStylesheet) $css .= $modStylesheet;
if ($modCss) $css .= $modCss;
includeStylesheet($css);

//our globals
include("javascript/globals.php");
$js = null;

//temporarily exclude from these two modules
$js .= "jslib/core.js;";
$js .= "jslib/mootools-core.js;";
$js .= "jslib/mootools-more.js;";
$js .= "jslib/xml.js;";
$js .= "jslib/query.js;";
$js .= "jslib/proto.js;";
$js .= "jslib/string.js;";
$js .= "javascript/common.js;";
$js .= "javascript/popup.js;"; 
$js .= "javascript/sitesearch.js;";

if ($modJs) $js .= $modJs;
includeJavascript($js);

if (!$show_login_form) {

  $onPageLoad = "setupModNav();".$onPageLoad;
  
  if ($onPageLoad) $onPageLoadStr = "onload=\"".$onPageLoad."\"";
  if ($siteHeadStr) echo $siteHeadStr;
}

?>

</head>

<body <?php echo $onPageLoadStr;?>>
<!-- template=solo -->

<iframe style="display:none;" name="hFrame" id="hFrame"></iframe>
<div id="sitePopupWin"></div>
<div id="siteStatus">
  <img alt="" src="themes/default/images/loading.gif">
  <div id="siteStatusMessage"></div>
</div>
    
<table width="100%" cellpadding="0" cellspacing="0" border="0" class="siteMainContainer">

<tr><td class="siteLeftColumn" valign="top">

	<div class="siteNav">
	  <?php
	  $modName = $siteModInfo[$module]["module_name"];
	  $tl = getTopLevelParent($module);
	  ?>
	  <div>
  	  <div class="modTabSelected" id="<?php echo $tl;?>ModTab"><?php echo $modName;?></div>
  	  <div id="<?php echo $tl;?>ModuleCtrl" class="siteModCtrl" style="display:none"></div>
  	  <div id="<?php echo $tl;?>ModuleNav" class="siteModNav" style="display:none"></div>
  	  <div id="<?php echo $tl;?>ModuleFooter" class="siteModFooter" style="display:none"></div>
    </div>
	</div>

</td><td class="siteCenterColumn" valign="top">

  <!-- for displaying the actual web pages -->
  <div class="siteContainer">

    <?php if ($siteMessage) echo $siteMessage;?>
    <?php echo $siteContent;?>
  
  </div>

</td></tr></table>

</body>
</html>
