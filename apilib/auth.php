<?php

class AUTH 
{

	private $error;
	private $accountId;
	private $DB;
	private $login;
	private $password;
				
	function __construct($login=null,$password=null) 
	{

		if ($login) $this->login = $login;
		else $this->login = $_REQUEST["login"];

		if ($password) $this->password = $password;
		else $this->password = $_REQUEST["password"];

		$this->DB = $GLOBALS["DB"];

		//if already authorized, set everything up.  Otherwise auth us
		if (!$_SESSION["api"]["authorize"]) $this->authorize();

		if (!$this->getError()) 
		{

			$this->accountId = $_SESSION["api"]["accountInfo"]["id"];

			//set the user's permission level based on individual perms and group membership
			$p = new PERM($this->accountId);
			$p->set();

			//set our user information from that which is returned from the function
			define("USER_ID",$_SESSION["api"]["accountInfo"]["id"]);
			define("USER_LOGIN",$_SESSION["api"]["accountInfo"]["login"]);
			define("USER_PASSWORD",$_SESSION["api"]["accountInfo"]["password"]);
			define("USER_EMAIL",$_SESSION["api"]["accountInfo"]["email"]);
			define("USER_FN",$_SESSION["api"]["accountInfo"]["first_name"]);
			define("USER_LN",$_SESSION["api"]["accountInfo"]["last_name"]);

			//check and update account locked status
			$this->time_unlock_account();
			$this->reset_failed_login_count();
			$this->update_activity();

			//record the login
			$GLOBALS["logger"]->log($this->login." logged in",LOGGER_DEBUG,"AUTH");
  

		} else
		{
		
			//update the number of login failures
			$this->update_failed_login_attempts();

			//record the login
			$GLOBALS["logger"]->log($this->login." login attempt failed",LOGGER_DEBUG,"AUTH");
			
		}

	}

	function throwError($err) 
	{
		$this->error = $err;
	}
	
	function getError() 
	{
		return $this->error;
	}
	
	function authorize() 
	{

		$ACCOUNT = new ACCOUNT();

		//user is trying to login, process the information
		if (!$this->login || !$this->password) 
		{
			$this->throwError("API: Username or password not passed");
			return false;
		}

		//check to see if the user and password combo exist
		$accountInfo = $ACCOUNT->password_check($this->login,$this->password);

		//store our info in sessions for later
		if ($accountInfo) 
		{

			if ($accountInfo["enabled"]=="f")
			{
			
				$this->throwError("API: Account is disabled");
			
			} else 
			{

				//set our user information from that which is returned from the function
				$_SESSION["api"]["accountInfo"] = $accountInfo;

				//set our session value so we do not get requeried.
				$_SESSION["api"]["authorize"] = "1";

			}
			
		} else 
		{

			$this->throwError("API: Invalid username and/or password specified");
			return false;		

		}

	}

	// this function will reset the number of login attempts to 0
	function reset_failed_login_count()
	{

	  $sql = "UPDATE auth_accountperm SET failed_logins=0,last_success_login=now() WHERE account_id='".$this->accountId."';";
	  $this->DB->query($sql);

	}
	
	// this function will increment the number of login attempts
	function update_failed_login_attempts()
	{

		$ACCOUNT = new ACCOUNT();
    $aid = $ACCOUNT->getId($this->login);    

    //found a valid account, record attempt failure
    if ($aid)
    {
    
    	$sql = "UPDATE auth_accountperm SET failed_logins=(failed_logins+1) WHERE account_id='$aid'";
    	$this->DB->query($sql);

    	if (defined("ENABLE_ACCOUNT_LOCKOUT")) $this->lock_account($aid);	

		}
		
	}
	
	// this function will lock an account if the number of failed logins exceeds
	// the allowed number, account lockout is enabled, and so long as it is not an administrative account
	function lock_account($aid)
	{

    if ($aid)
    {
    
      // verify that the number of login attempts exceeds the allowed number
      $sql="SELECT failed_logins FROM auth_accountperm WHERE account_id='$aid';";
      $failLogin = $this->DB->single($sql);

      if ( $failLogin["failed_logins"] >= ACCOUNT_LOCKOUT_ATTEMPTS )
      {
	
        // disable account and timestamp
        $sql = "UPDATE auth_accountperm SET failed_logins_locked=TRUE,enable=FALSE,locked_time=now() WHERE account_id='$aid';";
        $this->DB->query($sql);
      
      }

    }
    
	}
	
	//this function will unlock an account after a specified period of time
	function time_unlock_account() 
	{
	
		if (defined("ACCOUNT_LOCKOUT_TIME") && ACCOUNT_LOCKOUT_TIME > 0)
		{

			$lockout_time=ACCOUNT_LOCKOUT_TIME." minutes";
			
			// see if this user has been locked out and if the lockout time has passed
			$sql = "SELECT * FROM auth_accountperm WHERE account_id='".$this->accountId."' AND failed_logins_locked=TRUE AND locked_time < now() - INTERVAL '$lockout_time'";
			$login_attempts = $this->DB->single($sql);
			
			if ($login_attempts["failed_logins_locked"]=="t")
			{
				$sql = "UPDATE auth_accountperm SET failed_logins_locked=FALSE,enable=TRUE,locked_time=NULL WHERE account_id='".$this->accountId."';";
				$this->DB->query($sql);
			}
		
		}

	}

	function update_activity()
	{

		$sql = "UPDATE auth_accountperm SET last_activity='".date("Y-m-d H:i:s")."' WHERE account_id='".$this->accountId."'";
		$this->DB->query($sql);
    	
	}

}


	