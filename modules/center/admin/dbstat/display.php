<?php

$arr = db_statistics($conn);

$content = "

	<table width=95% cellpadding=10 border=0>
	<tr><td valign=top width=50%>
		<div class=\"formHeader\">
		"._FILESYSTEM.":
		</div>
		<b>".$arr["fileSize"]."</b>
		<br><br>
		<div class=\"formHeader\">
		"._NUMBER_FILES.":
		</div>
		<b>".$arr["fileNum"]."</b>
		<br><br>
		<div class=\"formHeader\">
		Number of DocMGR Documents:
		</div>
		<b>".$arr["docNum"]."</b>
		<br><br>
	</td><td valign=top width=50%>
		<div class=\"formHeader\">
		"._NUMBER_USERS.":
		</div>
		<b>".$arr["usersNum"]."</b>
		<br><br>
		<div class=\"formHeader\">
		"._NUMBER_COLLECTIONS.":
		</div>
		<b>".$arr["catNum"]."</b>
		<br><br>
		<div class=\"formHeader\">
		Number of URLs:
		</div>
		<b>".$arr["urlNum"]."</b>
		<br><br>
	</td></tr>
	</table>

	";


$option = null;
$option["leftHeader"] = _MT_DBSTAT;
$option["content"] = $content;

$siteContent .= sectionDisplay($option);

?>


