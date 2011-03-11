<?php

function mergeCheckboxData($prospectId,$arr) {

  $field = $arr["data"];
  
  //get all ids selected by this prospect from the table
  $sql = "SELECT $field FROM ".$arr["save_table"]." WHERE prospect_id='$prospectId'";
  $list = total_result($GLOBALS["conn"],$sql);

  return @implode(",",$list[$field]);

}

function mergePriceRangeData($prospectId) {

  //get all ids selected by this prospect from the table
  $sql = "SELECT * FROM prospect.price_range_link WHERE prospect_id='$prospectId'";
  $info = single_result($GLOBALS["conn"],$sql);

  if ($info) return $info["price_min"].",".$info["price_max"];

}


function mergeAgeData($prospectId,$arr) {

  //get all ids selected by this prospect from the table
  $sql = "SELECT * FROM ".$arr["save_table"]." WHERE prospect_id='$prospectId'";
  $list = total_result($GLOBALS["conn"],$sql);

  //they should look like (id1:number1,id2:number2,id3:number3)
  $arr = array();
  for ($i=0;$i<$list["count"];$i++) {
    $arr[] = $list["option_id"][$i].":".$list["number"][$i];
  }

  return @implode(",",$arr);

}

