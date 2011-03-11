<?php

/****************************************************************
  get current prospects assigned to the contact
****************************************************************/

$contactId = $_REQUEST["contactId"];

//see if this user has permissions to edit the contact
if ($contactId && !canEditContact($contactId)) {
  $moduleError = "You do not have permissions to edit this contact";
  return false;
}
    
//teams are comma-delimited, so just convert all to an array and loop through
$accountId = @explode(",",$_REQUEST["accountId"]);

//get all accounts matching the passed filter
if ($_REQUEST["action"]=="removeaccount") {

  $sql = "DELETE FROM addressbook.contact_account WHERE contact_id='$contactId' AND account_id IN (".implode(",",$accountId).")";
  $DB->query($sql);
  $moduleError = $c->getError();
  
}
//get all accounts matching the passed filter
else if ($_REQUEST["action"]=="addaccount") {

  $sql = "DELETE FROM addressbook.contact_account WHERE contact_id='$contactId' AND account_id IN (".implode(",",$accountId).")";
  $DB->query($sql);

  for ($i=0;$i<count($accountId);$i++) {
  
    $opt = null;
    $opt["contact_id"] = $contactId;
    $opt["account_id"] = $accountId[$i];
    $opt["account_name"] = addslashes(returnAccountName($accountId[$i]));
    $DB->insert("addressbook.contact_account",$opt);
  
  }

  $moduleError = $c->getError();

}

//get our updated memberlist
$sql = "SELECT * FROM addressbook.contact_account WHERE contact_id='$contactId'";
$accountList = $DB->fetch($sql);
