<?php
/*****************************************************************************************************

	noheader.inc.php
	
	This file displays the actual site layout, but does not display the logo, toolbars,
	left, or right columns.  This is useful for simple popup windows.  It is called
	if the $hideHeader variable is set

*****************************************************************************************************/
?>
<html>
<head>
<title>
<?php
if ($siteTitle) echo $siteTitle;
else echo SITE_TITLE;
?>
</title>

<?php

/****************************************************
	our stylesheets and javascript files
****************************************************/
includeStylesheet(THEME_PATH."/css/core-dso.css");
if ($modCss) includeStylesheet($modCss);        //module stylesheet that lives in the theme modcss directory

//our globals
include("javascript/globals.php");

$js .= "jslib/core.js;";
$js .= "jslib/legacy.js;";
$js .= "jslib/string.js;";
$js .= "javascript/common.js;";

if ($modJs) $js .= $modJs;
includeJavascript($js);   

if ($onPageLoad) $onPageLoadStr = "onload=\"".$onPageLoad."\"";

if ($siteHeadStr) echo $siteHeadStr;

?>

</head>

<body <?php echo $onPageLoadStr;?>>
<!-- template=blank -->

<iframe style="display:none" name="hFrame" id="hFrame"></iframe>
<div id="sitePopupWin"></div> 
<div id="siteStatus">
  <img alt="" src="themes/default/images/loading.gif">
	<div id="siteStatusMessage"></div>
</div>
    
<div class="siteBody">
	<div class="siteCenterColumnNoHeader">
	<?php if ($siteMessage && !$hideMessage) echo $siteMessage."<br>";?>
	<?php echo $siteContent;?>
	</div>
</div>

</body>
</html>
