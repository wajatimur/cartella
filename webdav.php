<?php

/******************************************
  BEGIN CONFIGURABLE OPTIONS
******************************************/

//snag our docmgr options
include("config/config.php");

// Make sure this setting is turned on and reflect the root url for your WebDAV server.
// This can be for example the root / or a complete path to your server script.  you probably
//don't need to change this
$baseUri = '/';

/******************************************
  END CONFIGURABLE OPTIONS
******************************************/

//apiclient needs this
define("ALT_FILE_PATH",SITE_PATH);

//docmgr classes need this
define("BASE_URI",$baseUri);

//sabre needs this
set_include_path(SITE_PATH."/sabredav/lib/" . PATH_SEPARATOR . get_include_path()); 

//for later
$GLOBALS["DOCMGR"] = null;

// Files we need
require_once 'Sabre.autoload.php';
require_once 'lib/apiclient.php';

// Create the parent node
$publicDirObj = new Sabre_DAV_DOCMGR_Directory($baseUri);

// Now we create an ObjectTree, which dispatches all requests to your newly created file system
$objectTree = new Sabre_DAV_ObjectTree($publicDirObj);

// The object tree needs in turn to be passed to the server class
$server = new Sabre_DAV_Server($objectTree);
$server->setBaseUri($baseUri);

// Support for LOCK and UNLOCK 
$lockBackend = new Sabre_DAV_Locks_Backend_DOCMGR($tmpDir);
$lockPlugin = new Sabre_DAV_Locks_Plugin();
$server->addPlugin($lockPlugin);

// Support for html frontend
$browser = new Sabre_DAV_Browser_Plugin();
$server->addPlugin($browser);

// Authentication backend
$authBackend = new Sabre_DAV_Auth_Backend_DOCMGR();
$auth = new Sabre_DAV_Auth_Plugin($authBackend,'SabreDAV');
$server->addPlugin($auth);

// And off we go!
$server->exec();

