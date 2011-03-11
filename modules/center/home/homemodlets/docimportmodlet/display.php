<?php

$content = "

<table width=\"100%\" border=\"0\">
<tr><td width=\"50%\" align=\"center\" class=\"pendingCell\">
    <div class=\"pendingHeader\"><a href=\"javascript:pendingBrowse('home')\">Personal directory</a></div>
    <div id=\"personalList\"><a href=\"javascript:pendingBrowse('home');\">".$localnum."</a></div>
</td><td align=\"center\" class=\"pendingCell\">
    <div class=\"pendingHeader\"><a href=\"javascript:pendingBrowse('shared')\">Shared directory</a></div>
    <div id=\"sharedList\"><a href=\"javascript:pendingBrowse('shared')\">".$sharednum."</a></div>
</td></tr></table>
";
    
$opt = null;
$opt["module"] = $module;
$opt["content"] = $content;
$opt["rightheader"] = $edit;
$str = createModlet($opt);

//we stop here to avoid overhead of extra processing
die($str);

