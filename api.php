<?php

//get header file
include("apilib/header.php");

//get header file
include("apilib/preauth.php");

//do we authorize people in this site to access any module
include("apilib/auth.php");
$a = new AUTH();

//stop here if login error
if ($err = $a->getError()) 
{
	
	$PROTO->add("error",$err);
	
} else if (!$_SESSION["api"]["authorize"]) 
{
	//if not authorized but don't have an error message, show a generic error 
	$PROTO->add("error","Could not log into api");

} 
else 
{

	//not sure if we still need this or not
	define("THEME_PATH","themes/".SITE_THEME);

	//get the main api library
	//include("apilib/api.php");

	//for handling requests
	include("apilib/request.php");

}

$PROTO->output();

