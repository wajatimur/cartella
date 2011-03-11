<?php
/************************************************************************************************************
	ldap.php
	
	Holds account processing and search functions for
	an ldap database
	
	02-07-2005 - Fixed returnAccountInfo returning an error if it did not find an account Id (Eric L.)
	02-14-2005 - Split group info
	11-20-2005 - Stripped file down more and added support for an ldap map file

***********************************************************************************************************/

//call our extended attributes file
if (defined("ENABLE_SHADOW")) include("ldap_shadow.php");
if (defined("ENABLE_SAMBA")) include("ldap_samba.php");
if (defined("ENABLE_EWP")) include("ldap_ewp.php");
include("ldap_group.php");

class LDAP {

	protected $accountId;
	protected $conn;
	protected $errorMessage;
	protected $DB;
			
	function __construct($aid=null) {

			if (!$this->checkSanity()) return false;

			if ($aid) $this->accountId = $aid;
			$this->DB = $GLOBALS["DB"];

	}

	/***************************************************
		connect to the database
	***************************************************/
	function connect() {

		if ($this->conn) return true;

		$this->conn = ldap_connect(LDAP_SERVER,LDAP_PORT);
		ldap_set_option($this->conn, LDAP_OPT_PROTOCOL_VERSION, LDAP_PROTOCOL);
		$r = ldap_bind($this->conn,BIND_DN,BIND_PASSWORD);
			
	}

	function close() {
	
		ldap_close($this->conn);
		$this->conn = null;
			
	}

	function search($filter=null,$sort=null) {

		$this->connect();
		
  	if ($filter) $filter = "(&".$filter."(".LDAP_UID."=*))";
  	else $filter = "(".LDAP_UID."=*)";

    $sr = ldap_search($this->conn,LDAP_BASE,$filter);
    if ($sort) ldap_sort($this->conn,$sr,$sort);
		$res = ldap_get_entries($this->conn,$sr);     
        	
    return $res;
        	
	}


	function reformat($arr) {

		$info = array();

		if ($arr["count"]==0) return false;

		$keys = array_keys($arr[0]);
		
		$i = 0;
		
		foreach ($arr AS $temp) {

			if (!is_array($temp)) continue;

			$info[$i] = array();
			$info[$i]["id"] 		= 	$temp[strtolower(LDAP_UIDNUMBER)][0];
			$info[$i]["login"] 		= 	$temp[strtolower(LDAP_UID)][0];	
			$info[$i]["first_name"] 	= 	$temp[strtolower(LDAP_GIVENNAME)][0];
			$info[$i]["last_name"] 	= 	$temp[strtolower(LDAP_SN)][0];
			$info[$i]["email"] 		= 	$temp[strtolower(LDAP_MAIL)][0];
			$info[$i]["phone"]		=	$temp[strtolower(LDAP_TELEPHONENUMBER)][0];
			$info[$i]["full_name"] = $info[$i]["first_name"]." ".$info[$i]["last_name"];
			$info[$i]["dn"]		=	$temp["dn"];
			$info[$i]["objectClass"]	=	$temp["objectclass"];
			$info[$i]["password"] = $temp[strtolower(LDAP_CLEARPASSWORD)][0];
			$info[$i]["crypt_password"] = $temp[strtolower(LDAP_USERPASSWORD)][0];
			
			if (strstr($temp["dn"],"ou=machine")) $info[$i]["account_type"] = "machine";
			elseif (strstr($temp["dn"],"ou=misc")) $info[$i]["account_type"] = "misc";
			else $info[$i]["account_type"] = "people";

			$i++;
			
		}
		
		return $info;
			
	}
	
	/************************************************
		make sure we have everything we need to work
	************************************************/
	function checkSanity() {

		//sanity checking.  If our attributes are not defined, this won't work
		if (!defined("LDAP_UID")) die("ldap attribute \"LDAP_UID\" is not defined");
		if (!defined("LDAP_UIDNUMBER")) die("ldap attribute \"LDAP_UIDNUMBER\" is not defined");
		if (!defined("LDAP_CN")) die("ldap attribute \"LDAP_CN\" is not defined");
		if (!defined("LDAP_GECOS")) die("ldap attribute \"LDAP_GECOS\" is not defined");
		if (!defined("LDAP_SN")) die("ldap attribute \"LDAP_SN\" is not defined");
		if (!defined("LDAP_GIVENNAME")) die("ldap attribute \"LDAP_GIVENNAME\" is not defined");
		if (!defined("LDAP_TELEPHONENUMBER")) die("ldap attribute \"LDAP_TELEPHONENUMBER\" is not defined");
		if (!defined("LDAP_MAIL")) die("ldap attribute \"LDAP_MAIL\" is not defined");
		if (!defined("LDAP_USERPASSWORD")) die("ldap attribute \"LDAP_USERPASSWORD\" is not defined");

		return true;

	}

