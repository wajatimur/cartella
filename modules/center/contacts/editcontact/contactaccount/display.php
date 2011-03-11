<?php

if ($moduleError) $PROTO->add("error",$moduleError);

for ($i=0;$i<$accountList["count"];$i++) 
{

    $arr = array();
    $arr["id"] = $accountList[$i]["account_id"];
    $arr["name"] = $accountList[$i]["account_name"];
    $PROTO->add("account",$arr);

}

$PROTO->output();
