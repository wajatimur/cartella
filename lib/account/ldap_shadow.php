<?php
/************************************************************************************************************
	ldap_extend.inc.php
	
	Holds functions revolving around samba and posix account manipulation

***********************************************************************************************************/


class LDAP_SHADOW extends LDAP {

  //so we can pass the api to other subclasses
  private $LDAP;

  function __construct($ldap) {
  
  	//transfer variables from parent
  	foreach ($ldap AS $key=>$val) $this->$key = $val;
  
  	$this->LDAP=$ldap;
  
	}

	function add() {

	  $this->LDAP->connect();
	  $accountInfo = $this->LDAP->getInfo($this->accountId);

	  $arr = array();
	  $dn = $accountInfo["dn"];

	  //add our extended attributes
	  unset($accountInfo["objectClass"]["count"]);
	  $accountInfo["objectClass"][] = "shadowAccount";
	  $arr["objectClass"] = array_values($accountInfo["objectClass"]);

	  //these need real values
	  $arr["shadowLastChange"]="12129";
	  $arr["shadowMax"]="99999";
	  $arr["shadowWarning"]="7";
	  $arr["loginShell"]=LOGIN_SHELL;

	  if (!$ret=ldap_modify($this->LDAP->conn,$dn,$arr)) $this->LDAP->throwError("Unable to add shadow properties to account");
	  
	  return $ret;
	
	}

	function update($option) {

	  extract($option);
	  $this->LDAP->connect();
	  $accountInfo = $this->LDAP->getInfo($this->accountId);

	  $arr = array();
	  $dn = $accountInfo["dn"];

	  //these need real values
	  if ($loginShell) $arr["loginShell"] = $loginShell;
	  if ($homeDirectory) $arr["homeDirectory"] = $homeDirectory;
	  
	  if (!$ret=ldap_modify($this->LDAP->conn,$dn,$arr)) $this->LDAP->throwError("Unable to add shadow properties to account");
	  
	  return $ret;
	
	}

	function remove() {

	  $this->LDAP->connect();
	  $accountInfo = $this->LDAP->getInfo($this->accountId);

	  $arr = array();
	  $dn = $accountInfo["dn"];

	  $arr["shadowLastChange"]=array();
	  $arr["shadowMax"]=array();
	  $arr["shadowWarning"]=array();
	  $arr["loginShell"]=array();
	  $arr["objectClass"] = "shadowAccount";

	  if (!$ret = ldap_mod_del($this->LDAP->conn,$dn,$arr)) $this->LDAP->throwError("Unable to remove shadow properties from account");
	  
	  return $ret;

  }

  function getInfo($data) {

  	$arr = array();

		$arr["loginShell"] = $data["loginshell"][0];
    $arr["homeDirectory"] = $data["homedirectory"][0];
    		
		if ($data["shadowInactive"][0] == "1") $arr["enable"] = "0";
		else $arr["enable"] = "1";

		if (@in_array("shadowAccount",$data["objectclass"])) $arr["shadowEnable"] = 1;

		return $arr;
  
  }
  
}



