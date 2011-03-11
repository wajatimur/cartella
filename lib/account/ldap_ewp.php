<?php
/************************************************************************************************************
	ldap_extend.inc.php
	
	Holds functions revolving around samba and posix account manipulation

***********************************************************************************************************/


class LDAP_EWP extends LDAP {

  //so we can pass the api to other subclasses
  private $LDAP;
  
  function __construct($ldap) {
  
  	//transfer variables from parent
  	foreach ($ldap AS $key=>$val) $this->$key = $val;

  	$this->LDAP=$ldap;
		  	
	}

	function add($option) {

	  extract($option);
    $accountInfo = $this->LDAP->getInfo($this->accountId);
	
    $ds = ldap_connect(LDAP_SERVER,LDAP_PORT);
    $arr = array();
    $dn = $accountInfo["dn"];

    //add our custom schema here
		if (!in_array("ewp",$accountInfo["objectClass"])) {
			unset($accountInfo["objectClass"]["count"]);
			$arr["objectClass"] = $accountInfo["objectClass"];
			$arr["objectClass"][] = "ewp";
		}

    if (!$ret=ldap_modify($this->conn,$dn,$arr)) $this->throwError("Unable to add ewp properties to account");
    
    return $ret;
	
	}


	function getInfo($data) {

	  $arr = array();

		$arr["employeeType"] =  $data["employeetype"][0];
		$arr["locationId"]  =  $data["locationid"];
		$arr["employerId"]  =  $data["employerid"];
		$arr["childLocationId"]  =  $data["childlocationid"];
		$arr["direct"] = $data["homephone"][0];
		$arr["fax"] = $data["facsimiletelephonenumber"][0];
		$arr["mobile"] = $data["mobile"][0];
		$arr["callForward"] = $data["callforward"][0];
		$arr["longDistanceCode"] = $data["longdistancecode"][0];
		$arr["callGroup"] = $data["callgroup"][0];
		$arr["pickupGroup"] = $data["pickupgroup"][0];
		$arr["sms"] = $data["smsaddress"][0];
		$arr["vmEmailNotify"] = $data["vmemailnotify"][0];
		$arr["vmEmailAttach"] = $data["vmemailattach"][0];
		$arr["vmSMSNotify"] = $data["vmsmsnotify"][0];
		$arr["conferenceRoom"] = $data["conferenceroom"][0];
		$arr["agentCode"] = $data["agentcode"][0];
		$arr["sn"] = $data["sn"][0];
		$arr["uid"] = $data["uid"][0];
		$arr["uidNumber"] = $data["uidnumber"][0];
		$arr["gidNumber"] = $data["gidnumber"][0];
		$arr["astPhone"] = $data["astphone"];
		$arr["astFax"] = $data["astfax"];
		$arr["astDirect"] = $data["astdirect"];
		$arr["astVMPassword"] = $data["astvmpassword"];
		$arr["astDirectDest"] = $data["astdirectdest"];
		$arr["callerIdName"] = $data["calleridname"];
		$arr["callerIdNum"] = $data["calleridnum"];
		
		//remove counts from employer and location
		unset($arr["locationId"]["count"]);
		unset($arr["employerId"]["count"]);
		unset($arr["childLocationId"]["count"]);
		unset($arr["astPhone"]["count"]);
		unset($arr["astDirect"]["count"]);
		unset($arr["astFax"]["count"]);
		unset($arr["astDirectDest"]["count"]);
		unset($arr["callerIdName"]["count"]);
		unset($arr["callerIdNum"]["count"]);
				
		if (stristr($arr["dn"],"ou=people")) $accountType = "people";
		elseif (stristr($arr["dn"],"ou=system")) $accountType = "system";
		elseif (stristr($arr["dn"],"ou=misc")) $accountType = "misc";
		elseif (stristr($arr["dn"],"ou=machine")) $accountType = "machine";
	
		$arr["accountType"] = $accountType;

		return $arr;

  }


  function updateLocation($option) {
  
    $this->connect();
    extract($option);

    $result = array();

    if ($this->accountId==NULL) {
		  $this->LDAP->throwError("The account id must be passed to update the account");
		  return false;
    }

    $accountInfo = $this->LDAP->getInfo($this->accountId);
    $login = $accountInfo["login"];

    //add our custom schema here
		$arr = null;
		if (!in_array("ewp",$accountInfo["objectClass"])) {
			$arr["objectClass"] = $accountInfo["objectClass"];
			$arr["objectClass"][] = "ewp";
		}

    $dn = $accountInfo["dn"];
    if ($locationId) $arr["locationId"] = array_values(array_unique($locationId));
    if ($employerId) $arr["employerId"] = array_values(array_unique($employerId));
    if ($childLocationId) $arr["childLocationId"] = array_values(array_unique($childLocationId));

    //$arr["objectClass"] = array_values($objectClass);

    if (!$ret=ldap_modify($this->conn,$dn,$arr)) $this->LDAP->throwError("Error updating account location");
    
    return $ret;
  
  }  


