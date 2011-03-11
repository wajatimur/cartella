<?php
/************************************************************************************************************
	ldap.php
	
	Holds account processing and search functions for
	an ldap database
	
	02-07-2005 - Fixed returnAccountInfo returning an error if it did not find an account Id (Eric L.)
	02-14-2005 - Split group info
	11-20-2005 - Stripped file down more and added support for an ldap map file

***********************************************************************************************************/

class DB {

	protected $accountId;
	protected $errorMessage;
	protected $DB;
	
	function __construct($aid=null) {

		$this->DB = $GLOBALS["DB"];
		if ($aid) $this->accountId = $aid;

	}

	function throwError($msg) {
		$this->errorMessage = $msg;
	}
	
	function getError() {
		return $this->errorMessage;
	}

	function getInfo($aid=null) {

		if (!$aid) $aid = $this->accountId;

		$sql = "SELECT * FROM auth_accounts WHERE	id='".$aid."'";
		$info = $this->DB->single($sql);
		
		return $info;

	}

	function search($filter=null,$sort=null) {

		$sql = "SELECT auth_accounts.*,(first_name || ' ' || last_name) AS full_name FROM auth_accounts ";
		if ($filter) $sql .= " WHERE ".$filter;
		if ($sort) $sql .= " ORDER BY ".$sort;
		
		$list = $this->DB->fetch($sql);
		
		return $list;
		
	}

	function getList($filter,$sort) {
	
		return $this->search($filter,$sort);
	
	}

	function insert($option) {

		extract($option);
		
		//make sure it doesn't already exist
		$sql = "SELECT id FROM auth_accounts WHERE login='$login'";
		$info = $this->DB->single($sql);

		if ($info) {
			$this->throwError("Account already exists");
			return false;
		}

		$this->DB->begin();
				
		$opt = null;
		$opt["login"] = $login;
		$opt["password"] = md5($password);
		$opt["digest_hash"] = md5($login.":".DIGEST_REALM.":".$password);
		$opt["first_name"] = $firstName;
		$opt["last_name"] = $lastName;
		$opt["email"] = $email;
		$opt["phone"] = $phone;
		$this->accountId = $this->DB->insert("auth_accounts",$opt,"id");

		//insert a base permission record for this account
		$opt = null;
		$opt["account_id"] = $this->accountId;
		$opt["bitset"] = "0";
		$opt["enable"] = "t";
		$this->DB->insert("auth_accountperm",$opt);
                             
    $this->DB->end();
                                   
		return $this->accountId;
				
	}

	function update($option) {

		extract($option);
		
		$opt = null;
		$opt["login"] = $login;
		$opt["first_name"] = $firstName;
		$opt["last_name"] = $lastName;
		$opt["email"] = $email;
		$opt["phone"] = $phone;
		$opt["where"] = "id='".$this->accountId."'";
		$this->accountId = $this->DB->update("auth_accounts",$opt);

		return $this->accountId;
		
	}

	function setPassword($password) {

		$info = $this->getInfo();

		$opt = null;
		$opt["password"] = md5($password);
		$opt["digest_hash"] = md5($info["login"].":".DIGEST_REALM.":".$password);
		$opt["where"] = "id='".$this->accountId."'";
		$this->DB->update("auth_accounts",$opt);
		
	}

	function delete() {

		$sql = "DELETE FROM auth_accounts WHERE id='".$this->accountId."';";
		$sql .= "DELETE FROM auth_accountperm WHERE account_id='".$this->accountId."';";
		$sql .= "DELETE FROM auth_grouplink WHERE accountid='".$this->accountId."';";
		$this->DB->query($sql);

	}

	function password_check($login,$password) {

		//if it's longer than 25 characters, it came from a cookie
		if (strlen($password)<=25) $pass = md5($password);
		else $pass = $password;
		
		$sql = "SELECT * FROM auth_accounts WHERE login='$login' AND password='$pass'";
		$accountInfo = $this->DB->single($sql);
		
		if ($accountInfo) {
			$accountInfo["crypt_password"] = $pass;

			//store the plaintext password
			$accountInfo["password"] = $password;

			return $accountInfo;
		} else return false;
		
	}
			
}
		