<?php

$PROTO->add("count",count($dirlist));

for ($i=0;$i<count($dirlist);$i++) 
{
  $arr = array();
  $arr["name"] = $dirlist[$i];
  $PROTO->add("file",$arr);
}

$PROTO->output();