	function throwError($msg) {
		$this->errorMessage = $msg;
	}
	
	function getError() {
		return $this->errorMessage;
	}

	function getInfo($aid=null) {
	
		if (!$aid) $aid = $this->accountId;

		$res = $this->search("(".LDAP_UIDNUMBER."=".$aid.")");
		$formatted = $this->reformat($res);

		if ($formatted) {
		
			$info = $formatted[0];

			//if samba exists, pull samba info
			if (defined("ENABLE_SAMBA")) {
				$ls = new LDAP_SAMBA($this);
				$info = array_merge($info,$ls->getInfo($res[0]));
			}

			//if shadow account exists, pull info
			if (defined("ENABLE_SHADOW")) {
				$ls = new LDAP_SHADOW($this);
				$info = array_merge($info,$ls->getInfo($res[0]));
			}

			//if shadow account exists, pull info
			if (defined("ENABLE_EWP")) {
				$ls = new LDAP_EWP($this);
				$info = array_merge($info,$ls->getInfo($res[0]));
			}
			
			return $info;		
		
		} else return false;
	
	}

	function getList($filter=null,$sort=null) {
	
		if (!$aid) $aid = $this->accountId;

		$res = $this->search($filter,$sort);
		$ret =  $this->reformat($res);

		$num = count($ret);

		for ($i=0;$i<$num;$i++) {
		
			//if shadow account exists, pull info
			if (defined("ENABLE_EWP")) {
				$ls = new LDAP_EWP($this);
				$ret[$i] = @array_merge($ret[$i],$ls->getInfo($res[$i]));		//pass unformated info to get merged in with formatted stuff
			}
		
		}

		return $ret;

	}

	function accountExists($login) {

		$filter = "(".LDAP_UID."=".$login.")";
		$res = $this->search($filter);

		if ($res["count"]>0) return false;
		else return true;
		
	}

	function validLogin($login) {
	
		//only allow special chars _ and . in a login
		$arr = array(   ",","/","?","'","\"","!","@","#",
			"%","^","&","*","(",")","+","=",
			"}","{","[","]","|","\\",":",";","<",
			">"
			);

		$num = count($arr);

		for ($row=0;$row<$num;$row++) if (strstr($login,$arr[$row])) return false;

		return true;

	}

	function nextId() {

		$arr = $this->reformat($this->search());
		$arr = transposeArray($arr);

		//reverse the order and take the highest result	
		rsort($arr["id"]);
		$ret = $arr["id"][0] + 1;
		return $ret;
		
	}


