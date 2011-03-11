<?php

/***************************************************************
  parameters to use this module
  
  if quickSearch passed, then only searches names
  if runSearch passed, can run multiple filters
  can limit returns using offset and limit
  
***************************************************************/

$sql = null;

if ($_REQUEST["sortField"]) $sortfield = $_REQUEST["sortField"];
else $sortfield = "name";

if ($_REQUEST["sortDir"]) $sortdir = $_REQUEST["sortDir"];
else $sortdir = "ASC";


  if ($_REQUEST["searchString"]) {
    $arr = organizeName($_REQUEST["searchString"]);
    $firstName = $arr["fn"];
    $lastName = $arr["ln"];
  }

  if ($firstName || $lastName) $namesql = getNameQuery($firstName,$lastName);
  
  $sql = "SELECT * FROM addressbook.view_contact WHERE account_id='".USER_ID."'";
  if ($namesql) $sql .= " AND ".$namesql;

  //our sort direction
  if ($_REQUEST["sortDir"]=="DESC") $sortdir = "DESC";
  else $sortdir = "ASC";

  if ($_REQUEST["sortField"]) $sortfield = $_REQUEST["sortField"];
  else $sortfield = "name";
  
  $sql .= " ORDER BY lower(last_name) ".$sortdir.",lower(first_name) ".$sortdir;

$searchResults = $DB->fetch($sql);
   