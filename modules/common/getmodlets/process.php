<?php

if (!$_REQUEST["showModule"]) die;

//get all modlets available for this showModule
$modletArr = loadModletList($_REQUEST["showModule"]);

//get those currently being displayed
$modlets = getModletLayout($conn,$_REQUEST["showModule"],$_REQUEST["groupId"]);
$modcol1 = &$modlets["column1"];
$modcol2 = &$modlets["column2"];

