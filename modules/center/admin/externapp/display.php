<?php

$content = "

<div class=\"leftColumn\">
	<div class=\"pageHeader\">
	"._PDF_SUPPORT."
	</div>
	".pdfSupport()."
	<br><br>
	<div class=\"pageHeader\">
	"._IMAGE_TIFF_SUPPORT."
	</div>
	".imageSupport()."
</div>
<div class=\"rightColumn\">
	<div class=\"pageHeader\">
	"._MISC_SUPPORT."
	</div>
	".miscSupport()."
</div>
";

$option = null;
$option["hideHeader"] = 1;
$option["content"] = $content;

$siteContent .= sectionDisplay($option);

?>


