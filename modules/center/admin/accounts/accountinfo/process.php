<?php

$accountId = $_REQUEST["accountId"];
if (!$accountId) $accountId = USER_ID;

if ($accountId!=USER_ID && !bitset_compare(BITSET,MANAGE_USERS,ADMIN)) {
  $moduleError = "You do not have permission to view information for this account";
  return false;
}


$l = new ACCOUNT($accountId);
$accountInfo = $l->getInfo();

//get groups
$sql = "SELECT groupid FROM auth_grouplink WHERE accountid='$accountId'";
$groupList = $DB->fetch($sql);

//get permissions
$sql = "SELECT bitset,bitmask,enable FROM auth_accountperm WHERE account_id='$accountId'";
$permInfo = $DB->single($sql);

$sql = "SELECT * FROM auth_settings WHERE account_id='$accountId'";
$settingsInfo = $DB->single($sql);

