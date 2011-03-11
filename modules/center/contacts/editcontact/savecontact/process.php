<?php

$contactId = $_REQUEST["contactId"];
$pageAction = $_REQUEST["pageAction"];

//see if this user has permissions to edit the contact
if ($contactId && !canEditContact($contactId)) {
  $moduleError = "You do not have permissions to edit this contact";
  return false;
}

$contactId = $_REQUEST["contactId"];
$pageAction = $_REQUEST["pageAction"];

//save the contact
if ($pageAction=="save") {

  //get our field data
  $file = "config/forms/editcontact.xml";
  $formArr = getFieldInfo($file);
  $dateStamp = date("Y-m-d H:i:s");

  //get submitted form data
  $opt = simpleXmlQuery($formArr);

  $opt["work_phone"] = preg_replace("/[^0-9]/","",$opt["work_phone"]);
  $opt["home_phone"] = preg_replace("/[^0-9]/","",$opt["home_phone"]);
  $opt["work_fax"] = preg_replace("/[^0-9]/","",$opt["work_fax"]);
  $opt["mobile"] = preg_replace("/[^0-9]/","",$opt["mobile"]);

  $DB->begin();

  //add our additional info
  $opt["id"] = $contactId;
  $opt["last_modified"] = $dateStamp;

  //update if passed an id
  if ($contactId) {

    $opt["where"] = "id='$contactId'";
    $DB->update("addressbook.contact",$opt);

  } else {

    $contactId = $DB->insert("addressbook.contact",$opt,"id");

    $opt = null;
    $opt["contact_id"] = $contactId;
    $opt["account_id"] = USER_ID;
    $opt["account_name"] = USER_FN." ".USER_LN;
    $DB->insert("addressbook.contact_account",$opt);

  }

  $DB->end();

  $moduleError = $DB->error();

}
