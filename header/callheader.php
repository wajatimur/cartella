<?php

if (defined("ALT_FILE_PATH")) $calldir = ALT_FILE_PATH."/";
else $calldir = null;

//the rest of our includes with our base functions
require_once($calldir."lib/accperms.php");
require_once($calldir."lib/account/account.php");
require_once($calldir."lib/arrays.php");
require_once($calldir."lib/calc.php");
require_once($calldir."lib/customforms.php");
require_once($calldir."lib/data_formatting.php");
require_once($calldir."lib/email.php");
require_once($calldir."lib/filefunctions.php");
require_once($calldir."lib/misc.php");
require_once($calldir."lib/modules.php");
require_once($calldir."lib/perm.php");
require_once($calldir."lib/postgresql.php");
require_once($calldir."lib/pgsql.php");
require_once($calldir."lib/logger.php");
require_once($calldir."lib/presentsite.php");
require_once($calldir."lib/sanitize.php");
require_once($calldir."lib/xml.php");

require_once($calldir."lib/proto/proto.php");

