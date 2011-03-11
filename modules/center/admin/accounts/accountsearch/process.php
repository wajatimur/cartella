<?php

$searchString = $_REQUEST["searchString"];
if (!$searchString && !$_REQUEST["showAll"]) return false;

//setup our search string
$filter = array();
$filter["login"] = $searchString;
$filter["name"] = $searchString;

$l = new ACCOUNT();
$searchResults = $l->search($filter);

