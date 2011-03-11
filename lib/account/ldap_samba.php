<?php
/************************************************************************************************************
	ldap_extend.inc.php
	
	Holds functions revolving around samba and posix account manipulation

***********************************************************************************************************/


class LDAP_SAMBA extends LDAP {

  //so we can pass the api to other subclasses
  private $LDAP;

  function __construct($ldap) {
  
  	//transfer variables from parent
  	foreach ($ldap AS $key=>$val) $this->$key = $val;
  
  	$this->LDAP=$ldap;
  
	}

  //add samba fields with default values to an account  
  function add() {

  	$info = $this->LDAP->getInfo($this->accountId);
  	$this->LDAP->connect();

  	$arr = array();
  	$dn = $info["dn"];
  	$password = $info["password"];
	
  	//make a default password.  The user won't be able to login until the password is reset
  	$ntpwd = `mkntpwd "$password"`;		//mkntpwd must be in apache's path
  	$pwd = @explode(":",$ntpwd);		//printfs "ENCLMPWD:ENCNTPWD"
  	if (is_array($pwd)) {
			$arr["sambaNTPassword"] = trim($pwd[1]);
			$arr["sambaLMPassword"] = trim($pwd[0]);
		}

		$arr["sambaAcctFlags"] = "[UX ]";

		//since no sid is passed with the user info, use a new random one
		$arr["sambaSID"] = SAMBA_SID."-".$info["id"];

		//setup our objectclass array
		if (!in_array("sambaSamAccount",$info["objectClass"])) {
		  unset($info["objectClass"]["count"]);
			$info["objectClass"][] = "sambaSamAccount";
			$arr["objectClass"] = array_values($info["objectClass"]);
		}
		
		if (!$ret = ldap_modify($this->LDAP->conn,"$dn",$arr)) $this->LDAP->throwError("Unable to add samba information to account");

		return $ret;
		
  }

	function update($option) {

		$this->LDAP->connect();
		extract($option);
	
		$accountInfo = $this->LDAP->getInfo($this->accountId);
		$dn = $accountInfo["dn"];

		$arr = array();
		if ($sambaHomePath) $arr["sambaHomePath"] = $sambaHomePath;
		if ($sambaHomeDrive) $arr["sambaHomeDrive"] = $sambaHomeDrive;
		if ($sambaProfilePath) $arr["sambaProfilePath"] = $sambaProfilePath;
		//if ($sambaLogonScript) $arr["sambaLogonScript"] = $sambaLogonScript;
		if ($sambaDomainName) $arr["sambaDomainName"] = $sambaDomainName;
		if ($sambaSID) $arr["sambaSID"] = $sambaSID;
		if ($sambaPrimaryGroupSID) $arr["sambaPrimaryGroupSID"] = $sambaPrimaryGroupSID;

		if (!$ret = ldap_modify($this->LDAP->conn,"$dn",$arr)) $this->LDAP->throwError("Unable to update samba information");

		return $ret;
	
	}  


	function remove() {

		$accountInfo = $this->LDAP->getInfo($this->accountId);
		$dn = $accountInfo["dn"];
		$arr = array();

		$arr["sambaNTPassword"] = array();
		$arr["sambaLMPassword"] = array();
		$arr["sambaDomainName"] = array();
		$arr["sambaPrimaryGroupSID"] = array();
		$arr["sambaSID"] = array();
		$arr["sambaAcctFlags"] = array();
		$arr["objectClass"] = "sambaSamAccount";
	
		if (!$ret = ldap_mod_del($this->LDAP->conn,$dn,$arr)) $this->LDAP->throwError("Unable to remove samba information from account");

		return $ret;
	
	}

	function setPassword($password) {

		$this->LDAP->connect();
		$arr = array();
		$result = array();
	
		if (!$password) {
			$this->LDAP->throwError("You must specify a password");
			return false;
		}

		$accountInfo = $this->LDAP->getInfo($this->accountId);
	
		$arr = array();
		$dn = $accountInfo["dn"];
		$login = $accountInfo["login"];
	
		//do something special if we are using samba
		if ($accountInfo["sambaEnable"]) {

			$ntpwd = `mkntpwd "$password"`;		//mkntpwd must be in apache's path
			$pwd = @explode(":",$ntpwd);		//printfs "ENCLMPWD:ENCNTPWD"
			if (is_array($pwd)) {
  			$arr["sambaNTPassword"] = trim($pwd[1]);
  			$arr["sambaLMPassword"] = trim($pwd[0]);
      }
      
		}

		if (!$ret = ldap_modify($this->LDAP->conn,"$dn",$arr)) $this->LDAP->throwError("Unable to update samba password");

		return $ret;

	}
  

	function getInfo($data) {

		$arr = array();

		$keys = array_keys($data);
		
		foreach ($keys AS $key) {

      //if the key has samba in it, use it		
		  if (strstr($key,"samba")) $arr[$key] = $data[$key][0];
		  
    }

    if (@in_array("sambaSamAccount",$data["objectclass"])) $arr["sambaEnable"] = 1;

		return $arr;
	
	}

}
  
