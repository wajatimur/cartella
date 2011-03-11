<?php

//template pattern = array("value","enabled","comment","hidden from setup")

$template = array();

$template["Required"] = array();

//database
$template["Required"]["DBHOST"] = array("localhost",true,"Database Host");
$template["Required"]["DBUSER"] = array("postgres",true,"Database User");
$template["Required"]["DBPASSWORD"] = array("secret",true,"Database Password");
$template["Required"]["DBPORT"] = array("5432",true,"Database Port");
$template["Required"]["DBNAME"] = array("docmgr",true,"Database Name");

//admin email
$template["Required"]["ADMIN_EMAIL"] = array("admin@mydomain.com",true,"Admin email set as return address for system emails");

//site settings
$template["Required"]["SITE_URL"] = array("http://".str_replace("index.php","",$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"]),true,"Full site URL.  Must have trailing slash!");
$template["Required"]["SITE_PATH"] = array("/www/docmgr",true,"Full site path.  No trailing slash required");

//these are hidden by default
$template["Required"]["FILE_DIR"] = array("[[SITE_PATH]]/files",true,"Absolute path to DocMGR files directory",true);
$template["Required"]["TMP_DIR"] = array("[[FILE_DIR]]/tmp",true,"Path to DocMGR tmp folder",true);
$template["Required"]["DATA_DIR"] = array("[[FILE_DIR]]/data",true,"Path to DocMGR tmp folder",true);
$template["Required"]["THUMB_DIR"] = array("[[FILE_DIR]]/thumbnails",true,"Path to DocMGR thumbnail folder",true);
$template["Required"]["PREVIEW_DIR"] = array("[[FILE_DIR]]/preview",true,"Path to DocMGR preview folder",true);
$template["Required"]["DOC_DIR"] = array("[[FILE_DIR]]/document",true,"Path to DocMGR documents folder",true);
$template["Required"]["HOME_DIR"] = array("[[FILE_DIR]]/home",true,"Path to DocMGR home folder",true);
$template["Required"]["IMPORT_DIR"] = array("[[FILE_DIR]]/import",true,"Path to DocMGR Import Folder",true);

//ldap
$template["Required"]["USE_LDAP"] = array("1",false,"Enable LDAP for accounts",true);

/******************************************************************
	Indexing Options
******************************************************************/
$template["Indexing"] = array();

$template["Indexing"]["REGEXP_OPTION"] = array("-a-z0-9_",true,"Regular expression used to determine what characters to index");
$template["Indexing"]["INDEX_WORD_LIMIT"] = array("1000",false,"Limit index to this many words");

/*****************************************************************
        Permissions Settings
*****************************************************************/
$template["Permissions"] = array();

$template["Permissions"]["USE_COOKIES"] = array("1",true,"Allow automated logins with cookies");
$template["Permissions"]["FILE_REVISION_REMOVE"] = array("yes",true,"Allow removal of past file revisions");
$template["Permissions"]["DOC_REVISION_REMOVE"] = array("yes",true,"Allow removal of past document revisions");

/*****************************************************************
	Optional Settings
*****************************************************************/

$template["Optional"]["DEFAULT_LANG"] = array("eng",true,"Default language for users (not used currently)",true);
$template["Optional"]["RESULTS_PER_PAGE"] = array("10",true,"Default search results per page");
$template["Optional"]["PAGE_RESULT_LIMIT"] = array("20",true,"Number of pages of results to show at once");
$template["Optional"]["BROWSE_PAGINATE"] = array("1",false,"Paginate results of browse mode",true);
$template["Optional"]["EXECUTION_TIME"] = array("60",true,"Max number of seconds of processing per page per file");
$template["Optional"]["DATE_FORMAT"] = array("mm/dd/yyyy",true,"Date format for entering and viewing dates (either mm/dd/yyyy or dd/mm/yyyy)");
$template["Optional"]["FILE_REVISION_LIMIT"] = array("0",true,"Number of file histories to keep.  O for unlimited");
$template["Optional"]["DOC_REVISION_LIMIT"] = array("0",true,"Number of document histories to keep.  O for unlimited");
$template["Optional"]["SEND_MD5_CHECKSUM"] = array("1",false,"Send md5 checksum file w/ all email attachments");
$template["Optional"]["BYPASS_MD5CHECK"] = array("1",false,"Allow file to be viewed even md5 check fails (after warning displayed)");
$template["Optional"]["DSOFRAMER_ENABLE"] = array("1",true,"Turn on DSO Framer editor (IE Only)");
$template["Optional"]["USE_TRASH"] = array("1",true,"Use trash can instead of direct delete");
$template["Optional"]["TSEARCH2_PROFILE"] = array("english",true,"Tsearch2 profile to use for indexing");
$template["Optional"]["ROOT_NAME"] = array("Root Level",true,"Name for the top level bookmark");
$template["Optional"]["BROWSE_GROUPBY"] = array("object_type",false,"group browse results by object type");
$template["Optional"]["DEFAULT_MOD"] = array("home",true,"Change default module to display after login");
$template["Optional"]["SITE_THEME"] = array("default",true,"Default theme for DocMGR");
$template["Optional"]["DMEDITOR_DEFAULT_SAVE"] = array("docmgr",false,"Default file type for DocMGR's built-in editor to save as.  Options are 'docmgr','odt','doc'... or whatever you set allow_dmsave tag to in extensions.xml file");

/*************************************************************************
        Security Options
*************************************************************************/

$template["Security"] = array();

$template["Security"]["WARNING_BANNER"] = array("Warning!!!!",false,"Login banner displayed on login page");
$template["Security"]["ENABLE_ACCOUNT_LOCKOUT"] = array("1",true,"Enable account lockout feature - affects all users but admins");
$template["Security"]["ACCOUNT_LOCKOUT_TIME"] = array("5",true,"Number of minutes to lock out account. 0 = forever");
$template["Security"]["ACCOUNT_LOCKOUT_ATTEMPTS"] = array("5",true,"Number of failed login attempts for an account is locked");
$template["Security"]["SESSION_TIMEOUT"] = array("20",false,"Number of minutes before a session is timed out");
$template["Security"]["SECURE_COOKIES"] = array("1",false,"Select whether cookies should only be sent over secure connections");
$template["Security"]["DISALLOW_CHARS"] = array("\"/*",true,"Characters we disallow in a filename");
$template["Security"]["RESTRICTED_DELETE"] = array("1",false,"Set this if nobody can delete objects except administrators");

/******************************************************************
  Don't change
******************************************************************/
$template["Unchangeable"] = array();
$template["Unchangeable"]["hidden"] = 1;

$template["Unchangeable"]["DOCMGR_KEEPALIVE"] = array("50000",true,"docmgr keepalive check (five minutes)");
$template["Unchangeable"]["DOCMGR_API"] = array("api.php",true,"url to docmgr api");
$template["Unchangeable"]["LOGGER_MODE"] = array("db",true,"Logger mode");
$template["Unchangeable"]["DIGEST_REALM"] = array("SabreDAV",true,"used for digest authentication on webdav");
$template["Unchangeable"]["PROTO_DEFAULT"] = array("JSON",true,"Our proto transfer protocol");
$template["Unchangeable"]["DOCMGR_URL"] = array("[[SITE_URL]]",true,null);
$template["Unchangeable"]["_AT"] = array("at",true,null);
$template["Unchangeable"]["PERM_BITLEN"] = array("32",true,"length of our permissions bitmask");
$template["Unchangeable"]["PROCESS_AUTH"] = array("1",true,"Process authentications on this site");
$template["Unchangeable"]["DEV_MODE"] = array("1",false,"Reload modules every time for development");
$template["Unchangeable"]["DEBUG"] = array("5",false,"Debugging level");
$template["Unchangeable"]["LEVEL1_NUM"] = array("16",true,"Top directory level (DO NOT CHANGE)",true);
$template["Unchangeable"]["LEVEL2_NUM"] = array("256",true,"Second directory level (DO NOT CHANGE)",true);

//just written directly to the config file
$template["Suffix"] = "

//set error reporting to not show notices
error_reporting(E_ALL ^ E_NOTICE);

//turn on error reporting
ini_set(\"display_error\",\"1\");

\$exemptRequest = array();
\$exemptRequest[] = \"editorContent\";
\$exemptRequest[] = \"editor_content\";
\$exemptRequest[] = \"apidata\";
\$exemptRequest[] = \"apiparm\";
\$exemptRequest[] = \"to\"; 
\$exemptRequest[] = \"from\"; 
\$exemptRequest[] = \"cc\";
\$exemptRequest[] = \"bcc\";

include(\"config-custom.php\");

";
