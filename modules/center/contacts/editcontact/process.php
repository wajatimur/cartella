<?php

$contactId = $_REQUEST["contactId"];

if ($contactId && 1==2) {

  if (!checkEditContact($contactId)) {
    die("You do not have permissions to view this contact");
  }
  
}
