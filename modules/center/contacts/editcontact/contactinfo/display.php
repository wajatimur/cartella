<?php

//show the module error if we have one
if ($moduleError) $PROTO->add("error",$moduleError);
else 
{

  //reformat a few
  $contactInfo["home_phone"] = phoneView($contactInfo["home_phone"]);
  $contactInfo["work_phone"] = phoneView($contactInfo["work_phone"]);
  $contactInfo["mobile"] = phoneView($contactInfo["mobile"]);
  $contactInfo["work_fax"] = phoneView($contactInfo["work_fax"]);

  //kick to the output queue
  $PROTO->add("contact",$contactInfo);

}

//show results
$PROTO->output();
