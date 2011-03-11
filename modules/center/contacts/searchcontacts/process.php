<?php

//reorganize a search string if passed
if ($_REQUEST["searchString"]) 
{
  $arr = organizeName($_REQUEST["searchString"]);
  $firstName = $arr["fn"];
  $lastName = $arr["ln"];
}
