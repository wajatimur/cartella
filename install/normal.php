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
<!-- Mimic Internet Explorer 7 -->
<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7" />
<title>
<?php
if ($siteTitle) echo $siteTitle;
else echo $siteModInfo[$module]["module_name"]." - ".SITE_TITLE;
?>
</title>

<meta http-equiv="Content-Type" content="text/html; charset=utf-8">

<?php
/****************************************************
	our stylesheets and javascript files
****************************************************/
$css = THEME_PATH."/css/core.css;";
$css .= THEME_PATH."/css/toolbar.css;";
if ($modStylesheet) $css .= $modStylesheet;
if ($modCss) $css .= $modCss;
includeStylesheet($css);

$js .= "jslib/core.js;";

if ($modJs) $js .= $modJs;
includeJavascript($js);
$maintbclass = "siteMainToolbar";

?>

</head>

<body>

<!-- template=normal -->

<div class="siteLeftColumnBack">&nbsp;</div>

<table class="siteMainContainer" border="0" cellpadding="0" cellspacing="0" width="100%">

<tr><td class="siteLeftColumn" valign="top">
  <div class="siteTitlePic"><img alt="" src="<?php echo THEME_PATH;?>/images/docmgr_logo.png" /></div>
</td>
<td class="siteCenterColumn" valign="top">

  <div class="siteMainToolbar" style="height:22px">&nbsp;</div>

  <!-- for displaying the actual web pages -->
  <div class="siteContainer">

    <?php echo $siteContent;?>
  
  </div>


</td></tr>
</table>


</body>
</html>
