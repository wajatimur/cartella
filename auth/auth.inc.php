<?php

//logout out.  If there is a login set to show it
if ($_REQUEST["logout"]) 
{

	logout($conn);

	//go back to the main page
	header("Location: index.php");

} 
else if ($_SESSION["authorize"])
{

	include("apilib/auth.php");
	$a = new AUTH();

	//update last access time.  If module is one we're supposed to ignore, don't update
	if (defined("ACCESS_IGNORE")) 
	{
		$arr = explode(",",ACCESS_IGNORE);
		if (!in_array($module,$arr)) $_SESSION["login"]["last"] = time();
	} 
	else $_SESSION["login"]["last"] = time();

} 
else if ($_REQUEST["login"] && $_REQUEST["password"])
{

	include("apilib/auth.php");
	$a = new AUTH();

	//stop here if login error
	$loginFormError = $a->getError();

	//error logging in.  handle it
	if (!$loginFormError) 
	{

		//copy the api session keys into our local one
		$keys = array_keys($_SESSION["api"]);

		foreach ($keys AS $key) $_SESSION[$key] = $_SESSION["api"][$key];

		//setup session login timer
		$_SESSION["login"] = array();
		$_SESSION["login"]["in"] = time();				//time they logged in
		$_SESSION["login"]["last"] = time();
		
		//default to the current login date if none is selected
		if ($_SESSION["last_login"]=="1970-01-01 00:00:00") $_SESSION["last_login"] = date("Y-m-d H:i:s");

		if (defined("USE_COOKIES") && $_POST["savePassword"]) 
		{

			$expire = time()+60*60*24*30;
			$path = substr($_SERVER["PHP_SELF"],0,strlen($_SERVER["PHP_SELF"]) - 9);
			$domain = $_SERVER["SERVER_NAME"];

			//send only over secure site if necessary
			if (defined("HTTPS_ONLY")) $secure = true;
			else $secure = false;

			//set the cookie
			setcookie("login",USER_LOGIN,"$expire","$path","$domain",$secure);
			setcookie("password",md5(USER_PASSWORD),"$expire","$path","$domain",$secure);

			//set our permission defines
			$show_login_form = null;

		}

		//if we have gotten this far and there was a passed query string, redirect to that
		if ($_POST["queryString"]) header("Location: index.php?".$_POST["queryString"]);

	}

}

function logout($conn) 
{

	@session_unset();
	@session_destroy();

	$path = substr($_SERVER["PHP_SELF"],0,strlen($_SERVER["PHP_SELF"]) - 9);
	$domain = $_SERVER["SERVER_NAME"];
	
	setcookie("login","",time()-3600,"$path","$domain",0);
	setcookie("password","",time()-3600,"$path","$domain",0);

	$_SESSION = null;

	//pause 1/2 second to prevent brute force password cracking
	usleep(500000);

}

