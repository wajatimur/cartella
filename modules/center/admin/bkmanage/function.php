<?php

function createAccountSelect() {

  $l = new ACCOUNT();
  $arr = $l->getList();
  $arr["count"] = count($arr);
  
  $aaSelect = "<select name=\"account_id\" id=\"account_id\" size=\"1\" onChange=\"selectAccount()\">
              <option value=\"0\">Select account to manage\n";

  for ($i=0;$i<$arr["count"];$i++) {

    if (!$arr[$i]["id"]) continue;

    if ($aid==$arr[$i]["id"]) $sel = " SELECTED ";
    else $sel = null;

    $aaSelect .= "<option ".$sel." value=\"".$arr[$i]["id"]."\">".$arr[$i]["login"]."\n";
  }

  $aaSelect .= "</select>\n";

  return $aaSelect;

}