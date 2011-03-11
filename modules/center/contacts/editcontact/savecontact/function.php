<?php

function canEditContact($contactId) {

  global $DB;
  
  $sql = "SELECT contact_id FROM addressbook.contact_account WHERE contact_id='$contactId' AND account_id='".USER_ID."'";
  $info = $DB->single($sql);
  
  if ($info) return true;
  else return false;
  
}
