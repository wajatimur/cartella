<?php

//show links for all options here
$content = showModTable($siteModInfo["admin"]["module_path"],"module_name");

$option = null;
$option["hideHeader"] = 1;
$option["content"] = $content;
$siteContent .= sectionDisplay($option);








