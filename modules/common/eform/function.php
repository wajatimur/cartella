<?php

function runLdapQuery($title_field,$data_field,$filter,$sort=null) {

  if (!defined("USE_LDAP")) return false;

  //connect to the server
  $ds=ldap_connect(LDAP_SERVER,LDAP_PORT);
  ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, LDAP_PROTOCOL);
  $search_base = LDAP_BASE;

  if ($ds) {

    //log in and run our query
    $r = ldap_bind($ds,BIND_DN,BIND_PASSWORD);
    $sr = ldap_search($ds,$search_base,"$filter");
    if ($sort) ldap_sort($ds,$sr,$sort);
    $results = ldap_get_entries($ds,$sr);
      
    //create a proper array w/ our title/value values in it
    $list = array();
    $list["count"] = $results["count"];
      
    for ($i=0;$i<$results["count"];$i++) {
      $list[$i][$title_field] = $results[$i][strtolower($title_field)][0];
      $list[$i][$data_field] = $results[$i][strtolower($data_field)][0];
    }

    return $list;

  }

}

