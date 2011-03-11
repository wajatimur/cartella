<?php
/*****************************************************************************************************

	noheader.inc.php
	
	This file displays the actual site layout, but does not display the logo, toolbars,
	left, or right columns.  This is useful for simple popup windows.  It is called
	if the $hideHeader variable is set

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

<META http-equiv="Content-Type" content="text/html; charset=utf-8">
<!-- Mimic Internet Explorer 7 -->
<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7" />

<?php
/****************************************************
 our stylesheets and javascript files
****************************************************/
includeStylesheet(THEME_PATH."/css/core.css");    
includeStylesheet(THEME_PATH."/css/tabs.css");    
includeStylesheet(THEME_PATH."/css/sitemenu.css");
includeStylesheet(THEME_PATH."/css/modal.css");    
if ($modStylesheet) includeStylesheet($modStylesheet);  //module stylesheet
if ($modCss) includeStylesheet($modCss);        //module stylesheet that lives in the theme modcss directory

//our globals
include("javascript/globals.php");
$js .= "jslib/core.js;";
$js .= "jslib/mootools-core.js;";
$js .= "jslib/mootools-more.js;";
$js .= "jslib/xml.js;";
$js .= "jslib/query.js;";
$js .= "jslib/proto.js;";
$js .= "jslib/string.js;";
$js .= "javascript/common.js;";

if ($modJs) includeJavascript($modJs);  //module javascript

if ($onPageLoad) $onPageLoadStr = "onLoad=\"".$onPageLoad."\"";

?>

</head>

<body leftmargin=0 topmargin=0 rightmargin=0>
<!-- template=modal -->
<div class="siteBody">

	<div class="siteCenterColumnNoHeader">
	<?php if ($siteMessage) echo $siteMessage."<br>";?>
	<?php echo $siteContent;?>
	</div>

</div>
</body>
</html>
