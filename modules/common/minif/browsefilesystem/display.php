<?php

$PROTO->add("path",$path);
if ($moduleError) $PROTO->add("error",$moduleError);

//if only directory markers, bail
for ($i=0;$i<count($entryArr);$i++) {

  $e = $entryArr[$i];
  if ($e=="." || $e=="..") continue;
 
  $fullpath = $path."/".$e;

  $arr = array();
  $arr["name"] = $e;
  $arr["path"] = $fullpath;
  
  if (is_dir($fullpath)) $arr["type"] = "directory";
  else $arr["type"] = "file";
  
  $PROTO->add("entry",$arr);

}

$PROTO->output();
