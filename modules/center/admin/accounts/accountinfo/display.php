<?php

if ($moduleError) $PROTO->add("error",$moduleError);

if ($accountInfo) {

  if ($settingsInfo) $accountInfo = array_merge($accountInfo,$settingsInfo);

  $a = array();

  foreach ($accountInfo AS $key=>$val) {
  
    if ($key=="count") continue;
    if (is_numeric($key)) continue;
      
    if (is_array($val)) 
    {

      for ($i=0;$i<count($val);$i++) $a[$key] = $val[$i];

    }
    else $a[$key] = $val;
    
  }

  //groups
  $a["group_id"] = array();
  for ($i=0;$i<$groupList["count"];$i++) $a["group_id"][] = $groupList[$i]["groupid"];

  $a["bitset"] = $permInfo["bitset"];
  $a["bitmask"] = $permInfo["bitmask"];
  $a["enable"] = $permInfo["enable"];

  $PROTO->add("account",$a);
  
}

$PROTO->output();
