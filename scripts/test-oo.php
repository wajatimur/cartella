<?php

include("../config/config.php");
include("../config/app-config.php");

$cmd = OPENOFFICE_PATH."/program/python ../bin/DocumentConverter.py \"test.html\" \"test.pdf\"";
$res = `$cmd`;
echo $res."\n";
