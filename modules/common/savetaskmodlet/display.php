<?php

if ($moduleError) $PROTO->add("error",$moduleError);

for ($i=0;$i<$calList["count"];$i++) 
{

  $PROTO->add("calendar",$calList[$i]);
  
}

$PROTO->output();


