<?php

$action = $_POST["action"];
$accountId = $_POST["accountId"];

if (!$accountId && $action!="createuser") $accountId = USER_ID;

$AS = new ACCOUNTSAVE($accountId);

//look for permissions errors
if ($AS->getError()) {
  $moduleError = $AS->getError();
  return false;
}

switch ($action) {

  case "saveprofile":
    $AS->saveProfile();
    break;  

  case "createuser":
    $AS->createUser();
    break;  

  case "savepassword":
    $AS->savePassword();
    break;  

  case "savedocmgrsetting":
    $AS->saveDocmgrSetting();
    break;  

  case "savegroup":
    $AS->saveGroup();
    break;  

  case "savepermission":
    $AS->savePermission();
    break;  

  case "deleteaccount":
    $AS->deleteAccount();
    break;
    
  default:
    $moduleError = "Unrecognized command";
    break;

}


if ($action && !$moduleError) {
  $moduleError = $AS->getError();
}