<?php

$content = "<div id=\"bkList\">Loading Bookmarks...</div>";

$opt = null;
$opt["header"] = $info["name"];
$opt["module"] = $module;
$opt["content"] = $content;
$opt["pageload"] = "loadBookmarks();";
$str = createModlet($opt);

//we stop here to avoid overhead of extra processing
die($str);

?>