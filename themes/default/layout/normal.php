<?php
/*****************************************************************************************************

	body.inc.php

	This file displays the site including all side columns and logos

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">

*****************************************************************************************************/
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
<title>
<?php
if ($siteTitle) echo $siteTitle;
else echo $siteModInfo[$module]["module_name"]." - ".SITE_TITLE;
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

  $onPageLoad = "loginDocmgr();setupModNav();createSiteSearch();runPageLoader();".$onPageLoad;
  if ($onPageLoad) $onPageLoadStr = "onload=\"".$onPageLoad."\"";

  if ($siteHeadStr) echo $siteHeadStr;

}

if (defined("TRAINER")) {
  $maintbclass = "siteMainToolbarTrain";
} else {
  $maintbclass = "siteMainToolbar";
}

?>

</head>

<body <?php echo $onPageLoadStr;?>>

<!-- template=normal -->
<div id="screenKiller"></div>
<div id="screenMessage"></div>
<iframe style="display:none" name="hFrame" id="hFrame" src=""></iframe>
<div id="sitePopupWin"></div>
<div id="siteStatus">
  <img alt="" src="themes/default/images/loading.gif">
  <div id="siteStatusMessage"></div>
</div>


<div class="siteLeftColumnBack">&nbsp;</div>

<table class="siteMainContainer" border="0" cellpadding="0" cellspacing="0" width="100%">

<tr><td class="siteLeftColumn" valign="top">
  <div class="siteTitlePic"><img alt="" src="<?php echo THEME_PATH;?>/images/docmgr_logo.png" /></div>
  <div class="siteNav"><?php echo $modTabs;?></div>
  <div><br><?php echo $leftColumnContent;?></div>
</td>
<td class="siteCenterColumn" valign="top">

  <!-- for our nav history and site search -->
  <div class="<?php echo $maintbclass;?>">

  	<div id="messageSiteSearch"></div>
  	<div id="siteNavHistory"><?php echo $navHistory;?></div>
  
  </div>

  <!-- for displaying the actual web pages -->
  <div class="siteContainer">

    <?php if ($siteMessage) echo $siteMessage;?>
    <?php echo $siteContent;?>
  
  </div>


</td></tr>
</table>


</body>
</html>
