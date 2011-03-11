<?php

//files common to this app
require_once("app/common.php");
require_once("app/common-docmgr.php");
require_once("app/openoffice.php");
require_once("app/task.php");

//docmgr only libs
require_once("common.php");

//set the execution time for uploading and file processing
if (defined("EXECUTION_TIME")) ini_set("max_execution_time",EXECUTION_TIME);

//setup which apps are available to docmgr
setExternalApps();


