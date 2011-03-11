<?php

/*******************************************************************
  NAME: contactinfo
  PURPOSE: returns info for a contactid in xml format
*******************************************************************/
    
$contactId = $_REQUEST["contactId"];

if (!$contactId) {
  $moduleError = "No contact specified";
}
else {

  $sql = "SELECT * FROM addressbook.contact_account WHERE contact_id='$contactId' AND account_id='".USER_ID."'";
  $accountInfo = $DB->single($sql);

  if ($accountInfo) {
  
    $sql = "SELECT * FROM addressbook.contact WHERE id='$contactId'";
    $contactInfo = $DB->single($sql);

  } else {
  
    $moduleError = "You do not have permission to view this contact";
    
  }
      
}
