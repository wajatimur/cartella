<?php

function checkEditContact($contactId) 
{

  global $DB;

  if (!is_array($contactId)) $contactId = array($contactId);
  $ret = true; 

  foreach ($contactId AS $contact) 
  {

    $sql = "SELECT contact_id FROM addressbook.contact_account WHERE contact_id='$contact' AND account_id='".USER_ID."'";
    $info = $DB->single($sql);

    if (!$info) 
    {
      $ret = false;
      break;
    }

  }
 
 return $ret;
 
}
 