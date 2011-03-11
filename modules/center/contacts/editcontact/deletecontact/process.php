<?php

$contactId = $_REQUEST["contactId"];

if (!$contactId) {
  $moduleError = "No contact id specified for deletion";
  return false;
}

if (!is_array($contactId)) $contactId = array($contactId);

//see if this user has permissions to edit the contact
if (!checkEditContact($contactId)) 
{
  $moduleError = "You do not have permissions to edit this contact";
  return false;
}
    

if ($_REQUEST["delete"]) {
 
  $DB->begin(); 

  foreach ($contactId AS $contact) {

    $sql = "DELETE FROM addressbook.contact WHERE id='$contact';";
    $sql .= "DELETE FROM addressbook.contact_account WHERE contact_id='$contact';";
    $DB->query($sql);
  
  }

  $DB->end();
    
  $moduleError = $DB->error();

}
