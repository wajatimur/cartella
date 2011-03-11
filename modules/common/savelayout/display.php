<?php
//vary response depending on whether or not the query came out okay
if ($queryReturn=="error") $PROTO->add("error","1");
else $PROTO->add("success","1");

$PROTO->output();
