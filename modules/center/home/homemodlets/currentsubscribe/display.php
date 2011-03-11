<?php

$content = "<div id=\"objSubList\">Loading Subscriptions...</div>";

$opt = null;
$opt["header"] = $info["name"];
$opt["module"] = $module;
$opt["content"] = $content;
$opt["pageload"] = "loadObjSub();";
$str = createModlet($opt);

//we stop here to avoid overhead of extra processing
die($str);

?>