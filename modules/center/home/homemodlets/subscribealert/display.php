<?php

$content = "<div id=\"objAlertList\">Loading Alerts...</div>";

$opt = null;
$opt["header"] = $info["name"];
$opt["module"] = $module;
$opt["content"] = $content;
$opt["pageload"] = "loadObjAlerts();";
$str = createModlet($opt);

//we stop here to avoid overhead of extra processing
die($str);

?>