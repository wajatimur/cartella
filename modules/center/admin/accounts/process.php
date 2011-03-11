<?php

$accountId = $_REQUEST["accountId"];

if (!$accountId) $accountId = USER_ID;

$l = new ACCOUNT($accountId);
$accountInfo = $l->getInfo();

