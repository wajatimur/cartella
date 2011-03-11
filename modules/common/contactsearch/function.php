<?php

//return an array of contact ids with a matching name
function getNameQuery($fn,$ln) {

  $fn = strtolower($fn);
  $ln = strtolower($ln);

  if ($ln && !$fn)
  {
  
    $sql = " lower(last_name) LIKE '$ln%' OR lower(first_name) LIKE '$ln%' OR lower(email) LIKE '$ln%' ";
  
  } else {
  
    $sql = " lower(first_name) LIKE '$fn%' AND lower(last_name) LIKE '$ln%' ";
  
  }

  //return the query
  return $sql;

}
