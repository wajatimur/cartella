<?php

$xml = createXmlHeader("contactsuggest");
$xml .= xmlEntry("count",$searchResults["count"]);
$xml .= xmlEntry("offset","$offset");
$xml .= xmlEntry("limit","$limit");

if ($searchResults["count"] > 0) {

	//preload statuses for our contacts
	if ($addressbook!="account" && $addressbook!="newagent") {

		//create a contactarray of matches for our queries
		//$contactArr = array();
		//for ($i=0;$i<$searchResults["count"];$i++) $contactArr[] = $searchResults[$i]["contact_id"];
	
  }
                 
	//loop through the results
	for ($i=0;$i<$searchResults["count"];$i++) {
	
	  $entry = &$searchResults[$i];

	  //convert fields returned from ldap into something matching our contact database structure
	  if ($addressbook=="account" || $addressbook=="newagent") $entry = convertLDAPData($entry);
	  //else $entry["status"] = getBulkContactStatus($contactArr,$entry["id"]);
	  
	  //remove any password data
		$entry["plainPassword"] = null;
		$entry["sambaNTPassword"] = null;
		$entry["sambaLMPassword"] = null;
		$entry["userPassword"] = null;

	  if ($entry["work_ext"]) $entry["work_ext"] = ereg_replace("[^0-9]","",$entry["work_ext"]);
	  if ($entry["work_phone"]) $entry["work_phone"] = phoneView($entry["work_phone"]);
	  if ($entry["work_fax"]) $entry["work_fax"] = phoneView($entry["work_fax"]);
	  if ($entry["home_phone"]) $entry["home_phone"] = phoneView($entry["home_phone"]);
	  if ($entry["mobile"]) $entry["mobile"] = phoneView($entry["mobile"]);

	  //some ewp ldap specific data
	  if ($entry["mobilePhone"]) $entry["mobilePhone"] = phoneView($entry["mobilePhone"]);
	  if ($entry["faxPhone"]) $entry["faxPhone"] = phoneView($entry["faxPhone"]);
	  if ($entry["directPhone"]) $entry["directPhone"] = phoneView($entry["directPhone"]);

	  //convert our table data into an xml string
	  $xml .= "<contact>\n";
	  $xml .= tableToXml($entry);
	  $xml .= "</contact>\n";
	  
	}
	
}
	
$xml .= createXmlFooter();
die($xml);

