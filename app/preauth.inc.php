<?php

include("app/common.php");
include("app/common-docmgr.php");
include("app/openoffice.php");
include("app/modlets.php");
include("app/task.php");
include("app/client.php");

//make sure magic quotes is not turned on
if (get_magic_quotes_gpc()==1)
{
  die("You must turn of magic quotes in your php.ini file");
}

//setup which apps are available to docmgr
setExternalApps();

//register our autoloader so we can call
//api functions direct from the client
spl_autoload_register('client_autoload');
