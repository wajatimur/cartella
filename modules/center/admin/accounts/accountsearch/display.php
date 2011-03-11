<?php

unset($searchResults["count"]);

for ($i=0;$i<count($searchResults);$i++) 
{

  $PROTO->add("account",$searchResults[$i]);

}

$PROTO->output();
