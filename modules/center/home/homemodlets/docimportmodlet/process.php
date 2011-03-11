<?php

$shared = IMPORT_DIR;
$local = SITE_PATH."/files/home/".USER_LOGIN;

//sanity checking  
if (!is_dir($shared)) recurmkdir($shared);
if (!is_dir($local)) recurmkdir($local);

$localarr = scandir($local);
$sharedarr = scandir($shared);

$localnum = count($localarr);
$sharednum = count($sharedarr);

for ($i=0;$i<count($localarr);$i++) {

  if ($localarr[$i][0]=="." || is_dir($local."/".$localarr[$i])) $localnum--;

}

for ($i=0;$i<count($sharedarr);$i++) {

  if ($sharedarr[$i][0]=="." || is_dir($shared."/".$sharedarr[$i])) $sharednum--;

}

