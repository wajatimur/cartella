<?php

if ($moduleError) $PROTO->add("error",$moduleError);
$PROTO->add("contact_id",$contactId);

//in case it's requested to return info
if ($contactInfo) 
{

  //reformat some fields
  if ($contactInfo["work_phone"]) $contactInfo["work_phone"] = phoneView($contactInfo["work_phone"]);
  if ($contactInfo["work_fax"]) $contactInfo["work_fax"] = phoneView($contactInfo["work_fax"]);
  if ($contactInfo["home_phone"]) $contactInfo["home_phone"] = phoneView($contactInfo["home_phone"]);
  if ($contactInfo["mobile"]) $contactInfo["mobile"] = phoneView($contactInfo["mobile"]);

  //an extra field for some modules who may be looking for something different for id
  $contactInfo["contact_id"] = $contactInfo["id"];

  $PROTO->add("contact",$contactInfo);

}

$PROTO->output();
