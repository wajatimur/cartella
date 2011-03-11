<?php

/***************************************************************
  MODULE:	contactsuggest
  PURPOSE:	for running quick searches on specific address
            books to find matching names.
  PARAMETERS:	firstName -> contact first name
              lastName -> contact last name
              addressbook -> addr book to search in
                             local is user's address book
                             ldap is our ldap book
                             mls is the central mls database
              limit (optional) -> number of matches to show
              offset (optional) -> match offset
**************************************************************/

//default our limit to 20
$limit = 20;
$offset = 0;
$addressbook = "local";
$contactType = null;
$showAll = null;

if ($_REQUEST["searchString"]) {
  $arr = organizeName($_REQUEST["searchString"]);
  $firstName = $arr["fn"];
  $lastName = $arr["ln"];
} 
if ($_REQUEST["firstName"]) $firstName = $_REQUEST["firstName"];
if ($_REQUEST["lastName"]) $lastName = $_REQUEST["lastName"];

if ($_REQUEST["addressbook"]=="account") 		$searchResults = runLDAPQuery($firstName,$lastName);
else if ($_REQUEST["addressbook"]=="both") 	$searchResults = runBothQuery($firstName,$lastName,$_REQUEST["limit"]);
else $searchResults = runAddrQuery($firstName,$lastName);