  function astEntry($astPhoneCur,$entry) {

		//get the server for our entry
		$pos = strpos($entry,":");
		if ($pos===FALSE) return array();
		
		$server = trim(substr($entry,0,$pos));
		$number = trim(substr($entry,$pos+1));

		$ret = array();
	
		//no records, just add the new one
		if (count($astPhoneCur)==0) {
			if ($server && $number) $ret[] = $entry;
		} else {
	
				$found = 0;
		
				foreach ($astPhoneCur AS $cur) {
	
					$curserver = null;
					$pos = strpos($cur,":");
					if ($pos!==FALSE) {
						$curserver = trim(substr($cur,0,$pos));
						$curnumber = trim(substr($cur,$pos+1));
					}
					
					//no server, skip.  should remove invalid entries
					if (!$curserver) continue;
	
					//if curserver matches the passed server, use the new entry
					//otherwise just add what's left
					if ($curserver==$server) {
					
						if ($number) {
							$ret[] = $entry;
							$found = 1;	//there's an entry for this server and it's being updated
						}

					}
					//don't re-add a blank entry
					else if ($curnumber) $ret[] = $cur;
	
				}
	
				//we didnt' find an entry for the server, so add one
				if (!$found && $number) $ret[] = $entry;
	
			
		}
	
		return $ret;
  
  
  }

  function updatePhone($option) {
  
  	$this->connect();
  	extract($option);

  	$arr = array();
  	$result = array();

  	if ($this->accountId==NULL) {
			$this->LDAP->throwError("The account id must be passed to update the account");
			return false;
		}

		$accountInfo = $this->LDAP->getInfo($this->accountId);

		$login = $accountInfo["login"];
		$astPhoneArr = $this->astEntry($accountInfo["astPhone"],$astPhone);
		$astDirectArr = $this->astEntry($accountInfo["astDirect"],$astDirect);
		$astFaxArr = $this->astEntry($accountInfo["astFax"],$astFax);
		$cidNameArr = $this->astEntry($accountInfo["callerIdName"],$callerIdName);
		$cidNumArr = $this->astEntry($accountInfo["callerIdNum"],$callerIdNum);
		$astVMArr = $this->astEntry($accountInfo["astVMPassword"],$astVMPassword);
	
		//since we go by account id we should be able to just yank the search base from here
		$searchBase = $accountInfo["searchBase"];
		$objectClass = $accountInfo["objectClass"];

		$dn = $accountInfo["dn"];

		//add our custom schema here
		$arr = null;
		if (!in_array("ewp",$accountInfo["objectClass"])) {
			$arr["objectClass"] = $accountInfo["objectClass"];
			$arr["objectClass"][] = "ewp";
		}

		if ($phone) $phone = ereg_replace("[^0-9]","",$phone);
		else $phone = array();

		if ($directPhone) $directPhone = ereg_replace("[^0-9]","",$directPhone);
		else $directPhone = array();

		if ($faxPhone) $faxPhone = ereg_replace("[^0-9]","",$faxPhone);
		else $faxPhone = array();

		if (!$longDistanceCode) $longDistanceCode = array();
		if (!$conferenceRoom) $conferenceRoom = array();
		if (!$callGroup) $callGroup = array();
		if (!$pickupGroup) $pickupGroup = array();

		//set a default callForward if not set
		if (!$accountInfo["callForward"]) $arr["callForward"] = "office";

		//$arr["telephoneNumber"] = $phone;
		//$arr["homeTelephoneNumber"] = $directPhone;
		$arr["facsimileTelephoneNumber"] = $faxPhone;
		$arr["longDistanceCode"] = $longDistanceCode;
		$arr["conferenceRoom"] = $conferenceRoom;
		$arr["callGroup"] = $callGroup;
		$arr["pickupGroup"] = $pickupGroup;
		$arr["astPhone"] = $astPhoneArr;
		$arr["astDirect"] = $astDirectArr;
		$arr["astFax"] = $astFaxArr;
		$arr["callerIdName"] = $cidNameArr;
		$arr["callerIdNum"] = $cidNumArr;
		$arr["astVMPassword"] = $astVMArr;

    if (!$ret=ldap_modify($this->conn,$dn,$arr)) $this->throwError("Unable to update phone properties");
    
    return $ret;

  }

  
  function updateOptions($option) {

		$this->connect();  
		extract($option);

		$arr = array();
		$result = array();

  	if ($this->accountId==NULL) {
			$this->LDAP->throwError("The account id must be passed to update the account");
			return false;
		}

		$accountInfo = $this->LDAP->getInfo($this->accountId);
		$login = $accountInfo["login"];
	
		//since we go by account id we should be able to just yank the search base from here
		$searchBase = $accountInfo["searchBase"];
		$objectClass = $accountInfo["objectClass"];

		$dn = $accountInfo["dn"];

		//add our custom schema here
		$arr = null;
		if (!in_array("ewp",$accountInfo["objectClass"])) {
			$arr["objectClass"] = $accountInfo["objectClass"];
			$arr["objectClass"][] = "ewp";
		}

		if ($mobile) $mobile = ereg_replace("[^0-9]","",$mobile);
		else $mobile = array();

		if (!$sms) $sms = array();
		if (!$callForward) $callForward = "office";
		if (!$vmEmailNotify) $vmEmailNotify = array();
		if (!$vmEmailAttach) $vmEmailAttach = array();
		if (!$vmSMSNotify) $vmSMSNotify = array();
		
		$arr["mobileTelephoneNumber"] = $mobile;
		$arr["callForward"] = $callForward;
		$arr["smsAddress"] = $sms;
		$arr["vmEmailNotify"] = $vmEmailNotify;
		$arr["vmEmailAttach"] = $vmEmailAttach;
		$arr["vmSMSNotify"] = $vmSMSNotify;
		
    if (!$ret=ldap_modify($this->conn,$dn,$arr)) $this->LDAP->throwError("Error updating account phone options");
    
    return $ret;
	

  }


