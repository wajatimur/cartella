<?php

//return an array of contact ids with a matching name
function runAddrQuery($fn,$ln) {

  global $DB;
  
  $fn = strtolower($fn);
  $ln = strtolower($ln);

  /**********************************************************************************
    
    search address book for this community

  **********************************************************************************/

  $fn = strtolower($fn);
  $ln = strtolower($ln);

  if ($ln && !$fn)
  {	

    $parms = " lower(last_name) LIKE '$ln%' OR lower(first_name) LIKE '$ln%' OR lower(email) LIKE '$ln%' ";

  } else 
  {

    $parms = " lower(first_name) LIKE '$fn%' AND lower(last_name) LIKE '$ln%' ";

  }
                

  //we were passed contacts, run them first.  Running it this way will put an account's matches
  //before the public ones in teh result list  
  $sql = "SELECT DISTINCT id AS contact_id,first_name,last_name,email FROM addressbook.contact
              LEFT JOIN addressbook.contact_account ON contact.id=contact_account.contact_id
              WHERE 
              account_id='".USER_ID."' AND ".$parms." AND
              email IS NOT NULL ORDER BY first_name,last_name";
  //run the query
  return $DB->fetch($sql);
    
}



//return an array of contact ids with a matching name
function runLDAPQuery($fn,$ln) {

  if (!defined("USE_LDAP")) return false;

  $fn = strtolower($fn);
  $ln = strtolower($ln);

  //setup a filter
  if ($fn || $ln) {
  
    $filter = "(|";
    if ($fn && $ln) {
      $filter .= "(&(givenName=".$fn."*)(sn=".$ln."*))";
    } else if ($ln && !$fn) {
      $filter .= "(givenName=".$ln."*)(sn=".$ln."*)";
    } else {
      if ($fn) $filter .= "(givenName=".$fn."*)";
      if ($ln) $filter .= "(sn=".$ln."*)";
    }
    $filter .= "(uid=".$ln."*)";
    $filter .= "(mail=".$ln."*)";

    $filter .= ")";

  } else $filter = null;

  $filter = "(&(mail=*)".$filter.")";
  
  $opt = null;
  $l = new LDAP();
  $res = $l->getList($filter,"cn");
  $res["count"] = count($res);

  return $res;

}

/*************************************************************************
  FUNCTION: convertLDAPData
  PURPOSE:	converts data in fields from returnAccountInfo() function
            to match fields returned from other contactsuggest queries
  INPUT:		entry -> assoc array results from runLDAPQuery, one row at at ime
*************************************************************************/
function convertLDAPData($entry) {

  //remove any password data
  $entry["plainPassword"] = null;  
  $entry["sambaNTPassword"] = null;
  $entry["sambaLMPassword"] = null;
  $entry["userPassword"] = null;

  $entry["contact_id"] = $entry["id"];
  
  return $entry;
                      
}

function runBothQuery($fn,$ln,$limit) {

  if (defined("USE_LDAP"))
  {

    $arr1 = runAddrQuery($fn,$ln);
    $arr2 = runLDAPQuery($fn,$ln);
    $res = array_merge($arr1,$arr2);

  } else 
  {
  
    $res = runAddrQuery($fn,$ln);
  
  }
  
  if ($limit)	$res = array_slice($res,0,$limit);

  $res = arrayMSort($res,"first_name");
  
  $res["count"] = count($res);
  
  return $res;
  
}
