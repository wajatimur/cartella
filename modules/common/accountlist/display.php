<?php

$a = new ACCOUNT();
$arr = $a->getList();
$num = count($arr);

for ($i=0;$i<$num;$i++) 
{
  
    if (!$arr[$i]["id"]) continue;

    $output = array();
    $output["id"] = $arr[$i]["id"];
    $output["name"] = $arr[$i]["full_name"];
    $PROTO->add("account",$output);

}

$PROTO->output();