	function insert($option) {

		$this->accountId = null;
		$this->connect();

		extract($option);

		$arr = array();
		$result = array();
	
		if (!$login) {
			$this->throwError("You must enter a login to create the account");
			return false;
		}

		//grab a first name if there is none
		if (!$firstName) $firstName = $login;

		//make sure our login doesn't have bad characters in it
		if (!$this->validLogin($login)) {
			$this->throwError("Invalid characters used in login");
			return false;
		}

		// prepare our objectclass and common data
		$arr = array();
		$arr["objectclass"][0]="person";
		$arr["objectclass"][1]="organizationalPerson";
		$arr["objectclass"][2]="top";
		$arr["objectclass"][3]="inetOrgPerson";
		$arr["objectclass"][4]="posixAccount";
	
		$dn = LDAP_UID."=".$login.",".LDAP_CREATE_BASE;
	
		if ($firstName && $lastName) $fullname = $firstName." ".$lastName;
		else {
			if ($firstName) $fullname = $firstName;
			elseif ($lastName) $fullname = $lastName;
		}
	
		if ($fullname) {
			$arr[LDAP_CN] = $fullname;
			$arr[LDAP_GECOS] = $fullname;
		}
		if ($lastName) $arr[LDAP_SN] = $lastName;
		if ($firstName) $arr[LDAP_GIVENNAME] = $firstName;
		if ($phone) $arr[LDAP_TELEPHONENUMBER] = $phone;
		if ($email) $arr[LDAP_MAIL] = $email;
	
		$this->accountId = $this->nextId();
	
		$arr[LDAP_UID] = $login;
		$arr[LDAP_UIDNUMBER] = $this->accountId;
		$arr[LDAP_GIDNUMBER] = DEFAULT_GID;
	
		if (!$homeDirectory) $homeDirectory = "/home/".$login;
		$arr["homeDirectory"] = $homeDirectory;

		if (ldap_add($this->conn,"$dn",$arr)) 
		{

			//insert a base permission record for this account
			$opt = null;
			$opt["account_id"] = $this->accountId;
			$opt["bitset"] = "0";
			$opt["enable"] = "t";
			$this->DB->insert("auth_accountperm",$opt);

			//if samba exists, pull samba info
			if (defined("ENABLE_SAMBA")) 
			{
				$ls = new LDAP_SAMBA($this);
				$ls->add($option);
			}

			//if shadow account exists, pull info
			if (defined("ENABLE_SHADOW")) 
			{
				$ls = new LDAP_SHADOW($this);
				$ls->add($option);
			}

			//if shadow account exists, pull info
			if (defined("ENABLE_EWP")) 
			{
				$ls = new LDAP_EWP($this);
				$ls->add($option);
			}

			//set the password
			$this->setPassword($option["password"]);

		} else $this->accountId = null;

		return $this->accountId;
	
	}

	function cryptPassword($password,$salt=null) {

		if (LDAP_CRYPT=="MD5") $cryptpw = "{MD5}".base64_encode(pack("H*",md5($password)));
		else if ($salt) $cryptpw = "{CRYPT}".crypt($password,$salt);
		else $cryptpw = "{CRYPT}".crypt($password);
            
		return $cryptpw;

	}


	function setPassword($password) {

		$this->connect();

		$arr = array();
		$result = array();
	
		if ($this->accountId==NULL) {
			$this->throwError("The account id must be passed to update the account");
			return false;
		}

		if (!$password) {
			$this->throwError("You must specifiy a password");
			return false;
		}

		//get our current account info
		$info = $this->reformat($this->search("(".LDAP_UIDNUMBER."=".$this->accountId.")"));
		$login = $info[0]["login"];
		$dn = $info[0]["dn"];

		$arr[LDAP_USERPASSWORD] = $this->cryptPassword($password);
		
		//run the query
		if (!$ret = ldap_modify($this->conn,"$dn", $arr)) $this->throwError("Failed to update account password");

		//if samba exists, pull samba info
		if (defined("ENABLE_SAMBA")) {
			$ls = new LDAP_SAMBA($this);
			$ls->setPassword($password);
		}

		//if shadow account exists, pull info
		if (defined("ENABLE_EWP")) {
			$ls = new LDAP_EWP($this);
			$ls->setPassword($password);
		}

		return $ret;
		
	}


	function saveProfile($option) {

		$this->connect();
		extract($option);

		$arr = array();
		$result = array();
	
		if ($this->accountId==NULL) {
			$this->throwError("The account id must be passed to update the account");
			return false;
		}

		$res = $this->reformat($this->search("(".LDAP_UIDNUMBER."=".$this->accountId.")"));
		$info = $res[0];

		if ($login) {

			//make sure our login doesn't have bad characters in it
			if (!$this->validLogin($login)) {
				$this->throwError("Invalid characters used in login");
				return $result;
			}

			//they do not match, check to see if the new one exists
			if ($login!=$info["login"]) {

				//check to make sure this does not exist.
				if (!$this->accountExists($login)) {
					$this->throwError("The new username you selected is already in use");
					return false;
				} else {
					return $this->rename($option,$accountInfo);
				}

			}
			

		} else $login = $info["login"];
	
		//since we go by account id we should be able to just yank the search base from here
		$objectClass = $info["objectClass"];
		$dn = $info["dn"];

		if ($firstName && $lastName) $fullname = $firstName." ".$lastName;
		else {
			if ($firstName) $fullname = $firstName;
			elseif ($lastName) $fullname = $lastName;
		}

		if ($fullname) {
			$arr[LDAP_CN] = $fullname;
			$arr[LDAP_GECOS] = $fullname;
		}
		if ($lastName) $arr[LDAP_SN] = $lastName;
		if ($firstName) $arr[LDAP_GIVENNAME] = $firstName;
		if ($phone) $arr[LDAP_TELEPHONENUMBER] = $phone;
		if ($email) $arr[LDAP_MAIL] = $email;

		if (!$res = ldap_modify($this->conn,"$dn", $arr)) $this->throwError("Account profile update failed");
	
		return $res;

	}


