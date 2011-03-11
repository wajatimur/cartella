#!/usr/bin/php
<?php

die("Disabled\n");

include("../config/config.php");
include("../lib/pgsql.php");

$userId = $argv[1];

if (!$userId)
{

  echo "You must pass the user id of the account you want to add as an administrator\n";
  exit(0);


}


$DB = new POSTGRESQL(DBHOST,DBUSER,DBPASSWORD,DBPORT,DBNAME);

$sql = "INSERT INTO auth_accountperm (account_id,bitset,enable) VALUES ('$userId','1','t');";
$DB->query($sql);