  function checkType($option) {

  	$this->connect();
		extract($option);

		//return the current info for this account
		$res = $this->LDAP->search("(".LDAP_UIDNUMBER."=".$this->accountId.")");
		$arr = $res[0];

		if (strstr($arr["dn"],"ou=machine")) $at = "machine";
    elseif (strstr($arr["dn"],"ou=misc")) $at = "misc";
    else $at = "people";

		if ($at!=$accountType) { 

			$arr = $this->LDAP->fixLdapArray($arr);
			
			$newdn = LDAP_UID."=".$arr["uid"].",ou=".$accountType.",".LDAP_ROOT;
			$olddn = $arr["dn"];
			unset($arr["dn"]);

			$ret = null;
		
			if (ldap_add($this->conn,"$newdn", $arr)) {
				ldap_delete($this->conn,"$olddn");
				$ret = true;
			} else {
				$this->throwError("Unable to change account type");
			}
		
			return $ret;

		}
  
  }

  function update($option) {
  
  	$this->connect();
		extract($option);

		$arr = array();
		$r = array();

  	if ($this->accountId==NULL) {
			$this->LDAP->throwError("The account id must be passed to update the account");
			return false;
		}

		//make sure we aren't changing account types
		$this->checkType($option);
	
		//if a machine account, get out of here and do special machine processing
		if ($accountType=="machine") return $this->updateMachine($option);
		else return $this->updateAccount($option);

	}	

	function updateAccount($option) {

  	$this->connect();
		extract($option);

		$arr = array();
		$r = array();

  	if ($this->accountId==NULL) {
			$this->LDAP->throwError("The account id must be passed to update the account");
			return false;
		}

		$accountInfo = $this->LDAP->getInfo($this->accountId);
		$dn = $accountInfo["dn"];

		//make our shell changes if necessary
		$shadow = new LDAP_SHADOW($this->LDAP);
		if ($shadowEnable && !$accountInfo["shadowEnable"]) $shadow->add();
		if (!$shadowEnable && $accountInfo["shadowEnable"]) $shadow->remove();

		$samba = new LDAP_SAMBA($this->LDAP);
		if ($sambaEnable && !$accountInfo["sambaEnable"]) $samba->add();
		if (!$sambaEnable && $accountInfo["sambaEnable"]) $samba->remove();
			
		//set employee type		
		if ($employeeType) $arr["employeeType"] = $employeeType;
	
    if (!$ret=ldap_modify($this->conn,$dn,$arr)) $this->LDAP->throwError("Error updating account phone options");
    
    return $ret;
  
  }


  function updateMachine($option) {
  
  	$this->connect();
		extract($option);

		$arr = array();
		$r = array();
	
  	if ($this->accountId==NULL) {
			$this->LDAP->throwError("The account id must be passed to update the account");
			return false;
		}

		$accountInfo = $this->LDAP->getInfo();
		$dn = $accountInfo["dn"];

		//setup a machine entry with this account's information		
		$arr = null;
		$arr["cn"] = $accountInfo["full_name"];
		$arr["sn"] = $accountInfo["last_name"];
		$arr["givenName"] = $accountInfo["first_name"];
		$arr["uid"] = $accountInfo["uid"];
		$arr["uidNumber"] = $accountInfo["uidNumber"];
		$arr["gidNumber"] = "400";
		$arr["homeDirectory"] = "/dev/null";
		$arr["loginShell"] = "/bin/false";
		$arr["sambaAcctFlags"] = "[WX ]";
		$arr["sambaSID"] = SAMBA_SID."-".$accountInfo["uidNumber"];

    if (!$ret=ldap_modify($this->conn,$dn,$arr)) $this->LDAP->throwError("Error updating machine options");
    
    return $ret;

	}


	function setPassword($password) {

		$this->connect();
		$arr = array();
		$result = array();

  	if ($this->accountId==NULL) {
			$this->LDAP->throwError("The account id must be passed to update the account");
			return false;
		}

  	if (!$password) {
			$this->LDAP->throwError("You must specify a password");
			return false;
		}

		$accountInfo = $this->LDAP->getInfo();
		$dn = $accountInfo["dn"];
		$arr["plainPassword"] = $password;

    if (!$ret=ldap_modify($this->conn,$dn,$arr)) $this->LDAP->throwError("Error updating account phone options");
    
    return $ret;

	}



}