	function renameAccount($option,$accountInfo) {

		$this->connect();
		extract($option);

		//return the current info for this account
		$cnString = "(".LDAP_UIDNUMBER."=".$this->accountId.")";
		$res = $this->format($this->search($cnString));
		$info = $res[0];

		//update the info if stuff passed from the form
		if ($firstName && $lastName) $fullname = $firstName." ".$lastName;
		else {
			if ($firstName) $fullname = $firstName;
			elseif ($lastName) $fullname = $lastName;
		}

		if ($fullname) {
			$arr[LDAP_CN] = $fullname;
			$arr[LDAP_GECOS] = $fullname;
		}

		if ($lastName) $arr[LDAP_SN] = $lastName;
		if ($firstName) $arr[LDAP_GIVENNAME] = $firstName;
		if ($phone) $arr[LDAP_TELEPHONENUMBER] = $phone;
		if ($email) $arr[LDAP_MAIL] = $email;

		//add the new uid to the entry array	
		$arr[LDAP_UID] = $login;

		$arr = $this->fixLdapArray($arr);

		$newdn = LDAP_UID."=".$login.",".LDAP_BASE;
		$olddn = $accountInfo["dn"];	

		if (ldap_add($this->conn,"$newdn", $arr)) {
			ldap_delete($this->conn,"$olddn");
			$ret = true;
		} else $ret = false;
		
		if (!$ret) $this->throwError("Account renaming failed");
		
		return $ret;

	}

	//takes ldap results from ldap_search and makes them ready
	//for reinsertion
	function fixLdapArray($arr) {
	
		$newarr = array();

		$keys = array_keys($arr);
	
		for ($row=0;$row<count($keys);$row++) {

			$key = $keys[$row];

			if (is_numeric($key) || $key=="count" || $key=="objectClass") continue;

			if (is_array($arr[$key])) $newarr[$key] = $arr[$key][0];
			else $newarr[$key] = $arr[$key];
	
		}

		$c = 0;

		unset($arr["objectclass"]["count"]);
		$newarr["objectclass"] = $arr["objectclass"];

		return $newarr;

	}

	function delete() {

		$this->connect();

		if ($this->accountId==NULL) {
			$this->throwError("The account id must be passed to delete the account");
			return false;
		}

		$res = $this->reformat($this->search("(".LDAP_UIDNUMBER."=".$this->accountId.")"));
		$info = $res[0];
		
		if (ldap_delete($this->conn,$info["dn"])) {

    	$sql = "DELETE FROM auth_accountperm WHERE account_id='".$this->accountId."';";
    	$sql .= "DELETE FROM auth_settings WHERE account_id='".$this->accountId."';";
      $this->DB->query($sql);
			$ret = true;		

		} else {
		
			$this->throwError("Account removal failed");
			$ret = false;
			
		}

		return $ret;

	}

	function getSalt($pwd) {
	
		if (LDAP_CRYPT=="MD5") $pwd = str_replace("{MD5}","",$pwd);
		else $pwd = str_replace("{CRYPT}","",$pwd);
		
		$salt = substr($pwd,0,CRYPT_SALT_LENGTH);

		return $salt;
			
	}

	//compares the passed password to that in the ldap db
	function password_check($login,$password) {
	
		$list = $this->getList("(".LDAP_UID."=".$login.")");
	
		if (count($list) > 0) {

			$ai = $list[0];
				
      if (strlen($password)>25) 
      {

				//passed an md5 hash, just to a comparison to see if we're good
 				if ($password==md5($ai["password"])) return $ai;
 				else return false;
			
			}
			else 
			{
			
				//get the salt and encrypt the passed password
				$cryptpw = $this->cryptPassword($password,$this->getSalt($ai["crypt_password"]));

				//return info if we have a match
				if ($cryptpw == $ai["crypt_password"]) return $ai;
				else return false;

			}
				
		} else return false;
	
	}
	
}
	
	