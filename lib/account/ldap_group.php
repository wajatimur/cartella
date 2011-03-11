<?php
/************************************************************************************************************
	ldap_group.inc.php
	
	Holds group processing and search functions for
	an ldap database
	
	02-07-2005 - Split from ldap.inc.php

***********************************************************************************************************/

class LDAP_GROUP {

	protected $groupId;
	protected $conn;
	protected $errorMessage;
	protected $DB;
 
	function __construct($gid=null) {

		if ($gid) $this->groupId = $gid;
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

	function search($filter=null) {

		$this->connect();

		if ($filter) $filter = "(&".$filter."(gidNumber=*))";
		else $filter = "(gidNumber=*)";

		$sr = ldap_search($this->conn,GROUP_BASE,$filter);
		$res = ldap_get_entries($this->conn,$sr);

		return $res;

	}

	
	function setGroupId($name) {

		$res = $this->search("(cn=".$name.")");

		if ($res["count"]==0) return false;
		else {
		
			$this->groupId = $res[0]["gidnumber"][0];
			return $this->groupId;
			
		}	
	
	}

	function throwError($msg) {
		$this->errorMessage = $msg;
	}

	function getError() {
		return $this->errorMessage;
	}

	function getInfo($gid=null) {
	
		if (!$gid) $gid = $this->groupId;

		$res = $this->search("(gidNumber=".$gid.")");
		$arr = $this->reformat($res);
	
		return $arr[0];

	}
	
	function reformat($res) {

		$arr = array();
		$c = 0;
		
		foreach ($res AS $info) {

			if (!is_array($info)) continue;

			$arr[$c] = array();					
			$arr[$c]["id"] = $info["gidnumber"][0];
			$arr[$c]["name"] = $info["cn"][0];
		
			//show our samba settings if set
			if (in_array("sambaGroupMapping",$info["objectclass"])) {
				$arr[$c]["sambaEnable"] = 1;	
				$arr[$c]["sambaGroupName"] = $info["displayName"][0];
				$arr[$c]["sambaSID"] = $info["sambaSID"][0];
			}
		
			//populate our member list	
			$num = count($info["memberuid"]);

			for ($i=0;$i<$num;$i++) 
				$arr[$c]["member"][]  = $info["memberuid"][$i];
	
			$c++;
	
		}

		return $arr;
		
	}
                                                                                                     
	function nextGroupNumber() {

		$res = $this->search();
		$res = transposeArray($res);
		
		$num = max($res["gidNumber"]) + 1;
		
		return $num;
		
	}

	function checkGroup($name) {
	
		$res = $this->search("(cn=".$name.")");
		if ($res["count"]>0) return false;
		else return true;
		
	}

	function add($option) {

		$this->connect();
		
		if (!$option["name"]) {
			$this->throwError("No group name was specified");
			return false;
		}

		if (!checkGroup($name)) {
			$this->throwError("A group with this name already exists");
			return false;
		}
		
		$this->groupId = $this->nextGroupNumber();

		// prepare our objectclass and common data
		$info = array();
		$info["objectclass"][0]="top";
		$info["objectclass"][1]="posixGroup";
		$info["gidNumber"] = $this->groupId;
		$info["cn"] = $name;

		//set our cn
		$cn = "cn=".$name.",".GROUP_BASE;

		//add the data
		if (ldap_add($this->conn, "$cn", $info)) {

			//add our samba options
			if ($option["sambaSID"]) $this->addSamba($option);
			return $this->groupId;
			
		} else {
			$this->throwError("Group unable to be added");
			return false;
		}
		
	}

	function addSamba($option) {

		extract($option);
		$this->connect();

		if (!$this->groupId) {
			$this->throwError("You must have a group id to modify settings");
			return false;
		}

		if (!$sambaGroupName) {
			$this->throwError("You must enter a samba group name to modify the samba settings");
			return false;
		}

		if (!$sambaSID) {
			$this->throwError("You must enter a samba sid to modify the samba settings");
			return false;
		}
				
		$dn = "cn=".$name.",".GROUP_BASE;

		//make sure it's a posix group as well
		$arr = array();
		$arr["objectclass"][0]="top";
		$arr["objectclass"][1]="posixGroup";
		$arr["objectclass"][2] = "sambaGroupMapping";
		$arr["sambaGroupType"] = "2";
		$arr["displayName"] = $sambaGroupName;
		$arr["sambaSID"] = $sambaSID;

		//add the data
		if (!ldap_modify($this->conn, "$dn", $arr)) $this->throwError("Unable to add samba settings to group");

	}

	//remove samba functionality from the account
	function removeSamba($option) {

		$this->connect();
		extract($option);

		if (!$this->groupId) {
			$this->throwError("You must have a group id to modify settings");
			return false;
		}

		$dn = "cn=".$name.",".GROUP_BASE;

		$arr = array();
		$arr["sambaSID"] = array();
		$arr["displayName"] = array();
		$arr["objectClass"] = "sambaGroupMapping";
		$arr["sambaGroupType"] = array();
	
		if (!ldap_mod_del($this->conn,$dn,$arr)) $this->throwError("Unable to remove samba settings from group");

	}       

	function updateMembers($mode,$newmem) {
	
		$this->connect();

		if (!$this->groupId) {
			$this->throwError("You must have a group id to modify settings");
			return false;
		}

		$info = $this->getInfo();
		$curmem = $info["member"];
			
		//if the last entry is blank, pop it off
		if (count($curmem)==0) $curmem = array();
		else {
			$n = count($curmem) - 1;
			if (!$curmem[$n] && is_array($curmem)) array_pop($curmem);
		}
		
		if ($mode=="add") $newmem = array_merge($curmem,$newmem);
		elseif ($mode=="remove") {
			if (is_array($newmem)) $newmem = array_diff($curmem,$newmem);
			else $newmem = $curmem;
		}
	
		//remove dups
		$newmem = @array_values(@array_unique($newmem));
	
		$arr = array();
	
		if (count($newmem) > 0) {

			foreach ($newmem AS $mem) {
				if ($mem) $arr["memberUid"][] = $mem;
			}
	
		} else $arr["memberUid"] = array();
	
		//set our cn
		$dn = "cn=".$info["name"].",".GROUP_BASE;

		//add the data
		if (!ldap_modify($this->conn, "$dn", $arr)) $this->throwError("Error updating group members");

	
	}

	//for now, this function does not support renaming
	function update($option) {
	
		$this->connect();
		extract($option);
	
		if (!$this->groupId) {
			$this->throwError("You must have a group id to modify settings");
			return false;
		}

		$arr = array();
		$arr["name"] = $name;	
	
		$cn = "cn=".$name.",".$search_base;
	
		//add the data
		if (ldap_modify($this->conn, "$cn", $arr)) {
	
			//update the group's samba settings
			updateSamba($option);
	
		} else $this->throwError("Error updating group information");
	
	}
	
	
	//for now, this function does not support renaming
	function delete() {

		$this->connect();
		extract($option);
	
		if (!$this->groupId) {
			$this->throwError("You must have a group id to modify settings");
			return false;
		}

		$info = $this->getInfo();
	
		//set our cn
		$cn = "cn=".$info["name"].",".GROUP_BASE;
	
		//add the data
		if (!ldap_delete($this->conn, "$cn")) $this->throwError("Unable to remove group");
	
	}

}

	
	
	
