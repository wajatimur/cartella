<?php

$l = &$_SESSION["login"];
$time = time();

//we need the time they've logged in, time until expiration
$inactivity = $time - $l["last"];
$timeleft = (SESSION_TIMEOUT * 60) - $inactivity;

$PROTO->add("inactive",$inactivity);
$PROTO->add("time_left",$timeleft);
$PROTO->output();

