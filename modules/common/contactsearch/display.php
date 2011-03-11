<?php

//loop through the results
for ($i=0;$i<$searchResults["count"];$i++) 
{

  $entry = &$searchResults[$i];

  //reformat any fields as necessary
  $entry["home_phone"] = phoneView($entry["home_phone"]);
  $entry["work_phone"] = phoneView($entry["work_phone"]);
  $entry["work_fax"] = phoneView($entry["work_fax"]);
  $entry["mobile"] = phoneView($entry["mobile"]);

  //convert our table data into an xml string
  $PROTO->add("contact",$entry);

}

$PROTO->output();

